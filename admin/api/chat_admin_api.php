<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/pusher.php';

$db = (new Database())->getConnection();

function triggerChatUpdate($session_id = null) {
    global $pusher;
    try {
        $data = ['time' => time()];
        if ($session_id) $data['session_id'] = $session_id;
        $pusher->trigger('chat-channel', 'chat_updated', $data);
    } catch (Exception $e) {}
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$session_id = $_POST['session_id'] ?? $_GET['session_id'] ?? '';

switch ($action) {
    case 'get_messages':
        $last_id = (int)($_GET['last_id'] ?? 0);
        
        $stmt = $db->prepare("SELECT MAX(id) FROM chat_messages WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $max_id = (int)$stmt->fetchColumn();
        
        if ($max_id <= $last_id) {
            http_response_code(304);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY id ASC");
        $stmt->execute([$session_id, $last_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đánh dấu là đã đọc
        try {
            $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'customer' AND is_read = 0")->execute([$session_id]);
        } catch(PDOException $e) {}

        echo json_encode([
            'status' => 'success',
            'messages' => $messages
        ]);
        break;

    case 'send_message':
        $content = $_POST['content'] ?? '';
        if ($content) {
            $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender_type, message_type, content) VALUES (?, 'admin', 'text', ?)");
            $stmt->execute([$session_id, $content]);
            
            // Cập nhật trạng thái session nếu đang là waiting_agent
            try {
                $db->prepare("UPDATE chat_sessions SET status = 'agent_handling', first_response_at = IFNULL(first_response_at, CURRENT_TIMESTAMP) WHERE session_id = ? AND status = 'waiting_agent'")->execute([$session_id]);
            } catch(PDOException $e) {
                // Thử lại không có first_response_at
                $db->prepare("UPDATE chat_sessions SET status = 'agent_handling' WHERE session_id = ? AND status = 'waiting_agent'")->execute([$session_id]);
            }
        }
        triggerChatUpdate($session_id);
        echo json_encode(['status' => 'success']);
        break;

    case 'upload_image':
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['image']['tmp_name'];
            $name = basename($_FILES['image']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $upload_dir = __DIR__ . '/../../uploads/chat/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $new_name = uniqid() . '_admin.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $url = 'uploads/chat/' . $new_name;
                    $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender_type, message_type, content) VALUES (?, 'admin', 'image', ?)");
                    $stmt->execute([$session_id, $url]);
                    
                    try {
                        $db->prepare("UPDATE chat_sessions SET status = 'agent_handling', first_response_at = IFNULL(first_response_at, CURRENT_TIMESTAMP) WHERE session_id = ? AND status = 'waiting_agent'")->execute([$session_id]);
                    } catch(PDOException $e) {
                        $db->prepare("UPDATE chat_sessions SET status = 'agent_handling' WHERE session_id = ? AND status = 'waiting_agent'")->execute([$session_id]);
                    }

                    triggerChatUpdate($session_id);
                    echo json_encode(['status' => 'success', 'url' => $url]);
                    exit;
                }
            }
        }
        echo json_encode(['status' => 'error']);
        break;

    case 'close_session':
        try {
            $db->prepare("UPDATE chat_sessions SET status = 'closed', closed_at = CURRENT_TIMESTAMP WHERE session_id = ?")->execute([$session_id]);
        } catch(PDOException $e) {
            $db->prepare("UPDATE chat_sessions SET status = 'closed' WHERE session_id = ?")->execute([$session_id]);
        }
        triggerChatUpdate($session_id);
        echo json_encode(['status' => 'success']);
        break;

    case 'reopen_session':
        $db->prepare("UPDATE chat_sessions SET status = 'agent_handling' WHERE session_id = ?")->execute([$session_id]);
        triggerChatUpdate($session_id);
        echo json_encode(['status' => 'success']);
        break;

    case 'get_sessions':
        $stmt = $db->query("SELECT * FROM chat_sessions ORDER BY FIELD(status, 'waiting_agent', 'agent_handling', 'bot_handling', 'closed'), created_at DESC");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'sessions' => $sessions]);
        break;

    case 'check_alerts':
        $stmt = $db->query("SELECT COUNT(*) FROM chat_sessions WHERE status = 'waiting_agent'");
        $waiting_count = (int)$stmt->fetchColumn();
        echo json_encode(['status' => 'success', 'waiting_count' => $waiting_count]);
        break;

    case 'hide_message':
        $msg_id = (int)($_POST['msg_id'] ?? 0);
        if ($msg_id) {
            $db->prepare("UPDATE chat_messages SET is_hidden = 1 WHERE id = ?")->execute([$msg_id]);
            $stmt = $db->prepare("SELECT session_id FROM chat_messages WHERE id = ?");
            $stmt->execute([$msg_id]);
            $s_id = $stmt->fetchColumn();
            if ($s_id) {
                try {
                    global $pusher;
                    $pusher->trigger('chat-channel', 'message_hidden', ['message_id' => $msg_id, 'session_id' => $s_id]);
                } catch (Exception $e) {}
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        break;

    case 'unhide_message':
        $msg_id = (int)($_POST['msg_id'] ?? 0);
        if ($msg_id) {
            $db->prepare("UPDATE chat_messages SET is_hidden = 0 WHERE id = ?")->execute([$msg_id]);
            $stmt = $db->prepare("SELECT session_id FROM chat_messages WHERE id = ?");
            $stmt->execute([$msg_id]);
            $s_id = $stmt->fetchColumn();
            if ($s_id) {
                try {
                    global $pusher;
                    $pusher->trigger('chat-channel', 'message_unhidden', ['message_id' => $msg_id, 'session_id' => $s_id]);
                } catch (Exception $e) {}
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        break;
}

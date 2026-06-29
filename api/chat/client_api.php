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

if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing session_id']);
    exit;
}

function sendBotMessage($db, $session_id, $content) {
    $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender_type, message_type, content) VALUES (?, 'bot', 'text', ?)");
    $stmt->execute([$session_id, $content]);
}

switch ($action) {
    case 'init_session':
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        $stmt = $db->prepare("SELECT session_id FROM chat_sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO chat_sessions (session_id, customer_name, customer_phone, status) VALUES (?, ?, ?, 'bot_handling')");
            $stmt->execute([$session_id, $name, $phone]);
            sendBotMessage($db, $session_id, "Chào $name! Mình là Trợ lý ảo của Restaurantly. Mình có thể giúp gì cho bạn hôm nay?");
        }
        triggerChatUpdate($session_id);
        echo json_encode(['status' => 'success']);
        break;

    case 'get_messages':
        $last_id = (int)($_GET['last_id'] ?? 0);
        
        // Kiểm tra session có tồn tại không
        $stmt_check = $db->prepare("SELECT session_id FROM chat_sessions WHERE session_id = ?");
        $stmt_check->execute([$session_id]);
        if (!$stmt_check->fetchColumn()) {
            echo json_encode(['status' => 'invalid_session']);
            exit;
        }

        // Caching optimization: Check if there are new messages
        $stmt = $db->prepare("SELECT MAX(id) FROM chat_messages WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $max_id = (int)$stmt->fetchColumn();
        
        if ($max_id <= $last_id) {
            // No new messages
            http_response_code(304);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND is_hidden = 0 AND id > ? ORDER BY id ASC");
        $stmt->execute([$session_id, $last_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_st = $db->prepare("SELECT status FROM chat_sessions WHERE session_id = ?");
        $stmt_st->execute([$session_id]);
        $session_status = $stmt_st->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'messages' => $messages,
            'session_status' => $session_status,
            'is_admin_typing' => false // For future typing indicator from admin
        ]);
        break;

    case 'send_message':
        $content = $_POST['content'] ?? '';
        if (!$content) {
            echo json_encode(['status' => 'error', 'message' => 'Empty message']);
            exit;
        }

        // Kiểm tra session có tồn tại không
        $stmt_check = $db->prepare("SELECT session_id FROM chat_sessions WHERE session_id = ?");
        $stmt_check->execute([$session_id]);
        if (!$stmt_check->fetchColumn()) {
            echo json_encode(['status' => 'invalid_session']);
            exit;
        }

        // Insert customer message
        $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender_type, message_type, content) VALUES (?, 'customer', 'text', ?)");
        $stmt->execute([$session_id, $content]);

        // Smart Context Logic
        $stmt = $db->prepare("SELECT status FROM chat_sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $status = $stmt->fetchColumn();

        if ($status === 'closed') {
            $db->prepare("UPDATE chat_sessions SET status = 'bot_handling' WHERE session_id = ?")->execute([$session_id]);
            $status = 'bot_handling';
        }

        if ($status === 'bot_handling') {
            $text = mb_strtolower($content, 'UTF-8');
            
            // Log keyword searched
            $stmt_log = $db->prepare("INSERT INTO bot_context_logs (keyword_searched) VALUES (?)");
            $stmt_log->execute([$text]);

            if (preg_match('/gặp nhân viên|nhân viên|tư vấn|người thật|liên hệ/i', $text)) {
                $db->prepare("UPDATE chat_sessions SET status = 'waiting_agent' WHERE session_id = ?")->execute([$session_id]);
                sendBotMessage($db, $session_id, "Vui lòng đợi giây lát, nhân viên sẽ hỗ trợ bạn ngay.");
            } elseif (preg_match('/thực đơn|món ăn|giá|menu/i', $text)) {
                $foods = $db->query("SELECT name, price FROM foods WHERE status = 'available' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                $reply = "Dưới đây là một số món nổi bật của nhà hàng:\n";
                foreach ($foods as $f) {
                    $reply .= "- " . $f['name'] . ": " . number_format($f['price']) . "đ\n";
                }
                $reply .= "Bạn có thể xem chi tiết trên website nhé.";
                sendBotMessage($db, $session_id, $reply);
            } elseif (preg_match('/khuyến mãi|combo|ưu đãi/i', $text)) {
                $combos = $db->query("SELECT name, price FROM combos WHERE is_active = 1 LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                if (count($combos) > 0) {
                    $reply = "Hiện nhà hàng đang có các Set/Combo hấp dẫn:\n";
                    foreach ($combos as $c) {
                        $reply .= "- " . $c['name'] . ": " . number_format($c['price']) . "đ\n";
                    }
                    sendBotMessage($db, $session_id, $reply);
                } else {
                    sendBotMessage($db, $session_id, "Hiện tại nhà hàng chưa có chương trình khuyến mãi nào mới. Bạn có thể theo dõi Fanpage để cập nhật nhé.");
                }
            } elseif (preg_match('/bàn trống|đặt bàn/i', $text)) {
                sendBotMessage($db, $session_id, "Để kiểm tra tình trạng bàn chính xác, bạn vui lòng chọn phần Đặt bàn trên Website hoặc nhắn 'gặp nhân viên' để được hỗ trợ kiểm tra ngay nhé.");
            } elseif (preg_match('/dị ứng|ăn kiêng|chay/i', $text)) {
                sendBotMessage($db, $session_id, "Nhà hàng có phục vụ các món chay và điều chỉnh gia vị cho người dị ứng. Vui lòng ghi chú kỹ khi đặt bàn hoặc nói với nhân viên tư vấn ạ.");
            } else {
                // Check bot_responses
                $stmt_resp = $db->query("SELECT keywords, answer FROM bot_responses");
                $replied = false;
                while ($row = $stmt_resp->fetch(PDO::FETCH_ASSOC)) {
                    $keywords = explode(',', mb_strtolower($row['keywords'], 'UTF-8'));
                    foreach ($keywords as $kw) {
                        $kw = trim($kw);
                        if ($kw && strpos($text, $kw) !== false) {
                            sendBotMessage($db, $session_id, $row['answer']);
                            $replied = true;
                            break 2;
                        }
                    }
                }
                if (!$replied) {
                    sendBotMessage($db, $session_id, "Xin lỗi, mình chưa hiểu ý bạn. Bạn có muốn 'gặp nhân viên' không?");
                }
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
                
                $new_name = uniqid() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $url = 'uploads/chat/' . $new_name;
                    $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender_type, message_type, content) VALUES (?, 'customer', 'image', ?)");
                    $stmt->execute([$session_id, $url]);
                    triggerChatUpdate($session_id);
                    echo json_encode(['status' => 'success', 'url' => $url]);
                    exit;
                }
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
        break;
}

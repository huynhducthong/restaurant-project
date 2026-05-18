<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$content_id  = (int)($_POST['content_id'] ?? 0);
$is_anon     = (int)($_POST['is_anonymous'] ?? 0);
$author_name = mb_substr(strip_tags(trim($_POST['author_name'] ?? 'Ẩn danh')), 0, 100);
$comment     = mb_substr(strip_tags(trim($_POST['comment'] ?? '')), 0, 1000);
$user_ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (!$content_id || empty($comment)) {
    echo json_encode(['status'=>'error','message'=>'Thiếu thông tin']); exit;
}

$db = (new Database())->getConnection();

// Kiểm tra ban
$user_id = $_SESSION['user_id'] ?? null;
$stmt = $db->prepare("SELECT banned_until FROM about_comment_bans 
                      WHERE ( (ban_type='ip' AND user_ip=?) OR (ban_type='account' AND user_id=?) ) 
                      AND banned_until > NOW() 
                      LIMIT 1");
$stmt->execute([$user_ip, $user_id]);
$ban = $stmt->fetch();
if ($ban) {
    echo json_encode(['status'=>'banned','message'=>'Bạn bị cấm bình luận đến '.date('d/m/Y H:i', strtotime($ban['banned_until']))]);
    exit;
}

// Lọc ngôn từ thô tục
$bad_words = ['đụ','địt','lồn','cặc','đéo','vãi','buồi','đĩ','fuck','shit','bitch','asshole','dmm','dm','vl'];
$cl = mb_strtolower($comment);
foreach ($bad_words as $w) {
    if (mb_strpos($cl, $w) !== false) {
        echo json_encode(['status'=>'error','message'=>'Bình luận chứa ngôn từ không phù hợp!']); exit;
    }
}

$parent_id   = (int)($_POST['parent_id'] ?? 0);
if (!$author_name) $author_name = 'Ẩn danh';

$stmt = $db->prepare("INSERT INTO about_comments (content_id, author_name, author_ip, user_id, comment, is_anonymous, parent_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')");
$stmt->execute([$content_id, $author_name, $user_ip, $user_id, $comment, $is_anon, $parent_id]);
$new_id = $db->lastInsertId();

// Trigger Notification
try {
    $target_user_id = 0;
    if ($parent_id > 0) {
        $p_stmt = $db->prepare("SELECT user_id FROM about_comments WHERE id = ?");
        $p_stmt->execute([$parent_id]);
        $target_user_id = $p_stmt->fetchColumn();
    } else {
        $p_stmt = $db->prepare("SELECT user_id FROM about WHERE id = ?");
        $p_stmt->execute([$content_id]);
        $target_user_id = $p_stmt->fetchColumn();
    }

    if ($target_user_id && $target_user_id != $user_id) {
        $type = ($parent_id > 0) ? 'reply' : 'comment';
        $notif_sql = "INSERT INTO notifications (user_id, from_user_id, type, content_id) VALUES (?, ?, ?, ?)";
        $db->prepare($notif_sql)->execute([$target_user_id, $user_id, $type, $content_id]);
    }
} catch (Exception $e) {}

$user_avatar = null;
if ($user_id) {
    $u_stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $u_stmt->execute([$user_id]);
    $user_avatar = $u_stmt->fetchColumn();
}

echo json_encode([
    'status'  => 'success',
    'comment' => [
        'id'           => $new_id,
        'author_name'  => $author_name,
        'comment'      => $comment,
        'is_anonymous' => $is_anon,
        'parent_id'    => $parent_id,
        'user_avatar'  => $user_avatar,
        'user_id'      => $user_id,
        'created_at'   => date('d/m/Y H:i')
    ]
]);

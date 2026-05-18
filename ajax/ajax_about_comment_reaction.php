<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$comment_id = (int)($_POST['comment_id'] ?? 0);
$type = trim($_POST['type'] ?? ''); // 'like' or 'dislike'

if (!$comment_id || !in_array($type, ['like', 'dislike'])) {
    echo json_encode(['status' => 'error', 'message' => 'Tham số không hợp lệ']);
    exit;
}

$db = (new Database())->getConnection();

// Check if comment exists
$stmt = $db->prepare("SELECT id FROM about_comments WHERE id = ?");
$stmt->execute([$comment_id]);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Bình luận không tồn tại']);
    exit;
}

$session_key = "reacted_cmt_{$comment_id}";
$existing_reaction = $_SESSION[$session_key] ?? null;

if ($existing_reaction === null) {
    // 1. New reaction
    if ($type === 'like') {
        $db->prepare("UPDATE about_comments SET likes = likes + 1 WHERE id = ?")->execute([$comment_id]);
    } else {
        $db->prepare("UPDATE about_comments SET dislikes = dislikes + 1 WHERE id = ?")->execute([$comment_id]);
    }
    $_SESSION[$session_key] = $type;
    $message = ($type === 'like') ? 'Đã thích bình luận' : 'Đã không thích bình luận';
} else if ($existing_reaction === $type) {
    // 2. Undo reaction (clicked the same button again)
    if ($type === 'like') {
        $db->prepare("UPDATE about_comments SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$comment_id]);
    } else {
        $db->prepare("UPDATE about_comments SET dislikes = GREATEST(0, dislikes - 1) WHERE id = ?")->execute([$comment_id]);
    }
    unset($_SESSION[$session_key]);
    $message = 'Đã hủy bày tỏ cảm xúc';
} else {
    // 3. Switch reaction (clicked the opposite button)
    if ($type === 'like') {
        // Switch from dislike to like
        $db->prepare("UPDATE about_comments SET dislikes = GREATEST(0, dislikes - 1), likes = likes + 1 WHERE id = ?")->execute([$comment_id]);
    } else {
        // Switch from like to dislike
        $db->prepare("UPDATE about_comments SET likes = GREATEST(0, likes - 1), dislikes = dislikes + 1 WHERE id = ?")->execute([$comment_id]);
    }
    $_SESSION[$session_key] = $type;
    $message = ($type === 'like') ? 'Đã đổi thành thích bình luận' : 'Đã đổi thành không thích bình luận';
}

// Fetch new counts
$stmt = $db->prepare("SELECT likes, dislikes FROM about_comments WHERE id = ?");
$stmt->execute([$comment_id]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'message' => $message,
    'likes' => (int)$counts['likes'],
    'dislikes' => (int)$counts['dislikes'],
    'current_reaction' => $_SESSION[$session_key] ?? ''
]);

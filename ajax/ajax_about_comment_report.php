<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$comment_id = (int)($_POST['comment_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!$comment_id || empty($reason)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền lý do báo cáo']);
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

$user_id = $_SESSION['user_id'] ?? null;
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Prevent spamming reports for the same comment by the same IP or User ID
$check = $db->prepare("SELECT id FROM about_comment_reports WHERE comment_id = ? AND (user_ip = ? OR (? IS NOT NULL AND user_id = ?))");
$check->execute([$comment_id, $user_ip, $user_id, $user_id]);
if ($check->fetch()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Ý kiến của bạn đã được ghi nhận trước đó. Cảm ơn bạn!'
    ]);
    exit;
}

$stmt = $db->prepare("INSERT INTO about_comment_reports (comment_id, user_id, reason, user_ip) VALUES (?, ?, ?, ?)");
$stmt->execute([$comment_id, $user_id, $reason, $user_ip]);

echo json_encode([
    'status' => 'success',
    'message' => 'Báo cáo vi phạm thành công! Cảm ơn ý kiến đóng góp của bạn.'
]);

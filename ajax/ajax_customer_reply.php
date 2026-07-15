<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['reply'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

$booking_id = (int)$_POST['id'];
$user_id = (int)$_SESSION['user_id'];
$reply_text = trim($_POST['reply']);

if (empty($reply_text)) {
    echo json_encode(['status' => 'error', 'message' => 'Nội dung phản hồi không được để trống']);
    exit;
}

$db = (new Database())->getConnection();

try {
    // Kiểm tra đơn hàng có thuộc về user này không
    $stmt_check = $db->prepare("SELECT chef_requirements, status FROM service_bookings WHERE id = ? AND user_id = ?");
    $stmt_check->execute([$booking_id, $user_id]);
    $booking = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }
    
    if ($booking['status'] !== 'Pending') {
        echo json_encode(['status' => 'error', 'message' => 'Đơn hàng không ở trạng thái chờ duyệt, không thể gửi phản hồi']);
        exit;
    }

    $current_req = $booking['chef_requirements'] ?? '';
    $timestamp = date('d/m/Y H:i');
    $new_req = $current_req . "\n\n[Phản hồi từ khách lúc $timestamp]:\n" . $reply_text;

    $stmt_update = $db->prepare("UPDATE service_bookings SET chef_requirements = ? WHERE id = ?");
    $stmt_update->execute([$new_req, $booking_id]);
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

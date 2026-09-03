<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Check admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager', 'chef'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['booking_id']) || !isset($_POST['menu'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

$booking_id = (int)$_POST['booking_id'];
$menu_content = trim($_POST['menu']);

$db = (new Database())->getConnection();

try {
    $stmt = $db->prepare("UPDATE service_bookings SET ai_suggested_menu = ?, is_waiting_customer = 1 WHERE id = ?");
    $stmt->execute([$menu_content, $booking_id]);
    
    require_once __DIR__ . '/../../config/notification_helper.php';
    $stmtUser = $db->prepare("
        SELECT sb.*, u.email as user_email, u.full_name as customer_name
        FROM service_bookings sb
        JOIN users u ON sb.user_id = u.id
        WHERE sb.id = ?
    ");
    $stmtUser->execute([$booking_id]);
    $bookingInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if ($bookingInfo && !empty($bookingInfo['user_email'])) {
        sendBespokeMenuEmail($bookingInfo['user_email'], $bookingInfo);
    }

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()]);
}

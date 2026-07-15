<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Check admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
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
    $stmt = $db->prepare("UPDATE service_bookings SET ai_suggested_menu = ? WHERE id = ?");
    $stmt->execute([$menu_content, $booking_id]);
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()]);
}

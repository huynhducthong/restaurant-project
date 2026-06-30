<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2])) {
    http_response_code(403); 
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); 
    exit; 
}

require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if (!isset($_POST['um_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$um_id = (int)$_POST['um_id'];
$db = (new Database())->getConnection();

try {
    $stmt = $db->prepare("UPDATE user_milestones SET is_redeemed = 1 WHERE id = ?");
    $stmt->execute([$um_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Milestone not found or already redeemed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>

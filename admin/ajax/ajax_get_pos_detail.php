<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403); 
    exit; 
}
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$id = (int)$_GET['id'];
$db = (new Database())->getConnection();

// Lấy thông tin cơ bản
$stmt = $db->prepare("SELECT p.*, t.table_code FROM pos_orders p LEFT JOIN restaurant_tables t ON p.table_id = t.id WHERE p.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Not found']);
    exit;
}

// Lấy món
$stmt_items = $db->prepare("
    SELECT i.quantity, i.price, IFNULL(i.notes, '') as toppings_info,
           CASE WHEN i.item_type='food' THEN f.name ELSE c.name END as food_name
    FROM pos_order_items i
    LEFT JOIN foods f ON i.item_id = f.id AND i.item_type='food'
    LEFT JOIN combos c ON i.item_id = c.id AND i.item_type='combo'
    WHERE i.pos_order_id = ?
");
$stmt_items->execute([$id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Map sang format giống service_bookings
echo json_encode([
    'customer_phone' => 'Khách Vãng Lai',
    'service_type' => 'POS',
    'table_code' => $order['table_code'] ?? 'Tại quầy',
    'booking_date' => date('d/m/Y H:i', strtotime($order['created_at'])),
    'guests' => $order['guests'],
    'combo_name' => '',
    'foods' => $items,
    'total_amount' => $order['total_amount'],
    'deposit_amount' => $order['deposit_amount'] ?? 0,
    'message' => 'Đơn tạo tại quầy POS'
]);

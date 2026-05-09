<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }
// admin/ajax/ajax_get_booking_detail.php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$id = (int)$_GET['id'];
$db = (new Database())->getConnection();

try {
    // 1. Lấy thông tin cơ bản của đơn đặt bàn
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }

    // 2. Lấy danh sách món ăn đã đặt
    $stmt_items = $db->prepare("
        SELECT bd.*, f.name as food_name, f.unit_name as food_unit, f.price
        FROM booking_details bd
        JOIN foods f ON bd.menu_id = f.id
        WHERE bd.booking_id = ?
    ");
    $stmt_items->execute([$id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 3. Với mỗi món ăn, lấy định mức nguyên liệu (Recipe)
    foreach ($items as &$item) {
        $stmt_recipe = $db->prepare("
            SELECT r.*, i.item_name, i.unit_name as inventory_unit
            FROM food_recipes r
            JOIN inventory i ON r.ingredient_id = i.id
            WHERE r.food_id = ?
        ");
        $stmt_recipe->execute([$item['menu_id']]);
        $item['recipes'] = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);
    }

    $booking['items'] = $items;

    echo json_encode($booking);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

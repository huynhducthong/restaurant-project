<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403); 
    echo json_encode(['error'=>'Unauthorized']); 
    exit; 
}

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

    // 3. TỐI ƯU HÓA: Lấy tất cả định mức nguyên liệu chỉ bằng 1 câu truy vấn (Fix N+1 Query)
    if (!empty($items)) {
        // Gom tất cả ID món ăn thành một mảng
        $food_ids = array_column($items, 'menu_id');
        
        // Tạo danh sách dấu hỏi (?) tương ứng với số lượng ID để dùng IN(...)
        $placeholders = implode(',', array_fill(0, count($food_ids), '?'));

        $stmt_recipe = $db->prepare("
            SELECT r.*, i.item_name, i.unit_name as inventory_unit
            FROM food_recipes r
            JOIN inventory i ON r.ingredient_id = i.id
            WHERE r.food_id IN ($placeholders)
        ");
        $stmt_recipe->execute($food_ids);
        $all_recipes = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);

        // Nhóm các nguyên liệu theo ID món ăn để dễ map dữ liệu
        $recipes_by_food = [];
        foreach ($all_recipes as $recipe) {
            $recipes_by_food[$recipe['food_id']][] = $recipe;
        }

        // Gán mảng nguyên liệu đã nhóm vào từng món ăn tương ứng
        foreach ($items as &$item) {
            $item['recipes'] = $recipes_by_food[$item['menu_id']] ?? [];
        }
    }

    // Gộp mảng món ăn vào thông tin booking và trả về JSON
    $booking['foods'] = $items;
    echo json_encode($booking);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
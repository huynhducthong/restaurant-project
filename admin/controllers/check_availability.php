<?php
// File: admin/controllers/check_availability.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

try {
    $db = (new Database())->getConnection();

    // Nhận dữ liệu từ AJAX
    $food_id = isset($_POST['food_id']) ? (int)$_POST['food_id'] : 0;
    $order_qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($food_id <= 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Mã món ăn không hợp lệ']);
        exit;
    }

    // ĐỊNH VỊ KHO BẾP LÀ WAREHOUSE ID = 2
    $kitchen_warehouse_id = 2;

    // Lấy công thức và tồn kho TRONG KHO BẾP
    $stmt = $db->prepare("
        SELECT r.quantity_required, i.item_name, i.unit_name,
               IFNULL((SELECT quantity FROM inventory_stocks WHERE ingredient_id = i.id AND warehouse_id = ?), 0) as stock_quantity
        FROM food_recipes r
        JOIN inventory i ON r.ingredient_id = i.id
        WHERE r.food_id = ?
    ");
    $stmt->execute([$kitchen_warehouse_id, $food_id]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $shortages = [];
    foreach ($recipes as $rcp) {
        $total_needed = $rcp['quantity_required'] * $order_qty;
        
        // Nếu tồn kho bếp nhỏ hơn số lượng cần dùng -> Thiếu hàng
        if ($rcp['stock_quantity'] < $total_needed) {
            $shortages[] = $rcp['item_name'] . " (Cần: " . $total_needed . " " . $rcp['unit_name'] . ", Bếp còn: " . $rcp['stock_quantity'] . " " . $rcp['unit_name'] . ")";
        }
    }

    if (count($shortages) > 0) {
        // Trả về mảng lỗi để JS hiện thông báo
        echo json_encode([
            'status' => 'error', 
            'msg' => 'Kho Bếp không đủ nguyên liệu, hãy báo cho Bếp Trưởng!',
            'details' => $shortages
        ]);
    } else {
        // Đủ hàng
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
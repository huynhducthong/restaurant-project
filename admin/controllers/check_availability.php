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

    // Lấy công thức nguyên liệu
    $stmt = $db->prepare("
        SELECT r.quantity_required, i.id as ing_id, i.item_name, i.unit_name, i.category
        FROM food_recipes r
        JOIN inventory i ON r.ingredient_id = i.id
        WHERE r.food_id = ?
    ");
    $stmt->execute([$food_id]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $shortages = [];
    foreach ($recipes as $rcp) {
        $total_needed = $rcp['quantity_required'] * $order_qty;
        $ing_id = $rcp['ing_id'];
        $category = $rcp['category'];

        // Xác định kho tương ứng: Đồ uống -> Bar (3), Khác -> Bếp (2)
        $target_warehouse_id = ($category === 'Đồ uống') ? 3 : 2;
        $warehouse_name = ($target_warehouse_id === 3) ? 'Bar' : 'Bếp';

        // Lấy tồn kho tại kho tương ứng
        $stmt_stock = $db->prepare("SELECT IFNULL(quantity, 0) FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = ?");
        $stmt_stock->execute([$ing_id, $target_warehouse_id]);
        $stock_qty = (float)$stmt_stock->fetchColumn();
        
        // Kiểm tra tồn kho
        if ($stock_qty < $total_needed) {
            $shortages[] = $rcp['item_name'] . " (Cần: " . $total_needed . " " . $rcp['unit_name'] . ", tại $warehouse_name còn: " . $stock_qty . " " . $rcp['unit_name'] . ")";
        }
    }


    if (count($shortages) > 0) {
        // Trả về mảng lỗi để JS hiện thông báo
        echo json_encode([
            'status' => 'error', 
            'msg' => 'Kho không đủ nguyên liệu, hãy báo cho Bếp Trưởng hoặc Quản lý Bar!',
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
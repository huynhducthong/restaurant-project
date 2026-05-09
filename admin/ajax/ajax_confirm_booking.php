<?php
// File: admin/controllers/ajax_confirm_booking.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/inventory_helper.php';
header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
        $booking_id = (int)$_POST['booking_id'];

        $db->beginTransaction();

        // 1. Kiểm tra đơn hàng có tồn tại không
        $stmt = $db->prepare("SELECT id FROM service_bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Không tìm thấy đơn hàng ID: " . $booking_id);
        }

        // 2. Cập nhật trạng thái xác nhận đơn hàng
        $db->prepare("UPDATE service_bookings SET status = 'Confirmed' WHERE id = ?")->execute([$booking_id]);

        // 3. Lấy danh sách tất cả các món ăn khách đã đặt
        $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
        $stmt_items->execute([$booking_id]);
        $ordered_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        // Chuẩn bị sẵn các câu lệnh SQL (Đã JOIN để lấy đơn vị tồn kho i_unit và category)
        $query_recipe = $db->prepare("
            SELECT r.ingredient_id, r.quantity_required, r.unit as r_unit, i.unit_name as i_unit, i.category
            FROM food_recipes r
            JOIN inventory i ON r.ingredient_id = i.id
            WHERE r.food_id = ?
        ");
        
        $query_update_inventory = $db->prepare("
            UPDATE inventory_stocks 
            SET quantity = quantity - ? 
            WHERE ingredient_id = ? AND warehouse_id = ?
        ");

        $query_history = $db->prepare("
            INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) 
            VALUES (?, ?, 'export', ?, 'Hệ thống POS (AJAX)')
        ");

        // Duyệt qua từng món ăn
        foreach ($ordered_items as $item) {
            $food_id = $item['menu_id'];
            $food_qty = (float)$item['quantity'];

            // Tìm định mức nguyên liệu (Recipe)
            $query_recipe->execute([$food_id]);
            $recipes = $query_recipe->fetchAll(PDO::FETCH_ASSOC);

            // Duyệt qua từng nguyên liệu trong định mức
            foreach ($recipes as $r) {
                $ing_id = $r['ingredient_id'];
                $qty_req = (float)$r['quantity_required'];
                $category = $r['category'];

                // Xác định kho tương ứng: Đồ uống -> Bar (3), Khác -> Bếp (2)
                $target_warehouse_id = ($category === 'Đồ uống') ? 3 : 2;
                
                // SỬ DỤNG HELPER ĐỂ QUY ĐỔI ĐƠN VỊ TẬP TRUNG
                $qty_in_stock_unit = convert_to_base_unit($qty_req, $r['r_unit'], $r['i_unit']);

                $total_reduction = $qty_in_stock_unit * $food_qty;

                // 4. Trừ tồn kho tại kho tương ứng
                $query_update_inventory->execute([$total_reduction, $ing_id, $target_warehouse_id]);

                // 5. Ghi lịch sử xuất kho
                $query_history->execute([$ing_id, $target_warehouse_id, $total_reduction]);
            }
        }

        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Xác nhận đơn và trừ kho thành công!']);


    } else {
        echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>
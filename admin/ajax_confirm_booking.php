<?php
require_once __DIR__ . '/../config/database.php';
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

        // 3. Lấy danh sách tất cả các món ăn khách đã đặt trong đơn này
        $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
        $stmt_items->execute([$booking_id]);
        $ordered_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        // Chuẩn bị sẵn các câu lệnh SQL để tối ưu hiệu suất trong vòng lặp
        $query_recipe = $db->prepare("SELECT ingredient_id, quantity_required, unit FROM food_recipes WHERE food_id = ?");
        
        $query_update_inventory = $db->prepare("
            UPDATE inventory 
            SET stock_quantity = stock_quantity - ?, 
                revenue = revenue + (cost_price * ?) 
            WHERE id = ?
        ");

        $query_history = $db->prepare("
            INSERT INTO inventory_history (ingredient_id, type, quantity) 
            VALUES (?, 'export', ?)
        ");

        // Duyệt qua từng món ăn
        foreach ($ordered_items as $item) {
            $food_id = $item['menu_id'];
            $food_qty = (float)$item['quantity'];

            // Tìm định mức nguyên liệu (Recipe) của món ăn đó
            $query_recipe->execute([$food_id]);
            $recipes = $query_recipe->fetchAll(PDO::FETCH_ASSOC);

            // Duyệt qua từng nguyên liệu trong định mức
            foreach ($recipes as $r) {
                $ing_id = $r['ingredient_id'];
                $qty_req = (float)$r['quantity_required'];
                
                // Quy đổi đơn vị: Nếu định mức tính bằng 'g', chia 1000 để khớp với kho 'kg'
                if (strtolower(trim($r['unit'])) == 'g') { 
                    $qty_req /= 1000; 
                }

                // Tổng lượng tiêu hao = Định mức x Số lượng món đặt
                $total_reduction = $qty_req * $food_qty;

                // 4. Cập nhật tồn kho và doanh thu tiêu hao (giá vốn)
                $query_update_inventory->execute([$total_reduction, $total_reduction, $ing_id]);

                // 5. Ghi lịch sử xuất kho để phục vụ Báo cáo - Thống kê
                $query_history->execute([$ing_id, $total_reduction]);
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
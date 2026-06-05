<?php
// File: admin/controllers/ajax_confirm_booking.php
session_start();
// FIX 1: Lưu tạm biến session và giải phóng ngay lập tức để không gây treo web
$current_user = $_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'Admin');
session_write_close(); 

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/inventory_helper.php';
require_once __DIR__ . '/../../config/notification_helper.php';
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

        // Thông tin đơn để gửi Telegram sau khi commit
        $stmt_bk = $db->prepare("SELECT id, service_type, customer_name, customer_phone, booking_date, guests, total_amount, deposit_amount FROM service_bookings WHERE id = ?");
        $stmt_bk->execute([$booking_id]);
        $booking_info = $stmt_bk->fetch(PDO::FETCH_ASSOC) ?: null;

        // 3. Lấy danh sách tất cả các món ăn khách đã đặt
        $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
        $stmt_items->execute([$booking_id]);
        $ordered_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        // FIX 2: TRIỆT TIÊU LỖI N+1 QUERY - Lấy TẤT CẢ công thức trong 1 lần gọi DB duy nhất
        $recipes_by_food = [];
        $food_ids = array_column($ordered_items, 'menu_id');
        if (!empty($food_ids)) {
            $placeholders = implode(',', array_fill(0, count($food_ids), '?'));
            $query_recipe = $db->prepare("
                SELECT r.food_id, r.ingredient_id, r.quantity_required, r.unit as r_unit, i.unit_name as i_unit, i.category
                FROM food_recipes r
                JOIN inventory i ON r.ingredient_id = i.id
                WHERE r.food_id IN ($placeholders)
            ");
            $query_recipe->execute($food_ids);
            while ($row = $query_recipe->fetch(PDO::FETCH_ASSOC)) {
                $recipes_by_food[$row['food_id']][] = $row;
            }
        }

        $query_check_stock = $db->prepare("
            SELECT quantity
            FROM inventory_stocks
            WHERE ingredient_id = ? AND warehouse_id = ?
            FOR UPDATE
        ");
        
        $query_update_inventory = $db->prepare("
            UPDATE inventory_stocks
            SET quantity = quantity - ?
            WHERE ingredient_id = ? AND warehouse_id = ? AND quantity >= ?
        ");

        $query_history = $db->prepare("
            INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) 
            VALUES (?, ?, 'export', ?, 'Hệ thống POS (AJAX)')
        ");

        // Duyệt qua từng món ăn để gom tổng lượng cần trừ theo từng nguyên liệu + kho đích
        $required_by_stock = [];
        foreach ($ordered_items as $item) {
            $food_id = $item['menu_id'];
            $food_qty = (float)$item['quantity'];

            // Lấy công thức từ mảng PHP thay vì gọi DB
            $recipes = $recipes_by_food[$food_id] ?? [];

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

                $key = $ing_id . ':' . $target_warehouse_id;
                if (!isset($required_by_stock[$key])) {
                    $required_by_stock[$key] = [
                        'ingredient_id' => $ing_id,
                        'warehouse_id' => $target_warehouse_id,
                        'quantity' => 0
                    ];
                }
                $required_by_stock[$key]['quantity'] += $total_reduction;
            }
        }

        // 4. Khóa tồn kho, kiểm tra đủ hàng rồi mới trừ để tránh âm kho
        foreach ($required_by_stock as $row) {
            $ing_id = (int)$row['ingredient_id'];
            $warehouse_id = (int)$row['warehouse_id'];
            $total_reduction = (float)$row['quantity'];

            $query_check_stock->execute([$ing_id, $warehouse_id]);
            $current_stock = (float)($query_check_stock->fetchColumn() ?: 0);
            if ($current_stock < $total_reduction) {
                throw new Exception("Kho không đủ nguyên liệu (ID: $ing_id) tại kho $warehouse_id. Cần $total_reduction, còn $current_stock.");
            }

            $query_update_inventory->execute([$total_reduction, $ing_id, $warehouse_id, $total_reduction]);
            if ($query_update_inventory->rowCount() === 0) {
                throw new Exception("Không thể cập nhật tồn kho cho nguyên liệu ID $ing_id tại kho $warehouse_id.");
            }

            // 5. Ghi lịch sử xuất kho sau khi trừ thành công
            $query_history->execute([$ing_id, $warehouse_id, $total_reduction]);
        }

        $db->commit();

        // --- THÔNG BÁO TELEGRAM: ĐÃ XÁC NHẬN ĐƠN (endpoint AJAX cũ) ---
        if ($booking_info) {
            $time_str = date('H:i d/m/Y', strtotime($booking_info['booking_date']));
            $svc = htmlspecialchars((string)$booking_info['service_type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $name = htmlspecialchars((string)$booking_info['customer_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $phone = htmlspecialchars((string)$booking_info['customer_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $who = htmlspecialchars((string)$current_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $money_total = number_format((float)($booking_info['total_amount'] ?? 0), 0, ',', '.');
            $money_dep = number_format((float)($booking_info['deposit_amount'] ?? 0), 0, ',', '.');

            $msg = "✅ <b>ĐƠN DỊCH VỤ ĐÃ XÁC NHẬN</b>\n\n";
            $msg .= "🧾 Mã đơn: <b>#{$booking_info['id']}</b>\n";
            $msg .= "👤 Khách: <b>{$name}</b>\n";
            $msg .= "📞 SĐT: {$phone}\n";
            $msg .= "🏷 Loại: <b>{$svc}</b>\n";
            $msg .= "⏰ Lúc: {$time_str}\n";
            $msg .= "👥 Số khách: {$booking_info['guests']}\n";
            $msg .= "💰 Tổng: <b>{$money_total} VNĐ</b>\n";
            $msg .= "🧾 Cọc (30%): <b>{$money_dep} VNĐ</b>\n";
            $msg .= "👤 Xác nhận bởi: <b>{$who}</b>\n";
            
            // FIX: Hàm này bắt buộc phải có Timeout (xem mục 5 bên dưới)
            @sendTelegramNotification($msg);
        }

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
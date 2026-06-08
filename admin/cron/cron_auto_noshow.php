<?php
// Tự động kiểm tra và đánh dấu Khách không đến (No-Show)
// Chạy mỗi phút (tương tự như cron_telegram_reminder)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/notification_helper.php';

try {
    $db = (new Database())->getConnection();

    // Tìm các đơn:
    // - Trạng thái Confirmed
    // - Đã quá giờ hẹn 45 phút
    $stmt = $db->query("
        SELECT sb.id, sb.table_id, sb.customer_name, sb.service_type, t.table_code
        FROM service_bookings sb
        LEFT JOIN restaurant_tables t ON sb.table_id = t.id
        WHERE sb.status = 'Confirmed' 
          AND sb.booking_date <= DATE_SUB(NOW(), INTERVAL 45 MINUTE)
    ");
    $noshow_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $noshow_count = 0;

    foreach ($noshow_bookings as $b) {
        $db->beginTransaction();
        try {
            $id = $b['id'];
            
            // Hoàn kho
            $stmt_deduct = $db->prepare("SELECT ingredient_id, warehouse_id, quantity FROM booking_inventory_deductions WHERE booking_id = ?");
            $stmt_deduct->execute([$id]);
            $deductions = $stmt_deduct->fetchAll(PDO::FETCH_ASSOC);

            foreach ($deductions as $d) {
                $db->prepare("UPDATE inventory_stocks SET quantity = quantity + ? WHERE ingredient_id = ? AND warehouse_id = ?")
                   ->execute([$d['quantity'], $d['ingredient_id'], $d['warehouse_id']]);
                
                $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)")
                   ->execute([$d['ingredient_id'], $d['warehouse_id'], $d['quantity'], 'System Auto (Hoàn kho No-Show #' . $id . ')']);
                
                $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE ingredient_id = ? AND warehouse_id = 6")
                   ->execute([$d['quantity'], $d['ingredient_id']]);
            }
            $db->prepare("DELETE FROM booking_inventory_deductions WHERE booking_id = ?")->execute([$id]);

            // Giải phóng bàn
            if ($b['table_id']) {
                $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$b['table_id']]);
            }
            
            // Cập nhật trạng thái thành No-Show
            $db->prepare("UPDATE service_bookings SET status = 'No-Show', is_archived = 1 WHERE id = ?")->execute([$id]);
            
            $db->commit();
            $noshow_count++;

            // Báo Telegram
            $msg = "🚫 <b>HỆ THỐNG TỰ ĐỘNG HỦY (NO-SHOW)</b>\n";
            $msg .= "Mã đơn: <b>#{$id}</b>\n";
            $msg .= "Khách hàng: <b>{$b['customer_name']}</b>\n";
            $msg .= "Lý do: <i>Đã trễ hẹn quá 45 phút không đến.</i>\n";
            @sendTelegramNotification($msg);

        } catch (Exception $innerE) {
            $db->rollBack();
            // Tiếp tục vòng lặp
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'success', 'noshow_processed' => $noshow_count]);
    } else {
        echo "Auto No-Show run success: $noshow_count processed.";
    }

} catch (Exception $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } else {
        echo "Error: " . $e->getMessage();
    }
}

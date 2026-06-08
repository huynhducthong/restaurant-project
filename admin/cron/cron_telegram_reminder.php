<?php
// Tự động kiểm tra và gửi thông báo nhắc nhở khách đến qua Telegram
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/notification_helper.php';

try {
    $db = (new Database())->getConnection();

    // Tìm các đơn:
    // - Trạng thái Confirmed
    // - Chưa được nhắc (is_reminded = 0)
    // - Thời gian đến trong vòng 30 phút tính từ hiện tại, nhưng lớn hơn hiện tại (để không nhắc đơn trong quá khứ)
    $stmt = $db->query("
        SELECT sb.*, t.table_code, u.email 
        FROM service_bookings sb
        LEFT JOIN restaurant_tables t ON sb.table_id = t.id
        LEFT JOIN users u ON sb.user_id = u.id
        WHERE sb.status = 'Confirmed' 
          AND sb.is_reminded = 0 
          AND sb.booking_date > NOW() 
          AND sb.booking_date <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    ");
    $upcoming_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reminded_count = 0;

    foreach ($upcoming_bookings as $b) {
        // Chuẩn bị tin nhắn
        $time_str = date('H:i', strtotime($b['booking_date']));
        $date_str = date('d/m/Y', strtotime($b['booking_date']));
        
        $msg = "🛎 <b>[NHẮC NHỞ] KHÁCH SẮP ĐẾN TRONG 30 PHÚT!</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "👤 Khách hàng: <b>{$b['customer_name']}</b>\n";
        $msg .= "📞 Điện thoại: <b>{$b['customer_phone']}</b>\n";
        $msg .= "👥 Số lượng: <b>{$b['guests']} người</b>\n";
        $msg .= "⏰ Thời gian đến: <b>{$time_str}</b> ({$date_str})\n";
        
        if ($b['table_code']) {
            $msg .= "🍽 Bàn: <b>{$b['table_code']}</b>\n";
        }

        $msg .= "📝 Dịch vụ: <b>" . strtoupper($b['service_type']) . "</b>\n";

        if (!empty($b['message'])) {
            $msg .= "💬 Ghi chú: <i>{$b['message']}</i>\n";
        }

        // Gửi Telegram
        $result = sendTelegramNotification($msg);

        // Gửi Email Nhắc nhở cho khách hàng (nếu có email)
        if (!empty($b['email'])) {
            @sendBookingReminderEmail($b['email'], $b);
        }

        // Đánh dấu là đã nhắc nhở
        $update = $db->prepare("UPDATE service_bookings SET is_reminded = 1 WHERE id = ?");
        $update->execute([$b['id']]);

        $reminded_count++;
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'success', 'reminded' => $reminded_count]);
    } else {
        echo "Cron run success: $reminded_count reminded.";
    }

} catch (Exception $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } else {
        echo "Error: " . $e->getMessage();
    }
}

<?php
/**
 * File: config/notification_helper.php
 * Chức năng: Gửi thông báo qua Telegram Bot
 */

function sendTelegramNotification($message) {
    require_once __DIR__ . '/database.php';
    $db = (new Database())->getConnection();

    // Ưu tiên lấy từ file .env
    $token   = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $chat_id = $_ENV['TELEGRAM_CHAT_ID']   ?? '';
    $enabled = ($_ENV['ENABLE_TELEGRAM']   ?? '') === '1';

    // Nếu .env trống, lấy cấu hình từ bảng settings (Admin UI)
    if (!$token || !$chat_id) {
        $settings = $db->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('telegram_bot_token', 'telegram_chat_id', 'enable_telegram')")->fetchAll(PDO::FETCH_KEY_PAIR);
        $token   = $token ?: ($settings['telegram_bot_token'] ?? '');
        $chat_id = $chat_id ?: ($settings['telegram_chat_id'] ?? '');
        $enabled = $enabled ?: (($settings['enable_telegram'] ?? '0') === '1');
    }

    if (!$enabled || !$token || !$chat_id) {
        return false;
    }

    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    // Tắt kiểm tra SSL trên localhost XAMPP để tránh lỗi HTTPS
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Giới hạn thời gian kết nối & thực thi (Tránh đơ web)
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);

    $result = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($result !== false) && ($httpCode >= 200 && $httpCode < 300);
}

/**
 * Tạo nội dung thông báo tổng hợp buổi sáng
 */
function generateMorningReport() {
    require_once __DIR__ . '/database.php';
    $db = (new Database())->getConnection();

    // 1. Lấy cấu hình ngưỡng
    $settings = $db->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('inv_low_stock', 'inv_expiry_days')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $cfg_low_stock = (float)($settings['inv_low_stock'] ?? 5);
    $cfg_expiry_days = (int)($settings['inv_expiry_days'] ?? 7);
    $warn_date = date('Y-m-d', strtotime("+$cfg_expiry_days days"));

    // 2. Query Tồn kho thấp
    $stmt_low = $db->query("
        SELECT item_name, total_stock, unit_name, min_stock 
        FROM (
            SELECT i.item_name, i.unit_name, i.min_stock, IFNULL(SUM(s.quantity), 0) as total_stock
            FROM inventory i
            LEFT JOIN inventory_stocks s ON i.id = s.ingredient_id
            WHERE i.is_active = 1
            GROUP BY i.id
        ) as t 
        WHERE t.total_stock <= CASE WHEN t.min_stock > 0 THEN t.min_stock ELSE $cfg_low_stock END
    ");
    $low_items = $stmt_low->fetchAll(PDO::FETCH_ASSOC);

    // 3. Query Sắp hết hạn (Còn hạn nhưng sắp hết)
    $stmt_exp = $db->query("
        SELECT i.item_name, MIN(b.expiry_date) as earliest_exp, i.unit_name 
        FROM inventory_batches b
        JOIN inventory i ON b.ingredient_id = i.id
        WHERE i.is_active = 1 
          AND b.quantity > 0 
          AND b.expiry_date IS NOT NULL 
          AND b.expiry_date <= '$warn_date' 
          AND b.expiry_date >= CURDATE()
          AND b.warehouse_id NOT IN (6, 7)
        GROUP BY i.id
    ");
    $exp_items = $stmt_exp->fetchAll(PDO::FETCH_ASSOC);

    // 4. Query Đã hết hạn (Quá HSD)
    $stmt_already_exp = $db->query("
        SELECT i.item_name, MIN(b.expiry_date) as earliest_exp, i.unit_name 
        FROM inventory_batches b
        JOIN inventory i ON b.ingredient_id = i.id
        WHERE i.is_active = 1 
          AND b.quantity > 0 
          AND b.expiry_date IS NOT NULL 
          AND b.expiry_date < CURDATE()
          AND b.warehouse_id NOT IN (6, 7)
        GROUP BY i.id
    ");
    $already_exp_items = $stmt_already_exp->fetchAll(PDO::FETCH_ASSOC);

    if (empty($low_items) && empty($exp_items) && empty($already_exp_items)) return null;

    $msg = "<b>☀️ BÁO CÁO KHO BUỔI SÁNG - " . date('d/m/Y') . "</b>\n\n";

    if (!empty($low_items)) {
        $msg .= "⚠️ <b>CẦN NHẬP HÀNG (" . count($low_items) . "):</b>\n";
        foreach ($low_items as $item) {
            $msg .= "- " . $item['item_name'] . ": " . (float)$item['total_stock'] . " " . $item['unit_name'] . " (Min: " . (float)$item['min_stock'] . ")\n";
        }
        $msg .= "\n";
    }

    if (!empty($already_exp_items)) {
        $msg .= "🔴 <b>ĐÃ HẾT HẠN - CẦN HỦY (" . count($already_exp_items) . "):</b>\n";
        foreach ($already_exp_items as $item) {
            $msg .= "- " . $item['item_name'] . " (HSD: " . date('d/m', strtotime($item['earliest_exp'])) . ")\n";
        }
        $msg .= "\n";
    }

    if (!empty($exp_items)) {
        $msg .= "⏰ <b>SẮP HẾT HẠN (" . count($exp_items) . "):</b>\n";
        foreach ($exp_items as $item) {
            $msg .= "- " . $item['item_name'] . " (HSD: " . date('d/m', strtotime($item['earliest_exp'])) . ")\n";
        }
        $msg .= "\n";
    }

    $msg .= "👉 <i>Vui lòng đăng nhập hệ thống để kiểm tra chi tiết.</i>";

    return $msg;
}

/**
 * Giờ gửi báo cáo doanh thu cuối ngày (0–23). Ưu tiên .env TELEGRAM_EOD_HOUR, sau đó settings, mặc định 22.
 */
function getTelegramEodHour(PDO $db): int {
    if (isset($_ENV['TELEGRAM_EOD_HOUR']) && $_ENV['TELEGRAM_EOD_HOUR'] !== '') {
        $h = (int) $_ENV['TELEGRAM_EOD_HOUR'];
    } else {
        $h = (int) ($db->query("SELECT key_value FROM settings WHERE key_name = 'telegram_eod_hour'")->fetchColumn() ?: 22);
    }
    if ($h < 0 || $h > 23) {
        $h = 22;
    }
    return $h;
}

/**
 * Báo cáo doanh thu cuối ngày gửi Telegram (theo ngày phục vụ = DATE(booking_date)).
 *
 * @param PDO    $db
 * @param string|null $forDateYmd Ngày dạng Y-m-d (mặc định hôm nay theo timezone PHP)
 */
function generateEndOfDayRevenueReport(PDO $db, ?string $forDateYmd = null): string {
    if ($forDateYmd === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $forDateYmd)) {
        $forDateYmd = date('Y-m-d');
    }

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM service_bookings
        WHERE DATE(booking_date) = ? AND is_archived = 0 AND service_type = 'table'
    ");
    $stmt->execute([$forDateYmd]);
    $count_table = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM service_bookings
        WHERE DATE(booking_date) = ? AND is_archived = 0
    ");
    $stmt->execute([$forDateYmd]);
    $count_all = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT IFNULL(SUM(CASE WHEN status = 'No-Show' THEN deposit_amount ELSE total_amount END), 0) FROM service_bookings
        WHERE DATE(booking_date) = ? AND is_archived = 0 AND status != 'Cancelled'
    ");
    $stmt->execute([$forDateYmd]);
    $revenue = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT f.name, SUM(bd.quantity) AS qty
        FROM booking_details bd
        INNER JOIN foods f ON f.id = bd.menu_id
        INNER JOIN service_bookings sb ON sb.id = bd.booking_id
        WHERE DATE(sb.booking_date) = ? AND sb.is_archived = 0 AND sb.status != 'Cancelled'
        GROUP BY f.id, f.name
        ORDER BY qty DESC
        LIMIT 1
    ");
    $stmt->execute([$forDateYmd]);
    $top = $stmt->fetch(PDO::FETCH_ASSOC);

    $dmy = date('d/m/Y', strtotime($forDateYmd));
    $money = number_format($revenue, 0, ',', '.');

    $msg = "<b>📊 BÁO CÁO CUỐI NGÀY — {$dmy}</b>\n";
    $msg .= "<i>(Theo lịch phục vụ — ngày giờ khách đặt)</i>\n\n";
    $msg .= "🍽 <b>Đơn đặt bàn (loại bàn):</b> {$count_table}\n";
    if ($count_all !== $count_table) {
        $msg .= "📋 <b>Tổng đơn dịch vụ cùng ngày:</b> {$count_all}\n";
    }
    $msg .= "💰 <b>Doanh thu dự kiến:</b> {$money} VNĐ\n";
    $msg .= "   <i>(Đơn chưa hủy, gồm mọi loại dịch vụ)</i>\n\n";

    if ($top && (float) $top['qty'] > 0) {
        $q = (float) $top['qty'];
        $qtyStr = ($q == floor($q)) ? (string) (int) $q : (string) $q;
        $msg .= "⭐ <b>Món được gọi nhiều nhất:</b> " . htmlspecialchars($top['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . " (x{$qtyStr})\n";
    } else {
        $msg .= "⭐ <b>Món được gọi nhiều nhất:</b> <i>Không có dữ liệu món trong đơn</i>\n";
    }

    $msg .= "\n👉 <i>Admin — Chi tiết tại Báo cáo / Quản lý dịch vụ.</i>";

    return $msg;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Gửi Email Xác nhận Đặt bàn cho Khách hàng
 */
function sendBookingEmailConfirmation($emailNguoiNhan, $booking_info) {
    if (empty($emailNguoiNhan)) return false;
    
    // Nạp thư viện nếu chưa có
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Xác Nhận Đặt Bàn - Restaurantly';
        
        $svc = htmlspecialchars($booking_info['service_type'] ?? 'Dịch vụ', ENT_QUOTES);
        if ($svc === 'table') $svc = 'Đặt bàn tiêu chuẩn';
        if ($svc === 'birthday') $svc = 'Tiệc kỷ niệm / Phòng VIP';
        if ($svc === 'chef') $svc = 'Đầu bếp tại gia';
        if ($svc === 'bespoke') $svc = 'Thiết kế riêng';

        $timeStr = date('H:i - d/m/Y', strtotime($booking_info['booking_date']));
        $money = number_format((float)($booking_info['total_amount'] ?? 0), 0, ',', '.');
        $deposit = number_format((float)($booking_info['deposit_amount'] ?? 0), 0, ',', '.');
        
        $name = htmlspecialchars($booking_info['customer_name'] ?? 'Quý khách', ENT_QUOTES);

        $mail->Body = "
            <div style='max-width: 600px; margin: auto; border: 2px solid #A88746; border-radius: 8px; font-family: Arial, sans-serif; overflow: hidden;'>
                <div style='background-color: #F9F9F9; padding: 20px; text-align: center;'>
                    <h1 style='color: #A88746; margin: 0; font-family: serif; letter-spacing: 2px;'>RESTAURANTLY</h1>
                    <p style='color: #fff; margin: 5px 0 0; font-size: 14px;'>Fine Dining Experience</p>
                </div>
                <div style='padding: 30px; background-color: #FFFFFF;'>
                    <h2 style='color: #2c2c2c; margin-top: 0;'>Kính chào $name,</h2>
                    <p style='color: #555; line-height: 1.6;'>Cảm ơn quý khách đã tin tưởng và lựa chọn dịch vụ tại Restaurantly. Chúng tôi xin trân trọng xác nhận yêu cầu đặt bàn của quý khách đã được hệ thống ghi nhận thành công.</p>
                    
                    <div style='background-color: #f9f6f0; padding: 20px; border-left: 4px solid #A88746; margin: 25px 0;'>
                        <h3 style='margin-top: 0; color: #A88746;'>Thông Tin Đặt Bàn (#{$booking_info['id']})</h3>
                        <table style='width: 100%; border-collapse: collapse; font-size: 15px;'>
                            <tr><td style='padding: 8px 0; color: #666; width: 40%;'>Loại dịch vụ:</td><td style='padding: 8px 0; font-weight: bold;'>$svc</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Thời gian:</td><td style='padding: 8px 0; font-weight: bold; color: #d32f2f;'>$timeStr</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Số khách:</td><td style='padding: 8px 0; font-weight: bold;'>{$booking_info['guests']} người</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Tổng dự kiến:</td><td style='padding: 8px 0; font-weight: bold;'>$money VNĐ</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Tiền cọc (30%):</td><td style='padding: 8px 0; font-weight: bold; color: #f57c00;'>$deposit VNĐ</td></tr>
                        </table>
                    </div>
                    
                    <p style='color: #555; line-height: 1.6;'>Vui lòng có mặt đúng giờ để chúng tôi có thể phục vụ quý khách một cách chu đáo nhất. Mọi thay đổi về lịch trình xin vui lòng liên hệ Hotline: <strong>0123 456 789</strong>.</p>
                    
                    <p style='color: #555; line-height: 1.6; margin-bottom: 0;'>Hân hạnh được đón tiếp quý khách!</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Gửi Email Nhắc Nhở Đặt Bàn (30 Phút) cho Khách hàng
 */
function sendBookingReminderEmail($emailNguoiNhan, $booking_info) {
    if (empty($emailNguoiNhan)) return false;
    
    // Nạp thư viện nếu chưa có
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Nhắc Nhở: Sắp Đến Giờ Đặt Bàn - Restaurantly';
        
        $svc = htmlspecialchars($booking_info['service_type'] ?? 'Dịch vụ', ENT_QUOTES);
        if ($svc === 'table') $svc = 'Đặt bàn tiêu chuẩn';
        if ($svc === 'birthday') $svc = 'Tiệc kỷ niệm / Phòng VIP';
        if ($svc === 'chef') $svc = 'Đầu bếp tại gia';
        if ($svc === 'bespoke') $svc = 'Thiết kế riêng';

        $timeStr = date('H:i - d/m/Y', strtotime($booking_info['booking_date']));
        $name = htmlspecialchars($booking_info['customer_name'] ?? 'Quý khách', ENT_QUOTES);

        $mail->Body = "
            <div style='max-width: 600px; margin: auto; border: 2px solid #A88746; border-radius: 8px; font-family: Arial, sans-serif; overflow: hidden;'>
                <div style='background-color: #F9F9F9; padding: 20px; text-align: center;'>
                    <h1 style='color: #A88746; margin: 0; font-family: serif; letter-spacing: 2px;'>RESTAURANTLY</h1>
                    <p style='color: #fff; margin: 5px 0 0; font-size: 14px;'>Fine Dining Experience</p>
                </div>
                <div style='padding: 30px; background-color: #FFFFFF;'>
                    <h2 style='color: #2c2c2c; margin-top: 0;'>Kính chào $name,</h2>
                    <p style='color: #555; line-height: 1.6;'>Đây là lời nhắc nhở tự động từ nhà hàng Restaurantly. Bạn có một lịch hẹn đặt bàn sẽ diễn ra trong khoảng <strong>30 phút nữa</strong>.</p>
                    
                    <div style='background-color: #f9f6f0; padding: 20px; border-left: 4px solid #A88746; margin: 25px 0;'>
                        <h3 style='margin-top: 0; color: #A88746;'>Thông Tin Đặt Bàn (#{$booking_info['id']})</h3>
                        <table style='width: 100%; border-collapse: collapse; font-size: 15px;'>
                            <tr><td style='padding: 8px 0; color: #666; width: 40%;'>Thời gian:</td><td style='padding: 8px 0; font-weight: bold; color: #d32f2f;'>$timeStr</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Dịch vụ:</td><td style='padding: 8px 0; font-weight: bold;'>$svc</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Số khách:</td><td style='padding: 8px 0; font-weight: bold;'>{$booking_info['guests']} người</td></tr>
                        </table>
                    </div>
                    
                    <p style='color: #555; line-height: 1.6;'>Vui lòng có mặt đúng giờ để chúng tôi có thể phục vụ quý khách một cách chu đáo nhất. Nếu quý khách đến muộn quá 15 phút mà không thông báo trước, hệ thống có thể sẽ tự động hủy lịch đặt.</p>
                    
                    <p style='color: #555; line-height: 1.6;'>Mọi thay đổi về lịch trình xin vui lòng liên hệ gấp qua Hotline: <strong>0123 456 789</strong>.</p>
                    
                    <p style='color: #555; line-height: 1.6; margin-bottom: 0;'>Hẹn gặp lại quý khách tại nhà hàng!</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Gửi Email Xin Lỗi Khi Hủy Lịch Đặt Bàn
 */
function sendBookingCancelEmail($emailNguoiNhan, $booking_info) {
    if (empty($emailNguoiNhan)) return false;
    
    // Nạp thư viện nếu chưa có
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Thông Báo Hủy Lịch Đặt Bàn - Restaurantly';
        
        $svc = htmlspecialchars($booking_info['service_type'] ?? 'Dịch vụ', ENT_QUOTES);
        if ($svc === 'table') $svc = 'Đặt bàn tiêu chuẩn';
        if ($svc === 'birthday') $svc = 'Tiệc kỷ niệm / Phòng VIP';
        if ($svc === 'chef') $svc = 'Đầu bếp tại gia';
        if ($svc === 'bespoke') $svc = 'Thiết kế riêng';

        $timeStr = date('H:i - d/m/Y', strtotime($booking_info['booking_date']));
        $name = htmlspecialchars($booking_info['customer_name'] ?? 'Quý khách', ENT_QUOTES);

        $mail->Body = "
            <div style='max-width: 600px; margin: auto; border: 2px solid #A88746; border-radius: 8px; font-family: Arial, sans-serif; overflow: hidden;'>
                <div style='background-color: #F9F9F9; padding: 20px; text-align: center;'>
                    <h1 style='color: #A88746; margin: 0; font-family: serif; letter-spacing: 2px;'>RESTAURANTLY</h1>
                    <p style='color: #fff; margin: 5px 0 0; font-size: 14px;'>Fine Dining Experience</p>
                </div>
                <div style='padding: 30px; background-color: #FFFFFF;'>
                    <h2 style='color: #d32f2f; margin-top: 0;'>Kính chào $name,</h2>
                    <p style='color: #555; line-height: 1.6;'>Chúng tôi vô cùng xin lỗi vì sự bất tiện này, nhưng do sự cố khách quan vượt ngoài mong muốn, chúng tôi buộc phải <strong>hủy lịch đặt bàn</strong> của quý khách.</p>
                    
                    <div style='background-color: #f9f6f0; padding: 20px; border-left: 4px solid #A88746; margin: 25px 0;'>
                        <h3 style='margin-top: 0; color: #A88746;'>Thông Tin Đặt Bàn Đã Hủy (#{$booking_info['id']})</h3>
                        <table style='width: 100%; border-collapse: collapse; font-size: 15px;'>
                            <tr><td style='padding: 8px 0; color: #666; width: 40%;'>Thời gian:</td><td style='padding: 8px 0; font-weight: bold;'>$timeStr</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Dịch vụ:</td><td style='padding: 8px 0; font-weight: bold;'>$svc</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Số khách:</td><td style='padding: 8px 0; font-weight: bold;'>{$booking_info['guests']} người</td></tr>
                        </table>
                    </div>
                    
                    <p style='color: #555; line-height: 1.6;'>Nếu quý khách đã tiến hành đặt cọc trực tuyến, hệ thống sẽ tự động đối soát và nhà hàng sẽ liên hệ với quý khách để tiến hành <strong>hoàn tiền 100%</strong> trong vòng 24h.</p>
                    
                    <p style='color: #555; line-height: 1.6;'>Quý khách vui lòng liên hệ ngay với quản lý nhà hàng qua Hotline: <strong>0123 456 789</strong> để được hỗ trợ giải quyết nhanh chóng nhất hoặc đặt lại lịch mới với ưu đãi đền bù.</p>
                    
                    <p style='color: #555; line-height: 1.6; margin-bottom: 0;'>Một lần nữa xin chân thành cáo lỗi cùng quý khách!</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendVipRegistrationEmail($emailNguoiNhan, $name, $plan_name, $price, $end_date) {
    if (empty($emailNguoiNhan)) return false;
    
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Kích Hoạt Đặc Quyền VIP Thành Công - Restaurantly';
        
        $priceStr = number_format((float)$price, 0, ',', '.');

        $mail->Body = "
            <div style='max-width: 600px; margin: auto; border: 2px solid #A88746; border-radius: 8px; font-family: Arial, sans-serif; overflow: hidden;'>
                <div style='background-color: #F9F9F9; padding: 20px; text-align: center;'>
                    <h1 style='color: #A88746; margin: 0; font-family: serif; letter-spacing: 2px;'>RESTAURANTLY</h1>
                    <p style='color: #222; margin: 5px 0 0; font-size: 14px;'>Fine Dining Experience</p>
                </div>
                <div style='padding: 30px; background-color: #FFFFFF;'>
                    <h2 style='color: #2c2c2c; margin-top: 0;'>Kính chào $name,</h2>
                    <p style='color: #555; line-height: 1.6;'>Cảm ơn quý khách đã tin tưởng và nâng tầm trải nghiệm ẩm thực cùng Restaurantly. Chúng tôi xin trân trọng thông báo <strong>Đặc quyền VIP</strong> của quý khách đã được kích hoạt thành công.</p>
                    
                    <div style='background-color: #f9f6f0; padding: 20px; border-left: 4px solid #A88746; margin: 25px 0;'>
                        <h3 style='margin-top: 0; color: #A88746;'>Thông Tin Gói VIP</h3>
                        <table style='width: 100%; border-collapse: collapse; font-size: 15px;'>
                            <tr><td style='padding: 8px 0; color: #666; width: 40%;'>Gói Hội Viên:</td><td style='padding: 8px 0; font-weight: bold;'>$plan_name</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Giá:</td><td style='padding: 8px 0; font-weight: bold;'>$priceStr VNĐ</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Hiệu lực đến:</td><td style='padding: 8px 0; font-weight: bold;'>$end_date</td></tr>
                        </table>
                    </div>
                    
                    <p style='color: #555; line-height: 1.6;'>Giờ đây, quý khách có thể tận hưởng toàn bộ các đặc quyền của hạng thẻ $plan_name, bao gồm chiết khấu hóa đơn, ưu tiên đặt bàn và các dịch vụ Fine Dining thượng lưu khác.</p>
                    
                    <p style='color: #555; line-height: 1.6; margin-bottom: 0;'>Rất hân hạnh được phục vụ quý khách tại nhà hàng!</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendVipCancellationEmail($emailNguoiNhan, $name) {
    if (empty($emailNguoiNhan)) return false;
    
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Xác Nhận Hủy Gói VIP - Restaurantly';
        
        $mail->Body = "
            <div style='max-width: 600px; margin: auto; border: 2px solid #A88746; border-radius: 8px; font-family: Arial, sans-serif; overflow: hidden;'>
                <div style='background-color: #F9F9F9; padding: 20px; text-align: center;'>
                    <h1 style='color: #A88746; margin: 0; font-family: serif; letter-spacing: 2px;'>RESTAURANTLY</h1>
                    <p style='color: #222; margin: 5px 0 0; font-size: 14px;'>Fine Dining Experience</p>
                </div>
                <div style='padding: 30px; background-color: #FFFFFF;'>
                    <h2 style='color: #2c2c2c; margin-top: 0;'>Kính chào $name,</h2>
                    <p style='color: #555; line-height: 1.6;'>Hệ thống đã ghi nhận và xử lý thành công yêu cầu <strong>hủy gia hạn gói VIP</strong> của quý khách.</p>
                    
                    <p style='color: #555; line-height: 1.6;'>Các đặc quyền VIP của thẻ hiện tại sẽ kết thúc. Hệ thống sẽ ngừng tự động gia hạn vào chu kỳ tiếp theo.</p>
                    
                    <p style='color: #555; line-height: 1.6;'>Nếu quý khách thay đổi quyết định, quý khách hoàn toàn có thể đăng ký lại gói VIP bất kỳ lúc nào tại mục Thông tin cá nhân trên website của chúng tôi.</p>
                    
                    <p style='color: #555; line-height: 1.6;'>Nếu quý khách cần hỗ trợ thêm hoặc có góp ý để chúng tôi cải thiện dịch vụ, vui lòng liên hệ Hotline: <strong>0123 456 789</strong>.</p>
                    
                    <p style='color: #555; line-height: 1.6; margin-bottom: 0;'>Trân trọng cảm ơn quý khách!</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

<?php
/**
 * File: config/notification_helper.php
 * Chức năng: Gửi thông báo qua Telegram Bot
 */

function sendTelegramNotification($message) {
    require_once __DIR__ . '/database.php';
    $db = (new Database())->getConnection();

    // Ưu tiên lấy từ file .env
    $token   = $_ENV['TELEGRAM_BOT_TOKEN'] ?? $_SERVER['TELEGRAM_BOT_TOKEN'] ?? '';
    $chat_id = $_ENV['TELEGRAM_CHAT_ID']   ?? $_SERVER['TELEGRAM_CHAT_ID'] ?? '';
    $enabled = ($_ENV['ENABLE_TELEGRAM']   ?? $_SERVER['ENABLE_TELEGRAM'] ?? '') === '1';

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

    // TẠO TIẾN TRÌNH CHẠY NGẦM ĐỂ KHÔNG LÀM LAG TRANG WEB
    $tmp_dir = sys_get_temp_dir();
    $tmp_file = $tmp_dir . '/telegram_msg_' . md5(uniqid('', true)) . '.txt';
    file_put_contents($tmp_file, $message);
    
    $runner = __DIR__ . '/telegram_bg_runner.php';
    if (!file_exists($runner)) {
        $runner_code = "<?php\n"
        . "\$file = \$argv[1] ?? '';\n"
        . "if (!file_exists(\$file)) exit;\n"
        . "\$msg = file_get_contents(\$file);\n"
        . "unlink(\$file);\n"
        . "\$token = \$argv[2] ?? '';\n"
        . "\$chat_id = \$argv[3] ?? '';\n"
        . "if (\$token && \$chat_id) {\n"
        . "    \$ch = curl_init(\"https://api.telegram.org/bot\$token/sendMessage\");\n"
        . "    curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n"
        . "    curl_setopt(\$ch, CURLOPT_POST, true);\n"
        . "    curl_setopt(\$ch, CURLOPT_POSTFIELDS, http_build_query(['chat_id' => \$chat_id, 'text' => \$msg, 'parse_mode' => 'HTML']));\n"
        . "    curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false);\n"
        . "    curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, false);\n"
        . "    curl_exec(\$ch);\n"
        . "    curl_close(\$ch);\n"
        . "}\n";
        file_put_contents($runner, $runner_code);
    }
    
    // Gọi PHP chạy ngầm qua cmd (Dành riêng cho XAMPP Windows)
    $cmd = "start /B php " . escapeshellarg($runner) . " " . escapeshellarg($tmp_file) . " " . escapeshellarg($token) . " " . escapeshellarg($chat_id);
    pclose(popen($cmd, "r"));
    
    return true;
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
        $mail->Host       = $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? $_SERVER['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
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
        <div style='background-color: #0b0c10; padding: 40px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; color: #e0e0e0;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #1f2833; border-radius: 12px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);'>
                <!-- Header -->
                <div style='background-color: #000000; padding: 40px 20px; text-align: center; border-bottom: 2px solid #c5a880;'>
                    <h1 style='color: #c5a880; margin: 0; font-family: \"Times New Roman\", Times, serif; font-size: 32px; letter-spacing: 4px; text-transform: uppercase;'>Restaurantly</h1>
                    <p style='color: #888; margin: 10px 0 0; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;'>Fine Dining Experience</p>
                </div>
                
                <!-- Body -->
                <div style='padding: 40px 30px;'>
                    <h2 style='color: #ffffff; margin-top: 0; font-weight: 300; font-size: 24px;'>Kính chào $name,</h2>
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Cảm ơn quý khách đã tin tưởng và lựa chọn dịch vụ tại Restaurantly. Chúng tôi xin trân trọng xác nhận yêu cầu đặt bàn của quý khách đã được hệ thống ghi nhận thành công.</p>
                    
                    <!-- Details Card -->
                    <div style='margin: 35px 0; background: #242f3b; border-radius: 8px; padding: 30px; border-left: 4px solid #c5a880; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2);'>
                        <h3 style='margin: 0 0 20px 0; color: #c5a880; font-family: \"Times New Roman\", Times, serif; font-size: 20px; letter-spacing: 1px;'>Thông Tin Đặt Bàn (#{$booking_info['id']})</h3>
                        
                        <table style='width: 100%; border-collapse: collapse; font-size: 15px;'>
                            <tr>
                                <td style='padding: 12px 0; color: #888; width: 40%; border-bottom: 1px solid rgba(255,255,255,0.05);'>Loại dịch vụ:</td>
                                <td style='padding: 12px 0; color: #ffffff; font-weight: 500; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>$svc</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888; border-bottom: 1px solid rgba(255,255,255,0.05);'>Thời gian:</td>
                                <td style='padding: 12px 0; color: #c5a880; font-weight: bold; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>$timeStr</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888; border-bottom: 1px solid rgba(255,255,255,0.05);'>Số khách:</td>
                                <td style='padding: 12px 0; color: #ffffff; font-weight: 500; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>{$booking_info['guests']} người</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888; border-bottom: 1px solid rgba(255,255,255,0.05);'>Tổng dự kiến:</td>
                                <td style='padding: 12px 0; color: #ffffff; font-weight: 500; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>$money VNĐ</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888;'>Tiền cọc (30%):</td>
                                <td style='padding: 12px 0; color: #4caf50; font-weight: bold; text-align: right;'>$deposit VNĐ</td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Vui lòng có mặt đúng giờ để chúng tôi có thể phục vụ quý khách một cách chu đáo nhất. Mọi thay đổi về lịch trình xin vui lòng liên hệ Hotline: <strong style='color: #c5a880;'>0123 456 789</strong>.</p>
                    
                    <p style='color: #ffffff; line-height: 1.8; font-size: 16px; margin-top: 30px; font-style: italic; font-family: \"Times New Roman\", Times, serif;'>Hân hạnh được đón tiếp quý khách!</p>
                </div>
                
                <!-- Footer -->
                <div style='background-color: #11151c; padding: 25px 20px; text-align: center; border-top: 1px solid #2a3644;'>
                    <p style='color: #666; margin: 0; font-size: 12px;'>&copy; " . date('Y') . " Restaurantly. All rights reserved.</p>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Gửi Email Thông Báo Báo Giá (Quote) cho Khách hàng
 */
function sendBookingQuoteEmail($emailNguoiNhan, $booking_info) {
    if (empty($emailNguoiNhan)) return false;
    
    // Nạp thư viện nếu chưa có
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? $_SERVER['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Nhà hàng đã cập nhật Báo Giá cho đơn đặt của quý khách - Restaurantly';
        
        $svc = htmlspecialchars($booking_info['service_type'] ?? 'Dịch vụ', ENT_QUOTES);
        if ($svc === 'table') $svc = 'Đặt bàn tiêu chuẩn';
        if ($svc === 'birthday') $svc = 'Tiệc kỷ niệm / Phòng VIP';
        if ($svc === 'chef') $svc = 'Đầu bếp tại gia';
        if ($svc === 'bespoke') $svc = 'Thiết kế riêng';

        $timeStr = date('H:i - d/m/Y', strtotime($booking_info['booking_date']));
        $name = htmlspecialchars($booking_info['customer_name'] ?? 'Quý khách', ENT_QUOTES);
        $totalAmt = number_format($booking_info['total_amount'] ?? 0);
        $depositAmt = number_format($booking_info['deposit_amount'] ?? 0);

        $mail->Body = "
        <div style='background-color: #0b0c10; padding: 40px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; color: #e0e0e0;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #1f2833; border-radius: 12px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);'>
                <!-- Header -->
                <div style='background-color: #000000; padding: 40px 20px; text-align: center; border-bottom: 2px solid #c5a880;'>
                    <h1 style='color: #c5a880; margin: 0; font-family: \"Times New Roman\", Times, serif; font-size: 32px; letter-spacing: 4px; text-transform: uppercase;'>Restaurantly</h1>
                    <p style='color: #888; margin: 10px 0 0; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;'>Fine Dining Experience</p>
                </div>
                
                <!-- Body -->
                <div style='padding: 40px 30px;'>
                    <h2 style='color: #ffffff; margin-top: 0; font-weight: 300; font-size: 24px;'>Kính chào $name,</h2>
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Bếp trưởng của chúng tôi đã xem xét và cập nhật <strong>Báo giá chi tiết</strong> cho đơn đặt <strong>$svc</strong> của quý khách.</p>
                    
                    <div style='margin: 30px 0; background: #242f3b; border-radius: 8px; padding: 25px; border-left: 4px solid #c5a880;'>
                        <h3 style='margin: 0 0 15px 0; color: #c5a880; font-family: \"Times New Roman\", Times, serif; font-size: 18px;'>Chi Tiết Báo Giá:</h3>
                        <table style='width: 100%; border-collapse: collapse; color: #e0e0e0; font-size: 15px;'>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; color: #888; width: 40%;'>Loại Dịch Vụ:</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; font-weight: bold;'>$svc</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; color: #888;'>Thời Gian:</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; font-weight: bold;'>$timeStr</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; color: #888;'>Tổng Tiền Dự Kiến:</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; font-weight: bold; color: #fff;'>{$totalAmt} VNĐ</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; color: #888;'>Tiền Cọc (30%):</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; font-weight: bold; color: #f39c12;'>{$depositAmt} VNĐ</td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Quý khách vui lòng truy cập vào tài khoản trên website để xem chi tiết thực đơn (nếu có) và tiến hành đặt cọc để giữ chỗ.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://restaurantly.com/profile.php?tab=bookings' style='display: inline-block; padding: 12px 30px; background-color: #c5a880; color: #000000; text-decoration: none; font-weight: bold; border-radius: 4px; text-transform: uppercase; font-size: 13px; letter-spacing: 1px;'>Xem Chi Tiết & Đặt Cọc</a>
                    </div>
                    
                    <p style='color: #ffffff; line-height: 1.8; font-size: 16px; margin-top: 30px; font-style: italic; font-family: \"Times New Roman\", Times, serif;'>Trân trọng,<br>Ban Quản Trị Restaurantly</p>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Gửi Email Xác nhận Đã Tiếp Nhận Yêu Cầu (Pending) cho Khách hàng
 */
function sendBookingReceivedEmail($emailNguoiNhan, $booking_info) {
    if (empty($emailNguoiNhan)) return false;
    
    // Nạp thư viện nếu chưa có
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? $_SERVER['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Hệ thống đã tiếp nhận yêu cầu đặt lịch - Restaurantly';
        
        $svc = htmlspecialchars($booking_info['service_type'] ?? 'Dịch vụ', ENT_QUOTES);
        if ($svc === 'table') $svc = 'Đặt bàn tiêu chuẩn';
        if ($svc === 'birthday') $svc = 'Tiệc kỷ niệm / Phòng VIP';
        if ($svc === 'chef') $svc = 'Đầu bếp tại gia';
        if ($svc === 'bespoke') $svc = 'Thiết kế riêng';

        $timeStr = date('H:i - d/m/Y', strtotime($booking_info['booking_date']));
        $name = htmlspecialchars($booking_info['customer_name'] ?? 'Quý khách', ENT_QUOTES);

        $mail->Body = "
        <div style='background-color: #0b0c10; padding: 40px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; color: #e0e0e0;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #1f2833; border-radius: 12px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);'>
                <!-- Header -->
                <div style='background-color: #000000; padding: 40px 20px; text-align: center; border-bottom: 2px solid #c5a880;'>
                    <h1 style='color: #c5a880; margin: 0; font-family: \"Times New Roman\", Times, serif; font-size: 32px; letter-spacing: 4px; text-transform: uppercase;'>Restaurantly</h1>
                    <p style='color: #888; margin: 10px 0 0; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;'>Fine Dining Experience</p>
                </div>
                
                <!-- Body -->
                <div style='padding: 40px 30px;'>
                    <h2 style='color: #ffffff; margin-top: 0; font-weight: 300; font-size: 24px;'>Kính chào $name,</h2>
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Hệ thống của chúng tôi vừa tiếp nhận thành công yêu cầu đặt lịch <strong>$svc</strong> của quý khách.</p>
                    
                    <div style='margin: 30px 0; background: #242f3b; border-radius: 8px; padding: 25px; border-left: 4px solid #c5a880;'>
                        <h3 style='margin: 0 0 15px 0; color: #c5a880; font-family: \"Times New Roman\", Times, serif; font-size: 18px;'>Thông Tin Yêu Cầu Đang Chờ Xử Lý:</h3>
                        <table style='width: 100%; border-collapse: collapse; color: #e0e0e0; font-size: 15px;'>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; color: #888; width: 40%;'>Loại Dịch Vụ:</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; font-weight: bold;'>$svc</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; color: #888;'>Thời Gian:</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; font-weight: bold;'>$timeStr</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; color: #888;'>Trạng Thái:</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #334050; font-weight: bold; color: #f39c12;'>Đang chờ duyệt</td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Ban Quản lý nhà hàng sẽ sớm kiểm tra yêu cầu và phản hồi lại cho quý khách (hoặc tiến hành báo giá nếu là dịch vụ thiết kế riêng). Xin quý khách kiên nhẫn chờ đợi.</p>
                    
                    <p style='color: #ffffff; line-height: 1.8; font-size: 16px; margin-top: 30px; font-style: italic; font-family: \"Times New Roman\", Times, serif;'>Trân trọng,<br>Ban Quản Trị Restaurantly</p>
                </div>
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
        $mail->Host       = $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? $_SERVER['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Thông Báo Hủy Lịch Đặt Bàn - Restaurantly';
        
        $svc = htmlspecialchars($booking_info['service_type'] ?? 'Dịch vụ', ENT_QUOTES);
        if ($svc === 'table') $svc = 'Đặt bàn tiêu chuẩn';
        if ($svc === 'birthday') $svc = 'Tiệc kỷ niệm / Phòng VIP';
        if ($svc === 'chef') $svc = 'Đầu bếp tại gia';
        if ($svc === 'bespoke') $svc = 'Thiết kế riêng';

        $timeStr = date('H:i - d/m/Y', strtotime($booking_info['booking_date']));
        $money = number_format((float)($booking_info['total_amount'] ?? 0), 0, ',', '.');
        $deposit = number_format((float)($booking_info['deposit_amount'] ?? 0), 0, ',', '.');
        $name = htmlspecialchars($booking_info['customer_name'] ?? 'Quý khách', ENT_QUOTES);

        $foodsHtml = "";
        if (!empty($booking_info['foods']) && is_array($booking_info['foods'])) {
            $foodsHtml .= "
                        <div style='margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;'>
                            <h4 style='color: #ff4d4d; margin: 0 0 15px 0; font-size: 16px;'>Các Món Đã Hủy:</h4>
                            <ul style='list-style: none; padding: 0; margin: 0; color: #b0b0b0;'>";
            foreach ($booking_info['foods'] as $food) {
                $foodsHtml .= "<li style='margin-bottom: 8px;'>- " . htmlspecialchars($food['name']) . " <span style='color: #888;'>(x" . $food['quantity'] . ")</span></li>";
            }
            $foodsHtml .= "</ul></div>";
        }

        $mail->Body = "
            <div style='max-width: 600px; margin: 0 auto; background-color: #1f2833; border-radius: 12px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);'>
                <!-- Header -->
                <div style='background-color: #000000; padding: 40px 20px; text-align: center; border-bottom: 2px solid #c5a880;'>
                    <h1 style='color: #c5a880; margin: 0; font-family: \"Times New Roman\", Times, serif; font-size: 32px; letter-spacing: 4px; text-transform: uppercase;'>Restaurantly</h1>
                    <p style='color: #888; margin: 10px 0 0; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;'>Fine Dining Experience</p>
                </div>
                
                <div style='padding: 40px 30px; background-color: #1f2833; font-family: Arial, sans-serif;'>
                    <h2 style='color: #ff4d4d; margin-top: 0; font-size: 24px; font-weight: 300;'>Kính chào <strong style='color: #ff4d4d;'>$name</strong>,</h2>
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Chúng tôi vô cùng xin lỗi vì sự bất tiện này, nhưng do sự cố khách quan vượt ngoài mong muốn, chúng tôi buộc phải <strong style='color: #ff4d4d;'>hủy lịch đặt bàn</strong> của quý khách.</p>
                    
                    <!-- Details Card -->
                    <div style='margin: 35px 0; background: #242f3b; border-radius: 8px; padding: 30px; border-left: 4px solid #ff4d4d; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2);'>
                        <h3 style='margin: 0 0 20px 0; color: #ff4d4d; font-family: \"Times New Roman\", Times, serif; font-size: 20px; letter-spacing: 1px;'>Thông Tin Đặt Bàn Đã Hủy (#{$booking_info['id']})</h3>
                        
                        <table style='width: 100%; border-collapse: collapse; font-size: 15px;'>
                            <tr>
                                <td style='padding: 12px 0; color: #888; width: 40%; border-bottom: 1px solid rgba(255,255,255,0.05);'>Loại dịch vụ:</td>
                                <td style='padding: 12px 0; color: #ffffff; font-weight: 500; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>$svc</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888; border-bottom: 1px solid rgba(255,255,255,0.05);'>Thời gian:</td>
                                <td style='padding: 12px 0; color: #c5a880; font-weight: bold; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>$timeStr</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888; border-bottom: 1px solid rgba(255,255,255,0.05);'>Số khách:</td>
                                <td style='padding: 12px 0; color: #ffffff; font-weight: 500; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>{$booking_info['guests']} người</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888; border-bottom: 1px solid rgba(255,255,255,0.05);'>Tổng dự kiến:</td>
                                <td style='padding: 12px 0; color: #ffffff; font-weight: 500; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);'>$money VNĐ</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888;'>Tiền cọc (đã thanh toán):</td>
                                <td style='padding: 12px 0; color: #ff4d4d; font-weight: bold; text-align: right;'>$deposit VNĐ</td>
                            </tr>
                        </table>
                        $foodsHtml
                    </div>
                    
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Nếu quý khách đã tiến hành đặt cọc trực tuyến, hệ thống sẽ tự động đối soát và nhà hàng sẽ liên hệ với quý khách để tiến hành <strong style='color: #c5a880;'>hoàn tiền 100%</strong> trong vòng 24h.</p>
                    
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Quý khách vui lòng liên hệ ngay với quản lý nhà hàng qua Hotline: <strong style='color: #c5a880;'>0123 456 789</strong> để được hỗ trợ giải quyết nhanh chóng nhất hoặc đặt lại lịch mới với ưu đãi đền bù.</p>
                    
                    <p style='color: #ffffff; line-height: 1.8; font-size: 16px; margin-top: 30px; font-style: italic; font-family: \"Times New Roman\", Times, serif;'>Một lần nữa xin chân thành cáo lỗi cùng quý khách!<br>Ban Quản Trị Restaurantly</p>
                </div>
                
                <!-- Footer -->
                <div style='background-color: #11151c; padding: 25px 20px; text-align: center; border-top: 1px solid #2a3644;'>
                    <p style='color: #666; margin: 0; font-size: 12px;'>&copy; " . date('Y') . " Restaurantly. All rights reserved.</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendVipRegistrationEmail($emailNguoiNhan, $name, $plan_name, $price, $end_date, $txn_id = 'N/A', $payment_method = 'N/A') {
    if (empty($emailNguoiNhan)) return false;
    
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? $_SERVER['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
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
                            <tr><td style='padding: 8px 0; color: #666; width: 40%;'>Mã Giao Dịch:</td><td style='padding: 8px 0; font-weight: bold;'>$txn_id</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Gói Hội Viên:</td><td style='padding: 8px 0; font-weight: bold;'>$plan_name</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Giá:</td><td style='padding: 8px 0; font-weight: bold;'>$priceStr VNĐ</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Phương Thức:</td><td style='padding: 8px 0; font-weight: bold;'>$payment_method</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Hiệu lực đến:</td><td style='padding: 8px 0; font-weight: bold;'>$end_date</td></tr>
                        </table>
                    </div>
                    
                    <div style='margin-top: 25px;'>
                        <h3 style='color: #A88746; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px;'>Lợi Ích Đặc Quyền VIP của quý khách:</h3>
                        <ul style='color: #555; line-height: 1.8; padding-left: 20px; font-size: 14px;'>
                            <li>Giảm giá trực tiếp <strong>10%</strong> cho mọi hóa đơn thanh toán.</li>
                            <li><strong>Ưu tiên đặt bàn</strong>, cam kết có vị trí đẹp nhất (kể cả Lễ, Tết).</li>
                            <li>Mở khóa dịch vụ cao cấp: <strong>Đầu bếp tại gia</strong> &amp; <strong>Thiết kế tiệc riêng</strong>.</li>
                            <li>Nhận huy hiệu <strong>VIP Crown</strong> trên hồ sơ tài khoản.</li>
                        </ul>
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


/**
 * Gửi Email Cảm ơn sau khi Hoàn thành (Completed)
 */
function sendBookingCompleteEmail($emailNguoiNhan, $booking_info) {
    if (empty($emailNguoiNhan)) return false;
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? ''; 
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? $_SERVER['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? 'noreply@restaurantly.com', 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        $mail->isHTML(true);
        $mail->Subject = 'Cảm Ơn Quý Khách Đã Trải Nghiệm - Restaurantly';
        
        $svc = htmlspecialchars($booking_info['service_type'] ?? 'Dịch vụ', ENT_QUOTES);
        if ($svc === 'table') $svc = 'Đặt bàn tiêu chuẩn';
        if ($svc === 'birthday') $svc = 'Tiệc kỷ niệm / Phòng VIP';
        if ($svc === 'chef') $svc = 'Đầu bếp tại gia';
        if ($svc === 'bespoke') $svc = 'Thiết kế riêng';

        $timeStr = date('d/m/Y', strtotime($booking_info['booking_date']));
        $name = htmlspecialchars($booking_info['customer_name'] ?? 'Quý khách', ENT_QUOTES);

        $mail->Body = "
        <div style='background-color: #0b0c10; padding: 40px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; color: #e0e0e0;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #1f2833; border-radius: 12px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);'>
                <!-- Header -->
                <div style='background-color: #000000; padding: 40px 20px; text-align: center; border-bottom: 2px solid #c5a880;'>
                    <h1 style='color: #c5a880; margin: 0; font-family: \"Times New Roman\", Times, serif; font-size: 32px; letter-spacing: 4px; text-transform: uppercase;'>Restaurantly</h1>
                    <p style='color: #888; margin: 10px 0 0; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;'>Fine Dining Experience</p>
                </div>
                
                <!-- Body -->
                <div style='padding: 40px 30px;'>
                    <h2 style='color: #ffffff; margin-top: 0; font-weight: 300; font-size: 24px;'>Kính chào $name,</h2>
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Đại diện nhà hàng Restaurantly, chúng tôi xin gửi lời cảm ơn chân thành nhất vì quý khách đã tin tưởng và lựa chọn dịch vụ <strong>$svc</strong> của chúng tôi vào ngày <strong>$timeStr</strong>.</p>
                    
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Hy vọng quý khách đã có một trải nghiệm ẩm thực tuyệt vời và những khoảnh khắc đáng nhớ. Sự hài lòng của quý khách là niềm tự hào và động lực to lớn để Restaurantly không ngừng hoàn thiện mỗi ngày.</p>
                    
                    <div style='margin: 35px 0; background: #242f3b; border-radius: 8px; padding: 25px; border-left: 4px solid #c5a880; text-align: center;'>
                        <h3 style='margin: 0 0 15px 0; color: #c5a880; font-family: \"Times New Roman\", Times, serif; font-size: 18px;'>Đánh Giá Trải Nghiệm</h3>
                        <p style='color: #b0b0b0; font-size: 14px; margin-bottom: 20px;'>Xin vui lòng dành vài giây để chia sẻ cảm nhận của quý khách, giúp chúng tôi phục vụ tốt hơn trong tương lai.</p>
                        <a href='https://restaurantly.com/feedback' style='display: inline-block; padding: 12px 30px; background-color: #c5a880; color: #000000; text-decoration: none; font-weight: bold; border-radius: 4px; text-transform: uppercase; font-size: 13px; letter-spacing: 1px;'>Gửi Đánh Giá Ngay</a>
                    </div>
                    
                    <p style='color: #b0b0b0; line-height: 1.8; font-size: 15px;'>Rất mong được tiếp tục vinh hạnh phục vụ quý khách trong thời gian sớm nhất!</p>
                    
                    <p style='color: #ffffff; line-height: 1.8; font-size: 16px; margin-top: 30px; font-style: italic; font-family: \"Times New Roman\", Times, serif;'>Trân trọng,<br>Ban Quản Trị Restaurantly</p>
                </div>
                
                <!-- Footer -->
                <div style='background-color: #11151c; padding: 25px 20px; text-align: center; border-top: 1px solid #2a3644;'>
                    <p style='color: #666; margin: 0; font-size: 12px;'>&copy; " . date('Y') . " Restaurantly. All rights reserved.</p>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

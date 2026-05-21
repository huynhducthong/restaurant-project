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

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    // Fallback: một số môi trường chặn allow_url_fopen hoặc https stream.
    if ($result === false && function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($resp !== false) && ($httpCode >= 200 && $httpCode < 300);
    }

    return $result !== false;
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

    // 3. Query Sắp hết hạn (Lấy từ bảng Lô hàng để chính xác nhất)
    $stmt_exp = $db->query("
        SELECT i.item_name, MIN(b.expiry_date) as earliest_exp, i.unit_name 
        FROM inventory_batches b
        JOIN inventory i ON b.ingredient_id = i.id
        WHERE i.is_active = 1 
          AND b.quantity > 0 
          AND b.expiry_date IS NOT NULL 
          AND b.expiry_date <= '$warn_date' 
          AND b.expiry_date >= CURDATE()
        GROUP BY i.id
    ");
    $exp_items = $stmt_exp->fetchAll(PDO::FETCH_ASSOC);

    if (empty($low_items) && empty($exp_items)) return null;

    $msg = "<b>☀️ BÁO CÁO KHO BUỔI SÁNG - " . date('d/m/Y') . "</b>\n\n";

    if (!empty($low_items)) {
        $msg .= "⚠️ <b>CẦN NHẬP HÀNG (" . count($low_items) . "):</b>\n";
        foreach ($low_items as $item) {
            $msg .= "- " . $item['item_name'] . ": " . (float)$item['total_stock'] . " " . $item['unit_name'] . " (Min: " . (float)$item['min_stock'] . ")\n";
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
        SELECT IFNULL(SUM(total_amount), 0) FROM service_bookings
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

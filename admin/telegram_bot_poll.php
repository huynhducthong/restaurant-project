<?php
/**
 * File: admin/telegram_bot_poll.php
 * Chức năng: Chạy nền để nhận lệnh từ Telegram (Long Polling)
 * Cách dùng: Chạy bằng lệnh 'php admin/telegram_bot_poll.php' trong Terminal
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/notification_helper.php';

$db = (new Database())->getConnection();

// Lấy token từ .env hoặc DB
$token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
if (!$token) {
    $settings = $db->query("SELECT key_value FROM settings WHERE key_name = 'telegram_bot_token'")->fetchColumn();
    $token = $settings ?: '';
}

if (!$token) {
    die("Lỗi: Chưa cấu hình TELEGRAM_BOT_TOKEN.\n");
}

echo "--- TELEGRAM BOT IS RUNNING (Press Ctrl+C to stop) ---\n";

$offset = 0;
$apiUrl = "https://api.telegram.org/bot$token/";

while (true) {
    $url = $apiUrl . "getUpdates?offset=$offset&timeout=30";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        echo "Lỗi kết nối API Telegram. Đang thử lại...\n";
        sleep(5);
        continue;
    }

    $updates = json_decode($response, true);

    if (isset($updates['result']) && !empty($updates['result'])) {
        foreach ($updates['result'] as $update) {
            $offset = $update['update_id'] + 1;

            if (isset($update['message'])) {
                $chatId = $update['message']['chat']['id'];
                $text = $update['message']['text'] ?? '';
                $username = $update['message']['from']['first_name'] ?? 'Admin';

                echo "Nhận lệnh: '$text' từ Chat ID: $chatId\n";

                $reply = "";

                switch (strtolower($text)) {
                    case '/start':
                        $reply = "Chào <b>$username</b>! Tôi là trợ lý ảo Restaurantly.\n\n" .
                                 "Dưới đây là các lệnh bạn có thể dùng:\n" .
                                 "🔹 /kho - Xem tổng quan tồn kho\n" .
                                 "🔹 /het_han - Xem danh sách sắp hết hạn\n" .
                                 "🔹 /ton_thap - Xem nguyên liệu sắp hết\n" .
                                 "🔹 /cuoi_ngay - Báo cáo doanh thu cuối ngày (hôm nay)\n" .
                                 "🔹 /id - Lấy Chat ID của bạn";
                        break;

                    case '/id':
                        $reply = "Chat ID của bạn là: <code>$chatId</code>\n(Hãy copy dán vào file .env hoặc Cài đặt hệ thống)";
                        break;

                    case '/kho':
                        $res = $db->query("SELECT COUNT(*) as total_items, SUM(q.qty) as total_qty FROM inventory i JOIN (SELECT ingredient_id, SUM(quantity) as qty FROM inventory_stocks GROUP BY ingredient_id) q ON i.id = q.ingredient_id WHERE i.is_active = 1")->fetch();
                        $reply = "📊 <b>TỔNG QUAN TỒN KHO</b>\n\n";
                        $reply .= "- Số loại nguyên liệu: " . $res['total_items'] . "\n";
                        $reply .= "- Tổng khối lượng/đơn vị: " . number_format($res['total_qty'], 2) . "\n";
                        $reply .= "👉 Gõ /ton_thap để xem chi tiết các món sắp hết.";
                        break;

                    case '/ton_thap':
                        $report = generateMorningReport(); // Tận dụng hàm cũ
                        if ($report) {
                            $reply = $report;
                        } else {
                            $reply = "✅ Hiện tại không có mặt hàng nào dưới mức tồn an toàn.";
                        }
                        break;

                    case '/het_han':
                        // Logic lấy hàng hết hạn
                        $settings = $db->query("SELECT key_value FROM settings WHERE key_name = 'inv_expiry_days'")->fetchColumn();
                        $days = (int)($settings ?: 7);
                        $warn_date = date('Y-m-d', strtotime("+$days days"));
                        
                        $stmt = $db->query("
                            SELECT i.item_name, MIN(b.expiry_date) as earliest_exp
                            FROM inventory_batches b
                            JOIN inventory i ON b.ingredient_id = i.id
                            WHERE i.is_active = 1 AND b.quantity > 0 AND b.expiry_date IS NOT NULL 
                              AND b.expiry_date <= '$warn_date' AND b.expiry_date >= CURDATE()
                            GROUP BY i.id
                        ");
                        $items = $stmt->fetchAll();
                        
                        if ($items) {
                            $reply = "⏰ <b>DANH SÁCH SẮP HẾT HẠN ($days ngày tới)</b>\n\n";
                            foreach ($items as $it) {
                                $reply .= "- " . $it['item_name'] . " (HSD: " . date('d/m', strtotime($it['earliest_exp'])) . ")\n";
                            }
                        } else {
                            $reply = "✅ Không có mặt hàng nào sắp hết hạn trong $days ngày tới.";
                        }
                        break;

                    case '/cuoi_ngay':
                        $reply = generateEndOfDayRevenueReport($db);
                        break;

                    default:
                        $reply = "Xin lỗi, tôi không hiểu lệnh này. Gõ /start để xem danh sách lệnh.";
                        break;
                }

                // Gửi phản hồi
                if ($reply) {
                    $sendUrl = $apiUrl . "sendMessage";
                    $data = [
                        'chat_id' => $chatId,
                        'text' => $reply,
                        'parse_mode' => 'HTML'
                    ];
                    
                    $opts = [
                        'http' => [
                            'method'  => 'POST',
                            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                            'content' => http_build_query($data),
                        ],
                    ];
                    $ctx = stream_context_create($opts);
                    @file_get_contents($sendUrl, false, $ctx);
                }
            }
        }
    }

    // Nghỉ 1 giây để tránh tốn tài nguyên
    sleep(1);
}

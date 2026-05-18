<?php
/**
 * Gửi báo cáo doanh thu cuối ngày qua Telegram (một lần mỗi ngày).
 * Hẹn giờ Task Scheduler (Windows) hoặc cron (Linux), ví dụ 22:00:
 *
 *   php C:\xampp\htdocs\restaurant-project\admin\cron_telegram_eod.php
 *
 * Nếu admin đã mở Dashboard sau giờ cấu hình, báo cáo có thể đã gửi — script sẽ thoát an toàn.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/notification_helper.php';

$db = (new Database())->getConnection();
$today = date('Y-m-d');

$last = $db->query("SELECT key_value FROM settings WHERE key_name = 'last_telegram_eod_date'")->fetchColumn();
if ($last === $today) {
    fwrite(STDOUT, "Báo cáo cuối ngày đã gửi hôm nay (last_telegram_eod_date).\n");
    exit(0);
}

$enabled = ($db->query("SELECT key_value FROM settings WHERE key_name = 'telegram_eod_enabled'")->fetchColumn() ?? '1') === '1';
if (!$enabled) {
    fwrite(STDOUT, "Báo cáo cuối ngày đang tắt (telegram_eod_enabled).\n");
    exit(0);
}

$msg = generateEndOfDayRevenueReport($db, $today);
if (sendTelegramNotification($msg)) {
    $db->prepare(
        "INSERT INTO settings (key_name, key_value) VALUES ('last_telegram_eod_date', ?)
         ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)"
    )->execute([$today]);
    fwrite(STDOUT, "Đã gửi Telegram OK.\n");
    exit(0);
}

fwrite(STDERR, "Gửi Telegram thất bại (kiểm tra token, chat ID, ENABLE_TELEGRAM).\n");
exit(1);

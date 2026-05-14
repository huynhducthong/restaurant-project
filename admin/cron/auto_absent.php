<?php
// BƯỚC 12: Kịch bản tự động đánh dấu vắng mặt (Chạy tự động bằng Cron Job vào cuối ngày)
require_once __DIR__ . '/../../config/database.php';

try {
    $db = (new Database())->getConnection();

    // Cập nhật trạng thái 'absent' cho các ca làm việc thõa mãn:
    // 1. Trạng thái hiện tại vẫn là 'scheduled' (đã phân công nhưng chưa check-in)
    // 2. Thời điểm hiện tại đã vượt quá thời gian kết thúc của ca làm việc đó
    $query = "UPDATE shift_assignments sa
              JOIN shifts s ON sa.shift_id = s.id
              SET sa.status = 'absent'
              WHERE sa.status = 'scheduled' 
              AND sa.check_in IS NULL
              AND TIMESTAMP(sa.work_date, s.end_time) < NOW()";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $absent_count = $stmt->rowCount();

    // 2. Tự động xử lý các ca đã check-in nhưng quên check-out sau khi ca đã kết thúc quá 4 tiếng
    $query_forgot_checkout = "
        UPDATE shift_assignments sa
        JOIN shifts s ON sa.shift_id = s.id
        SET sa.check_out = TIMESTAMP(sa.work_date, s.end_time),
            sa.approval_status = 'pending' -- Đẩy về cho Admin chú ý duyệt lại
        WHERE sa.status = 'present' 
        AND sa.check_in IS NOT NULL 
        AND sa.check_out IS NULL
        AND TIMESTAMPADD(HOUR, 4, TIMESTAMP(sa.work_date, s.end_time)) < NOW()";
    $stmt_forgot = $db->prepare($query_forgot_checkout);
    $stmt_forgot->execute();
    $forgot_count = $stmt_forgot->rowCount();

    $affected_rows = $absent_count + $forgot_count;

    // Log kết quả lại (Có thể lưu vào file text hoặc DB thay vì in ra màn hình)
    $log_message = "[" . date('Y-m-d H:i:s') . "] CRON RUN: Đã xử lý $absent_count ca vắng mặt và $forgot_count ca quên check-out.\n";
    file_put_contents(__DIR__ . '/cron_logs.txt', $log_message, FILE_APPEND);

    echo $log_message;

} catch (Exception $e) {
    $error_message = "[" . date('Y-m-d H:i:s') . "] CRON ERROR: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/cron_logs.txt', $error_message, FILE_APPEND);
    echo $error_message;
}
?>
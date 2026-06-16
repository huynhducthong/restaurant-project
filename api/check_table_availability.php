<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$datetime = $_GET['datetime'] ?? '';
$service_type = $_GET['type'] ?? 'table';

if (empty($datetime)) {
    echo json_encode(['error' => 'Datetime is required', 'unavailable_tables' => []]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // 1. Tìm các bàn đang có khách đặt Online xung quanh 2.5 tiếng (150 phút)
    $stmt = $db->prepare("
        SELECT table_id 
        FROM service_bookings 
        WHERE table_id IS NOT NULL 
          AND LOWER(status) IN ('pending', 'confirmed')
          AND ABS(TIMESTAMPDIFF(MINUTE, booking_date, ?)) < 150
    ");
    $stmt->execute([$datetime]);
    $unavailable_online = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Tìm các bàn đang có khách ngồi ăn tại quán (Luồng POS)
    // Nếu giờ đặt bàn nằm trong khoảng từ quá khứ 1 tiếng đến tương lai 2 tiếng so với HIỆN TẠI
    $booking_timestamp = strtotime($datetime);
    $time_diff_from_now = $booking_timestamp - time();
    $two_hours = 7200;
    
    $unavailable_pos = [];
    if ($time_diff_from_now > -3600 && $time_diff_from_now < $two_hours) {
        $pos_stmt = $db->query("SELECT table_id FROM pos_orders WHERE status = 'open'");
        $unavailable_pos = $pos_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    $unavailable = array_merge($unavailable_online, $unavailable_pos);
    
    echo json_encode([
        'success' => true,
        'unavailable_tables' => array_map('intval', array_unique($unavailable))
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

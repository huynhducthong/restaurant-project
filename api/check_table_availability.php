<?php
session_start();
header('Content-Type: application/json');
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
    
    // Tìm các bàn đang có người đặt xung quanh 2.5 tiếng (150 phút) của thời điểm được chọn
    // Trạng thái 'pending', 'confirmed' sẽ được coi là chiếm dụng bàn
    $stmt = $db->prepare("
        SELECT table_id 
        FROM service_bookings 
        WHERE table_id IS NOT NULL 
          AND status IN ('pending', 'confirmed')
          AND ABS(TIMESTAMPDIFF(MINUTE, booking_date, ?)) < 150
    ");
    $stmt->execute([$datetime]);
    $unavailable = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'unavailable_tables' => array_map('intval', array_unique($unavailable))
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$date = $_GET['date'] ?? date('Y-m-d');
$time = $_GET['time'] ?? date('H:i');

$booking_datetime = $date . ' ' . $time;
$booking_timestamp = strtotime($booking_datetime);
$two_hours = 2 * 3600;

$response = [];

// Get all tables
$tables = $db->query("SELECT id FROM restaurant_tables")->fetchAll(PDO::FETCH_ASSOC);

foreach ($tables as $t) {
    $table_id = $t['id'];
    $is_booked = false;

    // 1. Check upcoming service bookings
    $stmt = $db->prepare("
        SELECT id FROM service_bookings 
        WHERE table_id = ? 
        AND status IN ('Pending', 'Confirmed') 
        AND ABS(TIMESTAMPDIFF(SECOND, booking_date, ?)) < ?
    ");
    $stmt->execute([$table_id, $booking_datetime, $two_hours]);
    if ($stmt->fetch()) {
        $is_booked = true;
    }

    // 2. Check POS walk-in
    if (!$is_booked) {
        $time_diff = $booking_timestamp - time();
        if ($time_diff > -3600 && $time_diff < $two_hours) {
            $pos_stmt = $db->prepare("SELECT id FROM pos_orders WHERE table_id = ? AND status = 'open'");
            $pos_stmt->execute([$table_id]);
            if ($pos_stmt->fetch()) {
                $is_booked = true;
            }
        }
    }

    $response[$table_id] = $is_booked ? 'booked' : 'available';
}

header('Content-Type: application/json');
echo json_encode($response);

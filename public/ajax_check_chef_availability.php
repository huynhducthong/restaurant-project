<?php
require_once __DIR__ . "/../config/database.php";
header("Content-Type: application/json");
$chef_id = isset($_POST["chef_id"]) ? (int)$_POST["chef_id"] : 0;
$booking_date = isset($_POST["booking_date"]) ? $_POST["booking_date"] : "";
if ($chef_id <= 0 || empty($booking_date)) {
    echo json_encode(["status" => "success", "available" => true]);
    exit;
}
$db = (new Database())->getConnection();
$date_formatted = date("Y-m-d H:i:s", strtotime($booking_date));
$four_hours = 4 * 3600;
$check_chef_stmt = $db->prepare("
    SELECT id FROM service_bookings 
    WHERE chef_id = ? 
    AND status IN ('Pending', 'Confirmed') 
    AND ABS(TIMESTAMPDIFF(SECOND, booking_date, ?)) < ?
");
$check_chef_stmt->execute([$chef_id, $date_formatted, $four_hours]);
if ($check_chef_stmt->rowCount() > 0) {
    echo json_encode(["status" => "success", "available" => false, "message" => "Bếp trưởng đã có lịch trình vào khung giờ này. Vui lòng chọn thời gian khác cách ít nhất 4 tiếng, hoặc chọn Đầu bếp khác!"]);
} else {
    echo json_encode(["status" => "success", "available" => true]);
}

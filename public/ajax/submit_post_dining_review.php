<?php
session_start();
require_once "../../config/database.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Vui lòng dang nh?p."]);
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$booking_id = isset($_POST["booking_id"]) ? (int)$_POST["booking_id"] : 0;
$chef_id = isset($_POST["chef_id"]) && $_POST["chef_id"] !== "null" ? (int)$_POST["chef_id"] : 0;
$rating = isset($_POST["rating"]) ? (int)$_POST["rating"] : 5;
$comment = isset($_POST["comment"]) ? trim($_POST["comment"]) : "";

if (!$booking_id || !$comment) {
    echo json_encode(["success" => false, "message" => "Vui lòng di?n d?y d? nh?n xét."]);
    exit;
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id, status, booking_date, is_reviewed, service_type FROM service_bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(["success" => false, "message" => "Không tìm th?y don hàng h?p l?."]);
        $db->rollBack();
        exit;
    }

    if ($booking["status"] !== "Completed" && strtotime($booking["booking_date"] ?? "now") >= time()) {
        echo json_encode(["success" => false, "message" => "B?a an chua hoàn thành, không th? dánh giá."]);
        $db->rollBack();
        exit;
    }

    if ($booking["is_reviewed"]) {
        echo json_encode(["success" => false, "message" => "B?n dã dánh giá don hàng này r?i."]);
        $db->rollBack();
        exit;
    }
    
    $author_name = $_SESSION["full_name"] ?? ($_SESSION["username"] ?? "Khách hàng");
    $exp_type = ($booking["service_type"] == "chef" || $booking["service_type"] == "bespoke_chef") ? "Ð?u b?p t?i gia" : "Dùng b?a t?i nhà hàng";
    
    $stmt_insert = $db->prepare("INSERT INTO chef_reviews (chef_id, user_id, author_name, rating, comment, booking_id, experience_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt_insert->execute([
        $chef_id > 0 ? $chef_id : 0, 
        $user_id, 
        $author_name, 
        $rating, 
        $comment, 
        $booking_id,
        $exp_type
    ]);

    $stmt_upd = $db->prepare("UPDATE service_bookings SET is_reviewed = 1 WHERE id = ?");
    $stmt_upd->execute([$booking_id]);

    $db->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "L?i h? th?ng: " . $e->getMessage()]);
}
?>

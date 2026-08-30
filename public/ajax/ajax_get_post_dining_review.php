<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Please login"]);
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$booking_id = isset($_GET["booking_id"]) ? (int)$_GET["booking_id"] : 0;

if (!$booking_id) {
    echo json_encode(["success" => false, "message" => "No booking ID"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    $stmt = $db->prepare("SELECT id, rating, comment, images FROM chef_reviews WHERE booking_id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($review) {
        $review["images"] = $review["images"] ? json_decode($review["images"], true) : [];
        echo json_encode(["success" => true, "review" => $review]);
    } else {
        echo json_encode(["success" => false, "message" => "No review found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "System error: " . $e->getMessage()]);
}
?>
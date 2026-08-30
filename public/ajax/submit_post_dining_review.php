<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Vui lòng đăng nhập."]);
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$booking_id = isset($_POST["booking_id"]) ? (int)$_POST["booking_id"] : 0;
$chef_id = isset($_POST["chef_id"]) && $_POST["chef_id"] !== "null" ? (int)$_POST["chef_id"] : 0;
$rating = isset($_POST["rating"]) ? (int)$_POST["rating"] : 5;
$comment = isset($_POST["comment"]) ? trim($_POST["comment"]) : "";

if (!$booking_id || !$comment) {
    echo json_encode(["success" => false, "message" => "Vui lòng điền đầy đủ nhận xét."]);
    exit;
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id, status, booking_date, is_reviewed, service_type FROM service_bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(["success" => false, "message" => "Không tìm thấy đơn hàng hợp lệ."]);
        $db->rollBack();
        exit;
    }

    if ($booking["status"] !== "Completed" && strtotime($booking["booking_date"] ?? "now") >= time()) {
        echo json_encode(["success" => false, "message" => "Bữa ăn chưa hoàn thành, không thể đánh giá."]);
        $db->rollBack();
        exit;
    }

    // If already reviewed, we will update the existing review
    $existing_review = null;
    if ($booking["is_reviewed"]) {
        $stmt_rev = $db->prepare("SELECT id, images FROM chef_reviews WHERE booking_id = ? AND user_id = ?");
        $stmt_rev->execute([$booking_id, $user_id]);
        $existing_review = $stmt_rev->fetch(PDO::FETCH_ASSOC);
    }
    
    // Upload ảnh
    $uploaded_images = [];
    $has_new_images = false;
    if (isset($_FILES["images"]) && !empty($_FILES["images"]["name"][0])) {
        $has_new_images = true;
        $upload_dir = __DIR__ . "/../../uploads/reviews/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_count = count($_FILES["images"]["name"]);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES["images"]["error"][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES["images"]["tmp_name"][$i];
                $name = basename($_FILES["images"]["name"][$i]);
                $new_name = time() . "_" . uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $name);
                $destination = $upload_dir . $new_name;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $uploaded_images[] = "uploads/reviews/" . $new_name;
                }
            }
        }
    }
    
    // If updating and no new images were uploaded, keep the old ones.
    if ($existing_review && !$has_new_images) {
        $images_json = $existing_review["images"];
    } else {
        $images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;
    }
    
    $author_name = $_SESSION["full_name"] ?? ($_SESSION["username"] ?? "Khách hàng");
    $exp_type = ($booking["service_type"] == "chef" || $booking["service_type"] == "bespoke_chef") ? "Đầu bếp tại gia" : "Dùng bữa tại nhà hàng";
    
    if ($existing_review) {
        // Update
        $stmt_update = $db->prepare("UPDATE chef_reviews SET rating = ?, comment = ?, images = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt_update->execute([$rating, $comment, $images_json, $existing_review['id']]);
    } else {
        // Insert
        $stmt_insert = $db->prepare("INSERT INTO chef_reviews (chef_id, user_id, author_name, rating, comment, booking_id, experience_type, status, images) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt_insert->execute([
            $chef_id > 0 ? $chef_id : 0, 
            $user_id, 
            $author_name, 
            $rating, 
            $comment, 
            $booking_id,
            $exp_type,
            $images_json
        ]);
        
        $stmt_upd = $db->prepare("UPDATE service_bookings SET is_reviewed = 1 WHERE id = ?");
        $stmt_upd->execute([$booking_id]);
    }

    $db->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>

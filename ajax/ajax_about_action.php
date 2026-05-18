<?php
session_start();
include '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';
$content_id = $_POST['content_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập']);
    exit;
}

if ($action === 'save') {
    // Check if already saved
    $check = $conn->prepare("SELECT id FROM about_saved_posts WHERE user_id = ? AND post_id = ?");
    $check->execute([$user_id, $content_id]);
    $saved = $check->fetch();

    if ($saved) {
        $conn->prepare("DELETE FROM about_saved_posts WHERE user_id = ? AND post_id = ?")->execute([$user_id, $content_id]);
        echo json_encode(['status' => 'success', 'saved' => false]);
    } else {
        $conn->prepare("INSERT INTO about_saved_posts (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $content_id]);
        echo json_encode(['status' => 'success', 'saved' => true]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ']);
}
?>

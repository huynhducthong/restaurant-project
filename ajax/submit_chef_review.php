<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

$chef_id = (int)($_POST['chef_id'] ?? 0);
$rating  = (int)($_POST['rating'] ?? 0);
$comment = mb_substr(strip_tags(trim($_POST['comment'] ?? '')), 0, 1000);
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (!$chef_id || $rating < 1 || $rating > 5 || empty($comment)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng cung cấp đầy đủ thông tin và đánh giá từ 1 đến 5 sao.']);
    exit;
}

$db = (new Database())->getConnection();

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
$author_name = 'Ẩn danh';

if ($user_id) {
    $u_stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
    $u_stmt->execute([$user_id]);
    $u_name = $u_stmt->fetchColumn();
    if ($u_name) {
        $author_name = $u_name;
    }
} else {
    // If not logged in, allow name input or default to "Khách ẩn danh"
    $custom_name = trim($_POST['author_name'] ?? '');
    if (!empty($custom_name)) {
        $author_name = mb_substr(strip_tags($custom_name), 0, 100);
    } else {
        $author_name = 'Khách ẩn danh';
    }
}

// Simple word filter
$bad_words = ['đụ','địt','lồn','cặc','đéo','vãi','buồi','đĩ','fuck','shit','bitch','asshole','dmm','dm','vl'];
$cl = mb_strtolower($comment);
foreach ($bad_words as $w) {
    if (mb_strpos($cl, $w) !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Bình luận chứa ngôn từ không phù hợp!']);
        exit;
    }
}

$experience_type = mb_substr(strip_tags(trim($_POST['experience_type'] ?? 'Fine Dining')), 0, 100);

// Insert review
$stmt = $db->prepare("INSERT INTO chef_reviews (chef_id, user_id, author_name, rating, comment, experience_type, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
$stmt->execute([$chef_id, $user_id, $author_name, $rating, $comment, $experience_type]);
$new_id = $db->lastInsertId();

// Retrieve user avatar if logged in
$user_avatar = null;
if ($user_id) {
    $av_stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $av_stmt->execute([$user_id]);
    $user_avatar = $av_stmt->fetchColumn();
}

// Get updated stats for the chef
$stats_stmt = $db->prepare("SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM chef_reviews WHERE chef_id = ? AND status = 'approved'");
$stats_stmt->execute([$chef_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
$review_count = (int)$stats['count'];

echo json_encode([
    'status'  => 'success',
    'message' => 'Đánh giá của bạn đã được gửi thành công!',
    'avg_rating' => $avg_rating,
    'review_count' => $review_count,
    'review' => [
        'id'               => $new_id,
        'author_name'      => $author_name,
        'rating'           => $rating,
        'comment'          => htmlspecialchars($comment),
        'experience_type'  => htmlspecialchars($experience_type),
        'user_avatar'      => $user_avatar,
        'created_at'       => date('d/m/Y H:i')
    ]
]);

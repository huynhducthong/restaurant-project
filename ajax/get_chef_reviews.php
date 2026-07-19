<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$chef_id = (int)($_GET['chef_id'] ?? 0);

if (!$chef_id) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID đầu bếp.']);
    exit;
}

$db = (new Database())->getConnection();

// Fetch reviews
$stmt = $db->prepare("
    SELECT r.id, r.author_name, r.rating, r.comment, r.created_at, r.experience_type, r.chef_response, u.avatar as user_avatar, u.id as user_id, (u.avatar_blob IS NOT NULL) as has_avatar_blob
    FROM chef_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.chef_id = ? AND r.status = 'approved'
    ORDER BY r.rating DESC, r.created_at DESC
");
$stmt->execute([$chef_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format creation date
foreach ($reviews as &$r) {
    $r['created_at'] = date('d/m/Y H:i', strtotime($r['created_at']));
}
unset($r);

// Fetch stats
$stats_stmt = $db->prepare("SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM chef_reviews WHERE chef_id = ? AND status = 'approved'");
$stats_stmt->execute([$chef_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
$review_count = (int)$stats['count'];

echo json_encode([
    'status' => 'success',
    'avg_rating' => $avg_rating,
    'review_count' => $review_count,
    'reviews' => $reviews
]);

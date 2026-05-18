<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$content_id = (int)($_GET['content_id'] ?? 0);
if (!$content_id) {
    echo json_encode(['status'=>'error','message'=>'Thiếu ID bài viết']); exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT l.*, u.full_name, u.username, u.avatar 
                      FROM about_likes l 
                      LEFT JOIN users u ON l.user_id = u.id 
                      WHERE l.content_id = ? 
                      ORDER BY l.created_at DESC");
$stmt->execute([$content_id]);
$likers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($likers as $l) {
    $name = $l['full_name'] ?: ($l['username'] ?: 'Người dùng ẩn danh');
    $result[] = [
        'name' => $name,
        'avatar' => $l['avatar'] ?: null,
        'time' => date('d/m/Y H:i', strtotime($l['created_at']))
    ];
}

echo json_encode(['status'=>'success', 'likers'=>$result]);

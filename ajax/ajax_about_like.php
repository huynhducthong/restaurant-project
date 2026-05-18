<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

if (isset($_GET['get_users'])) {
    $cid = (int)($_GET['content_id'] ?? 0);
    // Lấy danh sách người thích (ưu tiên lấy tên từ bảng users nếu có user_id)
    $stmt = $db->prepare("SELECT DISTINCT COALESCE(u.full_name, 'Người dùng ẩn danh') as full_name 
                          FROM about_likes al 
                          LEFT JOIN users u ON al.user_id = u.id 
                          WHERE al.content_id = ?");
    $stmt->execute([$cid]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
    exit;
}

$content_id = (int)($_POST['content_id'] ?? 0);
$user_ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_id    = 0;
if (isset($_SESSION['user_id'])) $user_id = $_SESSION['user_id'];

if (!$content_id) { echo json_encode(['status'=>'error']); exit; }

// Kiểm tra xem đã like chưa
$s = $db->prepare("SELECT id FROM about_likes WHERE content_id=? AND (user_ip=? OR (user_id=? AND user_id > 0))");
$s->execute([$content_id, $user_ip, $user_id]);

if ($s->fetch()) {
    $db->prepare("DELETE FROM about_likes WHERE content_id=? AND (user_ip=? OR (user_id=? AND user_id > 0))")->execute([$content_id,$user_ip, $user_id]);
    $action = 'unliked';
} else {
    $db->prepare("INSERT INTO about_likes (content_id,user_ip,user_id) VALUES (?,?,?)")->execute([$content_id,$user_ip, $user_id]);
    $action = 'liked';
}

$count = (int)$db->prepare("SELECT COUNT(*) FROM about_likes WHERE content_id=?")->execute([$content_id]) ? $db->query("SELECT COUNT(*) FROM about_likes WHERE content_id=$content_id")->fetchColumn() : 0;
echo json_encode(['status'=>'success','action'=>$action,'count'=>(int)$count]);

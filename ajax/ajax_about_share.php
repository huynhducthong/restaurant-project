<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$content_id = (int)($_POST['content_id'] ?? 0);
$platform   = $_POST['platform'] ?? 'link';
$user_ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$allowed = ['facebook','link','view'];
if (!in_array($platform, $allowed)) $platform = 'link';
if (!$content_id) { echo json_encode(['status'=>'error']); exit; }

$db = (new Database())->getConnection();
$db->prepare("INSERT INTO about_shares (content_id,platform,user_ip) VALUES (?,?,?)")
   ->execute([$content_id, $platform, $user_ip]);

$stmt = $db->prepare("SELECT COUNT(*) FROM about_shares WHERE content_id=? AND platform!='view'");
$stmt->execute([$content_id]);
echo json_encode(['status'=>'success','share_count'=>(int)$stmt->fetchColumn()]);

<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
$db = (new Database())->getConnection();
$action = $_POST['action'] ?? '';
try {
    if ($action === 'add') {
        $t = trim($_POST['title'] ?? ''); $u = trim($_POST['url'] ?? ''); $p = (int)($_POST['priority'] ?? 0);
        if (!$t || !$u) { echo json_encode(['status'=>'error','message'=>'Thiếu dữ liệu']); exit; }
        $db->prepare("INSERT INTO footer_links (title, url, priority) VALUES (?,?,?)")->execute([$t,$u,$p]);
        echo json_encode(['status'=>'success']);
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM footer_links WHERE id=?")->execute([(int)$_POST['id']]);
        echo json_encode(['status'=>'success']);
    } elseif ($action === 'update') {
        $db->prepare("UPDATE footer_links SET priority=? WHERE id=?")->execute([(int)$_POST['priority'], (int)$_POST['id']]);
        echo json_encode(['status'=>'success']);
    } elseif ($action === 'edit') {
        $t = trim($_POST['title'] ?? ''); $u = trim($_POST['url'] ?? '');
        if (!$t || !$u) { echo json_encode(['status'=>'error','message'=>'Thiếu dữ liệu']); exit; }
        $db->prepare("UPDATE footer_links SET title=?, url=? WHERE id=?")->execute([$t, $u, (int)$_POST['id']]);
        echo json_encode(['status'=>'success']);
    } else echo json_encode(['status'=>'error','message'=>'Hành động không hợp lệ']);
} catch (Exception $e) { echo json_encode(['status'=>'error','message'=>$e->getMessage()]); }
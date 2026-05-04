<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();
$action = $_POST['action'] ?? '';
if ($action == 'add') {
    $db->prepare("INSERT INTO footer_links (title, url, priority) VALUES (?, ?, ?)")->execute([$_POST['title'], $_POST['url'], $_POST['priority']]);
} elseif ($action == 'delete') {
    $db->prepare("DELETE FROM footer_links WHERE id = ?")->execute([$_POST['id']]);
}
echo json_encode(['status' => 'success']);
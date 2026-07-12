<?php
require 'config/database.php';
$db = (new Database())->getConnection();

// Lấy tất cả các dòng kho có số lượng <= 0
$stmt = $db->query("SELECT ingredient_id, warehouse_id FROM inventory_stocks WHERE quantity <= 0");
$empty_stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($empty_stocks as $s) {
    $db->prepare("UPDATE inventory_batches SET quantity = 0 WHERE ingredient_id = ? AND warehouse_id = ?")
       ->execute([$s['ingredient_id'], $s['warehouse_id']]);
}

// Cập nhật lại expiry_date cho tất cả các inventory
$stmt = $db->query("SELECT id FROM inventory");
$items = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($items as $id) {
    $min = $db->prepare("SELECT MIN(expiry_date) FROM inventory_batches WHERE ingredient_id = ? AND quantity > 0 AND expiry_date IS NOT NULL AND warehouse_id NOT IN (6, 7)");
    $min->execute([$id]);
    $hsd = $min->fetchColumn();
    $db->prepare("UPDATE inventory SET expiry_date = ? WHERE id = ?")->execute([$hsd ?: null, $id]);
}

echo "Cleaned up batches and synced expiry_dates.\n";

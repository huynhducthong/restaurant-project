<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();

// 1. Cập nhật nhiệt độ cho các lô hàng bị thiếu
$stmt = $db->query("UPDATE inventory_batches SET receiving_temperature = 'Nhiệt độ phòng (20°C)' WHERE receiving_temperature IS NULL OR receiving_temperature = ''");
echo "Updated receiving_temperature for missing batches.\n";

// 2. Tạo lô hàng cho các mặt hàng có tồn kho nhưng chưa có lô hàng
$stmt = $db->query("
    SELECT s.ingredient_id, i.item_name, s.warehouse_id, s.quantity, i.expiry_date, i.cost_price
    FROM inventory_stocks s
    JOIN inventory i ON s.ingredient_id = i.id
    WHERE s.quantity > 0 AND s.ingredient_id NOT IN (
        SELECT ingredient_id FROM inventory_batches WHERE warehouse_id = s.warehouse_id AND quantity > 0
    )
");
$missing_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($missing_batches as $item) {
    // Determine an expiry date
    $expiry = $item['expiry_date'];
    if (empty($expiry)) {
        $expiry = date('Y-m-d', strtotime('+1 year'));
    }
    
    // Generate a default batch code
    $batch_code = 'BATCH-AUTO-' . date('Ymd-His') . '-' . rand(100, 999);
    
    $ins = $db->prepare("
        INSERT INTO inventory_batches (ingredient_id, warehouse_id, batch_code, quantity, expiry_date, cost_price, receiving_temperature)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([
        $item['ingredient_id'],
        $item['warehouse_id'],
        $batch_code,
        $item['quantity'],
        $expiry,
        (float)$item['cost_price'],
        'Nhiệt độ phòng (20°C)'
    ]);
    $count++;
}

echo "Created $count missing batches for existing stocks.\n";

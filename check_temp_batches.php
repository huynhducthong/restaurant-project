<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();

// 1. Find missing temperature in inventory (if the column exists)
try {
    $stmt = $db->query("SELECT id, item_name, receiving_temperature FROM inventory WHERE receiving_temperature IS NULL OR receiving_temperature = ''");
    $missing_temp_inv = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Missing receiving_temperature in INVENTORY: " . count($missing_temp_inv) . " items\n";
} catch(Exception $e) {
    echo "INVENTORY table might not have receiving_temperature column: " . $e->getMessage() . "\n";
}

// 2. Find missing temperature in inventory_batches
try {
    $stmt = $db->query("SELECT id, ingredient_id, receiving_temperature FROM inventory_batches WHERE receiving_temperature IS NULL OR receiving_temperature = ''");
    $missing_temp_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Missing receiving_temperature in BATCHES: " . count($missing_temp_batches) . " batches\n";
} catch(Exception $e) {}

// 3. Find items that have stock but no batches
$stmt = $db->query("
    SELECT s.ingredient_id, i.item_name, s.warehouse_id, s.quantity
    FROM inventory_stocks s
    JOIN inventory i ON s.ingredient_id = i.id
    WHERE s.quantity > 0 AND s.ingredient_id NOT IN (
        SELECT ingredient_id FROM inventory_batches WHERE warehouse_id = s.warehouse_id AND quantity > 0
    )
");
$missing_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Items with stock but NO BATCHES in that warehouse:\n";
print_r($missing_batches);


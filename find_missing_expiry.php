<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();

// Check inventory table for missing expiry dates
$stmt = $db->query("SELECT id, item_name, category FROM inventory WHERE expiry_date IS NULL");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Missing expiry_date in inventory:\n";
print_r($items);

// Check inventory_batches table for missing expiry dates
$stmt_batches = $db->query("SELECT id, ingredient_id, quantity, expiry_date FROM inventory_batches WHERE expiry_date IS NULL");
$batches = $stmt_batches->fetchAll(PDO::FETCH_ASSOC);

echo "\nMissing expiry_date in inventory_batches:\n";
print_r($batches);

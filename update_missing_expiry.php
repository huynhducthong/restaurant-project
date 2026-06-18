<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();

// Add expiry date for all items that don't have it
$future_date_food = date('Y-m-d', strtotime('+1 year'));
$future_date_supplies = date('Y-m-d', strtotime('+5 years'));

// 1. Update inventory table
$db->query("UPDATE inventory SET expiry_date = '$future_date_food' WHERE expiry_date IS NULL AND category != 'Vật tư'");
$db->query("UPDATE inventory SET expiry_date = '$future_date_supplies' WHERE expiry_date IS NULL AND category = 'Vật tư'");

// 2. Update inventory_batches table
// First, find the category for each batch
$batches = $db->query("SELECT b.id, i.category FROM inventory_batches b JOIN inventory i ON b.ingredient_id = i.id WHERE b.expiry_date IS NULL")->fetchAll(PDO::FETCH_ASSOC);

foreach ($batches as $batch) {
    $date = ($batch['category'] == 'Vật tư') ? $future_date_supplies : $future_date_food;
    $db->prepare("UPDATE inventory_batches SET expiry_date = ? WHERE id = ?")->execute([$date, $batch['id']]);
}

echo "Successfully updated missing expiry dates.";

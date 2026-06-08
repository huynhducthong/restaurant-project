<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

// Check Pizza Gà Nấm
$stmt = $db->query("SELECT name, allergens FROM foods WHERE name LIKE '%Pizza Gà Nấm%'");
$foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check user profile
$stmt2 = $db->query("SELECT id, username, allergies, disliked_ingredients FROM users");
$users = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "Foods:\n";
print_r($foods);
echo "\nUsers:\n";
print_r($users);

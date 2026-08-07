<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$tables = $db->query("SELECT * FROM restaurant_tables")->fetchAll(PDO::FETCH_ASSOC);
echo "<h1>Database: " . $db->query("SELECT DATABASE()")->fetchColumn() . "</h1>";
echo "<h2>Total tables: " . count($tables) . "</h2>";
echo "<pre>";
print_r($tables);
echo "</pre>";

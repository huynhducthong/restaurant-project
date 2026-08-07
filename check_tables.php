<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$tables = $db->query("SELECT * FROM restaurant_tables WHERE category = 'open' ORDER BY id ASC LIMIT 16")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $db->query("SELECT * FROM restaurant_tables WHERE category = 'room' ORDER BY id ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
echo "<h1>Database: " . $db->query("SELECT DATABASE()")->fetchColumn() . "</h1>";
echo "<h2>Tables (open): " . count($tables) . "</h2><pre>"; print_r($tables); echo "</pre>";
echo "<h2>Rooms (room): " . count($rooms) . "</h2><pre>"; print_r($rooms); echo "</pre>";
echo "</pre>";

<?php
$start = microtime(true);
require_once __DIR__ . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();
$time_db = microtime(true) - $start;

echo "DB Connection time: " . number_format($time_db, 4) . " seconds\n";

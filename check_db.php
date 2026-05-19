<?php
require 'config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE chefs");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($columns);

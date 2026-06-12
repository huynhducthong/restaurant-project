<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT * FROM event_types");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

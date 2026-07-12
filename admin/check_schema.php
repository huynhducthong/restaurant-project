<?php
require '../config/database.php';
$db = (new Database())->getConnection();
$res = $db->query('DESCRIBE inventory_history')->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

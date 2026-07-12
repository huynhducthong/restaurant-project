<?php
require '../config/database.php';
$db = (new Database())->getConnection();
$res = $db->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

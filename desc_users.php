<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$s = $db->query('DESCRIBE users');
print_r($s->fetchAll());
?>

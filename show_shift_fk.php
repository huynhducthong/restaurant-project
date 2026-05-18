<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$s = $db->query('SHOW CREATE TABLE shift_assignments');
print_r($s->fetch());
?>

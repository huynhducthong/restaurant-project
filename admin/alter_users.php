<?php
require '../config/database.php';
$db = (new Database())->getConnection();
try {
    $db->exec('ALTER TABLE users ADD COLUMN drink_preferences VARCHAR(255) NULL');
    echo 'Success';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

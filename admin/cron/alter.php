<?php
require_once __DIR__ . '/../../config/database.php';
try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE service_bookings ADD COLUMN is_reminded TINYINT(1) DEFAULT 0");
    echo "Success";
} catch(Exception $e) {
    echo $e->getMessage();
}

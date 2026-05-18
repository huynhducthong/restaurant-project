<?php
require_once __DIR__ . '/../../../../../../config/database.php';
$db = (new Database())->getConnection();

try {
    $db->exec("ALTER TABLE about_comments ADD COLUMN is_anonymous tinyint(1) DEFAULT 0 AFTER comment");
    echo "Added is_anonymous column to about_comments.\n";
} catch (Exception $e) {
    echo "Error or column already exists: " . $e->getMessage();
}
?>

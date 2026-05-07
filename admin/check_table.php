<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SHOW TABLES LIKE 'contacts'");
if ($stmt->rowCount() > 0) {
    echo "Table 'contacts' EXISTS.\n";
    $stmt2 = $db->query("SELECT * FROM contacts");
    $rows = $stmt2->fetchAll();
    echo "Number of rows: " . count($rows) . "\n";
    print_r($rows);
} else {
    echo "Table 'contacts' DOES NOT EXIST.\n";
}

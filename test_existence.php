<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
try {
    $stmt = $db->query("SELECT * FROM employees LIMIT 1");
    echo "Table employees exists.\n";
} catch (Exception $e) {
    echo "Table employees does NOT exist: " . $e->getMessage() . "\n";
}
try {
    $stmt = $db->query("SELECT * FROM users LIMIT 1");
    echo "Table users exists.\n";
} catch (Exception $e) {
    echo "Table users does NOT exist: " . $e->getMessage() . "\n";
}
?>

<?php
$host = '127.0.0.1';
$db   = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        $pdo->exec("ALTER TABLE restaurant_tables ADD COLUMN pos_x INT DEFAULT 0");
    } catch(PDOException $e) { }
    try {
        $pdo->exec("ALTER TABLE restaurant_tables ADD COLUMN pos_y INT DEFAULT 0");
    } catch(PDOException $e) { }
    
    echo "Columns added successfully.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

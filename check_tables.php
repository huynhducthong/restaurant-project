<?php
$host = '127.0.0.1';
$db   = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, table_code, pos_x, pos_y FROM restaurant_tables LIMIT 10");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($tables);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
$host = '127.0.0.1';
$db   = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, table_code FROM restaurant_tables WHERE category = 'open' ORDER BY CAST(REPLACE(table_code, 'B', '') AS UNSIGNED)");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compress y spacing to avoid hitting bottom reception area
    $x_start = 220; 
    $y_start = 230; // Slightly higher
    $x_step = 140;
    $y_step = 120; // Reduced vertical gap
    $offset_x = 70;

    foreach ($tables as $index => $t) {
        $row = floor($index / 4);
        $col = $index % 4;
        
        $x = $x_start + ($col * $x_step);
        if ($row % 2 != 0) {
            $x += $offset_x; // staggered row
        }
        $y = $y_start + ($row * $y_step);

        $up_stmt = $pdo->prepare("UPDATE restaurant_tables SET pos_x = ?, pos_y = ? WHERE id = ?");
        $up_stmt->execute([$x, $y, $t['id']]);
    }

    echo "Standard tables compressed vertically successfully.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

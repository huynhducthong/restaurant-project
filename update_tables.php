<?php
$host = '127.0.0.1';
$db   = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Rename T1-T16 to B1-B16 in case they reverted
    $pdo->exec("UPDATE restaurant_tables SET table_code = REPLACE(table_code, 'T', 'B') WHERE category = 'open' AND table_code LIKE 'T%'");

    // 2. Update Standard Tables
    $stmt = $pdo->query("SELECT id, table_code FROM restaurant_tables WHERE category = 'open' ORDER BY CAST(REPLACE(table_code, 'B', '') AS UNSIGNED)");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $x_start = 220; 
    $y_start = 230; 
    $x_step = 140;
    $y_step = 120; 
    $offset_x = 70;

    foreach ($tables as $index => $t) {
        $row = floor($index / 4);
        $col = $index % 4;
        
        $x = $x_start + ($col * $x_step);
        if ($row % 2 != 0) {
            $x += $offset_x;
        }
        $y = $y_start + ($row * $y_step);

        $up_stmt = $pdo->prepare("UPDATE restaurant_tables SET pos_x = ?, pos_y = ? WHERE id = ?");
        $up_stmt->execute([$x, $y, $t['id']]);
    }

    // 3. Update VIP Tables
    $stmt = $pdo->query("SELECT id, table_code FROM restaurant_tables WHERE category = 'room' ORDER BY CAST(REPLACE(table_code, 'VIP ', '') AS UNSIGNED)");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $v_x_col0 = 880;
    $v_x_col1 = 1000;
    $v_y_start = 280;
    $v_y_step = 145;

    foreach ($rooms as $index => $r) {
        if ($index < 3) {
            $x = $v_x_col0;
            $y = $v_y_start + ($index * $v_y_step);
        } else {
            $x = $v_x_col1;
            $y = $v_y_start + (($index - 3) * $v_y_step);
        }

        $up_stmt = $pdo->prepare("UPDATE restaurant_tables SET pos_x = ?, pos_y = ? WHERE id = ?");
        $up_stmt->execute([$x, $y, $r['id']]);
    }

    echo "All tables completely updated.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

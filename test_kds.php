<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

$pos_query = "
    SELECT 
        o.id as order_id, 
        t.table_code as table_name,
        o.created_at,
        oi.id as order_item_id,
        oi.item_type,
        oi.item_id as food_id,
        oi.quantity,
        oi.status,
        CASE WHEN oi.item_type = 'food' THEN f.name ELSE c.name END as food_name
    FROM pos_orders o
    JOIN restaurant_tables t ON o.table_id = t.id
    JOIN pos_order_items oi ON o.id = oi.pos_order_id
    LEFT JOIN foods f ON oi.item_id = f.id AND oi.item_type = 'food'
    LEFT JOIN combos c ON oi.item_id = c.id AND oi.item_type = 'combo'
    WHERE oi.status IN ('pending', 'cooking')
    ORDER BY o.created_at ASC
";
$pos_items = $db->query($pos_query)->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($pos_items);
echo "</pre>";
?>

<?php
// admin/controllers/ReportCostController.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// ĐÃ FIX: Lồng ghép logic quy đổi đơn vị (g -> kg, ml -> l) trực tiếp vào SQL để tính giá vốn chuẩn xác
$sql = "
    SELECT 
        f.id,
        f.name as food_name, 
        f.price as selling_price,
        f.image,
        COALESCE(SUM(
            (CASE 
                WHEN LOWER(TRIM(r.unit)) = 'g' AND LOWER(TRIM(i.unit_name)) = 'kg' THEN r.quantity_required / 1000
                WHEN LOWER(TRIM(r.unit)) = 'ml' AND LOWER(TRIM(i.unit_name)) = 'l' THEN r.quantity_required / 1000
                ELSE r.quantity_required 
            END) * i.cost_price
        ), 0) as real_cost,
        COUNT(r.ingredient_id) as ingredient_count
    FROM foods f
    LEFT JOIN food_recipes r ON f.id = r.food_id
    LEFT JOIN inventory i ON r.ingredient_id = i.id
    WHERE f.is_active = 1
    GROUP BY f.id
    ORDER BY (selling_price - real_cost) ASC
";

$food_costs = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Gọi View ra hiển thị
require_once __DIR__ . '/../views/reports/food_cost_view.php';
?>
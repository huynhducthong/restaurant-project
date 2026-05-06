<?php
// admin/controllers/ReportCostController.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// Câu SQL tính toán Giá vốn (Tổng định mức x Giá nhập bình quân)
$sql = "
    SELECT 
        f.id,
        f.name as food_name, 
        f.price as selling_price,
        f.image,
        COALESCE(SUM(r.quantity_required * i.cost_price), 0) as real_cost,
        COUNT(r.ingredient_id) as ingredient_count
    FROM foods f
    LEFT JOIN food_recipes r ON f.id = r.food_id
    LEFT JOIN inventory i ON r.ingredient_id = i.id
    WHERE f.is_active = 1
    GROUP BY f.id
    ORDER BY (f.price - COALESCE(SUM(r.quantity_required * i.cost_price), 0)) ASC
";

$food_costs = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Gọi View ra hiển thị
require_once __DIR__ . '/../views/reports/food_cost_view.php';
?>
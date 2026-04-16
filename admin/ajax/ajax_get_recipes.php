<?php
require_once __DIR__ . '../../config/database.php';
$db = (new Database())->getConnection();

$food_id = isset($_GET['food_id']) ? (int)$_GET['food_id'] : 0;

if ($food_id > 0) {
    // Lấy định mức nguyên liệu kèm tên và đơn vị từ bảng kho
    $query = "SELECT r.*, i.item_name, i.unit_name as default_unit 
              FROM food_recipes r 
              JOIN inventory i ON r.ingredient_id = i.id 
              WHERE r.food_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$food_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} else {
    echo json_encode([]);
}
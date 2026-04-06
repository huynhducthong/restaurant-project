<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = (int)$_POST['item_id'];
        $name = $_POST['item_name'];
        $cat = $_POST['category'];
        $unit = $_POST['unit_name'];
        $price = (float)$_POST['cost_price'];

        $query = "UPDATE inventory SET item_name = ?, category = ?, unit_name = ?, cost_price = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$name, $cat, $unit, $price, $id])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
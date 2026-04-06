<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['food_id'])) {
        $food_id = (int)$_POST['food_id'];
        $ingredients = $_POST['ingredients'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $units = $_POST['units'] ?? [];

        $db->beginTransaction();

        // 1. Xóa định mức cũ để ghi đè mới
        $db->prepare("DELETE FROM food_recipes WHERE food_id = ?")->execute([$food_id]);

        // 2. Chèn dữ liệu mới
        $stmt = $db->prepare("INSERT INTO food_recipes (food_id, ingredient_id, quantity_required, unit) VALUES (?, ?, ?, ?)");

        foreach ($ingredients as $index => $ing_id) {
            if (!empty($ing_id) && !empty($quantities[$index])) {
                // Chuyển đổi dấu phẩy thành dấu chấm nếu người dùng nhập kiểu 0,5
                $qty = str_replace(',', '.', $quantities[$index]);
                $stmt->execute([
                    $food_id, 
                    (int)$ing_id, 
                    (float)$qty, 
                    $units[$index]
                ]);
            }
        }

        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Lưu định mức thành công!']);
    } else {
        throw new Exception("Dữ liệu không hợp lệ.");
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
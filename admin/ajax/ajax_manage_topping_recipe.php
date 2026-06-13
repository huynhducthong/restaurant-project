<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'get') {
        $topping_id = (int)($_GET['topping_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT tr.id, tr.quantity_required, i.item_name, i.unit_name 
            FROM topping_recipes tr
            JOIN inventory i ON tr.item_id = i.id
            WHERE tr.topping_id = ?
            ORDER BY i.item_name ASC
        ");
        $stmt->execute([$topping_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'add') {
        $topping_id = (int)($_POST['topping_id'] ?? 0);
        $item_id = (int)($_POST['item_id'] ?? 0);
        $qty = (float)($_POST['qty'] ?? 0);

        if ($topping_id <= 0 || $item_id <= 0 || $qty <= 0) {
            throw new Exception("Dữ liệu không hợp lệ.");
        }

        // Kiểm tra xem nguyên liệu đã tồn tại trong công thức topping này chưa
        $stmt = $db->prepare("SELECT id FROM topping_recipes WHERE topping_id = ? AND item_id = ?");
        $stmt->execute([$topping_id, $item_id]);
        if ($stmt->fetch()) {
            throw new Exception("Nguyên liệu này đã có trong định lượng của Topping.");
        }

        $stmt = $db->prepare("INSERT INTO topping_recipes (topping_id, item_id, quantity_required) VALUES (?, ?, ?)");
        $stmt->execute([$topping_id, $item_id, $qty]);

        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID không hợp lệ.");

        $stmt = $db->prepare("DELETE FROM topping_recipes WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success']);
        exit;
    }

    throw new Exception("Hành động không hợp lệ.");

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

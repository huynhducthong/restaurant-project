<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// admin/ajax/ajax_fast_transfer.php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();
$current_user = $_SESSION['username'] ?? 'Admin';

try {
    $data = json_decode(file_get_contents('php://output'), true); // Wait, php://input
    // Let's use POST
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $items = $_POST['items'] ?? []; // Array of {id, qty, target_warehouse_id}

    if (empty($items)) {
        throw new Exception("Không có mặt hàng nào để chuyển.");
    }

    $db->beginTransaction();

    // Nhóm items theo kho đích để tạo phiếu chuyển (nếu có cả Bếp và Bar)
    $grouped_by_warehouse = [];
    foreach ($items as $item) {
        $w_id = (int)$item['target_warehouse_id'];
        $grouped_by_warehouse[$w_id][] = $item;
    }

    foreach ($grouped_by_warehouse as $to_w_id => $ing_items) {
        // 1. Tạo phiếu chuyển trạng thái 'completed' luôn để thực hiện trừ kho ngay
        $note = "Chuyển kho nhanh cho đơn đặt chỗ #" . $booking_id;
        $db->prepare("INSERT INTO inventory_transfers (from_warehouse_id, to_warehouse_id, performed_by, note, status, approved_by, approved_at) VALUES (1, ?, ?, ?, 'completed', ?, NOW())")
           ->execute([$to_w_id, $current_user, $note, $current_user]);
        
        $transfer_id = $db->lastInsertId();
        $stmt_detail = $db->prepare("INSERT INTO transfer_details (transfer_id, ingredient_id, quantity) VALUES (?, ?, ?)");
        $stmt_history = $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, ?, ?, ?)");
        $stmt_update_from = $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE warehouse_id = 1 AND ingredient_id = ?");
        $stmt_update_to = $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");

        foreach ($ing_items as $it) {
            $ing_id = (int)$it['id'];
            $qty = (float)$it['qty'];

            // Kiểm tra tồn kho tại Kho Tổng
            $chk = $db->prepare("SELECT quantity FROM inventory_stocks WHERE warehouse_id = 1 AND ingredient_id = ?");
            $chk->execute([$ing_id]);
            $stock_main = (float)($chk->fetchColumn() ?: 0);

            if ($stock_main < $qty) {
                $stmt_name = $db->prepare("SELECT item_name FROM inventory WHERE id = ?");
                $stmt_name->execute([$ing_id]);
                $name = $stmt_name->fetchColumn();
                throw new Exception("Kho Tổng không đủ '$name' (Cần $qty, Có $stock_main)");
            }

            // Ghi chi tiết
            $stmt_detail->execute([$transfer_id, $ing_id, $qty]);

            // Trừ kho tổng
            $stmt_update_from->execute([$qty, $ing_id]);

            // Cộng kho đích
            $stmt_update_to->execute([$to_w_id, $ing_id, $qty, $qty]);

            // Ghi lịch sử
            $stmt_history->execute([$ing_id, 1, 'export', $qty, $current_user . " (Chuyển nhanh #$transfer_id)"]);
            $stmt_history->execute([$ing_id, $to_w_id, 'import', $qty, $current_user . " (Nhận nhanh #$transfer_id)"]);
        }
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'Đã thực hiện chuyển kho nhanh thành công.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

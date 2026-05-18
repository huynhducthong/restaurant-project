<?php
require_once __DIR__ . '/../../config/database.php';

// 1. Thiết lập phản hồi trả về dạng JSON
header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // 2. Lấy dữ liệu từ form Modal gửi lên
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;

        if ($item_id <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ!']);
            exit;
        }

        // 3. Bắt đầu Transaction để đảm bảo tính toàn vẹn dữ liệu
        $db->beginTransaction();

        // Bước A: Cộng vào bảng tồn kho đa kho (Warehouse 1 - Kho Tổng)
        $query_update = "INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (1, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->execute([$item_id, $quantity, $quantity]);

        // Bước B: Ghi lại lịch sử nhập kho vào bảng inventory_history
        $query_history = "INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, 1, 'import', ?, ?)";
        $stmt_history = $db->prepare($query_history);
        $stmt_history->execute([$item_id, $quantity, $_SESSION['username'] ?? 'Admin']);

        // 4. Hoàn tất lưu dữ liệu
        $db->commit();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Nhập kho thành công và đã ghi lại lịch sử!'
        ]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Phương thức yêu cầu không hợp lệ.']);
    }

} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào, hủy bỏ mọi thay đổi để tránh sai lệch tồn kho
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
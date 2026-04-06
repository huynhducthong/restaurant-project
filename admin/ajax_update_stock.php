<?php
require_once __DIR__ . '/../config/database.php';

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

        // Bước A: Cộng dồn số lượng vào bảng tồn kho (inventory)
        $query_update = "UPDATE inventory SET stock_quantity = stock_quantity + ? WHERE id = ?";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->execute([$quantity, $item_id]);

        // Bước B: Ghi lại lịch sử nhập kho vào bảng inventory_history
        // Việc này giúp Dashboard hiển thị số liệu ở ô "NHẬP / XUẤT"
        $query_history = "INSERT INTO inventory_history (ingredient_id, type, quantity) VALUES (?, 'import', ?)";
        $stmt_history = $db->prepare($query_history);
        $stmt_history->execute([$item_id, $quantity]);

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
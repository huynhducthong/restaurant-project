<?php
/**
 * ajax_sort_chefs.php
 * Cập nhật thứ tự (sort_order) sau khi kéo thả trong manage_chefs.php
 * POST: { orders: [{id: 1, sort_order: 0}, ...] }
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

// Kiểm tra đăng nhập
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra CSRF
if (!verify_csrf()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
    exit;
}

// FIX: Giải phóng session sau khi xác thực thành công để cho phép request khác chạy song song
session_write_close();

// Đọc dữ liệu JSON
$input  = json_decode(file_get_contents('php://input'), true);
$orders = $input['orders'] ?? [];

if (empty($orders) || !is_array($orders)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    $db   = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE chefs SET sort_order = :so WHERE id = :id");

    $db->beginTransaction();
    foreach ($orders as $item) {
        $id = (int)($item['id'] ?? 0);
        $so = (int)($item['sort_order'] ?? 0);
        if ($id > 0) {
            $stmt->execute([':so' => $so, ':id' => $id]);
        }
    }
    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Đã lưu thứ tự thành công']);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
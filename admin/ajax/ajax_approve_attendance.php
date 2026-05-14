<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Chỉ định quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi quyền truy cập! Chỉ Admin mới được duyệt công.']);
    exit;
}

$action = $_POST['action'] ?? '';
$assignment_id = (int) ($_POST['assignment_id'] ?? 0);

try {
    $db = (new Database())->getConnection();

    if ($action === 'approve' || $action === 'reject') {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $db->prepare("UPDATE shift_assignments SET approval_status = ? WHERE id = ? AND status = 'present'");
        $stmt->execute([$new_status, $assignment_id]);

        if ($stmt->rowCount() > 0) {
            $msg = ($action === 'approve') ? 'Đã duyệt công thành công!' : 'Đã từ chối công!';
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            throw new Exception("Không thể cập nhật. Có thể ca làm này chưa có dữ liệu Check-in/out hợp lệ.");
        }
    } else {
        throw new Exception("Hành động không hợp lệ.");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi quyền truy cập!']);
    exit;
}

// Lấy dữ liệu từ form sửa giờ
$assignment_id = (int) ($_POST['assignment_id'] ?? 0);
$check_in = $_POST['check_in'] ?? null; // Format: YYYY-MM-DD HH:ii:ss
$check_out = $_POST['check_out'] ?? null;

try {
    if (empty($check_in)) {
        throw new Exception("Giờ Check-in không được để trống khi sửa thủ công.");
    }

    $db = (new Database())->getConnection();

    // Kiểm tra xem ID có tồn tại không
    $check_stmt = $db->prepare("SELECT id FROM shift_assignments WHERE id = ?");
    $check_stmt->execute([$assignment_id]);
    if (!$check_stmt->fetch()) {
        throw new Exception("Không tìm thấy dữ liệu ca làm việc này.");
    }

    // Khi Admin đã sửa bằng tay, ta tự động đánh dấu trạng thái là 'present' và duyệt luôn 'approved'
    $query = "UPDATE shift_assignments 
              SET check_in = ?, 
                  check_out = ?, 
                  status = 'present', 
                  approval_status = 'approved' 
              WHERE id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([
        $check_in,
        $check_out ?: null, // Nếu rỗng thì set NULL
        $assignment_id
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật giờ chấm công thủ công thành công!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kiểm tra phiên đăng nhập của nhân viên
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$assignment_id = (int) ($_POST['assignment_id'] ?? 0);

try {
    $db = (new Database())->getConnection();

    // Lấy employee_id từ user_id
    $stmt_emp = $db->prepare("SELECT employee_id FROM users WHERE id = ?");
    $stmt_emp->execute([$user_id]);
    $emp_id = $stmt_emp->fetchColumn();

    if (!$emp_id) {
        throw new Exception("Tài khoản này chưa được liên kết với hồ sơ nhân sự!");
    }

    // Lấy thông tin ca làm việc của nhân viên trong ngày hôm nay
    $stmt = $db->prepare("
        SELECT sa.*, s.start_time, s.end_time
        FROM shift_assignments sa
        JOIN shifts s ON sa.shift_id = s.id
        WHERE sa.id = ? AND sa.employee_id = ? AND sa.work_date = CURRENT_DATE
    ");
    $stmt->execute([$assignment_id, $emp_id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shift) {
        throw new Exception("Không tìm thấy dữ liệu phân công ca hợp lệ cho hôm nay!");
    }

    $now = new DateTime();
    $start_time = new DateTime($shift['work_date'] . ' ' . $shift['start_time']);
    $end_time = new DateTime($shift['work_date'] . ' ' . $shift['end_time']);

    // ---------------------------------------------------------
    // BƯỚC 6: XỬ LÝ CHECK-IN
    // ---------------------------------------------------------
    if ($action === 'check_in') {
        if ($shift['check_in'] !== null) {
            throw new Exception("Bạn đã thực hiện Check-in cho ca này rồi!");
        }

        // Tính toán khung giờ cho phép [-15 phút, +30 phút]
        $min_checkin = clone $start_time;
        $min_checkin->modify('-15 minutes');

        $max_checkin = clone $start_time;
        $max_checkin->modify('+30 minutes');

        if ($now < $min_checkin) {
            throw new Exception("Chưa tới giờ. Bạn chỉ được Check-in trước ca làm tối đa 15 phút!");
        }
        if ($now > $max_checkin) {
            throw new Exception("Quá giờ cho phép. Bạn đã trễ quá 30 phút nên không thể Check-in hệ thống!");
        }

        // Thực hiện ghi nhận giờ vào, chuyển trạng thái thành 'present' và chờ Admin duyệt ('pending')
        $update = $db->prepare("UPDATE shift_assignments SET check_in = NOW(), status = 'present', approval_status = 'pending' WHERE id = ?");
        $update->execute([$assignment_id]);

        echo json_encode(['status' => 'success', 'message' => 'Check-in thành công. Chúc bạn một ngày làm việc hiệu quả!']);

        // ---------------------------------------------------------
        // BƯỚC 7: XỬ LÝ CHECK-OUT
        // ---------------------------------------------------------
    } elseif ($action === 'check_out') {
        if ($shift['check_in'] === null) {
            throw new Exception("Lỗi: Bạn chưa Check-in nên không thể Check-out!");
        }
        if ($shift['check_out'] !== null) {
            throw new Exception("Bạn đã hoàn tất Check-out cho ca này rồi!");
        }

        // Ghi nhận giờ ra (Có thể về sớm, hệ thống sẽ báo cáo lên Admin ở Bước 8)
        $update = $db->prepare("UPDATE shift_assignments SET check_out = NOW() WHERE id = ?");
        $update->execute([$assignment_id]);

        echo json_encode(['status' => 'success', 'message' => 'Check-out thành công. Cảm ơn bạn!']);

    } else {
        throw new Exception("Hành động không hợp lệ.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
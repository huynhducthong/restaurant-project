<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: /restaurant-project/public/login.php');
    exit;
}

$page_title = 'Bảng điều khiển Nhân viên';
include __DIR__ . '/../../public/admin_layout_header.php';

$user_id = $_SESSION['user_id'];

// Lấy employee_id trực tiếp để đảm bảo không sai sót
$stmt_emp = $db->prepare("SELECT employee_id, full_name FROM users WHERE id = ?");
$stmt_emp->execute([$user_id]);
$user_data = $stmt_emp->fetch(PDO::FETCH_ASSOC);
$emp_id = $user_data['employee_id'] ?? 0;

// Lấy ca làm gần nhất trong ngày
$stmt = $db->prepare("
    SELECT sa.*, s.shift_name, s.start_time, s.end_time 
    FROM shift_assignments sa
    JOIN shifts s ON sa.shift_id = s.id
    WHERE sa.employee_id = ?
    AND sa.work_date = CURRENT_DATE
    AND sa.check_out IS NULL
    LIMIT 1
");
$stmt->execute([$emp_id]);
$my_shift = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="alert alert-info py-2 small mb-4">
    <i class="fas fa-info-circle me-2"></i> 
    Đang đăng nhập: <strong><?= htmlspecialchars($user_data['full_name']) ?></strong> 
    (Mã NV: <?= $emp_id ?>) - Ngày hệ thống: <?= date('d/m/Y') ?>
</div>

<div class="card p-5 text-center shadow-sm border-0 bg-white" style="border-radius: 15px;">
    <div class="mb-4">
        <i class="fas fa-id-badge fa-4x text-primary animate-bounce"></i>
    </div>
    <h3 class="fw-bold text-dark mb-2">Xin chào, <?= htmlspecialchars($user_data['full_name']) ?>!</h3>
    <p class="text-muted fs-6 mb-4">Chào mừng bạn đến với Cổng thông tin nội bộ của **Reastaurant Reastaurantly**.</p>
    
    <div class="alert alert-light border d-inline-block py-3 px-4 mb-0" style="border-radius: 10px; max-width: 600px; margin: 0 auto;">
        <p class="mb-2 fw-medium text-dark"><i class="fas fa-info-circle text-info me-2"></i> Thông báo Chấm công</p>
        <span class="small text-muted d-block text-start">
            Hệ thống chấm công trực tuyến tự động đã được chuyển sang chế độ <strong>Quản lý Nội bộ trực tiếp bởi Quản lý/Chủ nhà hàng</strong>. 
            Bạn không cần tự check-in trên website nữa. Mọi thông tin ngày công và bảng lương của bạn sẽ được tính toán trực tiếp và hiển thị đầy đủ trong phiếu lương cuối kỳ.
        </span>
    </div>
</div>
</div>
</body>
</html>
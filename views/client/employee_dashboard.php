<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: /restaurant-project/admin/login.php');
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

<div class="alert alert-info py-2 small">
    <i class="fas fa-info-circle me-2"></i> 
    Đang đăng nhập: <strong><?= htmlspecialchars($user_data['full_name']) ?></strong> 
    (Mã NV: <?= $emp_id ?>) - Ngày hệ thống: <?= date('d/m/Y') ?>
</div>

<div class="card text-center p-4 shadow-sm">
    <?php if ($my_shift): ?>
        <h5>Ca làm hiện tại: <span class="text-primary">
                <?= $my_shift['shift_name'] ?>
            </span></h5>
        <p class="text-muted">
            <?= $my_shift['start_time'] ?> -
            <?= $my_shift['end_time'] ?>
        </p>

        <div id="countdown-timer" class="display-4 fw-bold mb-3 text-warning">00:00:00</div>

        <?php if (!$my_shift['check_in']): ?>
            <button class="btn btn-lg btn-success w-100 py-4 shadow-lg" onclick="doAttendance(<?= $my_shift['id'] ?>, 'check_in')" style="font-size: 1.5rem; font-weight: 800;">
                <i class="fas fa-sign-in-alt me-3"></i> BẤM VÀO ĐÂY ĐỂ CHECK-IN
            </button>
        <?php else: ?>
            <button class="btn btn-lg btn-danger w-100 py-4 shadow-lg" onclick="doAttendance(<?= $my_shift['id'] ?>, 'check_out')" style="font-size: 1.5rem; font-weight: 800;">
                <i class="fas fa-sign-out-alt me-3"></i> BẤM VÀO ĐÂY ĐỂ CHECK-OUT
            </button>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-muted">Hôm nay bạn không có lịch phân công ca làm việc.</p>
    <?php endif; ?>
</div>

<script>
    function doAttendance(id, action) {
        const formData = new FormData();
        formData.append('assignment_id', id);
        formData.append('action', action);

        fetch('../../admin/ajax/ajax_attendance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            alert(res.message);
            if (res.status === 'success') location.reload();
        })
        .catch(err => {
            alert("Lỗi kết nối hệ thống hoặc phiên đăng nhập hết hạn!");
            console.error(err);
        });
    }
    // Logic đếm ngược đơn giản dựa trên my_shift.start_time có thể thêm tại đây
</script>
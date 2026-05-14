<?php
ob_start();
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();
$message_success = '';
$message_error = '';

$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Tự động tính lương
    if ($action === 'generate_payroll') {
        $calc_month = (int) $_POST['month'];
        $calc_year = (int) $_POST['year'];

        $start_date = "$calc_year-$calc_month-01";
        $end_date = date("Y-m-t", strtotime($start_date));

        // Lấy tất cả nhân viên đang làm việc
        $employees = $db->query("SELECT id, salary FROM employees WHERE status = 'working'")->fetchAll(PDO::FETCH_ASSOC);

        $generated = 0;
        foreach ($employees as $emp) {
            // Tính tổng giờ làm việc
            $stmt_work = $db->prepare("
                SELECT SUM(
                    LEAST(
                        TIMESTAMPDIFF(MINUTE, sa.check_in, sa.check_out), 
                        TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
                    )
                ) / 60 as total_hours
                FROM shift_assignments sa
                JOIN shifts s ON sa.shift_id = s.id
                WHERE sa.employee_id = ? 
                AND sa.work_date BETWEEN ? AND ? 
                AND sa.status = 'present' 
                AND sa.approval_status = 'approved'
            ");
            $stmt_work->execute([$emp['id'], $start_date, $end_date]);
            $total_hours = (float) $stmt_work->fetchColumn();

            // Lương tháng = (Lương cơ bản / 208 giờ chuẩn) * Số giờ thực tế đã duyệt
            $base = ($emp['salary'] / 208) * $total_hours;
            $net = $base; 

            // Upsert
            $sql = "INSERT INTO payrolls (employee_id, month, year, base_salary, work_days, net_salary) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    base_salary = VALUES(base_salary), 
                    work_days = VALUES(work_days), 
                    net_salary = VALUES(base_salary) + allowance + bonus - deduction";
            $stmt_upsert = $db->prepare($sql);
            if ($stmt_upsert->execute([$emp['id'], $calc_month, $calc_year, $base, $total_hours, $net])) {
                $generated++;
            }
        }
        $message_success = "Đã tính lương tự động cho $generated nhân viên trong tháng $calc_month/$calc_year.";
    }

    // 2. Cập nhật thủ công (Thưởng, Phạt, Phụ cấp)
    if ($action === 'update_payroll') {
        $payroll_id = $_POST['payroll_id'];
        $allowance = floatval(str_replace(',', '', $_POST['allowance']));
        $bonus = floatval(str_replace(',', '', $_POST['bonus']));
        $deduction = floatval(str_replace(',', '', $_POST['deduction']));

        $stmt = $db->prepare("SELECT base_salary FROM payrolls WHERE id = ?");
        $stmt->execute([$payroll_id]);
        $base_salary = $stmt->fetchColumn();

        $net_salary = $base_salary + $allowance + $bonus - $deduction;

        $stmt_update = $db->prepare("UPDATE payrolls SET allowance = ?, bonus = ?, deduction = ?, net_salary = ? WHERE id = ?");
        if ($stmt_update->execute([$allowance, $bonus, $deduction, $net_salary, $payroll_id])) {
            $message_success = "Đã cập nhật bảng lương thành công.";
        } else {
            $message_error = "Có lỗi xảy ra khi cập nhật.";
        }
    }

    // 3. Chốt lương
    if ($action === 'approve_payroll') {
        $payroll_id = $_POST['payroll_id'];
        $db->prepare("UPDATE payrolls SET status = 'approved' WHERE id = ?")->execute([$payroll_id]);
        $message_success = "Đã chốt phiếu lương.";
    }
}

// Lấy danh sách lương của tháng được chọn
$sql = "
    SELECT p.*, e.full_name, e.position, e.salary as contract_salary 
    FROM payrolls p
    JOIN employees e ON p.employee_id = e.id
    WHERE p.month = ? AND p.year = ?
    ORDER BY e.full_name ASC
";
$stmt = $db->prepare($sql);
$stmt->execute([$month, $year]);
$payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng hợp
$total_net = 0;
foreach ($payrolls as $p) {
    $total_net += $p['net_salary'];
}
?>

<style>
    .card-custom {
        border-radius: 10px;
    }

    .badge-status {
        font-weight: 500;
        font-size: 0.8rem;
        padding: 0.35em 0.6em;
    }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i> Quản lý Bảng Lương</h4>

        <form method="POST" class="d-inline"
            onsubmit="return confirm('Chạy tự động tính lương sẽ ghi đè số liệu hiện tại. Tiếp tục?');">
            <input type="hidden" name="action" value="generate_payroll">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <button type="submit" class="btn btn-success shadow-sm">
                <i class="fas fa-magic me-2"></i> Tự Động Tính Lương Tháng <?= $month ?>/<?= $year ?>
            </button>
        </form>
    </div>

    <?php if ($message_success): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($message_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($message_error): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($message_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card card-custom p-3 shadow-sm border-0 h-100">
                <form method="GET" class="row g-3 align-items-center h-100">
                    <div class="col-md-auto fw-bold text-muted">Kỳ lương:</div>
                    <div class="col-md-3">
                        <select name="month" class="form-select fw-bold" onchange="this.form.submit()">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="year" class="form-select fw-bold" onchange="this.form.submit()">
                            <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>Năm <?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <div
                class="card card-custom p-3 shadow-sm border-0 bg-primary text-white h-100 d-flex justify-content-center">
                <div class="small opacity-75 fw-bold text-uppercase">Tổng quỹ lương thực lãnh</div>
                <h3 class="m-0 fw-bold"><?= number_format($total_net) ?> đ</h3>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card card-custom p-0 shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                    <tr>
                        <th class="ps-4">Nhân viên</th>
                        <th class="text-center">Số giờ làm</th>
                        <th class="text-end">Lương cơ bản</th>
                        <th class="text-end">Thưởng/Phụ cấp</th>
                        <th class="text-end">Khấu trừ</th>
                        <th class="text-end">Thực lãnh</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payrolls) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-file-invoice fa-3x mb-3 text-light"></i>
                                <h5>Chưa có dữ liệu bảng lương tháng <?= $month ?>/<?= $year ?></h5>
                                <p>Hãy bấm nút "Tự động tính lương" ở góc trên bên phải để bắt đầu.</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($payrolls as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['full_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($p['position']) ?> • Lương HĐ:
                                    <?= number_format($p['contract_salary']) ?>đ
                                </div>
                            </td>
                            <td class="text-center fw-bold text-primary">
                                <?= number_format($p['work_days'], 1) ?> Giờ
                            </td>
                            <td class="text-end fw-medium">
                                <?= number_format($p['base_salary']) ?> đ
                            </td>
                            <td class="text-end text-success small">
                                + Phụ cấp: <?= number_format($p['allowance']) ?> đ<br>
                                + Thưởng: <?= number_format($p['bonus']) ?> đ
                            </td>
                            <td class="text-end text-danger small">
                                - Phạt: <?= number_format($p['deduction']) ?> đ
                            </td>
                            <td class="text-end fw-bold text-success fs-6">
                                <?= number_format($p['net_salary']) ?> đ
                            </td>
                            <td class="text-center">
                                <?php if ($p['status'] === 'draft'): ?>
                                    <span class="badge bg-secondary badge-status">Nháp</span>
                                <?php elseif ($p['status'] === 'approved'): ?>
                                    <span class="badge bg-success badge-status"><i class="fas fa-check"></i> Đã chốt</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($p['status'] === 'draft'): ?>
                                    <button class="btn btn-sm btn-outline-info rounded-circle me-1"
                                        title="Chỉnh sửa Thưởng/Phạt" onclick='openModal(<?= json_encode($p) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline"
                                        onsubmit="return confirm('Chốt bảng lương này? Sau khi chốt sẽ không thể chỉnh sửa.');">
                                        <input type="hidden" name="action" value="approve_payroll">
                                        <input type="hidden" name="payroll_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-circle"
                                            title="Chốt Lương">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light rounded-circle text-muted"
                                        title="Không thể sửa bảng lương đã chốt" disabled>
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Sửa Thưởng Phạt -->
<div class="modal fade" id="payrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="fas fa-money-check-alt me-2"></i> Điều chỉnh Thưởng/Phạt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="update_payroll">
                    <input type="hidden" name="payroll_id" id="modalPayrollId">

                    <div class="mb-3 text-center">
                        <h6 class="fw-bold text-dark" id="modalEmpName">Tên nhân viên</h6>
                        <span class="text-muted small">Lương cơ bản (dựa trên số công): </span>
                        <strong class="text-primary" id="modalBaseSalary">0đ</strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Tiền Phụ cấp (+)</label>
                            <div class="input-group">
                                <input type="number" class="form-control text-end" name="allowance" id="modalAllowance"
                                    value="0">
                                <span class="input-group-text">VNĐ</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Tiền Thưởng (+)</label>
                            <div class="input-group">
                                <input type="number" class="form-control text-end" name="bonus" id="modalBonus"
                                    value="0">
                                <span class="input-group-text">VNĐ</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-danger">Tiền Khấu trừ/Phạt (-)</label>
                            <div class="input-group">
                                <input type="number" class="form-control text-end" name="deduction" id="modalDeduction"
                                    value="0">
                                <span class="input-group-text">VNĐ</span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Lưu Cập
                            Nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let payrollModal;

    function openModal(data) {
        if (!payrollModal) {
            payrollModal = new bootstrap.Modal(document.getElementById('payrollModal'));
        }

        document.getElementById('modalPayrollId').value = data.id;
        document.getElementById('modalEmpName').innerText = data.full_name;

        // Format number with commas
        let formatter = new Intl.NumberFormat('vi-VN');
        document.getElementById('modalBaseSalary').innerText = formatter.format(data.base_salary) + ' đ';

        document.getElementById('modalAllowance').value = Math.round(data.allowance);
        document.getElementById('modalBonus').value = Math.round(data.bonus);
        document.getElementById('modalDeduction').value = Math.round(data.deduction);

        payrollModal.show();
    }
</script>

</div>
</body>

</html>
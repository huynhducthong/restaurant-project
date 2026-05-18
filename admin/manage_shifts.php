<?php
ob_start();
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();
$message_success = '';
$message_error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Thêm loại ca làm việc mới
    if ($action === 'create_shift') {
        $name = trim($_POST['shift_name']);
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        if ($name && $start && $end) {
            $stmt = $db->prepare("INSERT INTO shifts (shift_name, start_time, end_time) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $start, $end])) {
                $message_success = "Đã thêm loại Ca làm việc mới.";
            } else {
                $message_error = "Lỗi khi thêm ca làm việc.";
            }
        }
    }

    // Xóa loại ca
    if ($action === 'delete_shift') {
        $id = $_POST['shift_id'];
        $db->prepare("DELETE FROM shifts WHERE id = ?")->execute([$id]);
        $message_success = "Đã xóa ca làm việc.";
    }

    // Phân công ca làm
    if ($action === 'assign_shift') {
        $emp_id = $_POST['employee_id'];
        $shift_id = $_POST['shift_id'];
        $work_date = $_POST['work_date'];
        
        // Kiểm tra xem đã phân công chưa
        $check = $db->prepare("SELECT id FROM shift_assignments WHERE employee_id = ? AND shift_id = ? AND work_date = ?");
        $check->execute([$emp_id, $shift_id, $work_date]);
        if ($check->rowCount() > 0) {
            $message_error = "Nhân viên này đã được phân công vào ca này trong ngày được chọn!";
        } else {
            $stmt = $db->prepare("INSERT INTO shift_assignments (employee_id, shift_id, work_date) VALUES (?, ?, ?)");
            if ($stmt->execute([$emp_id, $shift_id, $work_date])) {
                $message_success = "Đã phân công ca làm thành công.";
            } else {
                $message_error = "Có lỗi xảy ra khi phân công.";
            }
        }
    }

    // Chấm công (Update status)
    if ($action === 'update_attendance') {
        $assign_id = $_POST['assignment_id'];
        $status = $_POST['status'];
        $time_col = $status === 'present' ? 'check_in' : ($status === 'late' ? 'check_in' : 'check_out');
        $current_time = date('Y-m-d H:i:s');
        
        $sql = "UPDATE shift_assignments SET status = ?";
        if ($status === 'present' || $status === 'late') {
            $sql .= ", check_in = '$current_time'";
        }
        $sql .= " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$status, $assign_id])) {
            $message_success = "Đã cập nhật trạng thái chấm công.";
        }
    }
    
    // Xóa phân công
    if ($action === 'delete_assignment') {
        $id = $_POST['assignment_id'];
        $db->prepare("DELETE FROM shift_assignments WHERE id = ?")->execute([$id]);
        $message_success = "Đã hủy phân công ca làm.";
    }
}

// Lấy danh sách Loại Ca Làm
$shifts = $db->query("SELECT * FROM shifts ORDER BY start_time ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách nhân viên để phân ca
$employees = $db->query("SELECT id, full_name, position FROM employees WHERE status = 'working' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy ngày cần xem (Mặc định là hôm nay)
$view_date = $_GET['date'] ?? date('Y-m-d');

// Lấy danh sách phân công trong ngày $view_date
$sql_assignments = "
    SELECT sa.*, e.full_name, e.position, e.avatar, s.shift_name, s.start_time, s.end_time 
    FROM shift_assignments sa
    JOIN employees e ON sa.employee_id = e.id
    JOIN shifts s ON sa.shift_id = s.id
    WHERE sa.work_date = ?
    ORDER BY s.start_time ASC, e.full_name ASC
";
$stmt_assign = $db->prepare($sql_assignments);
$stmt_assign->execute([$view_date]);
$assignments = $stmt_assign->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .card-custom { border-radius: 10px; }
    .nav-pills .nav-link.active { background-color: #0f172a; }
    .nav-pills .nav-link { color: #475569; font-weight: 500; }
    .avatar-sm { width: 32px; height: 32px; object-fit: cover; border-radius: 50%; background-color: #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #64748b; }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="fas fa-calendar-check me-2 text-primary"></i> Quản lý Ca & Chấm Công</h4>
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

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 shadow-sm bg-white p-2 rounded" id="shiftTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab"><i class="fas fa-clipboard-list me-2"></i>Chấm công Hàng Ngày</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab"><i class="fas fa-clock me-2"></i>Thiết lập Ca làm</button>
        </li>
    </ul>

    <div class="tab-content" id="shiftTabsContent">
        
        <!-- TAB: CHẤM CÔNG HÀNG NGÀY -->
        <div class="tab-pane fade show active" id="daily" role="tabpanel">
            <div class="card card-custom p-3 mb-4 shadow-sm border-0 bg-white">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-auto fw-bold text-muted">Xem lịch ngày:</div>
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control fw-bold" value="<?= htmlspecialchars($view_date) ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-8 text-end">
                        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                            <i class="fas fa-user-plus me-2"></i> Phân Công Nhân Sự
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-custom p-0 shadow-sm border-0 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4">Nhân viên</th>
                                <th>Ca làm việc</th>
                                <th>Thời gian</th>
                                <th>Trạng thái (Chấm công)</th>
                                <th>Giờ Check-in</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($assignments) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="fas fa-calendar-times fa-3x mb-3 text-light"></i>
                                        <h5>Không có nhân viên nào được phân công trong ngày này</h5>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($assignments as $asn): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($asn['avatar']): ?>
                                                <img src="/restaurant-project/public/assets/uploads/avatars/<?= htmlspecialchars($asn['avatar']) ?>" class="avatar-sm shadow-sm" alt="Avatar">
                                            <?php else: ?>
                                                <div class="avatar-sm"><?= strtoupper(mb_substr($asn['full_name'], 0, 1)) ?></div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($asn['full_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($asn['position']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2"><i class="fas fa-sun me-1"></i> <?= htmlspecialchars($asn['shift_name']) ?></span></td>
                                    <td class="fw-medium text-muted">
                                        <?= date('H:i', strtotime($asn['start_time'])) ?> - <?= date('H:i', strtotime($asn['end_time'])) ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="action" value="update_attendance">
                                            <input type="hidden" name="assignment_id" value="<?= $asn['id'] ?>">
                                            <select name="status" class="form-select form-select-sm shadow-none <?= $asn['status'] == 'present' ? 'border-success text-success' : ($asn['status'] == 'absent' ? 'border-danger text-danger' : '') ?>" style="width: 130px; font-weight: 500;" onchange="this.form.submit()">
                                                <option value="scheduled" <?= $asn['status'] == 'scheduled' ? 'selected' : '' ?>>Chưa tới</option>
                                                <option value="present" <?= $asn['status'] == 'present' ? 'selected' : '' ?>>Có mặt</option>
                                                <option value="late" <?= $asn['status'] == 'late' ? 'selected' : '' ?>>Đi trễ</option>
                                                <option value="absent" <?= $asn['status'] == 'absent' ? 'selected' : '' ?>>Vắng mặt</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($asn['check_in']): ?>
                                            <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> <?= date('H:i:s', strtotime($asn['check_in'])) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">--:--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" onsubmit="return confirm('Hủy phân công ca làm này?');">
                                            <input type="hidden" name="action" value="delete_assignment">
                                            <input type="hidden" name="assignment_id" value="<?= $asn['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Hủy ca">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: THIẾT LẬP CA LÀM -->
        <div class="tab-pane fade" id="manage" role="tabpanel">
            <div class="row">
                <div class="col-md-4">
                    <div class="card card-custom shadow-sm border-0 bg-white">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold m-0">Thêm Ca Làm Mới</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="create_shift">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên Ca <span class="text-danger">*</span></label>
                                    <input type="text" name="shift_name" class="form-control" placeholder="VD: Ca Sáng" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Giờ Bắt đầu <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Giờ Kết thúc <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Tạo Ca Làm</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card card-custom shadow-sm border-0 bg-white">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold m-0">Danh sách Ca làm việc</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="ps-4">Tên Ca</th>
                                        <th>Bắt đầu</th>
                                        <th>Kết thúc</th>
                                        <th class="text-end pe-4">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shifts as $s): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['shift_name']) ?></td>
                                        <td class="text-primary fw-medium"><?= date('H:i', strtotime($s['start_time'])) ?></td>
                                        <td class="text-danger fw-medium"><?= date('H:i', strtotime($s['end_time'])) ?></td>
                                        <td class="text-end pe-4">
                                            <form method="POST" onsubmit="return confirm('Bạn có chắc chắn xóa ca này? Các dữ liệu chấm công cũ sẽ bị ảnh hưởng.');">
                                                <input type="hidden" name="action" value="delete_shift">
                                                <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Xóa</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($shifts) === 0): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">Chưa có dữ liệu ca làm. Vui lòng tạo mới.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Phân Công -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="fas fa-user-clock me-2"></i> Phân Công Nhân Sự</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="assign_shift">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ngày làm việc</label>
                        <input type="date" name="work_date" class="form-control" value="<?= htmlspecialchars($view_date) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn Ca làm việc</label>
                        <select name="shift_id" class="form-select" required>
                            <option value="">-- Chọn Ca --</option>
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['shift_name']) ?> (<?= date('H:i', strtotime($s['start_time'])) ?> - <?= date('H:i', strtotime($s['end_time'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Chọn Nhân viên</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">-- Chọn Nhân viên --</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?> - <?= htmlspecialchars($e['position']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Lưu Phân Công</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>

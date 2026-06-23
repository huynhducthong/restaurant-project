<?php
ob_start();
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();
$message_success = '';
$message_error = '';

// Lấy danh sách chức vụ để hiện trong dropdown
$positions = $db->query("SELECT * FROM positions ORDER BY position_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $identity_card = trim($_POST['identity_card'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $dob = $_POST['dob'] ?: null;
        $gender = $_POST['gender'] ?? 'other';
        $position = trim($_POST['position']);
        $salary = floatval(str_replace(',', '', $_POST['salary'] ?? 0));
        $status = $_POST['status'] ?? 'working';

        // Auto Role Mapping based on Position
        $mapped_role = 'staff';
        if (mb_stripos($position, 'Quản lý') !== false) {
            $mapped_role = 'admin';
        } elseif (mb_stripos($position, 'bếp') !== false) {
            $mapped_role = 'chef';
        } elseif (mb_stripos($position, 'Thu ngân') !== false) {
            $mapped_role = 'cashier';
        } elseif (mb_stripos($position, 'Phục vụ') !== false || mb_stripos($position, 'Pha chế') !== false || mb_stripos($position, 'Lễ tân') !== false) {
            $mapped_role = 'waiter';
        }

        if (empty($full_name)) {
            $message_error = "Vui lòng nhập họ tên nhân viên.";
        } else {
            try {
                // Xử lý upload Avatar vào DB
                $avatar_blob = null;
                $avatar_mime = null;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $avatar_blob = file_get_contents($_FILES['avatar']['tmp_name']);
                        $avatar_mime = $_FILES['avatar']['type'];
                    }
                }

                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO employees (full_name, phone, email, identity_card, address, dob, gender, position, salary, status, avatar_blob, avatar_mime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$full_name, $phone, $email, $identity_card, $address, $dob, $gender, $position, $salary, $status, $avatar_blob, $avatar_mime])) {
                        $employee_id = $db->lastInsertId();
                        $message_success = "Đã thêm nhân sự thành công.";
                        
                        // Tự động tạo tài khoản đăng nhập (users)
                        if (!empty($email)) {
                            $default_password = password_hash('123456', PASSWORD_DEFAULT);
                            // Lấy phần trước @ của email làm username mặc định
                            $username = explode('@', $email)[0] . '_' . rand(10, 99); 
                            
                            $stmt_user = $db->prepare("INSERT INTO users (username, email, password, full_name, role, employee_id) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt_user->execute([$username, $email, $default_password, $full_name, $mapped_role, $employee_id]);
                        }
                    } else {
                        $message_error = "Có lỗi xảy ra khi thêm nhân sự: " . implode(" - ", $stmt->errorInfo());
                    }
                } else {
                    if ($avatar_blob) {
                        $stmt = $db->prepare("UPDATE employees SET full_name=?, phone=?, email=?, identity_card=?, address=?, dob=?, gender=?, position=?, salary=?, status=?, avatar_blob=?, avatar_mime=? WHERE id=?");
                        $success = $stmt->execute([$full_name, $phone, $email, $identity_card, $address, $dob, $gender, $position, $salary, $status, $avatar_blob, $avatar_mime, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE employees SET full_name=?, phone=?, email=?, identity_card=?, address=?, dob=?, gender=?, position=?, salary=?, status=? WHERE id=?");
                        $success = $stmt->execute([$full_name, $phone, $email, $identity_card, $address, $dob, $gender, $position, $salary, $status, $id]);
                    }
                    if ($success) {
                        // Update corresponding user's role and full_name if exists
                        $db->prepare("UPDATE users SET role = ?, full_name = ? WHERE employee_id = ?")->execute([$mapped_role, $full_name, $id]);
                        $message_success = "Cập nhật thông tin nhân sự thành công.";
                    } else {
                        $message_error = "Có lỗi xảy ra khi cập nhật: " . implode(" - ", $stmt->errorInfo());
                    }
                }
            } catch (PDOException $e) {
                $message_error = "Lỗi Database: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Delete associated user account first to avoid foreign key constraints or orphans
            $db->prepare("DELETE FROM users WHERE employee_id = ?")->execute([$id]);
            
            $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message_success = "Đã xóa nhân sự thành công.";
            } else {
                $message_error = "Lỗi khi xóa nhân sự.";
            }
        }
    }
}

// Filter & Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(full_name LIKE ? OR phone LIKE ? OR email LIKE ? OR identity_card LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 4, $search_param);
}

$where_clause = implode(" AND ", $where);

// Count records
$stmt_count = $db->prepare("SELECT COUNT(*) FROM employees WHERE $where_clause");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$sql = "SELECT * FROM employees WHERE $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .table-hover tbody tr:hover { background-color: #f8fafc; }
    .badge-status { font-weight: 500; padding: 0.4em 0.8em; }
    .avatar-placeholder { width: 40px; height: 40px; background-color: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b; }
    .card-custom { border-radius: 10px; }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="fas fa-users-cog me-2 text-primary"></i> Quản lý Nhân sự</h4>
        <div>
            <a href="export_employees.php" class="btn btn-success shadow-sm me-2">
                <i class="fas fa-file-excel me-2"></i> Xuất Excel (CSV)
            </a>
            <button class="btn btn-primary shadow-sm" onclick="openModal('create')">
                <i class="fas fa-plus me-2"></i> Thêm Nhân sự Mới
            </button>
        </div>
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
    <div class="card card-custom p-3 mb-4 shadow-sm border-0">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm tên, SĐT, CCCD, Email..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-secondary">Tìm kiếm</button>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <a href="manage_users.php" class="btn btn-light"><i class="fas fa-sync-alt"></i> Làm mới</a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="card card-custom p-0 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                    <tr>
                        <th class="ps-4">Nhân sự</th>
                        <th>Liên hệ</th>
                        <th>Vị trí</th>
                        <th>Lương ngày</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($employees) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-user-slash fa-3x mb-3 text-light"></i>
                                <h5>Chưa có dữ liệu nhân sự</h5>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($emp['avatar_blob']): ?>
                                        <img src="/restaurant-project/ajax/get_avatar.php?emp_id=<?= $emp['id'] ?>" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?= strtoupper(mb_substr($emp['full_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($emp['full_name']) ?></div>
                                        <div class="text-muted small">ID: #<?= $emp['id'] ?> - <?= $emp['gender'] === 'male' ? 'Nam' : ($emp['gender'] === 'female' ? 'Nữ' : 'Khác') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small"><i class="fas fa-phone-alt text-muted me-1"></i> <?= htmlspecialchars($emp['phone'] ?: 'N/A') ?></div>
                                <div class="small"><i class="fas fa-envelope text-muted me-1"></i> <?= htmlspecialchars($emp['email'] ?: 'N/A') ?></div>
                            </td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($emp['position'] ?: 'Chưa cập nhật') ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-success"><?= number_format($emp['salary']) ?>đ/ngày</div>
                            </td>
                            <td>
                                <?php if ($emp['status'] === 'working'): ?>
                                    <span class="badge bg-success badge-status">Đang làm việc</span>
                                <?php elseif ($emp['status'] === 'on_leave'): ?>
                                    <span class="badge bg-warning text-dark badge-status">Đang nghỉ phép</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary badge-status">Đã nghỉ việc</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-info rounded-circle me-1" title="Chỉnh sửa"
                                    onclick='openModal("edit", <?= json_encode($emp) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này? Dữ liệu không thể khôi phục.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white p-3 border-top d-flex justify-content-end">
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-plus me-2"></i> Thêm Nhân sự Mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" id="employeeForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="empId">
                    
                    <div class="row g-3">
                        <div class="col-md-12 text-center mb-3">
                            <label class="form-label fw-bold d-block">Ảnh đại diện (Avatar)</label>
                            <input type="file" class="form-control form-control-sm mx-auto" style="max-width: 300px;" name="avatar" id="empAvatar" accept="image/*">
                            <small class="text-muted">Định dạng hỗ trợ: JPG, PNG, GIF</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Họ và Tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" id="empFullName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Số điện thoại</label>
                            <input type="text" class="form-control" name="phone" id="empPhone">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" id="empEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">CCCD / CMND</label>
                            <input type="text" class="form-control" name="identity_card" id="empIdCard">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ngày sinh</label>
                            <input type="date" class="form-control" name="dob" id="empDob">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Giới tính</label>
                            <select class="form-select" name="gender" id="empGender">
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Địa chỉ</label>
                            <input type="text" class="form-control" name="address" id="empAddress">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Vị trí / Chức vụ</label>
                            <select name="position" class="form-select" id="empPosition" required>
                                <option value="">-- Chọn chức vụ --</option>
                                <?php foreach ($positions as $p): ?>
                                    <option value="<?= htmlspecialchars($p['position_name']) ?>"><?= htmlspecialchars($p['position_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Mức lương theo ngày</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="salary" id="empSalary" value="0">
                                <span class="input-group-text">VNĐ</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Trạng thái</label>
                            <select class="form-select" name="status" id="empStatus">
                                <option value="working">Đang làm việc</option>
                                <option value="on_leave">Đang nghỉ phép</option>
                                <option value="resigned">Đã nghỉ việc</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">Lưu Thông Tin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->


<script>
    let myModal;

    function openModal(mode, data = null) {
        if (!myModal) {
            myModal = new bootstrap.Modal(document.getElementById('employeeModal'));
        }
        document.getElementById('formAction').value = mode;
        
        if (mode === 'create') {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i> Thêm Nhân sự Mới';
            document.getElementById('btnSubmit').innerText = 'Lưu Nhân Sự';
            document.getElementById('employeeForm').reset();
            document.getElementById('empId').value = '';
        } else {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i> Cập nhật Thông tin Nhân sự';
            document.getElementById('btnSubmit').innerText = 'Cập Nhật';
            
            document.getElementById('empId').value = data.id;
            document.getElementById('empFullName').value = data.full_name;
            document.getElementById('empPhone').value = data.phone;
            document.getElementById('empEmail').value = data.email;
            document.getElementById('empIdCard').value = data.identity_card;
            document.getElementById('empDob').value = data.dob;
            document.getElementById('empGender').value = data.gender;
            document.getElementById('empAddress').value = data.address;
            document.getElementById('empPosition').value = data.position;
            document.getElementById('empSalary').value = parseFloat(data.salary) || 0;
            document.getElementById('empStatus').value = data.status;
        }
        
        myModal.show();
    }
</script>

</div>
</body>
</html>

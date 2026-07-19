<?php
// Include the header which handles authentication and outputs the sidebar/topbar
include '../public/admin_layout_header.php';
require_once '../config/database.php';

if (!isset($db)) {
    $db = (new Database())->getConnection();
}

// Xử lý thông báo
if (isset($_SESSION['flash_success'])) {
    $message = '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><i class="fas fa-check-circle me-2"></i>' . $_SESSION['flash_success'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['flash_success']);
} else {
    $message = '';
}

// Xử lý Xóa Budget
if (isset($_GET['delete_budget'])) {
    $db->prepare("DELETE FROM bespoke_budgets WHERE id=?")->execute([(int)$_GET['delete_budget']]);
    $_SESSION['flash_success'] = "Xóa Ngân sách thành công.";
    echo "<script>window.location.href='manage_bespoke.php';</script>"; exit;
}
// Xử lý Xóa Style
if (isset($_GET['delete_style'])) {
    $db->prepare("DELETE FROM bespoke_styles WHERE id=?")->execute([(int)$_GET['delete_style']]);
    $_SESSION['flash_success'] = "Xóa Phong cách thành công.";
    echo "<script>window.location.href='manage_bespoke.php';</script>"; exit;
}
// Xử lý Xóa Occasion
if (isset($_GET['delete_occasion'])) {
    $db->prepare("DELETE FROM bespoke_occasions WHERE id=?")->execute([(int)$_GET['delete_occasion']]);
    $_SESSION['flash_success'] = "Xóa Dịp tổ chức thành công.";
    echo "<script>window.location.href='manage_bespoke.php';</script>"; exit;
}

// POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';

    // ================= BUDGETS =================
    if ($action_type === 'budget') {
        $id = $_POST['id'] ?? '';
        $label = $_POST['label'] ?? '';
        $price_value = (int)($_POST['price_value'] ?? 0);
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        
        if ($id) {
            $stmt = $db->prepare("UPDATE bespoke_budgets SET label=?, price_value=?, sort_order=? WHERE id=?");
            $stmt->execute([$label, $price_value, $sort_order, $id]);
            $_SESSION['flash_success'] = "Cập nhật Ngân sách thành công.";
        } else {
            $stmt = $db->prepare("INSERT INTO bespoke_budgets (label, price_value, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$label, $price_value, $sort_order]);
            $_SESSION['flash_success'] = "Thêm Ngân sách thành công.";
        }
        echo "<script>window.location.href='manage_bespoke.php';</script>"; exit;
    }

    // ================= STYLES =================
    if ($action_type === 'style') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        
        if ($id) {
            $stmt = $db->prepare("UPDATE bespoke_styles SET name=?, sort_order=? WHERE id=?");
            $stmt->execute([$name, $sort_order, $id]);
            $_SESSION['flash_success'] = "Cập nhật Phong cách thành công.";
        } else {
            $stmt = $db->prepare("INSERT INTO bespoke_styles (name, sort_order) VALUES (?, ?)");
            $stmt->execute([$name, $sort_order]);
            $_SESSION['flash_success'] = "Thêm Phong cách thành công.";
        }
        echo "<script>window.location.href='manage_bespoke.php';</script>"; exit;
    }

    // ================= OCCASIONS =================
    if ($action_type === 'occasion') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        
        if ($id) {
            $stmt = $db->prepare("UPDATE bespoke_occasions SET name=?, sort_order=? WHERE id=?");
            $stmt->execute([$name, $sort_order, $id]);
            $_SESSION['flash_success'] = "Cập nhật Dịp tổ chức thành công.";
        } else {
            $stmt = $db->prepare("INSERT INTO bespoke_occasions (name, sort_order) VALUES (?, ?)");
            $stmt->execute([$name, $sort_order]);
            $_SESSION['flash_success'] = "Thêm Dịp tổ chức thành công.";
        }
        echo "<script>window.location.href='manage_bespoke.php';</script>"; exit;
    }
}

// Fetching lists
$budgets = $db->query("SELECT * FROM bespoke_budgets ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$styles = $db->query("SELECT * FROM bespoke_styles ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$occasions = $db->query("SELECT * FROM bespoke_occasions ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark"><i class="fas fa-magic text-primary me-2"></i>Cấu Hình Dịch Vụ Thiết Kế Riêng (Bespoke)</h4>
            <p class="text-muted mb-0 small">Thiết lập các thuộc tính dành riêng cho form đăng ký dịch vụ đầu bếp tại gia</p>
        </div>
    </div>

    <?= $message ?>

    <div class="row align-items-start">
        
        <!-- CỘT 1: NGÂN SÁCH -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-wallet text-primary me-2"></i>Ngân Sách Dự Kiến</h6>
                    <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBudget" onclick="resetBudgetForm()">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">TT</th>
                                    <th>Tên hiển thị</th>
                                    <th width="80" class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($budgets as $b): ?>
                                <tr>
                                    <td class="text-muted text-center"><?= $b['sort_order'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($b['label']) ?></div>
                                        <small class="text-success"><?= number_format($b['price_value']) ?> đ</small>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary" onclick='editBudget(<?= json_encode($b) ?>)'><i class="fas fa-edit"></i></button>
                                        <a href="?delete_budget=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa mục này?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT 2: PHONG CÁCH -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-utensils text-success me-2"></i>Phong Cách Ẩm Thực</h6>
                    <button class="btn btn-sm btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalStyle" onclick="resetStyleForm()">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">TT</th>
                                    <th>Tên Phong cách</th>
                                    <th width="80" class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($styles as $s): ?>
                                <tr>
                                    <td class="text-muted text-center"><?= $s['sort_order'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($s['name']) ?></td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-success" onclick='editStyle(<?= json_encode($s) ?>)'><i class="fas fa-edit"></i></button>
                                        <a href="?delete_style=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa mục này?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT 3: DỊP TỔ CHỨC -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-alt text-info me-2"></i>Dịp Tổ Chức</h6>
                    <button class="btn btn-sm btn-info text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalOccasion" onclick="resetOccasionForm()">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">TT</th>
                                    <th>Tên Dịp</th>
                                    <th width="80" class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($occasions as $o): ?>
                                <tr>
                                    <td class="text-muted text-center"><?= $o['sort_order'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($o['name']) ?></td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-info" onclick='editOccasion(<?= json_encode($o) ?>)'><i class="fas fa-edit"></i></button>
                                        <a href="?delete_occasion=<?= $o['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa mục này?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL BUDGET -->
<div class="modal fade" id="modalBudget" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST">
                <input type="hidden" name="action_type" value="budget">
                <input type="hidden" name="id" id="budget_id">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="budgetModalTitle">Thêm Ngân Sách Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tên hiển thị (Label)</label>
                        <input type="text" name="label" id="budget_label" class="form-control" required placeholder="VD: Dưới 1.500.000 đ / khách">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Giá trị ước tính (VNĐ)</label>
                        <input type="number" name="price_value" id="budget_price" class="form-control" required value="0">
                        <small class="text-muted">Nhập 0 nếu là Thỏa thuận.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Thứ tự sắp xếp</label>
                        <input type="number" name="sort_order" id="budget_sort" class="form-control" required value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL STYLE -->
<div class="modal fade" id="modalStyle" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST">
                <input type="hidden" name="action_type" value="style">
                <input type="hidden" name="id" id="style_id">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="styleModalTitle">Thêm Phong Cách Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tên Phong Cách</label>
                        <input type="text" name="name" id="style_name" class="form-control" required placeholder="VD: Ẩm thực Pháp">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Thứ tự sắp xếp</label>
                        <input type="number" name="sort_order" id="style_sort" class="form-control" required value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success px-4">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL OCCASION -->
<div class="modal fade" id="modalOccasion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST">
                <input type="hidden" name="action_type" value="occasion">
                <input type="hidden" name="id" id="occasion_id">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="occasionModalTitle">Thêm Dịp Tổ Chức Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tên Dịp Tổ Chức</label>
                        <input type="text" name="name" id="occasion_name" class="form-control" required placeholder="VD: Sinh nhật">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Thứ tự sắp xếp</label>
                        <input type="number" name="sort_order" id="occasion_sort" class="form-control" required value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-info text-white px-4">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetBudgetForm() {
    document.getElementById('budget_id').value = '';
    document.getElementById('budget_label').value = '';
    document.getElementById('budget_price').value = '0';
    document.getElementById('budget_sort').value = '0';
    document.getElementById('budgetModalTitle').innerText = 'Thêm Ngân Sách Mới';
}
function editBudget(data) {
    document.getElementById('budget_id').value = data.id;
    document.getElementById('budget_label').value = data.label;
    document.getElementById('budget_price').value = data.price_value;
    document.getElementById('budget_sort').value = data.sort_order;
    document.getElementById('budgetModalTitle').innerText = 'Chỉnh Sửa Ngân Sách';
    new bootstrap.Modal(document.getElementById('modalBudget')).show();
}

function resetStyleForm() {
    document.getElementById('style_id').value = '';
    document.getElementById('style_name').value = '';
    document.getElementById('style_sort').value = '0';
    document.getElementById('styleModalTitle').innerText = 'Thêm Phong Cách Mới';
}
function editStyle(data) {
    document.getElementById('style_id').value = data.id;
    document.getElementById('style_name').value = data.name;
    document.getElementById('style_sort').value = data.sort_order;
    document.getElementById('styleModalTitle').innerText = 'Chỉnh Sửa Phong Cách';
    new bootstrap.Modal(document.getElementById('modalStyle')).show();
}

function resetOccasionForm() {
    document.getElementById('occasion_id').value = '';
    document.getElementById('occasion_name').value = '';
    document.getElementById('occasion_sort').value = '0';
    document.getElementById('occasionModalTitle').innerText = 'Thêm Dịp Tổ Chức Mới';
}
function editOccasion(data) {
    document.getElementById('occasion_id').value = data.id;
    document.getElementById('occasion_name').value = data.name;
    document.getElementById('occasion_sort').value = data.sort_order;
    document.getElementById('occasionModalTitle').innerText = 'Chỉnh Sửa Dịp Tổ Chức';
    new bootstrap.Modal(document.getElementById('modalOccasion')).show();
}
</script>

<?php include '../public/admin_layout_footer.php'; ?>

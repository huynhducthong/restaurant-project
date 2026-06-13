<?php
// Include the header which handles authentication and outputs the sidebar/topbar
include '../public/admin_layout_header.php';
require_once '../config/database.php';

if (!isset($db)) {
    $db = (new Database())->getConnection();
}

$message = '';

// Handling Budgets CRUD
if (isset($_POST['add_budget']) || isset($_POST['edit_budget'])) {
    $id = $_POST['id'] ?? '';
    $label = $_POST['label'] ?? '';
    $price_value = (int)($_POST['price_value'] ?? 0);
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    if ($id) {
        $stmt = $db->prepare("UPDATE bespoke_budgets SET label=?, price_value=?, sort_order=? WHERE id=?");
        $stmt->execute([$label, $price_value, $sort_order, $id]);
        $message = '<div class="alert alert-success">Cập nhật Ngân sách thành công.</div>';
    } else {
        $stmt = $db->prepare("INSERT INTO bespoke_budgets (label, price_value, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$label, $price_value, $sort_order]);
        $message = '<div class="alert alert-success">Thêm Ngân sách thành công.</div>';
    }
}

if (isset($_GET['delete_budget'])) {
    $db->prepare("DELETE FROM bespoke_budgets WHERE id=?")->execute([(int)$_GET['delete_budget']]);
    $message = '<div class="alert alert-success">Xóa Ngân sách thành công.</div>';
}

// Handling Styles CRUD
if (isset($_POST['add_style']) || isset($_POST['edit_style'])) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    if ($id) {
        $stmt = $db->prepare("UPDATE bespoke_styles SET name=?, sort_order=? WHERE id=?");
        $stmt->execute([$name, $sort_order, $id]);
        $message = '<div class="alert alert-success">Cập nhật Phong cách thành công.</div>';
    } else {
        $stmt = $db->prepare("INSERT INTO bespoke_styles (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $sort_order]);
        $message = '<div class="alert alert-success">Thêm Phong cách thành công.</div>';
    }
}

if (isset($_GET['delete_style'])) {
    $db->prepare("DELETE FROM bespoke_styles WHERE id=?")->execute([(int)$_GET['delete_style']]);
    $message = '<div class="alert alert-success">Xóa Phong cách thành công.</div>';
}

// Fetching lists
$budgets = $db->query("SELECT * FROM bespoke_budgets ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$styles = $db->query("SELECT * FROM bespoke_styles ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Edit States
$edit_budget = null;
if (isset($_GET['edit_budget'])) {
    $stmt = $db->prepare("SELECT * FROM bespoke_budgets WHERE id=?");
    $stmt->execute([(int)$_GET['edit_budget']]);
    $edit_budget = $stmt->fetch(PDO::FETCH_ASSOC);
}

$edit_style = null;
if (isset($_GET['edit_style'])) {
    $stmt = $db->prepare("SELECT * FROM bespoke_styles WHERE id=?");
    $stmt->execute([(int)$_GET['edit_style']]);
    $edit_style = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
    <div class="container-fluid py-4">

    <div class="main-content p-4">
        <h2 class="mb-4">Cấu Hình Dịch Vụ Thiết Kế Riêng (Bespoke)</h2>
        <?= $message ?>

        <div class="row">
            <!-- SECTION: BUDGETS -->
            <div class="col-md-6 mb-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Quản Lý Ngân Sách Dự Kiến</h5>
                        <?php if($edit_budget): ?>
                            <a href="manage_bespoke.php" class="btn btn-sm btn-outline-light">Thêm mới</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form action="manage_bespoke.php" method="POST">
                            <input type="hidden" name="id" value="<?= $edit_budget['id'] ?? '' ?>">
                            <div class="mb-3">
                                <label class="form-label">Tên hiển thị (Label)</label>
                                <input type="text" class="form-control" name="label" value="<?= htmlspecialchars($edit_budget['label'] ?? '') ?>" placeholder="VD: Dưới 1.500.000 đ / khách" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giá trị ước tính (VNĐ)</label>
                                <input type="number" class="form-control" name="price_value" value="<?= $edit_budget['price_value'] ?? 0 ?>" required>
                                <small class="text-muted">Dùng để tính toán báo giá dự kiến. Nhập 0 nếu là Thỏa thuận.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thứ tự sắp xếp</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= $edit_budget['sort_order'] ?? 0 ?>" required>
                            </div>
                            <button type="submit" name="<?= $edit_budget ? 'edit_budget' : 'add_budget' ?>" class="btn btn-primary w-100">
                                <?= $edit_budget ? 'Cập nhật Ngân sách' : 'Thêm Ngân sách' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Thứ tự</th>
                                    <th>Hiển thị</th>
                                    <th>Giá trị</th>
                                    <th width="120">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($budgets as $b): ?>
                                <tr>
                                    <td><?= $b['sort_order'] ?></td>
                                    <td><?= htmlspecialchars($b['label']) ?></td>
                                    <td><?= number_format($b['price_value']) ?> đ</td>
                                    <td>
                                        <a href="?edit_budget=<?= $b['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <a href="?delete_budget=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa ngân sách này?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION: STYLES -->
            <div class="col-md-6 mb-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Quản Lý Phong Cách Ẩm Thực</h5>
                        <?php if($edit_style): ?>
                            <a href="manage_bespoke.php" class="btn btn-sm btn-outline-light">Thêm mới</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form action="manage_bespoke.php" method="POST">
                            <input type="hidden" name="id" value="<?= $edit_style['id'] ?? '' ?>">
                            <div class="mb-3">
                                <label class="form-label">Tên Phong Cách</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($edit_style['name'] ?? '') ?>" placeholder="VD: Ẩm thực Pháp - Việt" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thứ tự sắp xếp</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= $edit_style['sort_order'] ?? 0 ?>" required>
                            </div>
                            <button type="submit" name="<?= $edit_style ? 'edit_style' : 'add_style' ?>" class="btn btn-success w-100">
                                <?= $edit_style ? 'Cập nhật Phong cách' : 'Thêm Phong cách' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Thứ tự</th>
                                    <th>Tên Phong Cách</th>
                                    <th width="120">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($styles as $s): ?>
                                <tr>
                                    <td><?= $s['sort_order'] ?></td>
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <td>
                                        <a href="?edit_style=<?= $s['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <a href="?delete_style=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa phong cách này?');"><i class="fas fa-trash"></i></a>
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
<?php include '../public/admin_layout_footer.php'; ?>

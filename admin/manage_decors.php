<?php
// Include the header which handles authentication and outputs the sidebar/topbar
include '../public/admin_layout_header.php';
require_once '../config/database.php';

if (!isset($db)) {
    $db = (new Database())->getConnection();
}

$message = '';
$upload_dir = '../public/assets/images/decors/';

// Xử lý Xóa
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT image_url FROM decor_packages WHERE id = ?");
    $stmt->execute([$id]);
    $decor = $stmt->fetch();
    
    if ($decor) {
        // Có thể xóa file ảnh nếu muốn, nhưng ở đây chỉ xóa DB để an toàn
        $db->prepare("DELETE FROM decor_packages WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success">Đã xóa gói trang trí.</div>';
    }
}

// Xử lý Thêm/Sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $event_type_id = $_POST['event_type_id'] ?? null;
    if ($event_type_id === '') $event_type_id = null;
    $status = $_POST['status'] ?? 'active';
    
    $image_url = $_POST['existing_image'] ?? '';

    // Xử lý Upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['image']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
            $image_url = 'public/assets/images/decors/' . $file_name;
        }
    }

    if ($id) {
        // Update
        $stmt = $db->prepare("UPDATE decor_packages SET name=?, description=?, price=?, image_url=?, status=?, event_type_id=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $image_url, $status, $event_type_id, $id]);
        $message = '<div class="alert alert-success">Cập nhật thành công.</div>';
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO decor_packages (name, description, price, image_url, status, event_type_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $image_url, $status, $event_type_id]);
        $message = '<div class="alert alert-success">Thêm mới thành công.</div>';
    }
}

// Lấy danh sách
$stmt = $db->query("SELECT dp.*, et.name as event_type_name FROM decor_packages dp LEFT JOIN event_types et ON dp.event_type_id = et.id ORDER BY dp.id ASC");
$decors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách Event Types cho Dropdown
$stmt_et = $db->query("SELECT id, name FROM event_types WHERE status = 'active' ORDER BY id ASC");
$event_types = $stmt_et->fetchAll(PDO::FETCH_ASSOC);

// Nếu đang Sửa
$edit_decor = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM decor_packages WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_decor = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
    <div class="container-fluid py-4">

    <div class="main-content p-4">
        <h2 class="mb-4">Quản Lý Gói Trang Trí (Decor Packages)</h2>
        <?= $message ?>

        <div class="row">
            <!-- Form Thêm/Sửa -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0"><?= $edit_decor ? 'Sửa Gói Trang Trí' : 'Thêm Gói Mới' ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="manage_decors.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $edit_decor['id'] ?? '' ?>">
                            <input type="hidden" name="existing_image" value="<?= $edit_decor['image_url'] ?? '' ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Tên Gói</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($edit_decor['name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả ngắn</label>
                                <textarea class="form-control" name="description" rows="3" required><?= htmlspecialchars($edit_decor['description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giá phụ thu (VNĐ)</label>
                                <input type="number" class="form-control" name="price" value="<?= isset($edit_decor['price']) ? (float)$edit_decor['price'] : 0 ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh minh họa</label>
                                <input type="file" class="form-control mb-2" name="image" accept="image/*" <?= $edit_decor ? '' : 'required' ?>>
                                <?php if($edit_decor && $edit_decor['image_url']): ?>
                                    <img src="../<?= $edit_decor['image_url'] ?>" style="height: 60px; object-fit: cover; border-radius: 4px;">
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sự Kiện Kỷ Niệm (Event Type)</label>
                                <select name="event_type_id" class="form-select" required>
                                    <option value="">-- Chọn Loại Hình Sự Kiện --</option>
                                    <?php foreach($event_types as $et): ?>
                                        <option value="<?= $et['id'] ?>" <?= (isset($edit_decor['event_type_id']) && $edit_decor['event_type_id'] == $et['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($et['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($edit_decor['status']??'') == 'active' ? 'selected' : '' ?>>Hiển thị (Active)</option>
                                    <option value="inactive" <?= ($edit_decor['status']??'') == 'inactive' ? 'selected' : '' ?>>Đang ẩn (Inactive)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-save me-2"></i> Lưu Gói Trang Trí
                            </button>
                            <?php if($edit_decor): ?>
                                <a href="manage_decors.php" class="btn btn-secondary w-100 mt-2">Hủy Sửa</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Danh Sách Gói Trang Trí Hiện Có</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ảnh</th>
                                        <th>Tên Gói</th>
                                        <th>Sự Kiện</th>
                                        <th>Giá</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($decors as $d): ?>
                                        <tr>
                                            <td>
                                                <img src="../<?= $d['image_url'] ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($d['name']) ?></strong><br>
                                                <small class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($d['description']) ?></small>
                                            </td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($d['event_type_name'] ?? 'Chưa gán') ?></span></td>
                                            <td><strong class="text-danger"><?= number_format($d['price'], 0, ',', '.') ?> đ</strong></td>
                                            <td>
                                                <?php if($d['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Đang hiển thị</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Đang ẩn</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                <a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa gói này?');"><i class="fas fa-trash"></i></a>
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
    </div>

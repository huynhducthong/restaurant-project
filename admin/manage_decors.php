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
        $db->prepare("DELETE FROM decor_packages WHERE id = ?")->execute([$id]);
        $_SESSION['flash_success'] = "Đã xóa gói trang trí.";
        echo "<script>window.location.href='manage_decors.php';</script>";
        exit;
    }
}

// Xử lý Thêm/Sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
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

    if ($action === 'edit' && $id) {
        // Update
        $stmt = $db->prepare("UPDATE decor_packages SET name=?, description=?, price=?, image_url=?, status=?, event_type_id=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $image_url, $status, $event_type_id, $id]);
        $_SESSION['flash_success'] = "Cập nhật thành công.";
    } else if ($action === 'add') {
        // Insert
        $stmt = $db->prepare("INSERT INTO decor_packages (name, description, price, image_url, status, event_type_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $image_url, $status, $event_type_id]);
        $_SESSION['flash_success'] = "Thêm mới thành công.";
    }
    echo "<script>window.location.href='manage_decors.php';</script>";
    exit;
}

// Lấy danh sách
$stmt = $db->query("SELECT dp.*, et.name as event_type_name FROM decor_packages dp LEFT JOIN event_types et ON dp.event_type_id = et.id ORDER BY dp.id ASC");
$decors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách Event Types cho Dropdown
$stmt_et = $db->query("SELECT id, name FROM event_types WHERE status = 'active' ORDER BY id ASC");
$event_types = $stmt_et->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark"><i class="fas fa-gift text-primary me-2"></i>Quản Lý Gói Trang Trí</h4>
            <p class="text-muted mb-0 small">Cài đặt phân loại và hiển thị các gói trang trí sự kiện</p>
        </div>
        <div>
            <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>Thêm Gói Trang Trí
            </button>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="80" class="text-center">ID</th>
                        <th width="120">Hình Ảnh</th>
                        <th>Tên Gói & Mô tả</th>
                        <th>Sự Kiện</th>
                        <th>Giá (VNĐ)</th>
                        <th>Trạng Thái</th>
                        <th width="120" class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($decors as $e): ?>
                    <tr>
                        <td class="text-center fw-bold text-muted">#<?= $e['id'] ?></td>
                        <td>
                            <?php if($e['image_url']): ?>
                                <img src="../<?= $e['image_url'] ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <?php else: ?>
                                <div style="width: 80px; height: 50px; background: #eee; border-radius: 6px; display:flex; align-items:center; justify-content:center; color:#999;"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($e['name']) ?></h6>
                            <small class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($e['description']) ?></small>
                        </td>
                        <td>
                            <?php if($e['event_type_name']): ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-glass-cheers me-1"></i><?= htmlspecialchars($e['event_type_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Tất cả</span>
                            <?php endif; ?>
                        </td>
                        <td><strong class="text-danger"><?= number_format($e['price']) ?> đ</strong></td>
                        <td>
                            <?php if($e['status'] == 'active'): ?>
                                <span class="badge bg-success">Đang hiển thị</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Đang ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editDecor(<?= htmlspecialchars(json_encode($e)) ?>)" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa gói trang trí này?');" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($decors)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có gói trang trí nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Thêm Gói Trang Trí Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tên Gói</label>
                            <input type="text" name="name" class="form-control" required placeholder="VD: Gói Sinh Nhật Cơ Bản">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Sự Kiện Kỷ Niệm (Event Type)</label>
                            <select name="event_type_id" class="form-select">
                                <option value="">-- Chọn Loại Hình Sự Kiện --</option>
                                <?php foreach($event_types as $et): ?>
                                    <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Mô tả ngắn</label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="Chi tiết các phụ kiện có trong gói..."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Hình ảnh minh họa</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small text-muted">Giá phụ thu (VNĐ)</label>
                            <input type="number" name="price" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small text-muted">Trạng Thái</label>
                            <select name="status" class="form-select">
                                <option value="active">Hiển thị</option>
                                <option value="inactive">Đang ẩn</option>
                            </select>
                        </div>
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

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="existing_image" id="edit_existing_image">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Chỉnh Sửa Gói Trang Trí</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tên Gói</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Sự Kiện Kỷ Niệm (Event Type)</label>
                            <select name="event_type_id" id="edit_event_type_id" class="form-select">
                                <option value="">-- Chọn Loại Hình Sự Kiện --</option>
                                <?php foreach($event_types as $et): ?>
                                    <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Mô tả ngắn</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Hình ảnh minh họa</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Bỏ trống nếu không muốn thay đổi ảnh.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small text-muted">Giá phụ thu (VNĐ)</label>
                            <input type="number" name="price" id="edit_price" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small text-muted">Trạng Thái</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Hiển thị</option>
                                <option value="inactive">Đang ẩn</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDecor(decor) {
    document.getElementById('edit_id').value = decor.id;
    document.getElementById('edit_name').value = decor.name;
    document.getElementById('edit_description').value = decor.description;
    document.getElementById('edit_price').value = decor.price;
    document.getElementById('edit_status').value = decor.status;
    document.getElementById('edit_event_type_id').value = decor.event_type_id || '';
    document.getElementById('edit_existing_image').value = decor.image_url;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>

<?php include '../public/admin_layout_footer.php'; ?>

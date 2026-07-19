<?php
// Include the header which handles authentication and outputs the sidebar/topbar
include '../public/admin_layout_header.php';
require_once '../config/database.php';

if (!isset($db)) {
    $db = (new Database())->getConnection();
}

$message = '';
$upload_dir = '../public/assets/images/events/';

// Xử lý Xóa
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT image_url FROM event_types WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    
    if ($event) {
        $db->prepare("DELETE FROM event_types WHERE id = ?")->execute([$id]);
        $_SESSION['flash_success'] = "Đã xóa Loại hình Sự kiện.";
        echo "<script>window.location.href='manage_events.php';</script>";
        exit;
    }
}

// Xử lý Thêm/Sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    $image_url = $_POST['existing_image'] ?? '';

    // Xử lý Upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['image']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
            $image_url = 'public/assets/images/events/' . $file_name;
        }
    }

    if ($action === 'edit' && $id) {
        // Update
        $stmt = $db->prepare("UPDATE event_types SET name=?, description=?, image_url=?, status=? WHERE id=?");
        $stmt->execute([$name, $description, $image_url, $status, $id]);
        $_SESSION['flash_success'] = "Cập nhật thành công.";
    } else if ($action === 'add') {
        // Insert
        $stmt = $db->prepare("INSERT INTO event_types (name, description, image_url, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $image_url, $status]);
        $_SESSION['flash_success'] = "Thêm mới thành công.";
    }
    echo "<script>window.location.href='manage_events.php';</script>";
    exit;
}

// Lấy danh sách
$stmt = $db->query("SELECT * FROM event_types ORDER BY id ASC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark"><i class="fas fa-glass-cheers text-primary me-2"></i>Quản Lý Loại Hình Sự Kiện</h4>
            <p class="text-muted mb-0 small">Cài đặt phân loại và hiển thị các sự kiện</p>
        </div>
        <div>
            <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>Thêm Sự Kiện
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
                        <th>Tên Sự Kiện & Mô tả</th>
                        <th>Trạng Thái</th>
                        <th width="120" class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($events as $e): ?>
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
                            <?php if($e['status'] == 'active'): ?>
                                <span class="badge bg-success">Đang hiển thị</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Đang ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editEvent(<?= htmlspecialchars(json_encode($e)) ?>)" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa sự kiện này?');" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($events)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có sự kiện nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Thêm Sự Kiện Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tên Sự Kiện</label>
                        <input type="text" name="name" class="form-control" required placeholder="VD: Sinh nhật">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mô tả ngắn</label>
                        <textarea name="description" class="form-control" rows="3" required placeholder="Nhập mô tả sự kiện..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Hình ảnh minh họa</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Trạng Thái</label>
                        <select name="status" class="form-select">
                            <option value="active">Hiển thị (Active)</option>
                            <option value="inactive">Đang ẩn (Inactive)</option>
                        </select>
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
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="existing_image" id="edit_existing_image">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Chỉnh Sửa Sự Kiện</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tên Sự Kiện</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mô tả ngắn</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Hình ảnh minh họa</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Bỏ trống nếu không muốn thay đổi ảnh.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Trạng Thái</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Hiển thị (Active)</option>
                            <option value="inactive">Đang ẩn (Inactive)</option>
                        </select>
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
function editEvent(event) {
    document.getElementById('edit_id').value = event.id;
    document.getElementById('edit_name').value = event.name;
    document.getElementById('edit_description').value = event.description;
    document.getElementById('edit_status').value = event.status;
    document.getElementById('edit_existing_image').value = event.image_url;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>

<?php include '../public/admin_layout_footer.php'; ?>

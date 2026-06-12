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
        $message = '<div class="alert alert-success">Đã xóa Loại hình Sự kiện.</div>';
    }
}

// Xử lý Thêm/Sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if ($id) {
        // Update
        $stmt = $db->prepare("UPDATE event_types SET name=?, description=?, image_url=?, status=? WHERE id=?");
        $stmt->execute([$name, $description, $image_url, $status, $id]);
        $message = '<div class="alert alert-success">Cập nhật thành công.</div>';
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO event_types (name, description, image_url, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $image_url, $status]);
        $message = '<div class="alert alert-success">Thêm mới thành công.</div>';
    }
}

// Lấy danh sách
$stmt = $db->query("SELECT * FROM event_types ORDER BY id ASC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nếu đang Sửa
$edit_event = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM event_types WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_event = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
    <div class="container-fluid py-4">

    <div class="main-content p-4">
        <h2 class="mb-4">Quản Lý Loại Hình Sự Kiện (Event Types)</h2>
        <?= $message ?>

        <div class="row">
            <!-- Form Thêm/Sửa -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0"><?= $edit_event ? 'Sửa Sự Kiện' : 'Thêm Sự Kiện Mới' ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="manage_events.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $edit_event['id'] ?? '' ?>">
                            <input type="hidden" name="existing_image" value="<?= $edit_event['image_url'] ?? '' ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Tên Sự Kiện</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($edit_event['name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả ngắn</label>
                                <textarea class="form-control" name="description" rows="3" required><?= htmlspecialchars($edit_event['description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh minh họa</label>
                                <input type="file" class="form-control mb-2" name="image" accept="image/*" <?= $edit_event ? '' : 'required' ?>>
                                <?php if($edit_event && $edit_event['image_url']): ?>
                                    <img src="../<?= $edit_event['image_url'] ?>" style="height: 60px; object-fit: cover; border-radius: 4px;">
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($edit_event['status']??'') == 'active' ? 'selected' : '' ?>>Hiển thị (Active)</option>
                                    <option value="inactive" <?= ($edit_event['status']??'') == 'inactive' ? 'selected' : '' ?>>Đang ẩn (Inactive)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-save me-2"></i> Lưu Sự Kiện
                            </button>
                            <?php if($edit_event): ?>
                                <a href="manage_events.php" class="btn btn-secondary w-100 mt-2">Hủy Sửa</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Danh Sách Sự Kiện Hiện Có</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ảnh</th>
                                        <th>Tên Sự Kiện</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($events as $e): ?>
                                        <tr>
                                            <td>
                                                <img src="../<?= $e['image_url'] ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($e['name']) ?></strong><br>
                                                <small class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($e['description']) ?></small>
                                            </td>
                                            <td>
                                                <?php if($e['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Đang hiển thị</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Đang ẩn</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="?edit=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                <a href="?delete=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa sự kiện này?');"><i class="fas fa-trash"></i></a>
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

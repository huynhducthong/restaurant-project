<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// Lấy danh sách Món ăn và Set
$all_foods = $db->query("SELECT id, name, theme_id FROM foods ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_sets = $db->query("SELECT id, name, theme_id FROM combos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

function assignThemeToItems($db, $theme_id, $table, $item_ids) {
    $db->prepare("UPDATE $table SET theme_id = NULL WHERE theme_id = ?")->execute([$theme_id]);
    if (!empty($item_ids)) {
        $in = str_repeat('?,', count($item_ids) - 1) . '?';
        $params = $item_ids;
        array_unshift($params, $theme_id);
        $db->prepare("UPDATE $table SET theme_id = ? WHERE id IN ($in)")->execute($params);
    }
}

// Xử lý Thêm Chủ Đề
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = __DIR__ . '/../../public/assets/img/themes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('theme_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image = "public/assets/img/themes/$filename";
        }
    }

    $stmt = $db->prepare("INSERT INTO themes (name, description, image, is_active, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $desc, $image, $is_active, $start_date, $end_date]);
    $theme_id = $db->lastInsertId();

    assignThemeToItems($db, $theme_id, 'foods', $_POST['food_ids'] ?? []);
    assignThemeToItems($db, $theme_id, 'combos', $_POST['set_ids'] ?? []);

    $_SESSION['flash_success'] = "Thêm chủ đề thành công!";
    header('Location: manage_themes.php'); exit;
}

// Xử lý Sửa Chủ Đề
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    
    // Giữ lại ảnh cũ nếu không upload mới
    $stmt_old = $db->prepare("SELECT image FROM themes WHERE id = ?");
    $stmt_old->execute([$id]);
    $old_img = $stmt_old->fetchColumn();
    $image = $old_img;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = __DIR__ . '/../../public/assets/img/themes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('theme_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image = "public/assets/img/themes/$filename";
            if ($old_img && file_exists("../../$old_img")) @unlink("../../$old_img");
        }
    }

    $stmt = $db->prepare("UPDATE themes SET name=?, description=?, image=?, is_active=?, start_date=?, end_date=? WHERE id=?");
    $stmt->execute([$name, $desc, $image, $is_active, $start_date, $end_date, $id]);

    assignThemeToItems($db, $id, 'foods', $_POST['food_ids'] ?? []);
    assignThemeToItems($db, $id, 'combos', $_POST['set_ids'] ?? []);

    $_SESSION['flash_success'] = "Cập nhật chủ đề thành công!";
    header('Location: manage_themes.php'); exit;
}

// Xử lý Xóa Chủ Đề
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Lấy ảnh để xóa
    $stmt = $db->prepare("SELECT image FROM themes WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../../$img")) @unlink("../../$img");

    $db->prepare("UPDATE foods SET theme_id = NULL WHERE theme_id = ?")->execute([$id]);
    $db->prepare("UPDATE combos SET theme_id = NULL WHERE theme_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM themes WHERE id = ?")->execute([$id]);
    
    $_SESSION['flash_success'] = "Xóa chủ đề thành công!";
    header('Location: manage_themes.php'); exit;
}

// Lấy danh sách
$themes = $db->query("SELECT * FROM themes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../public/admin_layout_header.php';
?>
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold m-0"><i class="fas fa-layer-group me-2 text-primary"></i>Quản Lý Chủ Đề Thực Đơn</h3>
            <div class="small text-muted mt-1">Các Themed Collections sẽ hiển thị trên trang Thực đơn</div>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-2"></i>Thêm Chủ Đề
        </button>
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
                        <th width="150">Hình ảnh</th>
                        <th>Tên Chủ Đề</th>
                        <th>Trạng thái</th>
                        <th width="150" class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($themes as $t): 
                        $t['set_ids'] = array_column(array_filter($all_sets, fn($s) => $s['theme_id'] == $t['id']), 'id');
                        $t['food_ids'] = array_column(array_filter($all_foods, fn($f) => $f['theme_id'] == $t['id']), 'id');
                    ?>
                    <tr>
                        <td class="text-center fw-bold text-muted">#<?= $t['id'] ?></td>
                        <td>
                            <?php if($t['image']): ?>
                                <img src="../../<?= htmlspecialchars($t['image']) ?>" alt="" style="width:100px; height:60px; object-fit:cover; border-radius:8px;">
                            <?php else: ?>
                                <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="width:100px; height:60px; border-radius:8px; font-size:12px;">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($t['name']) ?></h6>
                            <div class="small text-muted text-truncate" style="max-width:300px;"><?= htmlspecialchars($t['description']) ?></div>
                        </td>
                        <td>
                            <?php if($t['is_active']): ?>
                                <span class="badge bg-success">Đang hoạt động</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tạm ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editTheme(<?= htmlspecialchars(json_encode($t)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa chủ đề này?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($themes)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có chủ đề nào.</td></tr>
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
                    <h5 class="modal-title fw-bold">Thêm Chủ Đề Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Tên Chủ Đề</label>
                                <input type="text" name="name" class="form-control" required placeholder="VD: Bản Giao Hưởng Mùa Thu">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Mô tả lãng mạn</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Đoạn văn ngắn xuất hiện dưới tên chủ đề..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Ảnh Banner ngang</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">Ngày bắt đầu</label>
                                    <input type="datetime-local" name="start_date" class="form-control">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">Ngày kết thúc</label>
                                    <input type="datetime-local" name="end_date" class="form-control">
                                </div>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="activeAdd" checked>
                                <label class="form-check-label fw-bold" for="activeAdd">Bật (Hiển thị ra trang chủ)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Phân bổ Thực đơn</label>
                            
                            <div class="card mb-3 shadow-sm border-0">
                                <div class="card-header bg-light fw-bold py-2"><i class="fas fa-boxes text-primary me-2"></i>Chọn Set Menu</div>
                                <div class="card-body p-0" style="max-height: 150px; overflow-y: auto;">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($all_sets as $s): ?>
                                        <li class="list-group-item py-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="set_ids[]" value="<?= $s['id'] ?>" id="add_set_<?= $s['id'] ?>">
                                                <label class="form-check-label" for="add_set_<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light fw-bold py-2"><i class="fas fa-utensils text-success me-2"></i>Chọn Món tự chọn</div>
                                <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($all_foods as $f): ?>
                                        <li class="list-group-item py-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="food_ids[]" value="<?= $f['id'] ?>" id="add_food_<?= $f['id'] ?>">
                                                <label class="form-check-label" for="add_food_<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu Chủ Đề</button>
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
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Chỉnh Sửa Chủ Đề</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Tên Chủ Đề</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Mô tả lãng mạn</label>
                                <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Ảnh Banner ngang (Để trống để giữ nguyên)</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">Ngày bắt đầu</label>
                                    <input type="datetime-local" name="start_date" id="edit_start_date" class="form-control">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">Ngày kết thúc</label>
                                    <input type="datetime-local" name="end_date" id="edit_end_date" class="form-control">
                                </div>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_active">
                                <label class="form-check-label fw-bold" for="edit_active">Bật (Hiển thị ra trang chủ)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Phân bổ Thực đơn</label>
                            
                            <div class="card mb-3 shadow-sm border-0">
                                <div class="card-header bg-light fw-bold py-2"><i class="fas fa-boxes text-primary me-2"></i>Chọn Set Menu</div>
                                <div class="card-body p-0" style="max-height: 150px; overflow-y: auto;">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($all_sets as $s): ?>
                                        <li class="list-group-item py-1">
                                            <div class="form-check">
                                                <input class="form-check-input edit_set_cb" type="checkbox" name="set_ids[]" value="<?= $s['id'] ?>" id="edit_set_<?= $s['id'] ?>">
                                                <label class="form-check-label" for="edit_set_<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light fw-bold py-2"><i class="fas fa-utensils text-success me-2"></i>Chọn Món tự chọn</div>
                                <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($all_foods as $f): ?>
                                        <li class="list-group-item py-1">
                                            <div class="form-check">
                                                <input class="form-check-input edit_food_cb" type="checkbox" name="food_ids[]" value="<?= $f['id'] ?>" id="edit_food_<?= $f['id'] ?>">
                                                <label class="form-check-label" for="edit_food_<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập Nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function editTheme(t) {
    document.getElementById('edit_id').value = t.id;
    document.getElementById('edit_name').value = t.name;
    document.getElementById('edit_desc').value = t.description;
    document.getElementById('edit_start_date').value = t.start_date ? t.start_date.substring(0, 16) : '';
    document.getElementById('edit_end_date').value = t.end_date ? t.end_date.substring(0, 16) : '';
    document.getElementById('edit_active').checked = (t.is_active == 1);
    
    document.querySelectorAll('.edit_set_cb').forEach(cb => cb.checked = false);
    document.querySelectorAll('.edit_food_cb').forEach(cb => cb.checked = false);
    
    if (t.set_ids) {
        t.set_ids.forEach(id => {
            let el = document.getElementById('edit_set_' + id);
            if(el) el.checked = true;
        });
    }
    if (t.food_ids) {
        t.food_ids.forEach(id => {
            let el = document.getElementById('edit_food_' + id);
            if(el) el.checked = true;
        });
    }

    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>


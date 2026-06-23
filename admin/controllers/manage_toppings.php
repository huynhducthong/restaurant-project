<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// Xử lý Thêm Topping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $selection_type = $_POST['selection_type'] ?? 'checkbox';
    $topping_group = trim($_POST['topping_group']) ?: 'Topping thêm';
    $status = isset($_POST['status']) ? 1 : 0;
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = __DIR__ . '/../../public/assets/img/toppings/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('topping_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image = "public/assets/img/toppings/$filename";
        }
    }

    $stmt = $db->prepare("INSERT INTO toppings (name, description, price, image, selection_type, topping_group, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $image, $selection_type, $topping_group, $status]);

    $_SESSION['flash_success'] = "Thêm topping thành công!";
    header('Location: manage_toppings.php'); exit;
}

// Xử lý Sửa Topping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $selection_type = $_POST['selection_type'] ?? 'checkbox';
    $topping_group = trim($_POST['topping_group']) ?: 'Topping thêm';
    $status = isset($_POST['status']) ? 1 : 0;
    
    $stmt_old = $db->prepare("SELECT image FROM toppings WHERE id = ?");
    $stmt_old->execute([$id]);
    $old_img = $stmt_old->fetchColumn();
    $image = $old_img;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = __DIR__ . '/../../public/assets/img/toppings/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('topping_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image = "public/assets/img/toppings/$filename";
            if ($old_img && file_exists("../../$old_img")) @unlink("../../$old_img");
        }
    }

    $stmt = $db->prepare("UPDATE toppings SET name=?, description=?, price=?, image=?, selection_type=?, topping_group=?, status=? WHERE id=?");
    $stmt->execute([$name, $description, $price, $image, $selection_type, $topping_group, $status, $id]);

    $_SESSION['flash_success'] = "Cập nhật topping thành công!";
    header('Location: manage_toppings.php'); exit;
}

// Xử lý Xóa Topping
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("SELECT image FROM toppings WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../../$img")) @unlink("../../$img");

    $db->prepare("DELETE FROM food_toppings WHERE topping_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM toppings WHERE id = ?")->execute([$id]);
    
    $_SESSION['flash_success'] = "Xóa topping thành công!";
    header('Location: manage_toppings.php'); exit;
}

// Lấy danh sách + Tìm kiếm + Phân trang
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where_sql = "";
$params = [];
if ($search !== '') {
    $where_sql = "WHERE name LIKE ? OR topping_group LIKE ? ";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Đếm tổng số
$total_stmt = $db->prepare("SELECT COUNT(*) FROM toppings $where_sql");
$total_stmt->execute($params);
$total_items = $total_stmt->fetchColumn();
$total_pages = ceil($total_items / $limit);

// Lấy dữ liệu
$sql = "SELECT * FROM toppings $where_sql ORDER BY id ASC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$toppings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$inventory_items = $db->query("SELECT id, item_name, unit_name FROM inventory WHERE is_active = 1 ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../public/admin_layout_header.php';
?>
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold m-0"><i class="fas fa-cheese me-2 text-primary"></i>Quản Lý Topping / Lựa Chọn</h3>
            <div class="small text-muted mt-1">Các tùy chọn sẽ hiển thị khi khách hàng chọn món ăn</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex" style="width: 250px;">
                <input type="text" name="search" class="form-control rounded-start" placeholder="Tìm topping..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-secondary rounded-end"><i class="fas fa-search"></i></button>
            </form>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>Thêm Mới
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
                        <th width="100">Hình ảnh</th>
                        <th>Tên Topping / Lựa Chọn</th>
                        <th>Mức Giá</th>
                        <th>Phân loại</th>
                        <th>Trạng thái</th>
                        <th width="120" class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($toppings as $t): ?>
                    <tr>
                        <td class="text-center fw-bold text-muted">#<?= $t['id'] ?></td>
                        <td>
                            <?php if($t['image']): ?>
                                <img src="../../<?= htmlspecialchars($t['image']) ?>" alt="" style="width:60px; height:60px; object-fit:cover; border-radius:8px;">
                            <?php else: ?>
                                <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="width:60px; height:60px; border-radius:8px; font-size:10px;">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($t['name']) ?></h6>
                            <div class="small text-muted text-truncate" style="max-width:250px;"><?= htmlspecialchars($t['description']) ?></div>
                        </td>
                        <td><strong class="text-success"><?= number_format($t['price']) ?>đ</strong></td>
                        <td>
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($t['topping_group']) ?></span>
                            <div class="small mt-1 text-muted">Loại: <strong><?= $t['selection_type'] === 'radio' ? 'Chọn 1' : 'Chọn nhiều' ?></strong></div>
                        </td>
                        <td>
                            <?php if($t['status']): ?>
                                <span class="badge bg-success">Hiển thị</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-success me-1" onclick="openRecipeModal(<?= htmlspecialchars(json_encode($t)) ?>)" title="Định lượng nguyên liệu">
                                <i class="fas fa-balance-scale"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editTopping(<?= htmlspecialchars(json_encode($t)) ?>)" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa topping này? Lưu ý: topping này cũng sẽ bị xóa khỏi các món ăn đang liên kết.');" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($toppings)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có topping nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($total_pages) && $total_pages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Thêm Tùy Chọn Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tên Tùy Chọn</label>
                            <input type="text" name="name" class="form-control" required placeholder="VD: Thêm phô mai, Chín vừa...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Mức Giá (VNĐ)</label>
                            <input type="number" name="price" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Mô tả (tùy chọn)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="VD: Phô mai béo ngậy..."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Nhóm Tùy Chọn</label>
                            <input type="text" name="topping_group" class="form-control" value="Topping thêm" placeholder="VD: Chọn sốt, Độ chín...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Loại Lựa Chọn</label>
                            <select name="selection_type" class="form-select">
                                <option value="checkbox">Nhiều lựa chọn (Checkbox)</option>
                                <option value="radio">Chỉ chọn một (Radio)</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Hình ảnh minh họa</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="status" id="activeAdd" checked>
                                <label class="form-check-label fw-bold" for="activeAdd">Bật (Hiển thị ra ngoài)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
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
                    <h5 class="modal-title fw-bold">Chỉnh Sửa Tùy Chọn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tên Tùy Chọn</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Mức Giá (VNĐ)</label>
                            <input type="number" name="price" id="edit_price" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Mô tả (tùy chọn)</label>
                            <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Nhóm Tùy Chọn</label>
                            <input type="text" name="topping_group" id="edit_group" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Loại Lựa Chọn</label>
                            <select name="selection_type" id="edit_type" class="form-select">
                                <option value="checkbox">Nhiều lựa chọn (Checkbox)</option>
                                <option value="radio">Chỉ chọn một (Radio)</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Hình ảnh (Để trống để giữ nguyên)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="status" id="edit_active">
                                <label class="form-check-label fw-bold" for="edit_active">Bật (Hiển thị ra ngoài)</label>
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

<!-- Modal Recipe -->
<div class="modal fade" id="recipeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">Định Lượng Topping: <span id="recipe_topping_name" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 shadow-sm" style="font-size:13px;">
                    <i class="fas fa-info-circle me-2"></i>Cấu hình nguyên liệu bị trừ khi món ăn có chọn Topping này.
                </div>
                <input type="hidden" id="recipe_topping_id">
                
                <form id="formAddRecipe" class="mb-4 bg-light p-3 rounded border">
                    <h6 class="fw-bold mb-3 small text-uppercase text-muted">Thêm Nguyên Liệu Mới</h6>
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label small fw-bold">Nguyên liệu</label>
                            <select id="recipe_item_id" class="form-select select2-recipe" required>
                                <option value="">-- Chọn nguyên liệu --</option>
                                <?php foreach($inventory_items as $item): ?>
                                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['item_name']) ?> (<?= htmlspecialchars($item['unit_name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label small fw-bold">Số lượng trừ kho</label>
                            <input type="number" step="0.001" min="0.001" id="recipe_qty" class="form-control" required placeholder="VD: 0.05">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fas fa-plus me-1"></i>Thêm</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nguyên liệu</th>
                                <th>Số lượng trừ</th>
                                <th>Đơn vị</th>
                                <th width="80" class="text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="recipe_list_body">
                            <!-- JS load -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>


<script>
function editTopping(t) {
    document.getElementById('edit_id').value = t.id;
    document.getElementById('edit_name').value = t.name;
    document.getElementById('edit_price').value = parseFloat(t.price);
    document.getElementById('edit_desc').value = t.description;
    document.getElementById('edit_group').value = t.topping_group;
    document.getElementById('edit_type').value = t.selection_type;
    document.getElementById('edit_active').checked = (t.status == 1);
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

let recipeModal;
function openRecipeModal(t) {
    document.getElementById('recipe_topping_name').textContent = t.name;
    document.getElementById('recipe_topping_id').value = t.id;
    if(!recipeModal) recipeModal = new bootstrap.Modal(document.getElementById('recipeModal'));
    recipeModal.show();
    loadRecipeList();
}

function loadRecipeList() {
    const topping_id = document.getElementById('recipe_topping_id').value;
    const tbody = document.getElementById('recipe_list_body');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải...</td></tr>';
    
    fetch(`../ajax/ajax_manage_topping_recipe.php?action=get&topping_id=${topping_id}`)
    .then(r => r.json())
    .then(data => {
        tbody.innerHTML = '';
        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Chưa có cấu hình định lượng.</td></tr>';
            return;
        }
        data.forEach(row => {
            tbody.innerHTML += `
                <tr>
                    <td class="fw-bold">${row.item_name}</td>
                    <td class="text-danger fw-bold">${row.quantity_required}</td>
                    <td>${row.unit_name}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecipe(${row.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });
    });
}

document.getElementById('formAddRecipe').addEventListener('submit', function(e) {
    e.preventDefault();
    const topping_id = document.getElementById('recipe_topping_id').value;
    const item_id = document.getElementById('recipe_item_id').value;
    const qty = document.getElementById('recipe_qty').value;
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('topping_id', topping_id);
    formData.append('item_id', item_id);
    formData.append('qty', qty);
    
    fetch('../ajax/ajax_manage_topping_recipe.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if(res.status === 'success') {
            document.getElementById('recipe_item_id').value = '';
            document.getElementById('recipe_qty').value = '';
            loadRecipeList();
        } else {
            alert(res.message);
        }
    });
});

function deleteRecipe(id) {
    if(!confirm('Xóa nguyên liệu này khỏi định lượng Topping?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('../ajax/ajax_manage_topping_recipe.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if(res.status === 'success') loadRecipeList();
        else alert(res.message);
    });
}
</script>

<?php include '../../public/admin_layout_footer.php'; ?>

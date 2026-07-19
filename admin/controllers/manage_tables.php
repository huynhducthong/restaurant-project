<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// Xử lý Thêm Bàn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $table_code = trim($_POST['table_code']);
    $table_number = trim($_POST['table_number']);
    $room_type = trim($_POST['room_type']);
    $category = $_POST['category']; // 'open' or 'room'
    $capacity = (int)$_POST['capacity'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'] ?? 'available';
    $pos_x = (int)($_POST['pos_x'] ?? 0);
    $pos_y = (int)($_POST['pos_y'] ?? 0);

    $stmt = $db->prepare("INSERT INTO restaurant_tables (table_code, table_number, room_type, category, capacity, price, status, is_available, pos_x, pos_y) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
    $stmt->execute([$table_code, $table_number, $room_type, $category, $capacity, $price, $status, $pos_x, $pos_y]);

    $_SESSION['flash_success'] = "Thêm bàn mới thành công!";
    header('Location: manage_tables.php'); exit;
}

// Xử lý Sửa Bàn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $table_code = trim($_POST['table_code']);
    $table_number = trim($_POST['table_number']);
    $room_type = trim($_POST['room_type']);
    $category = $_POST['category'];
    $capacity = (int)$_POST['capacity'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'] ?? 'available';
    $pos_x = (int)($_POST['pos_x'] ?? 0);
    $pos_y = (int)($_POST['pos_y'] ?? 0);

    $stmt = $db->prepare("UPDATE restaurant_tables SET table_code=?, table_number=?, room_type=?, category=?, capacity=?, price=?, status=?, pos_x=?, pos_y=? WHERE id=?");
    $stmt->execute([$table_code, $table_number, $room_type, $category, $capacity, $price, $status, $pos_x, $pos_y, $id]);

    $_SESSION['flash_success'] = "Cập nhật thông tin bàn thành công!";
    header('Location: manage_tables.php'); exit;
}

// Xử lý Xóa Bàn
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $db->prepare("DELETE FROM restaurant_tables WHERE id = ?")->execute([$id]);
    
    $_SESSION['flash_success'] = "Xóa bàn thành công!";
    header('Location: manage_tables.php'); exit;
}

// Lấy danh sách + Tìm kiếm + Phân trang
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where_sql = "";
$params = [];
if ($search !== '') {
    $where_sql = "WHERE table_code LIKE ? OR room_type LIKE ? ";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Đếm tổng số
$total_stmt = $db->prepare("SELECT COUNT(*) FROM restaurant_tables $where_sql");
$total_stmt->execute($params);
$total_items = $total_stmt->fetchColumn();
$total_pages = ceil($total_items / $limit);

// Lấy dữ liệu
$sql = "SELECT * FROM restaurant_tables $where_sql ORDER BY id ASC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../public/admin_layout_header.php';
?>
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold m-0"><i class="fas fa-chair me-2 text-primary"></i>Quản Lý Bàn & Phòng VIP</h3>
            <div class="small text-muted mt-1">Cài đặt phân loại, sức chứa và phụ phí cho các loại bàn</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex" style="width: 250px;">
                <input type="text" name="search" class="form-control rounded-start" placeholder="Tìm mã bàn..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-secondary rounded-end"><i class="fas fa-search"></i></button>
            </form>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>Thêm Bàn
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
                        <th>Mã Bàn</th>
                        <th>Khu Vực</th>
                        <th>Phân Loại</th>
                        <th>Sức Chứa</th>
                        <th>Phụ Phí (VNĐ)</th>
                        <th>Tọa độ (X, Y)</th>
                        <th>Trạng Thái</th>
                        <th width="100" class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tables as $t): ?>
                    <tr>
                        <td class="text-center fw-bold text-muted">#<?= $t['id'] ?></td>
                        <td>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($t['table_code']) ?></h6>
                        </td>
                        <td><?= htmlspecialchars($t['room_type']) ?></td>
                        <td>
                            <?php if($t['category'] == 'room'): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-gem me-1"></i>Phòng VIP</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark">Khu vực chung</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $t['capacity'] ?> người</td>
                        <td><strong class="text-success"><?= number_format($t['price']) ?>đ</strong></td>
                        <td><span class="text-muted small"><?= $t['pos_x'] ?>, <?= $t['pos_y'] ?></span></td>
                        <td>
                            <?php if($t['status'] == 'available'): ?>
                                <span class="badge bg-success">Hoạt động</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($t['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editTable(<?= htmlspecialchars(json_encode($t)) ?>)" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa bàn này?');" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($tables)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Chưa có bàn nào.</td></tr>
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
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Thêm Bàn Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Mã Bàn</label>
                            <input type="text" name="table_code" class="form-control" required placeholder="VD: V5, R7...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Số Bàn (Tùy chọn)</label>
                            <input type="text" name="table_number" class="form-control" placeholder="VD: 5">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Phân Loại</label>
                            <select name="category" class="form-select">
                                <option value="open">Khu vực chung</option>
                                <option value="room">Phòng VIP</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Khu vực</label>
                            <input type="text" name="room_type" class="form-control" value="Khu vực chung" placeholder="VD: Bàn cửa sổ">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Sức Chứa</label>
                            <input type="number" name="capacity" class="form-control" value="2" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Phụ Phí (VNĐ)</label>
                            <input type="number" name="price" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tọa độ X</label>
                            <input type="number" name="pos_x" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tọa độ Y</label>
                            <input type="number" name="pos_y" class="form-control" value="0">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Trạng Thái</label>
                            <select name="status" class="form-select">
                                <option value="available">Hoạt động (Có sẵn)</option>
                                <option value="maintenance">Bảo trì / Ngưng sử dụng</option>
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
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Cập Nhật Bàn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Mã Bàn</label>
                            <input type="text" name="table_code" id="edit_table_code" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Số Bàn</label>
                            <input type="text" name="table_number" id="edit_table_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Phân Loại</label>
                            <select name="category" id="edit_category" class="form-select">
                                <option value="open">Khu vực chung</option>
                                <option value="room">Phòng VIP</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Khu vực</label>
                            <input type="text" name="room_type" id="edit_room_type" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Sức Chứa</label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Phụ Phí (VNĐ)</label>
                            <input type="number" name="price" id="edit_price" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tọa độ X</label>
                            <input type="number" name="pos_x" id="edit_pos_x" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Tọa độ Y</label>
                            <input type="number" name="pos_y" id="edit_pos_y" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">Trạng Thái</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="available">Hoạt động (Có sẵn)</option>
                                <option value="maintenance">Bảo trì / Ngưng sử dụng</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">Lưu cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTable(table) {
    document.getElementById('edit_id').value = table.id;
    document.getElementById('edit_table_code').value = table.table_code;
    document.getElementById('edit_table_number').value = table.table_number;
    document.getElementById('edit_category').value = table.category;
    document.getElementById('edit_room_type').value = table.room_type;
    document.getElementById('edit_capacity').value = table.capacity;
    document.getElementById('edit_price').value = table.price;
    document.getElementById('edit_pos_x').value = table.pos_x;
    document.getElementById('edit_pos_y').value = table.pos_y;
    document.getElementById('edit_status').value = table.status;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include '../../public/admin_layout_footer.php'; ?>

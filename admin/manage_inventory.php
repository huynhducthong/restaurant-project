<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// --- 1. XỬ LÝ PHP (BACKEND) ---

// A. Bộ lọc Thống kê
$f_type = $_GET['f_type'] ?? 'month';
$f_val = $_GET['f_val'] ?? date('Y-m');
$where = ($f_type == 'day') ? "DATE(created_at) = '$f_val'" : (($f_type == 'year') ? "YEAR(created_at) = '$f_val'" : "DATE_FORMAT(created_at, '%Y-%m') = '$f_val'");

$stats = $db->query("SELECT 
    SUM(CASE WHEN type='import' THEN quantity ELSE 0 END) as ti,
    SUM(CASE WHEN type='export' THEN quantity ELSE 0 END) as te,
    SUM(CASE WHEN type='loss' THEN quantity ELSE 0 END) as tl
    FROM inventory_history WHERE $where")->fetch(PDO::FETCH_ASSOC);

// B. Quản lý Nhà Cung Cấp (Thêm/Sửa)
if (isset($_POST['save_supplier'])) {
    $data = [$_POST['s_name'], $_POST['s_phone'], $_POST['s_address'], $_POST['s_email'], $_POST['s_contact']];
    if (!empty($_POST['supplier_id'])) {
        $db->prepare("UPDATE suppliers SET name=?, phone=?, address=?, email=?, contact_person=? WHERE id=?")->execute([...$data, $_POST['supplier_id']]);
    } else {
        $db->prepare("INSERT INTO suppliers (name, phone, address, email, contact_person) VALUES (?, ?, ?, ?, ?)")->execute($data);
    }
    header("Location: manage_inventory.php?tab=suppliers"); exit;
}

// C. Quản lý Tags (Danh mục & Đơn vị)
if (isset($_POST['manage_tag'])) {
    $table = ($_POST['tag_type'] == 'category') ? 'inventory_categories' : 'inventory_units';
    if ($_POST['tag_action'] == 'add') {
        $db->prepare("INSERT IGNORE INTO $table (name) VALUES (?)")->execute([trim($_POST['tag_name'])]);
    } elseif ($_POST['tag_action'] == 'edit') {
        $db->prepare("UPDATE $table SET name = ? WHERE id = ?")->execute([trim($_POST['tag_name']), $_POST['tag_id']]);
    } elseif ($_POST['tag_action'] == 'delete') {
        $db->prepare("DELETE FROM $table WHERE id = ?")->execute([$_POST['tag_id']]);
    }
    header("Location: manage_inventory.php"); exit;
}

// D. Thêm/Sửa Nguyên liệu
if (isset($_POST['save_inventory'])) {
    $data = [trim($_POST['item_name']), $_POST['category'], $_POST['unit_name'], (float)$_POST['cost_price'], $_POST['supplier_id']];
    if (!empty($_POST['item_id'])) {
        $db->prepare("UPDATE inventory SET item_name=?, category=?, unit_name=?, cost_price=?, supplier_id=? WHERE id=?")->execute([...$data, $_POST['item_id']]);
    } else {
        $db->prepare("INSERT INTO inventory (item_name, category, unit_name, cost_price, supplier_id, stock_quantity) VALUES (?, ?, ?, ?, ?, 0)")->execute($data);
    }
    header("Location: manage_inventory.php"); exit;
}

// E. Nhập / Xuất / Hủy (AJAX)
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $db->beginTransaction();
    try {
        $id = $_POST['item_id']; $qty = (float)$_POST['quantity'];
        if ($_POST['action'] == 'import') {
            $price = (float)$_POST['import_price'];
            $db->prepare("UPDATE inventory SET stock_quantity = stock_quantity + ?, cost_price = ?, supplier_id = ?, expiry_date = ? WHERE id = ?")->execute([$qty, $price, $_POST['supplier_id'], $_POST['expiry_date'], $id]);
            $db->prepare("INSERT INTO inventory_history (ingredient_id, type, quantity) VALUES (?, 'import', ?)")->execute([$id, $qty]);
        } else {
            $db->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$qty, $id]);
            $db->prepare("INSERT INTO inventory_history (ingredient_id, type, quantity) VALUES (?, ?, ?)")->execute([$id, $_POST['action'], $qty]);
        }
        $db->commit(); echo json_encode(['status' => 'success']);
    } catch (Exception $e) { $db->rollBack(); echo json_encode(['status' => 'error']); }
    exit;
}

// F. Xóa nguyên liệu
if (isset($_GET['delete_id'])) {
    $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: manage_inventory.php"); exit;
}

// --- 2. TRUY VẤN DỮ LIỆU ---
$top_used = $db->query("SELECT i.item_name, SUM(h.quantity) as total, i.unit_name FROM inventory_history h JOIN inventory i ON h.ingredient_id = i.id WHERE h.type = 'export' GROUP BY i.id ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$cats = $db->query("SELECT * FROM inventory_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$units = $db->query("SELECT * FROM inventory_units ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $db->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$inv = $db->query("SELECT i.*, s.name as s_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.id ORDER BY i.item_name ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../public/admin_layout_header.php';
?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-warning fw-bold small">DÙNG NHIỀU NHẤT</div>
                <ul class="list-group list-group-flush">
                    <?php foreach($top_used as $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center small">
                        <?= htmlspecialchars($t['item_name']) ?> <span class="badge bg-warning text-dark"><?= (float)$t['total'] ?> <?= $t['unit_name'] ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="card p-3 shadow-sm border-0 mb-4">
                <div class="fw-bold mb-2 small text-muted text-uppercase">Thống kê kho</div>
                <div class="small mb-1 text-success">Tổng Nhập: <b><?= number_format($stats['ti'] ?? 0, 1) ?></b></div>
                <div class="small mb-1 text-primary">Tổng Xuất: <b><?= number_format($stats['te'] ?? 0, 1) ?></b></div>
                <div class="small mb-3 text-danger">Tổng Hủy: <b><?= number_format($stats['tl'] ?? 0, 1) ?></b></div>
                
                <form method="GET" class="row g-1">
                    <div class="col-6"><select name="f_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="day" <?= $f_type=='day'?'selected':'' ?>>Ngày</option>
                        <option value="month" <?= $f_type=='month'?'selected':'' ?>>Tháng</option>
                        <option value="year" <?= $f_type=='year'?'selected':'' ?>>Năm</option>
                    </select></div>
                    <div class="col-6"><input type="<?= $f_type=='day'?'date':($f_type=='year'?'number':'month') ?>" name="f_val" class="form-control form-control-sm" value="<?= $f_val ?>" onchange="this.form.submit()"></div>
                </form>
            </div>

            <div class="card p-2 shadow-sm border-0">
                <button class="btn btn-primary btn-sm w-100 mb-2 py-2 fw-bold" onclick="switchTab('suppliers')"><i class="fas fa-truck me-2"></i> NHÀ CUNG CẤP</button>
                <button class="btn btn-outline-dark btn-sm w-100 mb-2 py-2 fw-bold" onclick="switchTab('stock')"><i class="fas fa-boxes me-2"></i> TỒN KHO & HSD</button>
                <hr class="my-2">
                <button class="btn btn-light btn-sm w-100 mb-2 text-start" onclick="openTagManager('category')"><i class="fas fa-tags me-2"></i> Danh Mục</button>
                <button class="btn btn-light btn-sm w-100 text-start" onclick="openTagManager('unit')"><i class="fas fa-balance-scale me-2"></i> Đơn Vị</button>
            </div>
        </div>

        <div class="col-lg-9 tab-content">
            <div class="tab-pane fade show active" id="tab-stock">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-uppercase">Quản Lý Kho Chi Tiết</h4>
                    <button class="btn btn-warning fw-bold shadow-sm" onclick="openInventoryModal()">+ Thêm Nguyên Liệu</button>
                </div>
                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table align-middle mb-0 table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th>Nhà Cung Cấp</th> <th>Tồn Kho</th>
                                <th>Giá Vốn</th>
                                <th class="text-end">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inv as $i): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($i['item_name']) ?></strong></td>
                                <td class="text-primary fw-medium">
                                    <?= htmlspecialchars($i['s_name'] ?? 'Chưa gán NCC') ?>
                                </td>
                                <td><span class="fs-5 fw-bold"><?= (float)$i['stock_quantity'] ?></span> <?= $i['unit_name'] ?></td>
                                <td class="text-success fw-bold"><?= number_format($i['cost_price']) ?>đ</td>
                                <td class="text-end">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-sm btn-outline-success" onclick="openImport(<?= $i['id'] ?>, '<?= $i['item_name'] ?>', '<?= $i['unit_name'] ?>')">Nhập</button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openExport(<?= $i['id'] ?>, '<?= $i['item_name'] ?>', 'export')">Xuất</button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="openExport(<?= $i['id'] ?>, '<?= $i['item_name'] ?>', 'loss')">Hủy</button>
                                        <button class="btn btn-sm btn-light border" onclick='openEdit(<?= json_encode($i) ?>)'><i class="fas fa-edit"></i></button>
                                        <a href="?delete_id=<?= $i['id'] ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Xóa nguyên liệu?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-suppliers">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-uppercase text-primary">Danh Sách Nhà Cung Cấp</h4>
                    <button class="btn btn-dark fw-bold" onclick="openSupplierModal()">+ Thêm NCC Mới</button>
                </div>
                <div class="row g-3">
                    <?php foreach($suppliers as $s): ?>
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 p-3 h-100">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold text-dark m-0"><i class="fas fa-building text-primary me-2"></i><?= htmlspecialchars($s['name']) ?></h6>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light border-0" onclick='openEditSupplier(<?= json_encode($s) ?>)'><i class="fas fa-edit text-primary"></i></button>
                                    <a href="?delete_supplier=<?= $s['id'] ?>" class="btn btn-sm btn-light border-0 text-danger" onclick="return confirm('Xóa NCC?')"><i class="fas fa-trash text-danger"></i></a>
                                </div>
                            </div>
                            <hr class="my-2 opacity-25">
                            <div class="small"><b>Người liên hệ:</b> <?= htmlspecialchars($s['contact_person'] ?? 'Chưa cập nhật') ?></div>
                            <div class="small text-muted"><i class="fas fa-phone-alt me-2"></i><?= htmlspecialchars($s['phone']) ?></div>
                            <div class="small text-muted"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($s['email'] ?? '---') ?></div>
                            <div class="small text-muted text-truncate"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($s['address']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInventory" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content border-0 shadow" method="POST">
            <input type="hidden" name="save_inventory" value="1">
            <input type="hidden" name="item_id" id="inv-id">
            <div class="modal-header bg-warning"><h5>Nguyên Liệu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="small fw-bold">Tên nguyên liệu</label><input type="text" name="item_name" id="inv-name" class="form-control" required></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-bold">Danh mục</label><select name="category" id="inv-cat" class="form-select"><?php foreach($cats as $c) echo "<option value='{$c['name']}'>{$c['name']}</option>"; ?></select></div>
                    <div class="col-6"><label class="small fw-bold">Đơn vị</label><select name="unit_name" id="inv-unit" class="form-select"><?php foreach($units as $u) echo "<option value='{$u['name']}'>{$u['name']}</option>"; ?></select></div>
                </div>
                <div class="mb-3"><label class="small fw-bold">Nhà cung cấp mặc định</label><select name="supplier_id" id="inv-sup" class="form-select"><option value="">-- Chọn NCC --</option><?php foreach($suppliers as $s) echo "<option value='{$s['id']}'>{$s['name']}</option>"; ?></select></div>
                <div class="mb-0"><label class="small fw-bold">Giá vốn mặc định (đ)</label><input type="number" name="cost_price" id="inv-price" class="form-control" value="0"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100 fw-bold">XÁC NHẬN LƯU</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-import">
            <input type="hidden" name="action" value="import">
            <input type="hidden" name="item_id" id="imp-id">
            <div class="modal-header bg-success text-white"><h5>Nhập: <span id="imp-name"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="fw-bold small">Chọn NCC nhập hàng</label><select name="supplier_id" class="form-select" required><?php foreach($suppliers as $s) echo "<option value='{$s['id']}'>{$s['name']}</option>"; ?></select></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="fw-bold small">Số lượng (<span id="imp-unit"></span>)</label><input type="number" name="quantity" step="0.01" class="form-control" required></div>
                    <div class="col-6"><label class="fw-bold small">Giá vốn nhập (đ)</label><input type="number" name="import_price" class="form-control" required></div>
                </div>
                <div class="mb-0"><label class="fw-bold small">Hạn sử dụng</label><input type="date" name="expiry_date" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success w-100 fw-bold">XÁC NHẬN NHẬP</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalExport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-export">
            <input type="hidden" name="action" id="exp-action">
            <input type="hidden" name="item_id" id="exp-id">
            <div class="modal-header" id="exp-hdr"><h5>Xử lý</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 text-center">
                <label class="fw-bold mb-2">Số lượng <span id="exp-type-txt"></span></label>
                <input type="number" name="quantity" step="0.01" class="form-control form-control-lg text-center" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn w-100 fw-bold text-white" id="exp-btn">XÁC NHẬN</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalTags" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-secondary text-white"><h5 id="tagTitle">Quản Lý</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <form method="POST" class="d-flex gap-2 mb-3"><input type="hidden" name="manage_tag" value="1"><input type="hidden" name="tag_type" id="tagTypeInput"><input type="hidden" name="tag_action" value="add"><input type="text" name="tag_name" class="form-control form-control-sm" placeholder="Tên mới..." required><button type="submit" class="btn btn-primary btn-sm">Thêm</button></form>
                <div id="tagList" class="list-group list-group-flush border-top"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSupplier" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST">
            <input type="hidden" name="save_supplier" value="1">
            <input type="hidden" name="supplier_id" id="s-id">
            <div class="modal-header bg-dark text-white"><h5>Nhà Cung Cấp</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="small fw-bold">Tên NCC/Công ty *</label><input type="text" name="s_name" id="s-name" class="form-control" required></div>
                <div class="mb-3"><label class="small fw-bold">Người liên hệ đại diện</label><input type="text" name="s_contact" id="s-contact" class="form-control" placeholder="Họ và tên..."></div>
                <div class="row g-2 mb-3"><div class="col-6"><label class="small fw-bold">SĐT</label><input type="text" name="s_phone" id="s-phone" class="form-control"></div><div class="col-6"><label class="small fw-bold">Email</label><input type="email" name="s_email" id="s-email" class="form-control"></div></div>
                <div class="mb-0"><label class="small fw-bold">Địa chỉ</label><textarea name="s_address" id="s-address" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary w-100 fw-bold">LƯU NCC</button></div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const categories = <?= json_encode($cats) ?>;
const units = <?= json_encode($units) ?>;

function switchTab(tabId) {
    $('.tab-pane').removeClass('show active'); $('#tab-' + tabId).addClass('show active');
}

function openInventoryModal() {
    $('#inv-id').val(''); $('#inv-title').text('Thêm Nguyên Liệu'); $('#inv-name, #inv-price, #inv-sup').val('');
    new bootstrap.Modal(document.getElementById('modalInventory')).show();
}

function openEdit(data) {
    $('#inv-id').val(data.id); $('#inv-title').text('Cập Nhật Nguyên Liệu'); 
    $('#inv-name').val(data.item_name); $('#inv-cat').val(data.category); 
    $('#inv-unit').val(data.unit_name); $('#inv-price').val(data.cost_price); 
    $('#inv-sup').val(data.supplier_id);
    new bootstrap.Modal(document.getElementById('modalInventory')).show();
}

function openSupplierModal() {
    $('#s-id').val(''); $('#s-name, #s-contact, #s-phone, #s-email, #s-address').val('');
    new bootstrap.Modal(document.getElementById('modalSupplier')).show();
}

function openEditSupplier(data) {
    $('#s-id').val(data.id); $('#s-name').val(data.name); $('#s-contact').val(data.contact_person); 
    $('#s-phone').val(data.phone); $('#s-email').val(data.email); $('#s-address').val(data.address);
    new bootstrap.Modal(document.getElementById('modalSupplier')).show();
}

function openTagManager(type) {
    const data = (type === 'category') ? categories : units;
    $('#tagTitle').text(type === 'category' ? 'Quản lý Danh mục' : 'Quản lý Đơn vị');
    $('#tagTypeInput').val(type);
    let html = '';
    data.forEach(i => {
        html += `<div class="list-group-item d-flex justify-content-between align-items-center"><span>${i.name}</span><div class="btn-group"><button class="btn btn-sm btn-outline-primary" onclick="editTag(${i.id}, '${i.name}', '${type}')"><i class="fas fa-edit"></i></button><form method="POST" style="display:inline"><input type="hidden" name="manage_tag" value="1"><input type="hidden" name="tag_type" value="${type}"><input type="hidden" name="tag_action" value="delete"><input type="hidden" name="tag_id" value="${i.id}"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa?')"><i class="fas fa-trash"></i></button></form></div></div>`;
    });
    $('#tagList').html(html);
    new bootstrap.Modal(document.getElementById('modalTags')).show();
}

function editTag(id, oldName, type) {
    const newName = prompt("Nhập tên mới:", oldName);
    if (newName && newName !== oldName) { $('<form method="POST"><input type="hidden" name="manage_tag" value="1"><input type="hidden" name="tag_type" value="'+type+'"><input type="hidden" name="tag_action" value="edit"><input type="hidden" name="tag_id" value="'+id+'"><input type="hidden" name="tag_name" value="'+newName+'"></form>').appendTo('body').submit(); }
}

function openImport(id, name, unit) {
    $('#imp-id').val(id); $('#imp-name').text(name); $('#imp-unit').text(unit);
    new bootstrap.Modal(document.getElementById('modalImport')).show();
}

function openExport(id, name, type) {
    $('#exp-id').val(id); $('#exp-action').val(type);
    $('#exp-hdr').attr('class', 'modal-header ' + (type === 'export' ? 'bg-primary' : 'bg-danger') + ' text-white');
    $('#exp-hdr h5').text((type === 'export' ? 'Xuất kho: ' : 'Hủy hàng: ') + name);
    $('#exp-type-txt').text(type === 'export' ? 'xuất món' : 'hủy bỏ');
    $('#exp-btn').attr('class', 'btn ' + (type === 'export' ? 'btn-primary' : 'btn-danger') + ' w-100 fw-bold text-white');
    new bootstrap.Modal(document.getElementById('modalExport')).show();
}

$(document).on('submit', '#form-import, #form-export', function(e) {
    e.preventDefault();
    $.post('manage_inventory.php', $(this).serialize(), function(r) { 
        if(r.status === 'success') location.reload(); 
        else alert('Lỗi: ' + (r.msg || 'Không thể xử lý'));
    }, 'json');
});
</script>
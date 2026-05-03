<?php
// =============================================================
// File: admin/controllers/ComboController.php
// Route: ?action=list|add|edit|delete|toggle
// Thay thế: list_combos.php + add_combo.php + edit_combo.php
//           + processes/delete_combo.php + processes/process_combo.php
//           + processes/process_edit_combo.php
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

$action = $_GET['action'] ?? 'list';

// ============================================================
// HELPER: Validate & upload ảnh combo
// ============================================================
function validateComboImage(array &$errors): array {
    $result = ['file_name' => '', 'ok' => false];
    if (empty($_FILES['image']['name'])) return $result;

    $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
    $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $tmp_path = $_FILES['image']['tmp_name'];
    $size     = $_FILES['image']['size'];

    if (!in_array($ext, $allowed_ext)) {
        $errors[] = 'Ảnh combo chỉ chấp nhận: JPG, PNG, WEBP.';
        return $result;
    }
    if ($size > 5 * 1024 * 1024) {
        $errors[] = 'Ảnh quá lớn. Tối đa 5MB.';
        return $result;
    }
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp_path);
        finfo_close($finfo);
        if (!in_array($mime, $allowed_mime)) {
            $errors[] = 'File không phải ảnh hợp lệ.';
            return $result;
        }
    }
    $result['file_name'] = bin2hex(random_bytes(12)) . '.' . $ext;
    $result['ok']        = true;
    return $result;
}

function saveComboItems(PDO $db, int $combo_id): void {
    $db->prepare("DELETE FROM combo_items WHERE combo_id = ?")->execute([$combo_id]);
    if (empty($_POST['food_ids'])) return;
    $stmt = $db->prepare("INSERT INTO combo_items (combo_id, food_id) VALUES (?, ?)");
    foreach ($_POST['food_ids'] as $food_id) {
        $food_id = (int)$food_id;
        if ($food_id > 0) $stmt->execute([$combo_id, $food_id]);
    }
}

// ============================================================
// AJAX: Toggle bật / tắt combo
// ============================================================
if ($action === 'toggle' && isset($_POST['combo_id'])) {
    header('Content-Type: application/json');
    $cid = (int)$_POST['combo_id'];
    $db->prepare("UPDATE combos SET is_active = NOT is_active WHERE id = ?")->execute([$cid]);
    $s = $db->prepare("SELECT is_active FROM combos WHERE id = ?");
    $s->execute([$cid]);
    echo json_encode(['status' => 'success', 'is_active' => (int)$s->fetchColumn()]);
    exit;
}

// ============================================================
// XÓA combo
// ============================================================
if ($action === 'delete' && isset($_POST['delete_combo_id'])) {
    $del_id = (int)$_POST['delete_combo_id'];

    $img_s = $db->prepare("SELECT image FROM combos WHERE id = ?");
    $img_s->execute([$del_id]);
    $del_img = $img_s->fetchColumn();

    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM combo_items WHERE combo_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM combos WHERE id = ?")->execute([$del_id]);
        $db->commit();
        if ($del_img) {
            $img_path = __DIR__ . '/../../public/assets/img/combos/' . $del_img;
            if (file_exists($img_path)) @unlink($img_path);
        }
        header("Location: ComboController.php?action=list&msg=deleted"); exit;
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: ComboController.php?action=list&error=delete_failed"); exit;
    }
}

// ============================================================
// THÊM COMBO (GET = form | POST = lưu)
// ============================================================
if ($action === 'add') {
    $errors  = [];
    $success = false;
    $old     = ['name' => '', 'price' => '', 'description' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old['name']        = trim($_POST['name']        ?? '');
        $old['price']       = trim($_POST['price']       ?? '');
        $old['description'] = trim($_POST['description'] ?? '');

        if ($old['name'] === '')
            $errors[] = 'Tên combo không được để trống.';
        $price_val = (float)$old['price'];
        if ($old['price'] === '' || $price_val < 0)
            $errors[] = 'Giá combo phải là số không âm.';

        $img = validateComboImage($errors);

        if (empty($errors)) {
            $file_name = '';
            if ($img['ok']) {
                $target = __DIR__ . '/../../public/assets/img/combos/' . $img['file_name'];
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $errors[] = 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục.';
                } else {
                    $file_name = $img['file_name'];
                }
            }
            if (empty($errors)) {
                $db->beginTransaction();
                try {
                    $db->prepare(
                        "INSERT INTO combos (name, price, description, image, is_active) VALUES (?, ?, ?, ?, 1)"
                    )->execute([$old['name'], $price_val, $old['description'], $file_name]);
                    saveComboItems($db, (int)$db->lastInsertId());
                    $db->commit();
                    $success = true;
                    $old     = ['name' => '', 'price' => '', 'description' => ''];
                } catch (Exception $e) {
                    $db->rollBack();
                    if ($file_name) @unlink($target);
                    $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }

    $all_foods      = $db->query("SELECT id, name, price FROM foods WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $selected_foods = [];

    include '../../public/admin_layout_header.php';
    $mode = 'add'; // ✅ FIX: set mode trước khi include view
    require_once __DIR__ . '/../views/combo/formcombo.php';
    exit;
}

// ============================================================
// SỬA COMBO (GET = form điền sẵn | POST = lưu)
// ============================================================
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? $_POST['combo_id'] ?? 0);
    if ($id <= 0) { header("Location: ComboController.php?action=list&error=notfound"); exit; }

    $stmt = $db->prepare("SELECT * FROM combos WHERE id = ?");
    $stmt->execute([$id]);
    $combo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$combo) { header("Location: ComboController.php?action=list&error=notfound"); exit; }

    $sel_stmt = $db->prepare("SELECT food_id FROM combo_items WHERE combo_id = ?");
    $sel_stmt->execute([$id]);
    $selected_foods = $sel_stmt->fetchAll(PDO::FETCH_COLUMN);

    $errors  = [];
    $success = false;
    $old     = [
        'name'        => $combo['name'],
        'price'       => $combo['price'],
        'description' => $combo['description'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old['name']        = trim($_POST['name']        ?? '');
        $old['price']       = trim($_POST['price']       ?? '');
        $old['description'] = trim($_POST['description'] ?? '');

        if ($old['name'] === '')
            $errors[] = 'Tên combo không được để trống.';
        $price_val = (float)$old['price'];
        if ($old['price'] === '' || $price_val < 0)
            $errors[] = 'Giá combo phải là số không âm.';

        $img         = validateComboImage($errors);
        $final_image = $combo['image'];

        // Xóa ảnh nếu tick checkbox delete_image
        if (!empty($_POST['delete_image']) && $combo['image']) {
            $old_path = __DIR__ . '/../../public/assets/img/combos/' . $combo['image'];
            if (file_exists($old_path)) @unlink($old_path);
            $final_image = '';
        }

        if (empty($errors)) {
            if ($img['ok']) {
                $target = __DIR__ . '/../../public/assets/img/combos/' . $img['file_name'];
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $errors[] = 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục.';
                } else {
                    // Xóa ảnh cũ sau khi upload ảnh mới thành công
                    if ($combo['image'] && $combo['image'] !== $final_image) {
                        $old_img = __DIR__ . '/../../public/assets/img/combos/' . $combo['image'];
                        if (file_exists($old_img)) @unlink($old_img);
                    }
                    $final_image = $img['file_name'];
                }
            }

            if (empty($errors)) {
                $db->beginTransaction();
                try {
                    $db->prepare(
                        "UPDATE combos SET name=?, price=?, description=?, image=? WHERE id=?"
                    )->execute([$old['name'], $price_val, $old['description'], $final_image, $id]);
                    saveComboItems($db, $id);
                    $db->commit();
                    $success = true;

                    // Reload
                    $stmt->execute([$id]);
                    $combo = $stmt->fetch(PDO::FETCH_ASSOC);
                    $sel_stmt->execute([$id]);
                    $selected_foods = $sel_stmt->fetchAll(PDO::FETCH_COLUMN);
                    $old = [
                        'name'        => $combo['name'],
                        'price'       => $combo['price'],
                        'description' => $combo['description'],
                    ];
                } catch (Exception $e) {
                    $db->rollBack();
                    if ($img['ok'] && isset($target) && file_exists($target)) @unlink($target);
                    $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }

    $all_foods = $db->query("SELECT id, name, price FROM foods WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    // Danh sách combo để chuyển nhanh
    $all_combos_list = $db->query("SELECT id, name FROM combos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

    include '../../public/admin_layout_header.php';
    $mode = 'edit'; // ✅ FIX: set mode trước khi include view
    require_once __DIR__ . '/../views/combo/formcombo.php';
    exit;
}

// ============================================================
// DANH SÁCH (action=list — default)
// ============================================================
$delete_error = '';
if (isset($_GET['error'])) {
    $err_map = [
        'delete_failed' => 'Lỗi khi xóa. Vui lòng thử lại.',
        'notfound'      => 'Không tìm thấy combo.',
    ];
    $delete_error = $err_map[$_GET['error']] ?? '';
}

$search      = trim($_GET['q'] ?? '');
$show_hidden = isset($_GET['show_hidden']);
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;

$where_parts = [];
$params      = [];
if ($search !== '')  { $where_parts[] = "c.name LIKE ?"; $params[] = '%' . $search . '%'; }
if (!$show_hidden)   { $where_parts[] = "c.is_active = 1"; }
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$cnt = $db->prepare("SELECT COUNT(*) FROM combos c $where_sql");
$cnt->execute($params);
$total       = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$stmt = $db->prepare(
    "SELECT c.*,
        GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR '||') as list_foods,
        COUNT(ci.food_id)   as food_count,
        SUM(f.price)        as total_food_price
     FROM combos c
     LEFT JOIN combo_items ci ON c.id = ci.combo_id
     LEFT JOIN foods f        ON ci.food_id = f.id
     $where_sql
     GROUP BY c.id
     ORDER BY c.id DESC
     LIMIT $per_page OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hidden_count = (int)$db->query("SELECT COUNT(*) FROM combos WHERE is_active = 0")->fetchColumn();
$msg          = $_GET['msg'] ?? '';

function buildUrl(array $override = []): string {
    $base = array_merge(['action' => 'list', 'q' => $_GET['q'] ?? '', 'page' => 1], $override);
    foreach ($base as $k => $v) { if ($v === '' || $v === null) unset($base[$k]); }
    return 'ComboController.php?' . http_build_query($base);
}

include '../../public/admin_layout_header.php';
require_once __DIR__ . '/../views/combo/listcombo.php';
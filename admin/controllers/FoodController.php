<?php
// =============================================================
// File: admin/controllers/FoodController.php
// Route: ?action=list|add|edit|delete|toggle
// Thay thế: manage_foods.php + add_food.php + edit_food.php
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();
date_default_timezone_set('Asia/Ho_Chi_Minh');

$action       = $_GET['action'] ?? 'list';
$current_user = $_SESSION['username'] ?? 'Admin';

// ============================================================
// HELPER: Validate & upload ảnh — dùng chung add + edit
// ============================================================
function validateImage(array &$errors): array {
    $result = ['file_name' => '', 'ok' => false];
    if (empty($_FILES['image']['name'])) return $result;

    $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $tmp_path = $_FILES['image']['tmp_name'];
    $size     = $_FILES['image']['size'];

    if (!in_array($ext, $allowed_ext)) {
        $errors[] = 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận: JPG, PNG, WEBP, GIF.';
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
            $errors[] = 'File không phải ảnh hợp lệ. Vui lòng chọn lại.';
            return $result;
        }
    }
    $result['file_name'] = bin2hex(random_bytes(12)) . '.' . $ext;
    $result['ok']        = true;
    return $result;
}

function saveRecipes(PDO $db, int $food_id): void {
    $db->prepare("DELETE FROM food_recipes WHERE food_id = ?")->execute([$food_id]);
    if (empty($_POST['ingredients'])) return;
    $stmt = $db->prepare(
        "INSERT INTO food_recipes (food_id, ingredient_id, quantity_required, unit)
         VALUES (?, ?, ?, ?)"
    );
    foreach ($_POST['ingredients'] as $idx => $ing_id) {
        if (empty($ing_id)) continue;
        $qty  = (float)($_POST['quantities'][$idx] ?? 0);
        $unit = trim($_POST['units'][$idx] ?? '');
        if ($qty > 0 && $unit !== '') {
            $stmt->execute([$food_id, (int)$ing_id, $qty, $unit]);
        }
    }
}

// ============================================================
// AJAX: Toggle ẩn / hiện món
// ============================================================
if ($action === 'toggle' && isset($_POST['food_id'])) {
    header('Content-Type: application/json');
    $fid = (int)$_POST['food_id'];
    $db->prepare("UPDATE foods SET is_active = NOT is_active WHERE id = ?")->execute([$fid]);
    $s = $db->prepare("SELECT is_active FROM foods WHERE id = ?");
    $s->execute([$fid]);
    echo json_encode(['status' => 'success', 'is_active' => (int)$s->fetchColumn()]);
    exit;
}

// ============================================================
// XÓA món ăn
// ============================================================
if ($action === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $chk    = $db->prepare("SELECT COUNT(*) FROM order_items WHERE food_id = ?");
    $chk->execute([$del_id]);

    if ((int)$chk->fetchColumn() > 0) {
        header("Location: FoodController.php?action=list&error=in_order"); exit;
    }

    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM combo_items  WHERE food_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM food_recipes WHERE food_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM foods         WHERE id = ?")    ->execute([$del_id]);
        $db->commit();
        header("Location: FoodController.php?action=list&msg=deleted"); exit;
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: FoodController.php?action=list&error=delete_failed"); exit;
    }
}

// ============================================================
// THÊM MÓN (GET = form rỗng | POST = lưu)
// ============================================================
if ($action === 'add') {
    $errors  = [];
    $success = false;
    $old     = ['name' => '', 'category_id' => '', 'price' => '', 'description' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old['name']        = trim($_POST['name']        ?? '');
        $old['category_id'] = trim($_POST['category_id'] ?? '');
        $old['price']       = trim($_POST['price']       ?? '');
        $old['description'] = trim($_POST['description'] ?? '');

        if ($old['name'] === '')
            $errors[] = 'Tên món ăn không được để trống.';
        elseif (mb_strlen($old['name']) > 150)
            $errors[] = 'Tên món ăn tối đa 150 ký tự.';
        if ($old['category_id'] === '')
            $errors[] = 'Vui lòng chọn danh mục.';

        $price_val = (float)$old['price'];
        if ($old['price'] === '' || $price_val < 0)
            $errors[] = 'Giá bán phải là số không âm.';

        if (empty($_FILES['image']['name']))
            $errors[] = 'Vui lòng chọn ảnh cho món ăn.';

        $img = validateImage($errors);

        if (empty($errors)) {
            $target = __DIR__ . '/../../public/assets/img/menu/' . $img['file_name'];
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $errors[] = 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục.';
            } else {
                $db->beginTransaction();
                try {
                    $db->prepare(
                        "INSERT INTO foods (name, category_id, price, description, image, is_active)
                         VALUES (?, ?, ?, ?, ?, 1)"
                    )->execute([
                        $old['name'], (int)$old['category_id'],
                        $price_val, $old['description'], $img['file_name'],
                    ]);
                    saveRecipes($db, (int)$db->lastInsertId());
                    $db->commit();
                    $success = true;
                    $old     = ['name' => '', 'category_id' => '', 'price' => '', 'description' => ''];
                } catch (Exception $e) {
                    $db->rollBack();
                    @unlink($target);
                    $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }

    // Dữ liệu cho view
    $categories  = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_units   = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

    require_once __DIR__ . '/../views/food/formfood.php';
    exit;
}

// ============================================================
// SỬA MÓN (GET = form điền sẵn | POST = lưu)
// ============================================================
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? $_POST['food_id'] ?? 0);
    if ($id <= 0) { header("Location: FoodController.php?action=list&error=notfound"); exit; }

    $stmt = $db->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([$id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$food) { header("Location: FoodController.php?action=list&error=notfound"); exit; }

    $recipe_stmt = $db->prepare(
        "SELECT r.*, i.item_name FROM food_recipes r
         JOIN inventory i ON r.ingredient_id = i.id
         WHERE r.food_id = ? ORDER BY i.item_name"
    );
    $recipe_stmt->execute([$id]);
    $current_recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);

    $errors  = [];
    $success = false;
    $old     = [
        'name'        => $food['name'],
        'category_id' => $food['category_id'],
        'price'       => $food['price'],
        'description' => $food['description'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old['name']        = trim($_POST['name']        ?? '');
        $old['category_id'] = trim($_POST['category_id'] ?? '');
        $old['price']       = trim($_POST['price']       ?? '');
        $old['description'] = trim($_POST['description'] ?? '');

        if ($old['name'] === '')
            $errors[] = 'Tên món ăn không được để trống.';
        elseif (mb_strlen($old['name']) > 150)
            $errors[] = 'Tên món ăn tối đa 150 ký tự.';
        if ($old['category_id'] === '')
            $errors[] = 'Vui lòng chọn danh mục.';

        $price_val = (float)$old['price'];
        if ($old['price'] === '' || $price_val < 0)
            $errors[] = 'Giá bán phải là số không âm.';

        $img          = validateImage($errors);
        $final_image  = $food['image'];

        if (empty($errors)) {
            if ($img['ok']) {
                $target = __DIR__ . '/../../public/assets/img/menu/' . $img['file_name'];
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $errors[] = 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục.';
                } else {
                    $old_path = __DIR__ . '/../../public/assets/img/menu/' . $food['image'];
                    if ($food['image'] && file_exists($old_path)) @unlink($old_path);
                    $final_image = $img['file_name'];
                }
            }

            if (empty($errors)) {
                $db->beginTransaction();
                try {
                    $db->prepare(
                        "UPDATE foods SET name=?, category_id=?, price=?, description=?, image=? WHERE id=?"
                    )->execute([
                        $old['name'], (int)$old['category_id'],
                        $price_val, $old['description'], $final_image, $id,
                    ]);
                    saveRecipes($db, $id);
                    $db->commit();
                    $success = true;

                    // Reload sau khi lưu
                    $stmt->execute([$id]);
                    $food = $stmt->fetch(PDO::FETCH_ASSOC);
                    $recipe_stmt->execute([$id]);
                    $current_recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);
                    $old = [
                        'name'        => $food['name'],
                        'category_id' => $food['category_id'],
                        'price'       => $food['price'],
                        'description' => $food['description'],
                    ];
                } catch (Exception $e) {
                    $db->rollBack();
                    if ($img['ok'] && isset($target) && file_exists($target)) @unlink($target);
                    $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }

    // Dữ liệu cho view
    $categories  = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_units   = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

    require_once __DIR__ . '/../views/food/formfood.php';
    exit;
}

// ============================================================
// DANH SÁCH (action=list — default)
// ============================================================
$delete_error = '';
if (isset($_GET['error'])) {
    $err_map = [
        'in_order'      => "Không thể xóa! Món này đã xuất hiện trong đơn hàng. Hãy dùng nút <strong>Ẩn món</strong> thay vì xóa.",
        'delete_failed' => 'Lỗi khi xóa. Vui lòng thử lại.',
        'notfound'      => 'Không tìm thấy món ăn.',
    ];
    $delete_error = $err_map[$_GET['error']] ?? '';
}

// Filter / Search / Sort / Phân trang
$filter      = $_GET['filter'] ?? 'all';
$sort        = $_GET['sort']   ?? 'newest';
$search      = trim($_GET['q'] ?? '');
$show_hidden = isset($_GET['show_hidden']);
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 12;

$where_parts = [];
$params      = [];
if ($filter !== 'all')  { $where_parts[] = "c.name = ?"; $params[] = $filter; }
if ($search !== '')     { $where_parts[] = "f.name LIKE ?"; $params[] = '%' . $search . '%'; }
if (!$show_hidden)      { $where_parts[] = "f.is_active = 1"; }
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$sort_map  = [
    'newest' => 'f.id DESC', 'oldest' => 'f.id ASC',
    'name_asc' => 'f.name ASC', 'name_desc' => 'f.name DESC',
    'price_asc' => 'f.price ASC', 'price_desc' => 'f.price DESC',
];
$order_sql = 'ORDER BY ' . ($sort_map[$sort] ?? 'f.id DESC');

$cnt = $db->prepare("SELECT COUNT(*) FROM foods f LEFT JOIN categories c ON f.category_id = c.id $where_sql");
$cnt->execute($params);
$total       = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$food_stmt = $db->prepare(
    "SELECT f.*, c.name as category_name
     FROM foods f LEFT JOIN categories c ON f.category_id = c.id
     $where_sql $order_sql LIMIT $per_page OFFSET $offset"
);
$food_stmt->execute($params);
$foods = $food_stmt->fetchAll(PDO::FETCH_ASSOC);

// Bulk N+1 fix: combo + recipe + cost
$combo_map = $recipe_map = $cost_map = [];
if (!empty($foods)) {
    $food_ids     = array_column($foods, 'id');
    $placeholders = implode(',', array_fill(0, count($food_ids), '?'));

    $cs = $db->prepare("SELECT food_id, COUNT(*) as cnt FROM combo_items WHERE food_id IN ($placeholders) GROUP BY food_id");
    $cs->execute($food_ids);
    foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $r) $combo_map[(int)$r['food_id']] = (int)$r['cnt'];

    $rs = $db->prepare(
        "SELECT r.food_id, r.quantity_required, r.unit, i.item_name
         FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id
         WHERE r.food_id IN ($placeholders) ORDER BY i.item_name"
    );
    $rs->execute($food_ids);
    foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $r) $recipe_map[(int)$r['food_id']][] = $r;

    $cst = $db->prepare(
        "SELECT r.food_id, SUM(r.quantity_required * i.cost_price) as total_cost
         FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id
         WHERE r.food_id IN ($placeholders) GROUP BY r.food_id"
    );
    $cst->execute($food_ids);
    foreach ($cst->fetchAll(PDO::FETCH_ASSOC) as $r) $cost_map[(int)$r['food_id']] = (float)$r['total_cost'];
}

$hidden_count = (int)$db->query("SELECT COUNT(*) FROM foods WHERE is_active = 0")->fetchColumn();
$cats         = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_units    = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$ingredients  = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$msg          = $_GET['msg'] ?? '';

function buildUrl(array $override = []): string {
    $base = array_merge([
        'action' => 'list',
        'filter' => $_GET['filter'] ?? 'all',
        'sort'   => $_GET['sort']   ?? 'newest',
        'q'      => $_GET['q']      ?? '',
        'page'   => 1,
    ], $override);
    foreach ($base as $k => $v) { if ($v === '' || $v === null) unset($base[$k]); }
    if (($base['filter'] ?? '') === 'all') unset($base['filter']);
    return 'FoodController.php?' . http_build_query($base);
}

require_once __DIR__ . '/../views/food/listfood.php';

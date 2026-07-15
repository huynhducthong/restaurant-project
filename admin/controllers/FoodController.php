<?php
// =============================================================
// File: admin/controllers/FoodController.php
// Route: ?action=list|add|edit|delete|toggle
// Thay thế: manage_foods.php + add_food.php + edit_food.php
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();
date_default_timezone_set('Asia/Ho_Chi_Minh');

$action = $_GET['action'] ?? 'list';
$current_user = $_SESSION['username'] ?? 'Admin';

require_once __DIR__ . '/../../config/upload_helper.php';


function saveRecipes(PDO $db, int $food_id): void
{
    $db->prepare("DELETE FROM food_recipes WHERE food_id = ?")->execute([$food_id]);
    if (empty($_POST['ingredients']))
        return;
    $stmt = $db->prepare(
        "INSERT INTO food_recipes (food_id, ingredient_id, quantity_required, unit)
         VALUES (?, ?, ?, ?)"
    );
    foreach ($_POST['ingredients'] as $idx => $ing_id) {
        if (empty($ing_id))
            continue;
        $qty = (float) ($_POST['quantities'][$idx] ?? 0);
        $unit = trim($_POST['units'][$idx] ?? '');
        if ($qty > 0 && $unit !== '') {
            $stmt->execute([$food_id, (int) $ing_id, $qty, $unit]);
        }
    }
}

function saveToppings(PDO $db, int $food_id): void
{
    $db->prepare("DELETE FROM food_toppings WHERE food_id = ?")->execute([$food_id]);
    if (empty($_POST['toppings']))
        return;
    $stmt = $db->prepare("INSERT INTO food_toppings (food_id, topping_id) VALUES (?, ?)");
    foreach ($_POST['toppings'] as $topping_id) {
        $stmt->execute([$food_id, (int)$topping_id]);
    }
}

// ============================================================
// AJAX: Toggle ẩn / hiện món
// ============================================================
if ($action === 'toggle' && isset($_POST['food_id'])) {
    header('Content-Type: application/json');
    $fid = (int) $_POST['food_id'];
    $db->prepare("UPDATE foods SET is_active = NOT is_active, status = NOT status WHERE id = ?")->execute([$fid]);
    $s = $db->prepare("SELECT is_active FROM foods WHERE id = ?");
    $s->execute([$fid]);
    echo json_encode(['status' => 'success', 'is_active' => (int) $s->fetchColumn()]);
    exit;
}

// ============================================================
// XÓA món ăn
// ============================================================
if ($action === 'delete' && isset($_GET['id'])) {
    $del_id = (int) $_GET['id'];
    $chk = $db->prepare("SELECT COUNT(*) FROM order_items WHERE food_id = ?");
    $chk->execute([$del_id]);

    if ((int) $chk->fetchColumn() > 0) {
        header("Location: FoodController.php?action=list&error=in_order");
        exit;
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT name, image FROM foods WHERE id = ?");
        $stmt->execute([$del_id]);
        $food_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $foodName = $food_data['name'] ?? '';
        $old_img = $food_data['image'] ?? '';

        if ($old_img && file_exists(__DIR__ . '/../../public/assets/img/menu/' . $old_img)) {
            @unlink(__DIR__ . '/../../public/assets/img/menu/' . $old_img);
        }

        $db->prepare("DELETE FROM combo_items  WHERE food_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM food_recipes WHERE food_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM foods         WHERE id = ?")->execute([$del_id]);
        $db->commit();

        if ($foodName) {
            require_once __DIR__ . '/../../config/notification_helper.php';
            $who = htmlspecialchars((string)($_SESSION['username'] ?? 'Admin'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            sendTelegramNotification("🗑 <b>XÓA MÓN ĂN (MENU)</b>\n\n👤 Người xóa: <b>{$who}</b>\n🍔 Món ăn: <b>{$foodName}</b>");
        }

        header("Location: FoodController.php?action=list&msg=deleted");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: FoodController.php?action=list&error=delete_failed");
        exit;
    }
}

// ============================================================
// THÊM MÓN (GET = form rỗng | POST = lưu)
// ============================================================
if ($action === 'add') {
    $errors = [];
    $success = false;
    $old = ['name' => '', 'category_id' => '', 'price' => '', 'description' => '', 'max_toppings' => 4];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old['name'] = trim($_POST['name'] ?? '');
        $old['category_id'] = trim($_POST['category_id'] ?? '');
        $old['price'] = trim($_POST['price'] ?? '');
        $old['description'] = trim($_POST['description'] ?? '');
        $old['allergens'] = trim($_POST['allergens'] ?? '');
        $old['wine_pairing_id'] = !empty($_POST['wine_pairing_id']) ? (int)$_POST['wine_pairing_id'] : null;
        $old['chef_note'] = trim($_POST['chef_note'] ?? '');
        $fj_data = [
            'origin' => trim($_POST['fj_origin'] ?? ''),
            'selection' => trim($_POST['fj_selection'] ?? ''),
            'storage' => trim($_POST['fj_storage'] ?? ''),
            'prep' => trim($_POST['fj_prep'] ?? ''),
            'cooking_art' => trim($_POST['fj_cooking_art'] ?? ''),
            'presentation' => trim($_POST['fj_presentation'] ?? '')
        ];
        
        $fj_target_dir = __DIR__ . '/../../public/assets/img/journey/';
        if (!is_dir($fj_target_dir)) {
            mkdir($fj_target_dir, 0777, true);
        }
        
        // Decode old json to get old images if we are editing
        $old_fj_raw = '';
        if ($action === 'edit' && isset($food['food_journey'])) {
            $old_fj_raw = $food['food_journey'];
        } elseif ($action === 'add') {
             // In add, no old image.
        }
        $old_fj_arr = json_decode($old_fj_raw, true);
        if (!is_array($old_fj_arr)) $old_fj_arr = [];
        
        foreach(['origin', 'selection', 'storage', 'prep', 'cooking_art', 'presentation'] as $k) {
            $old_img = $old_fj_arr[$k . '_img'] ?? '';
            // process_image_upload is defined in upload_helper.php
            $img = process_image_upload('fj_img_' . $k, $fj_target_dir, $errors, $old_img);
            if ($img) {
                $fj_data[$k . '_img'] = $img;
            } elseif ($old_img) {
                // Keep the old image if new one is not uploaded
                $fj_data[$k . '_img'] = $old_img;
            }
        }
        
                // Process certificate image
        $old_cert_img = $old_fj_arr['certificate_img'] ?? '';
        $cert_img = process_image_upload('fj_img_certificate', $fj_target_dir, $errors, $old_cert_img);
        if ($cert_img) {
            $fj_data['certificate_img'] = $cert_img;
        } elseif ($old_cert_img) {
            $fj_data['certificate_img'] = $old_cert_img;
        }

        // Only save if at least one has content
        $fj_json = array_filter($fj_data) ? json_encode($fj_data, JSON_UNESCAPED_UNICODE) : '';
        $old['food_journey'] = $fj_json;
        $old['cooking_technique'] = '';
        $old['cooking_status'] = trim($_POST['cooking_status'] ?? '');
        $old['max_toppings'] = isset($_POST['max_toppings']) ? (int)$_POST['max_toppings'] : 4;
        $theme_id = !empty($_POST['theme_id']) ? (int)$_POST['theme_id'] : null;

        if ($old['name'] === '')
            $errors[] = 'Tên món ăn không được để trống.';
        elseif (mb_strlen($old['name']) > 150)
            $errors[] = 'Tên món ăn tối đa 150 ký tự.';
        if ($old['category_id'] === '')
            $errors[] = 'Vui lòng chọn danh mục.';

        $price_val = (float) $old['price'];
        if ($old['price'] === '' || $price_val < 0)
            $errors[] = 'Giá bán phải là số không âm.';

        if (empty($_FILES['image']['name']))
            $errors[] = 'Vui lòng chọn ảnh cho món ăn.';

        $target_dir = __DIR__ . '/../../public/assets/img/menu/';
        $image_name = process_image_upload('image', $target_dir, $errors);

        if (empty($errors)) {
            $db->beginTransaction();
            try {
                $db->prepare(
                    "INSERT INTO foods (name, category_id, price, description, allergens, wine_pairing_id, chef_note, cooking_status, food_journey, cooking_technique, image, is_active, is_chef_recommended, theme_id, max_toppings)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)"
                )->execute([
                            $old['name'],
                            (int) $old['category_id'],
                            $price_val,
                            $old['description'],
                            $old['allergens'],
                            $old['wine_pairing_id'],
                            $old['chef_note'],
                            $old['cooking_status'],
                            $old['food_journey'],
                            $old['cooking_technique'],
                            $image_name,
                            isset($_POST['is_chef_recommended']) ? 1 : 0,
                            $theme_id,
                            $old['max_toppings']
                        ]);
                $new_food_id = (int)$db->lastInsertId();
                saveRecipes($db, $new_food_id);
                saveToppings($db, $new_food_id);
                $db->commit();
                $success = true;
                $old = ['name' => '', 'category_id' => '', 'price' => '', 'description' => '', 'max_toppings' => 4];
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    // Dữ liệu cho view
    $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_units = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    $drinks = $db->query("SELECT id, name FROM foods WHERE is_active = 1 AND category_id IN (SELECT id FROM categories WHERE name LIKE '%uống%') ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_themes = $db->query("SELECT id, name FROM themes WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $toppings = $db->query("SELECT * FROM toppings WHERE status = 1 AND topping_group != 'Độ chín' ORDER BY topping_group ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $special_requests = $db->query("SELECT * FROM toppings WHERE status = 1 AND topping_group = 'Độ chín' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $current_toppings = [];

    require_once __DIR__ . '/../views/food/formfood.php';
    exit;
}

// ============================================================
// SỬA MÓN (GET = form điền sẵn | POST = lưu)
// ============================================================
if ($action === 'edit') {
    $id = (int) ($_GET['id'] ?? $_POST['food_id'] ?? 0);
    if ($id <= 0) {
        header("Location: FoodController.php?action=list&error=notfound");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([$id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$food) {
        header("Location: FoodController.php?action=list&error=notfound");
        exit;
    }

    $recipe_stmt = $db->prepare(
        "SELECT r.*, i.item_name FROM food_recipes r
         JOIN inventory i ON r.ingredient_id = i.id
         WHERE r.food_id = ? ORDER BY i.item_name"
    );
    $recipe_stmt->execute([$id]);
    $current_recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);

    $t_stmt = $db->prepare("SELECT topping_id FROM food_toppings WHERE food_id = ?");
    $t_stmt->execute([$id]);
    $current_toppings = $t_stmt->fetchAll(PDO::FETCH_COLUMN);

    $errors = [];
    $success = false;
    $old = [
        'name' => $food['name'],
        'category_id' => $food['category_id'],
        'price' => $food['price'],
        'description' => $food['description'],
        'allergens' => $food['allergens'],
        'wine_pairing_id' => $food['wine_pairing_id'],
        'chef_note' => $food['chef_note'],
        'food_journey' => $food['food_journey'] ?? '',
        'cooking_technique' => $food['cooking_technique'] ?? '',
        'cooking_status' => $food['cooking_status'],
        'is_chef_recommended' => $food['is_chef_recommended'],
        'theme_id' => $food['theme_id'],
        'max_toppings' => $food['max_toppings'] ?? 4,
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old['name'] = trim($_POST['name'] ?? '');
        $old['category_id'] = trim($_POST['category_id'] ?? '');
        $old['price'] = trim($_POST['price'] ?? '');
        $old['description'] = trim($_POST['description'] ?? '');
        $old['wine_pairing_id'] = !empty($_POST['wine_pairing_id']) ? (int)$_POST['wine_pairing_id'] : null;
        $old['chef_note'] = trim($_POST['chef_note'] ?? '');
        $fj_data = [
            'origin' => trim($_POST['fj_origin'] ?? ''),
            'selection' => trim($_POST['fj_selection'] ?? ''),
            'storage' => trim($_POST['fj_storage'] ?? ''),
            'prep' => trim($_POST['fj_prep'] ?? ''),
            'cooking_art' => trim($_POST['fj_cooking_art'] ?? ''),
            'presentation' => trim($_POST['fj_presentation'] ?? '')
        ];
        
        $fj_target_dir = __DIR__ . '/../../public/assets/img/journey/';
        if (!is_dir($fj_target_dir)) {
            mkdir($fj_target_dir, 0777, true);
        }
        
        // Decode old json to get old images if we are editing
        $old_fj_raw = '';
        if ($action === 'edit' && isset($food['food_journey'])) {
            $old_fj_raw = $food['food_journey'];
        } elseif ($action === 'add') {
             // In add, no old image.
        }
        $old_fj_arr = json_decode($old_fj_raw, true);
        if (!is_array($old_fj_arr)) $old_fj_arr = [];
        
        foreach(['origin', 'selection', 'storage', 'prep', 'cooking_art', 'presentation'] as $k) {
            $old_img = $old_fj_arr[$k . '_img'] ?? '';
            // process_image_upload is defined in upload_helper.php
            $img = process_image_upload('fj_img_' . $k, $fj_target_dir, $errors, $old_img);
            if ($img) {
                $fj_data[$k . '_img'] = $img;
            } elseif ($old_img) {
                // Keep the old image if new one is not uploaded
                $fj_data[$k . '_img'] = $old_img;
            }
        }
        
                // Process certificate image
        $old_cert_img = $old_fj_arr['certificate_img'] ?? '';
        $cert_img = process_image_upload('fj_img_certificate', $fj_target_dir, $errors, $old_cert_img);
        if ($cert_img) {
            $fj_data['certificate_img'] = $cert_img;
        } elseif ($old_cert_img) {
            $fj_data['certificate_img'] = $old_cert_img;
        }

        // Only save if at least one has content
        $fj_json = array_filter($fj_data) ? json_encode($fj_data, JSON_UNESCAPED_UNICODE) : '';
        $old['food_journey'] = $fj_json;
        $old['cooking_technique'] = '';
        $old['cooking_status'] = trim($_POST['cooking_status'] ?? '');
        $old['max_toppings'] = isset($_POST['max_toppings']) ? (int)$_POST['max_toppings'] : 4;
        $theme_id = !empty($_POST['theme_id']) ? (int)$_POST['theme_id'] : null;
        $old['theme_id'] = $theme_id;
        $old['allergens'] = isset($_POST['allergens']) && is_array($_POST['allergens']) ? implode(', ', $_POST['allergens']) : trim($_POST['allergens'] ?? '');

        if ($old['name'] === '')
            $errors[] = 'Tên món ăn không được để trống.';
        elseif (mb_strlen($old['name']) > 150)
            $errors[] = 'Tên món ăn tối đa 150 ký tự.';
        if ($old['category_id'] === '')
            $errors[] = 'Vui lòng chọn danh mục.';

        $price_val = (float) $old['price'];
        if ($old['price'] === '' || $price_val < 0)
            $errors[] = 'Giá bán phải là số không âm.';

        $target_dir = __DIR__ . '/../../public/assets/img/menu/';
        $final_image = process_image_upload('image', $target_dir, $errors, $food['image']);

        if (empty($errors)) {
            $db->beginTransaction();
            try {
                $db->prepare(
                    "UPDATE foods SET name=?, category_id=?, price=?, description=?, allergens=?, wine_pairing_id=?, chef_note=?, cooking_status=?, food_journey=?, cooking_technique=?, image=?, is_chef_recommended=?, theme_id=?, max_toppings=? WHERE id=?"
                )->execute([
                            $old['name'],
                            (int) $old['category_id'],
                            $price_val,
                            $old['description'],
                            $old['allergens'],
                            $old['wine_pairing_id'],
                            $old['chef_note'],
                            $old['cooking_status'],
                            $old['food_journey'],
                            $old['cooking_technique'],
                            $final_image,
                            isset($_POST['is_chef_recommended']) ? 1 : 0,
                            $theme_id,
                            $old['max_toppings'],
                            $id
                        ]);
                saveRecipes($db, $id);
                saveToppings($db, $id);
                $db->commit();
                $success = true;

                // Reload sau khi lưu
                $stmt->execute([$id]);
                $food = $stmt->fetch(PDO::FETCH_ASSOC);
                $recipe_stmt->execute([$id]);
                $current_recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);
                $t_stmt->execute([$id]);
                $current_toppings = $t_stmt->fetchAll(PDO::FETCH_COLUMN);
                $old = [
                    'name' => $food['name'],
                    'category_id' => $food['category_id'],
                    'price' => $food['price'],
                    'description' => $food['description'],
                    'allergens' => $food['allergens'],
                    'wine_pairing_id' => $food['wine_pairing_id'],
                    'chef_note' => $food['chef_note'],
        'food_journey' => $food['food_journey'] ?? '',
        'cooking_technique' => $food['cooking_technique'] ?? '',
                    'cooking_status' => $food['cooking_status'],
                    'theme_id' => $food['theme_id'],
                    'max_toppings' => $food['max_toppings'] ?? 4,
                ];
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    // Dữ liệu cho view
    $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_units = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    $drinks = $db->query("SELECT id, name FROM foods WHERE is_active = 1 AND category_id IN (SELECT id FROM categories WHERE name LIKE '%uống%') ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_themes = $db->query("SELECT id, name FROM themes WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $toppings = $db->query("SELECT * FROM toppings WHERE status = 1 AND topping_group != 'Độ chín' ORDER BY topping_group ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $special_requests = $db->query("SELECT * FROM toppings WHERE status = 1 AND topping_group = 'Độ chín' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/food/formfood.php';
    exit;
}

// ============================================================
// DANH SÁCH (action=list — default)
// ============================================================
$delete_error = '';
if (isset($_GET['error'])) {
    $err_map = [
        'in_order' => "Không thể xóa! Món này đã xuất hiện trong đơn hàng. Hãy dùng nút <strong>Ẩn món</strong> thay vì xóa.",
        'delete_failed' => 'Lỗi khi xóa. Vui lòng thử lại.',
        'notfound' => 'Không tìm thấy món ăn.',
    ];
    $delete_error = $err_map[$_GET['error']] ?? '';
}

// Filter / Search / Sort / Phân trang
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$search = trim($_GET['q'] ?? '');
$show_hidden = isset($_GET['show_hidden']);
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 12;

$where_parts = [];
$params = [];
if ($filter !== 'all') {
    $where_parts[] = "c.name = ?";
    $params[] = $filter;
}
if ($search !== '') {
    $where_parts[] = "f.name LIKE ?";
    $params[] = '%' . $search . '%';
}
if (!$show_hidden) {
    $where_parts[] = "f.is_active = 1";
}
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$sort_map = [
    'newest' => 'f.id DESC',
    'oldest' => 'f.id ASC',
    'name_asc' => 'f.name ASC',
    'name_desc' => 'f.name DESC',
    'price_asc' => 'f.price ASC',
    'price_desc' => 'f.price DESC',
];
$order_sql = 'ORDER BY ' . ($sort_map[$sort] ?? 'f.id DESC');

$cnt = $db->prepare("SELECT COUNT(*) FROM foods f LEFT JOIN categories c ON f.category_id = c.id $where_sql");
$cnt->execute($params);
$total = (int) $cnt->fetchColumn();
$total_pages = max(1, (int) ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

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
    $food_ids = array_column($foods, 'id');
    $placeholders = implode(',', array_fill(0, count($food_ids), '?'));

    $cs = $db->prepare("SELECT food_id, COUNT(*) as cnt FROM combo_items WHERE food_id IN ($placeholders) GROUP BY food_id");
    $cs->execute($food_ids);
    foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $r)
        $combo_map[(int) $r['food_id']] = (int) $r['cnt'];

    $rs = $db->prepare(
        "SELECT r.food_id, r.quantity_required, r.unit, i.item_name
         FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id
         WHERE r.food_id IN ($placeholders) ORDER BY i.item_name"
    );
    $rs->execute($food_ids);
    foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $r)
        $recipe_map[(int) $r['food_id']][] = $r;

    $cst = $db->prepare(
        "SELECT r.food_id, 
         SUM(r.quantity_required * i.cost_price * 
            CASE 
                WHEN LOWER(r.unit) = 'gram' AND LOWER(i.unit_name) = 'kg' THEN 0.001
                WHEN LOWER(r.unit) = 'ml' AND LOWER(i.unit_name) = 'lít' THEN 0.001
                WHEN LOWER(r.unit) = 'ml' AND LOWER(i.unit_name) = 'lit' THEN 0.001
                WHEN LOWER(r.unit) = 'ml' AND LOWER(i.unit_name) = 'chai' THEN 0.001333 /* Giả định 1 chai trung bình 750ml */
                WHEN LOWER(r.unit) = 'gram' AND LOWER(i.unit_name) = 'hộp' THEN 0.01 /* Giả định 1 hộp 100g */
                ELSE 1 
            END
         ) as total_cost
         FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id
         WHERE r.food_id IN ($placeholders) GROUP BY r.food_id"
    );
    $cst->execute($food_ids);
    foreach ($cst->fetchAll(PDO::FETCH_ASSOC) as $r)
        $cost_map[(int) $r['food_id']] = (float) $r['total_cost'];
}

$hidden_count = (int) $db->query("SELECT COUNT(*) FROM foods WHERE is_active = 0")->fetchColumn();
$cats = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_units = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$msg = $_GET['msg'] ?? '';

function buildUrl(array $override = []): string
{
    $base = array_merge([
        'action' => 'list',
        'filter' => $_GET['filter'] ?? 'all',
        'sort' => $_GET['sort'] ?? 'newest',
        'q' => $_GET['q'] ?? '',
        'page' => 1,
    ], $override);
    foreach ($base as $k => $v) {
        if ($v === '' || $v === null)
            unset($base[$k]);
    }
    if (($base['filter'] ?? '') === 'all')
        unset($base['filter']);
    return 'FoodController.php?' . http_build_query($base);
}

require_once __DIR__ . '/../views/food/listfood.php';

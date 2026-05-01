<?php
// File: admin/InventoryController.php

session_start();

// Xác thực session admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}
$current_user = $_SESSION['username'] ?? 'Admin';

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// ============================================================
// 1. XỬ LÝ YÊU CẦU (REQUEST HANDLING)
// ============================================================

// A. Bộ lọc Thống kê — chống SQL Injection hoàn toàn
$allowed_types = ['day', 'month', 'year'];
$f_type = in_array($_GET['f_type'] ?? '', $allowed_types) ? $_GET['f_type'] : 'month';

if ($f_type === 'day') {
    $f_val     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['f_val'] ?? '') ? $_GET['f_val'] : date('Y-m-d');
    $where_col = "DATE(created_at) = ?";
} elseif ($f_type === 'year') {
    $f_val     = preg_match('/^\d{4}$/', $_GET['f_val'] ?? '') ? $_GET['f_val'] : date('Y');
    $where_col = "YEAR(created_at) = ?";
} else {
    $f_val     = preg_match('/^\d{4}-\d{2}$/', $_GET['f_val'] ?? '') ? $_GET['f_val'] : date('Y-m');
    $where_col = "DATE_FORMAT(created_at, '%Y-%m') = ?";
}

$stmt_stats = $db->prepare("
    SELECT
        SUM(CASE WHEN type='import' THEN quantity ELSE 0 END) as ti,
        SUM(CASE WHEN type='export' THEN quantity ELSE 0 END) as te,
        SUM(CASE WHEN type='loss'   THEN quantity ELSE 0 END) as tl
    FROM inventory_history
    WHERE $where_col
");
$stmt_stats->execute([$f_val]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// ----------------------------------------------------------------
// B. Export CSV
// ----------------------------------------------------------------
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ton_kho_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['Nguyên Liệu', 'Danh Mục', 'Đơn Vị', 'Tồn Kho', 'Tồn Tối Thiểu', 'Giá Vốn (đ)', 'Nhà Cung Cấp', 'Hạn Sử Dụng']);
    $rows = $db->query(
        "SELECT i.*, s.name as s_name
         FROM inventory i
         LEFT JOIN suppliers s ON i.supplier_id = s.id
         ORDER BY i.item_name"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['item_name'], $r['category'], $r['unit_name'],
            $r['stock_quantity'], $r['min_stock'] ?? 0,
            $r['cost_price'], $r['s_name'] ?? 'Chưa gán',
            $r['expiry_date'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ----------------------------------------------------------------
// C. Quản lý Nhà Cung Cấp — Thêm / Sửa
// ----------------------------------------------------------------
if (isset($_POST['save_supplier'])) {
    $data = [
        trim($_POST['s_name']),
        trim($_POST['s_phone']),
        trim($_POST['s_address']),
        trim($_POST['s_email']),
        trim($_POST['s_contact']),
    ];
    if (!empty($_POST['supplier_id'])) {
        $db->prepare(
            "UPDATE suppliers SET name=?, phone=?, address=?, email=?, contact_person=? WHERE id=?"
        )->execute([...$data, (int)$_POST['supplier_id']]);
    } else {
        $db->prepare(
            "INSERT INTO suppliers (name, phone, address, email, contact_person) VALUES (?, ?, ?, ?, ?)"
        )->execute($data);
    }
    header("Location: InventoryController.php?tab=suppliers"); exit;
}

// Xóa Nhà Cung Cấp
if (isset($_GET['delete_supplier'])) {
    $db->prepare("DELETE FROM suppliers WHERE id = ?")->execute([(int)$_GET['delete_supplier']]);
    header("Location: InventoryController.php?tab=suppliers"); exit;
}

// ----------------------------------------------------------------
// D. Quản lý Tags (Danh mục & Đơn vị)
// ----------------------------------------------------------------
if (isset($_POST['manage_tag'])) {
    $allowed_tables = [
        'category' => 'inventory_categories',
        'unit'     => 'inventory_units',
    ];
    $table = $allowed_tables[$_POST['tag_type'] ?? ''] ?? null;
    if ($table) {
        $action = $_POST['tag_action'] ?? '';
        if ($action === 'add') {
            $db->prepare("INSERT IGNORE INTO $table (name) VALUES (?)")
               ->execute([trim($_POST['tag_name'])]);
        } elseif ($action === 'edit') {
            $db->prepare("UPDATE $table SET name = ? WHERE id = ?")
               ->execute([trim($_POST['tag_name']), (int)$_POST['tag_id']]);
        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM $table WHERE id = ?")
               ->execute([(int)$_POST['tag_id']]);
        }
    }
    header("Location: InventoryController.php"); exit;
}

// ----------------------------------------------------------------
// E. Thêm / Sửa Nguyên liệu
// ----------------------------------------------------------------
if (isset($_POST['save_inventory'])) {
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $min_stock   = max(0, (float)($_POST['min_stock'] ?? 0));
    $data = [
        trim($_POST['item_name']),
        $_POST['category'],
        $_POST['unit_name'],
        (float)$_POST['cost_price'],
        $supplier_id,
        $min_stock,
    ];
    if (!empty($_POST['item_id'])) {
        $db->prepare(
            "UPDATE inventory
             SET item_name=?, category=?, unit_name=?, cost_price=?, supplier_id=?, min_stock=?
             WHERE id=?"
        )->execute([...$data, (int)$_POST['item_id']]);
    } else {
        $db->prepare(
            "INSERT INTO inventory
             (item_name, category, unit_name, cost_price, supplier_id, min_stock, stock_quantity)
             VALUES (?, ?, ?, ?, ?, ?, 0)"
        )->execute($data);
    }
    header("Location: InventoryController.php"); exit;
}

// ----------------------------------------------------------------
// F. Nhập / Xuất / Hủy (AJAX) — Giá bình quân gia quyền
// ----------------------------------------------------------------
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $db->beginTransaction();
    try {
        $id  = (int)$_POST['item_id'];
        $qty = (float)$_POST['quantity'];
        if ($qty <= 0) throw new Exception("Số lượng phải lớn hơn 0.");

        if ($_POST['action'] === 'import') {
            $price = (float)$_POST['import_price'];
            $s_id  = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

            // Giá bình quân gia quyền
            $stmt_old = $db->prepare("SELECT stock_quantity, cost_price FROM inventory WHERE id = ?");
            $stmt_old->execute([$id]);
            $old = $stmt_old->fetch(PDO::FETCH_ASSOC);
            $total_qty = (float)$old['stock_quantity'] + $qty;
            $avg_price = $total_qty > 0
                ? (((float)$old['stock_quantity'] * (float)$old['cost_price']) + ($qty * $price)) / $total_qty
                : $price;

            $db->prepare(
                "UPDATE inventory
                 SET stock_quantity = stock_quantity + ?, cost_price = ?, supplier_id = ?, expiry_date = ?
                 WHERE id = ?"
            )->execute([$qty, $avg_price, $s_id, $_POST['expiry_date'] ?: null, $id]);

            $db->prepare(
                "INSERT INTO inventory_history (ingredient_id, type, quantity, performed_by)
                 VALUES (?, 'import', ?, ?)"
            )->execute([$id, $qty, $current_user]);

        } else {
            // Xuất / Hủy — kiểm tra tồn kho trước
            $check = $db->prepare("SELECT stock_quantity FROM inventory WHERE id = ?");
            $check->execute([$id]);
            $current_stock = (float)$check->fetchColumn();
            if ($current_stock < $qty) {
                throw new Exception("Không đủ tồn kho! Hiện còn: " . number_format($current_stock, 2));
            }

            $db->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE id = ?")
               ->execute([$qty, $id]);

            $db->prepare(
                "INSERT INTO inventory_history (ingredient_id, type, quantity, performed_by)
                 VALUES (?, ?, ?, ?)"
            )->execute([$id, $_POST['action'], $qty, $current_user]);
        }

        $db->commit();
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------------------
// G. Xóa nguyên liệu
// ----------------------------------------------------------------
if (isset($_GET['delete_id'])) {
    $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([(int)$_GET['delete_id']]);
    header("Location: InventoryController.php"); exit;
}

// ----------------------------------------------------------------
// H. Kiểm kê kho — ĐÃ FIX: bulk SELECT + validate $ing_id + redirect thay die()
// ----------------------------------------------------------------
if (isset($_POST['perform_audit'])) {
    $db->beginTransaction();
    try {
        // Lấy danh sách id hợp lệ từ POST, ép kiểu int toàn bộ
        $raw_ids = array_keys($_POST['actual_qty'] ?? []);
        $valid_ids = array_filter(array_map('intval', $raw_ids), fn($id) => $id > 0);

        if (empty($valid_ids)) {
            throw new Exception("Không có dữ liệu kiểm kê hợp lệ.");
        }

        // ✅ FIX N+1: Bulk SELECT 1 lần thay vì query trong loop
        $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
        $bulk = $db->prepare(
            "SELECT id, stock_quantity FROM inventory WHERE id IN ($placeholders)"
        );
        $bulk->execute($valid_ids);
        $inv_map = array_column($bulk->fetchAll(PDO::FETCH_ASSOC), 'stock_quantity', 'id');

        // Tạo đợt kiểm kê
        $db->prepare("INSERT INTO inventory_audits (performed_by, notes) VALUES (?, ?)")
           ->execute([$current_user, trim($_POST['audit_notes'] ?? '')]);
        $audit_id = (int)$db->lastInsertId();

        $ins_detail = $db->prepare(
            "INSERT INTO inventory_audit_details
             (audit_id, ingredient_id, system_qty, physical_qty, variance)
             VALUES (?, ?, ?, ?, ?)"
        );
        $ins_history = $db->prepare(
            "INSERT INTO inventory_history (ingredient_id, type, quantity, performed_by)
             VALUES (?, ?, ?, ?)"
        );
        $upd_stock = $db->prepare(
            "UPDATE inventory SET stock_quantity = ? WHERE id = ?"
        );

        foreach ($_POST['actual_qty'] as $ing_id => $physical_qty) {
            // ✅ FIX: Validate key — chỉ xử lý id tồn tại trong DB
            $ing_id = (int)$ing_id;
            if (!isset($inv_map[$ing_id])) continue;
            if ($physical_qty === '' || $physical_qty === null) continue;

            $physical_qty = (float)$physical_qty;
            $system_qty   = (float)$inv_map[$ing_id];
            $variance     = $physical_qty - $system_qty;

            // Lưu chi tiết kiểm kê
            $ins_detail->execute([$audit_id, $ing_id, $system_qty, $physical_qty, $variance]);

            // Chỉ cập nhật khi có chênh lệch
            if ($variance != 0) {
                $upd_stock->execute([$physical_qty, $ing_id]);
                $type = $variance > 0 ? 'import' : 'loss';
                $ins_history->execute([$ing_id, $type, abs($variance), $current_user . ' (Kiểm kê)']);
            }
        }

        $db->commit();
        header("Location: InventoryController.php?tab=audit&msg=success");

    } catch (Exception $e) {
        // ✅ FIX: Thay die() bằng redirect — không lộ lỗi hệ thống
        $db->rollBack();
        header("Location: InventoryController.php?tab=audit&msg=error");
    }
    exit;
}

// ============================================================
// 2. TRUY VẤN DỮ LIỆU HIỂN THỊ
// ============================================================
$top_used = $db->query("
    SELECT i.item_name, SUM(h.quantity) as total, i.unit_name
    FROM inventory_history h
    JOIN inventory i ON h.ingredient_id = i.id
    WHERE h.type = 'export'
    GROUP BY i.id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$cats      = $db->query("SELECT * FROM inventory_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$units     = $db->query("SELECT * FROM inventory_units ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $db->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$inv = $db->query("
    SELECT i.*, s.name as s_name
    FROM inventory i
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    ORDER BY i.item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$history = $db->query("
    SELECT h.*, i.item_name, i.unit_name
    FROM inventory_history h
    JOIN inventory i ON h.ingredient_id = i.id
    ORDER BY h.created_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$reorder_list = $db->query("
    SELECT i.*, (i.min_stock - i.stock_quantity) as suggest_qty, s.name as s_name
    FROM inventory i
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    WHERE i.min_stock > 0 AND i.stock_quantity <= i.min_stock
    ORDER BY (i.min_stock - i.stock_quantity) DESC
")->fetchAll(PDO::FETCH_ASSOC);

$today     = date('Y-m-d');
$warn_date = date('Y-m-d', strtotime('+7 days'));

$low_stock_count   = 0;
$expiry_warn_count = 0;
foreach ($inv as $i) {
    $min = (float)($i['min_stock'] ?? 0);
    if ($min > 0 && (float)$i['stock_quantity'] <= $min) $low_stock_count++;
    if (!empty($i['expiry_date']) && $i['expiry_date'] <= $warn_date) $expiry_warn_count++;
}

$chart_raw = $db->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as mo,
        SUM(CASE WHEN type='import' THEN quantity ELSE 0 END) as ti,
        SUM(CASE WHEN type='export' THEN quantity ELSE 0 END) as te,
        SUM(CASE WHEN type='loss'   THEN quantity ELSE 0 END) as tl
    FROM inventory_history
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
    GROUP BY mo
    ORDER BY mo ASC
")->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';

// ============================================================
// 3. NẠP VIEW
// ============================================================
require_once __DIR__ . '/inventory_view.php';
<?php
// File: admin/controllers/InventoryController.php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
$current_user = $_SESSION['username'] ?? 'Admin';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/inventory_helper.php';
require_once __DIR__ . '/../../config/notification_helper.php';
$db = (new Database())->getConnection();

// API: Lấy danh sách gợi ý nhập hàng (cho PO)
if (isset($_POST['action']) && $_POST['action'] === 'get_reorder_list') {
    header('Content-Type: application/json');
    $settings_raw = $db->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('inv_low_stock')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $cfg_low_stock = (float)($settings_raw['inv_low_stock'] ?? 5);

    $stmt = $db->query("
        SELECT i.id, i.item_name, i.unit_name, i.cost_price, i.min_stock,
               IFNULL(SUM(s.quantity), 0) as total_stock
        FROM inventory i
        LEFT JOIN inventory_stocks s ON i.id = s.ingredient_id
        WHERE i.is_active = 1
        GROUP BY i.id
        HAVING total_stock <= CASE WHEN i.min_stock > 0 THEN i.min_stock ELSE $cfg_low_stock END
    ");
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $list]);
    exit;
}

// Lấy danh sách các kho (Warehouses)
$warehouses = $db->query("SELECT * FROM warehouses WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);

// Lấy cấu hình kho từ bảng settings
$settings_raw = $db->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('inv_expiry_days', 'inv_low_stock')")->fetchAll(PDO::FETCH_KEY_PAIR);
$cfg_expiry_days = (int)($settings_raw['inv_expiry_days'] ?? 7);
$cfg_low_stock   = (float)($settings_raw['inv_low_stock'] ?? 5);

// ============================================================
// 1. XỬ LÝ YÊU CẦU (REQUEST HANDLING)
// ============================================================

$allowed_types = ['day', 'month', 'year'];
$f_type = in_array($_GET['f_type'] ?? '', $allowed_types) ? $_GET['f_type'] : 'month';

if ($f_type === 'day') {
    $f_val = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['f_val'] ?? '') ? $_GET['f_val'] : date('Y-m-d');
    $where_col = "DATE(created_at) = ?";
} elseif ($f_type === 'year') {
    $f_val = preg_match('/^\d{4}$/', $_GET['f_val'] ?? '') ? $_GET['f_val'] : date('Y');
    $where_col = "YEAR(created_at) = ?";
} else {
    $f_val = preg_match('/^\d{4}-\d{2}$/', $_GET['f_val'] ?? '') ? $_GET['f_val'] : date('Y-m');
    $where_col = "DATE_FORMAT(created_at, '%Y-%m') = ?";
}

$stmt_stats = $db->prepare("
    SELECT
        SUM(CASE WHEN type='import' THEN quantity ELSE 0 END) as ti,
        SUM(CASE WHEN type='export' THEN quantity ELSE 0 END) as te,
        SUM(CASE WHEN type='loss'   THEN quantity ELSE 0 END) as tl
    FROM inventory_history WHERE $where_col
");
$stmt_stats->execute([$f_val]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// --- Export CSV ---
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ton_kho_dakho_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Nguyên Liệu', 'Danh Mục', 'Đơn Vị', 'Tổng Tồn (Mọi Kho)', 'Tồn Tối Thiểu', 'Giá Vốn', 'Nhà Cung Cấp']);

    $rows = $db->query("
        SELECT i.item_name, i.category, i.unit_name, i.min_stock, i.cost_price, s.name as s_name,
               IFNULL((SELECT SUM(quantity) FROM inventory_stocks WHERE ingredient_id = i.id), 0) as total_stock
        FROM inventory i
        LEFT JOIN suppliers s ON i.supplier_id = s.id ORDER BY i.item_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        fputcsv($out, [$r['item_name'], $r['category'], $r['unit_name'], $r['total_stock'], $r['min_stock'] ?? 0, $r['cost_price'], $r['s_name'] ?? 'Chưa gán']);
    }
    fclose($out);
    exit;
}

// --- Quản lý NCC & Tags ---
if (isset($_POST['save_supplier'])) {
    $data = [trim($_POST['s_name']), trim($_POST['s_phone']), trim($_POST['s_address']), trim($_POST['s_email']), trim($_POST['s_contact'])];
    if (!empty($_POST['supplier_id'])) {
        $db->prepare("UPDATE suppliers SET name=?, phone=?, address=?, email=?, contact_person=? WHERE id=?")->execute([...$data, (int)$_POST['supplier_id']]);
    } else {
        $db->prepare("INSERT INTO suppliers (name, phone, address, email, contact_person) VALUES (?, ?, ?, ?, ?)")->execute($data);
    }
    header("Location: InventoryController.php?tab=suppliers");
    exit;
}
if (isset($_GET['delete_supplier'])) {
    $db->prepare("DELETE FROM suppliers WHERE id = ?")->execute([(int)$_GET['delete_supplier']]);
    header("Location: InventoryController.php?tab=suppliers");
    exit;
}
if (isset($_POST['manage_tag'])) {
    $tables = ['category' => 'inventory_categories', 'unit' => 'inventory_units'];
    $table = $tables[$_POST['tag_type'] ?? ''] ?? null;
    if ($table) {
        $action = $_POST['tag_action'] ?? '';
        if ($action === 'add') $db->prepare("INSERT IGNORE INTO $table (name) VALUES (?)")->execute([trim($_POST['tag_name'])]);
        elseif ($action === 'edit') $db->prepare("UPDATE $table SET name = ? WHERE id = ?")->execute([trim($_POST['tag_name']), (int)$_POST['tag_id']]);
        elseif ($action === 'delete') $db->prepare("DELETE FROM $table WHERE id = ?")->execute([(int)$_POST['tag_id']]);
    }
    header("Location: InventoryController.php");
    exit;
}

// --- Thêm/Sửa Nguyên liệu (Bỏ stock_quantity) ---
if (isset($_POST['save_inventory'])) {
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $min_stock = max(0, (float)($_POST['min_stock'] ?? 0));
    $storage_temp = trim($_POST['storage_temperature'] ?? '');

    $data = [
        trim($_POST['item_name']),
        $_POST['category'],
        $_POST['unit_name'],
        $supplier_id,
        $min_stock,
        $storage_temp
    ];

    if (!empty($_POST['item_id'])) {
        $db->prepare("UPDATE inventory SET item_name=?, category=?, unit_name=?, supplier_id=?, min_stock=?, storage_temperature=? WHERE id=?")->execute([...$data, (int)$_POST['item_id']]);
    } else {
        $db->prepare("INSERT INTO inventory (item_name, category, unit_name, supplier_id, min_stock, storage_temperature) VALUES (?, ?, ?, ?, ?, ?)")->execute($data);
    }
    header("Location: InventoryController.php");
    exit;
}

// --- XỬ LÝ GIAO DỊCH (NHẬP / XUẤT / HỦY / CHUYỂN KHO) ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $db->beginTransaction();
    try {
        // Chuẩn bị nội dung Telegram (nếu action là chuyển kho / duyệt chuyển kho)
        $telegram_msg = null;

        if ($_POST['action'] === 'import') {
            $id = (int)$_POST['item_id'];
            $qty = (float)$_POST['quantity'];
            if ($qty <= 0) throw new Exception("Số lượng phải lớn hơn 0.");

            $price = (float)$_POST['import_price'];
            $s_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
            // Đọc kho từ form (mặc định Kho Tổng = 1 nếu không chọn)
            $main_warehouse_id = !empty($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : 1;

            // Lấy tổng tồn kho ở mọi kho để tính giá BQGQ
            $stmt_old = $db->prepare("SELECT IFNULL(SUM(quantity), 0) as total_stock FROM inventory_stocks WHERE ingredient_id = ?");
            $stmt_old->execute([$id]);
            $total_stock = (float)$stmt_old->fetchColumn();

            $stmt_price = $db->prepare("SELECT cost_price FROM inventory WHERE id = ?");
            $stmt_price->execute([$id]);
            $old_price = (float)$stmt_price->fetchColumn();

            $avg_price = ($total_stock + $qty) > 0 ? (($total_stock * $old_price) + ($qty * $price)) / ($total_stock + $qty) : $price;

            // Cập nhật giá và HSD ở bảng gốc
            $db->prepare("UPDATE inventory SET cost_price = ?, supplier_id = ?, expiry_date = ? WHERE id = ?")
                ->execute([$avg_price, $s_id, $_POST['expiry_date'] ?: null, $id]);

            // Tạo lô hàng mới
            createBatch($db, $id, $main_warehouse_id, $qty, $_POST['expiry_date'], $price, "Nhập trực tiếp");

            // Cập nhật lại HSD tổng (Lấy ngày sớm nhất của các lô còn hàng)
            $stmt_min_hsd = $db->prepare("SELECT MIN(expiry_date) FROM inventory_batches WHERE ingredient_id = ? AND quantity > 0 AND expiry_date IS NOT NULL");
            $stmt_min_hsd->execute([$id]);
            $earliest_hsd = $stmt_min_hsd->fetchColumn() ?: ($_POST['expiry_date'] ?: null);
            $db->prepare("UPDATE inventory SET expiry_date = ? WHERE id = ?")->execute([$earliest_hsd, $id]);

            // Cộng vào Kho Tổng
            $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?")
                ->execute([$main_warehouse_id, $id, $qty, $qty]);

            $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)")->execute([$id, $main_warehouse_id, $qty, $current_user]);
        } elseif ($_POST['action'] === 'export' || $_POST['action'] === 'loss') {
            $id = (int)$_POST['item_id'];
            $qty = (float)$_POST['quantity'];
            if ($qty <= 0) throw new Exception("Số lượng phải lớn hơn 0.");

            $w_id = (int)$_POST['warehouse_id'];
            if (!$w_id) throw new Exception("Vui lòng chọn kho để xử lý.");

            $check = $db->prepare("SELECT quantity FROM inventory_stocks WHERE warehouse_id = ? AND ingredient_id = ?");
            $check->execute([$w_id, $id]);
            $current_stock = (float)$check->fetchColumn();

            if ($current_stock < $qty) throw new Exception("Kho này không đủ tồn kho! Hiện còn: " . number_format($current_stock, 2));

            $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE warehouse_id = ? AND ingredient_id = ?")->execute([$qty, $w_id, $id]);
            
            // TRỪ KHO THEO LÔ (FEFO)
            deductStockFEFO($db, $id, $w_id, $qty, $current_user, $_POST['action']);

            // CỘNG VÀO KHO ẢO (Kho Xuất ID: 6 hoặc Kho Hủy ID: 7) để theo dõi tổng quát
            $virtual_w_id = ($_POST['action'] === 'export') ? 6 : 7;
            $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?")
               ->execute([$virtual_w_id, $id, $qty, $qty]);

            $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, ?, ?, ?)")->execute([$id, $w_id, $_POST['action'], $qty, $current_user]);
        } elseif ($_POST['action'] === 'transfer') {
            // Action cũ (giữ cho tương thích nếu còn gọi từ nơi khác)
            $id = (int)$_POST['item_id'];
            $qty = (float)$_POST['quantity'];
            if ($qty <= 0) throw new Exception("Số lượng phải lớn hơn 0.");

            $from_id = (int)$_POST['from_warehouse_id'];
            $to_id = (int)$_POST['to_warehouse_id'];

            if ($from_id === $to_id) throw new Exception("Kho xuất và nhập không được trùng nhau.");

            // CHỈ TẠO YÊU CẦU (PENDING), CHƯA TRỪ KHO
            $db->prepare("INSERT INTO inventory_transfers (from_warehouse_id, to_warehouse_id, performed_by, note, status) VALUES (?, ?, ?, 'Yêu cầu chuyển kho nội bộ', 'pending')")
                ->execute([$from_id, $to_id, $current_user]);

            $transfer_id = $db->lastInsertId();
            $db->prepare("INSERT INTO transfer_details (transfer_id, ingredient_id, quantity) VALUES (?, ?, ?)")
                ->execute([$transfer_id, $id, $qty]);

            // Telegram: tạo yêu cầu chuyển kho
            $w_from = $db->prepare("SELECT name FROM warehouses WHERE id = ?");
            $w_to = $db->prepare("SELECT name FROM warehouses WHERE id = ?");
            $w_from->execute([$from_id]);
            $w_to->execute([$to_id]);
            $from_name = (string)($w_from->fetchColumn() ?: ('Kho #' . $from_id));
            $to_name = (string)($w_to->fetchColumn() ?: ('Kho #' . $to_id));

            $stmt_ing = $db->prepare("SELECT item_name, unit_name FROM inventory WHERE id = ?");
            $stmt_ing->execute([$id]);
            $ing = $stmt_ing->fetch(PDO::FETCH_ASSOC) ?: ['item_name' => ('ID ' . $id), 'unit_name' => ''];
            $qty_str = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');

            $who = htmlspecialchars((string)$current_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $telegram_msg = "📦 <b>YÊU CẦU CHUYỂN KHO</b>\n\n";
            $telegram_msg .= "🧾 Phiếu: <b>#{$transfer_id}</b>\n";
            $telegram_msg .= "👤 Tạo bởi: <b>{$who}</b>\n";
            $telegram_msg .= "🏭 Từ: <b>" . htmlspecialchars($from_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n";
            $telegram_msg .= "➡️ Đến: <b>" . htmlspecialchars($to_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n\n";
            $telegram_msg .= "- " . htmlspecialchars((string)$ing['item_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ": {$qty_str}" . (!empty($ing['unit_name']) ? (" " . htmlspecialchars((string)$ing['unit_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) : "");

        } elseif ($_POST['action'] === 'transfer_multi') {
            // Action mới: Chuyển kho NHIỀU MẶT HÀNG cùng lúc
            $from_id = (int)$_POST['from_warehouse_id'];
            $to_id   = (int)$_POST['to_warehouse_id'];

            if ($from_id === $to_id) throw new Exception("Kho xuất và nhập không được trùng nhau.");

            $item_ids = $_POST['trans_item_id'] ?? [];
            $qtys     = $_POST['trans_qty']     ?? [];

            if (empty($item_ids)) throw new Exception("Vui lòng chọn ít nhất 1 mặt hàng.");

            // Validate tất cả dòng trước khi tạo phiếu
            $valid_items = [];
            foreach ($item_ids as $k => $raw_id) {
                if ($raw_id === '') throw new Exception("Vui lòng chọn nguyên liệu ở tất cả các dòng.");
                $ing_id = (int)$raw_id;
                $qty    = (float)($qtys[$k] ?? 0);
                if ($qty <= 0)    throw new Exception("Số lượng phải lớn hơn 0 ở tất cả các dòng.");
                $valid_items[] = ['id' => $ing_id, 'qty' => $qty];
            }

            // Tạo 1 phiếu chuyển kho duy nhất
            $db->prepare("INSERT INTO inventory_transfers (from_warehouse_id, to_warehouse_id, performed_by, note, status) VALUES (?, ?, ?, ?, 'pending')")
               ->execute([$from_id, $to_id, $current_user, 'Yêu cầu chuyển kho nội bộ (' . count($valid_items) . ' mặt hàng)']);

            $transfer_id  = $db->lastInsertId();
            $stmt_detail  = $db->prepare("INSERT INTO transfer_details (transfer_id, ingredient_id, quantity) VALUES (?, ?, ?)");

            foreach ($valid_items as $item) {
                $stmt_detail->execute([$transfer_id, $item['id'], $item['qty']]);
            }

            // Telegram: tạo yêu cầu chuyển kho nhiều mặt hàng
            $w_from = $db->prepare("SELECT name FROM warehouses WHERE id = ?");
            $w_to = $db->prepare("SELECT name FROM warehouses WHERE id = ?");
            $w_from->execute([$from_id]);
            $w_to->execute([$to_id]);
            $from_name = (string)($w_from->fetchColumn() ?: ('Kho #' . $from_id));
            $to_name = (string)($w_to->fetchColumn() ?: ('Kho #' . $to_id));

            $stmt_ing = $db->prepare("SELECT item_name, unit_name FROM inventory WHERE id = ?");
            $lines = [];
            foreach ($valid_items as $it) {
                $stmt_ing->execute([(int)$it['id']]);
                $ing = $stmt_ing->fetch(PDO::FETCH_ASSOC) ?: ['item_name' => ('ID ' . (int)$it['id']), 'unit_name' => ''];
                $qty_str = rtrim(rtrim(number_format((float)$it['qty'], 2, '.', ''), '0'), '.');
                $line = "- " . htmlspecialchars((string)$ing['item_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ": {$qty_str}";
                if (!empty($ing['unit_name'])) $line .= " " . htmlspecialchars((string)$ing['unit_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lines[] = $line;
            }

            $who = htmlspecialchars((string)$current_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $telegram_msg = "📦 <b>YÊU CẦU CHUYỂN KHO</b>\n\n";
            $telegram_msg .= "🧾 Phiếu: <b>#{$transfer_id}</b>\n";
            $telegram_msg .= "👤 Tạo bởi: <b>{$who}</b>\n";
            $telegram_msg .= "🏭 Từ: <b>" . htmlspecialchars($from_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n";
            $telegram_msg .= "➡️ Đến: <b>" . htmlspecialchars($to_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n";
            $telegram_msg .= "📦 Số mặt hàng: <b>" . count($valid_items) . "</b>\n\n";
            $telegram_msg .= implode("\n", array_slice($lines, 0, 30));
            if (count($lines) > 30) $telegram_msg .= "\n<i>... (còn " . (count($lines) - 30) . " dòng)</i>";
        } elseif ($_POST['action'] === 'approve_transfer') {
            $t_id = (int)$_POST['transfer_id'];

            // 1. Lấy thông tin phiếu chuyển
            $stmt = $db->prepare("SELECT * FROM inventory_transfers WHERE id = ? AND status = 'pending'");
            $stmt->execute([$t_id]);
            $transfer = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$transfer) throw new Exception("Không tìm thấy phiếu chuyển hoặc phiếu đã được xử lý.");

            // 2. Lấy chi tiết hàng hóa
            $stmt_d = $db->prepare("SELECT * FROM transfer_details WHERE transfer_id = ?");
            $stmt_d->execute([$t_id]);
            $details = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

            foreach ($details as $d) {
                $ing_id = $d['ingredient_id'];
                $qty = (float)$d['quantity'];
                $from_w = $transfer['from_warehouse_id'];
                $to_w = $transfer['to_warehouse_id'];

                // Kiểm tra tồn kho tại kho xuất
                $chk = $db->prepare("SELECT quantity FROM inventory_stocks WHERE warehouse_id = ? AND ingredient_id = ?");
                $chk->execute([$from_w, $ing_id]);
                $current_stock = (float)$chk->fetchColumn();

                if ($current_stock < $qty) {
                    $item_stmt = $db->prepare("SELECT item_name FROM inventory WHERE id = ?");
                    $item_stmt->execute([$ing_id]);
                    $item_name = $item_stmt->fetchColumn();
                    throw new Exception("Kho xuất không đủ hàng cho mục: $item_name (Cần $qty, Có $current_stock)");
                }

                // Thực hiện trừ kho xuất
                $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE warehouse_id = ? AND ingredient_id = ?")->execute([$qty, $from_w, $ing_id]);
                // Thực hiện cộng kho nhập
                $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?")->execute([$to_w, $ing_id, $qty, $qty]);

                // Ghi lịch sử giao dịch (2 dòng: 1 xuất, 1 nhập)
                $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'export', ?, ?)")
                    ->execute([$ing_id, $from_w, $qty, $current_user . " (Chuyển đi #$t_id)"]);
                $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)")
                    ->execute([$ing_id, $to_w, $qty, $current_user . " (Nhận từ #$t_id)"]);
            }

            // 3. Cập nhật trạng thái phiếu chuyển
            $db->prepare("UPDATE inventory_transfers SET status = 'completed', approved_by = ?, approved_at = NOW() WHERE id = ?")
                ->execute([$current_user, $t_id]);

            // Telegram: duyệt phiếu chuyển kho
            $w_from = $db->prepare("SELECT name FROM warehouses WHERE id = ?");
            $w_to = $db->prepare("SELECT name FROM warehouses WHERE id = ?");
            $w_from->execute([(int)$transfer['from_warehouse_id']]);
            $w_to->execute([(int)$transfer['to_warehouse_id']]);
            $from_name = (string)($w_from->fetchColumn() ?: ('Kho #' . (int)$transfer['from_warehouse_id']));
            $to_name = (string)($w_to->fetchColumn() ?: ('Kho #' . (int)$transfer['to_warehouse_id']));

            $stmt_ing = $db->prepare("SELECT item_name, unit_name FROM inventory WHERE id = ?");
            $lines = [];
            foreach ($details as $d) {
                $ing_id = (int)$d['ingredient_id'];
                $qty = (float)$d['quantity'];
                $stmt_ing->execute([$ing_id]);
                $ing = $stmt_ing->fetch(PDO::FETCH_ASSOC) ?: ['item_name' => ('ID ' . $ing_id), 'unit_name' => ''];
                $qty_str = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
                $line = "- " . htmlspecialchars((string)$ing['item_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ": {$qty_str}";
                if (!empty($ing['unit_name'])) $line .= " " . htmlspecialchars((string)$ing['unit_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lines[] = $line;
            }

            $who = htmlspecialchars((string)$current_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $telegram_msg = "✅ <b>DUYỆT CHUYỂN KHO</b>\n\n";
            $telegram_msg .= "🧾 Phiếu: <b>#{$t_id}</b>\n";
            $telegram_msg .= "👤 Duyệt bởi: <b>{$who}</b>\n";
            $telegram_msg .= "🏭 Từ: <b>" . htmlspecialchars($from_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n";
            $telegram_msg .= "➡️ Đến: <b>" . htmlspecialchars($to_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n";
            $telegram_msg .= "📦 Số mặt hàng: <b>" . count($details) . "</b>\n\n";
            $telegram_msg .= implode("\n", array_slice($lines, 0, 30));
            if (count($lines) > 30) $telegram_msg .= "\n<i>... (còn " . (count($lines) - 30) . " dòng)</i>";
        } elseif ($_POST['action'] === 'cancel_transfer') {
            $t_id = (int)$_POST['transfer_id'];
            $db->prepare("UPDATE inventory_transfers SET status = 'cancelled' WHERE id = ?")->execute([$t_id]);
        } elseif ($_POST['action'] === 'get_batches') {
            $id = (int)$_POST['item_id'];
            $stmt = $db->prepare("
                SELECT b.*, w.name as warehouse_name 
                FROM inventory_batches b
                JOIN warehouses w ON b.warehouse_id = w.id
                WHERE b.ingredient_id = ? AND b.quantity > 0
                ORDER BY (b.expiry_date IS NULL), b.expiry_date ASC
            ");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        $db->commit();
        if ($telegram_msg) {
            @sendTelegramNotification($telegram_msg);
        }
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// --- XỬ LÝ ẨN/HIỆN VÀ XÓA NGUYÊN LIỆU ---
if (isset($_GET['toggle_id'])) {
    $db->prepare("UPDATE inventory SET is_active = NOT is_active WHERE id = ?")->execute([(int)$_GET['toggle_id']]);
    header("Location: InventoryController.php");
    exit;
}
if (isset($_GET['delete_id'])) {
    $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([(int)$_GET['delete_id']]);
    header("Location: InventoryController.php");
    exit;
}

// --- KIỂM KÊ KHO (AUDIT THEO WAREHOUSE) ---
if (isset($_POST['perform_audit'])) {
    $w_id = (int)$_POST['audit_warehouse_id'];
    $db->beginTransaction();
    try {
        if (!$w_id) throw new Exception("Chưa chọn kho kiểm kê.");
        $raw_ids = array_keys($_POST['actual_qty'] ?? []);
        $valid_ids = array_filter(array_map('intval', $raw_ids), fn($id) => $id > 0);
        if (empty($valid_ids)) throw new Exception("Không có dữ liệu hợp lệ.");

        $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
        $bulk = $db->prepare("SELECT ingredient_id, quantity FROM inventory_stocks WHERE warehouse_id = ? AND ingredient_id IN ($placeholders)");
        $bulk->execute(array_merge([$w_id], $valid_ids));
        $inv_map = array_column($bulk->fetchAll(PDO::FETCH_ASSOC), 'quantity', 'ingredient_id');

        $db->prepare("INSERT INTO inventory_audits (performed_by, notes) VALUES (?, ?)")->execute([$current_user, trim($_POST['audit_notes'] ?? '')]);
        $audit_id = (int)$db->lastInsertId();

        $ins_detail = $db->prepare("INSERT INTO inventory_audit_details (audit_id, ingredient_id, system_qty, physical_qty, variance) VALUES (?, ?, ?, ?, ?)");
        $ins_history = $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, ?, ?, ?)");
        $upd_stock = $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = ?");

        foreach ($_POST['actual_qty'] as $ing_id => $physical_qty) {
            $ing_id = (int)$ing_id;
            if ($physical_qty === '' || $physical_qty === null) continue;

            $physical_qty = (float)$physical_qty;
            $system_qty = (float)($inv_map[$ing_id] ?? 0);
            $variance = $physical_qty - $system_qty;

            $ins_detail->execute([$audit_id, $ing_id, $system_qty, $physical_qty, $variance]);

            if ($variance != 0) {
                $upd_stock->execute([$w_id, $ing_id, $physical_qty, $physical_qty]);
                $type = $variance > 0 ? 'audit_adjust_up' : 'audit_adjust_down';
                $ins_history->execute([$ing_id, $w_id, $type, abs($variance), $current_user . ' (Kiểm kê)']);
            }
        }
        $db->commit();
        header("Location: InventoryController.php?tab=audit&msg=success");
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: InventoryController.php?tab=audit&msg=error");
    }
    exit;
}

// ============================================================
// 2. TRUY VẤN DỮ LIỆU HIỂN THỊ CHÍNH
// ============================================================

// Lấy danh sách nguyên liệu
$inv_items = $db->query("SELECT i.*, s.name as s_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.id ORDER BY i.item_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy cục tồn kho chi tiết và Map vào mảng
$stocks_raw = $db->query("SELECT ingredient_id, warehouse_id, quantity FROM inventory_stocks")->fetchAll(PDO::FETCH_ASSOC);
$stock_map = [];
$total_stock_map = [];
foreach ($stocks_raw as $s) {
    $stock_map[$s['ingredient_id']][$s['warehouse_id']] = (float)$s['quantity'];
    
    // TỔNG TỒN: Chỉ tính các kho vật lý (1, 2, 3, 4, 5), KHÔNG cộng kho ảo (6: Xuất, 7: Hủy)
    if (!in_array((int)$s['warehouse_id'], [6, 7])) {
        if (!isset($total_stock_map[$s['ingredient_id']])) $total_stock_map[$s['ingredient_id']] = 0;
        $total_stock_map[$s['ingredient_id']] += (float)$s['quantity'];
    }
}

$low_stock_count = 0;
$expiry_warn_count = 0;
$expired_count = 0;
$today = date('Y-m-d');
$warn_date = date('Y-m-d', strtotime('+' . $cfg_expiry_days . ' days'));

foreach ($inv_items as &$item) {
    $item['stocks'] = $stock_map[$item['id']] ?? [];
    $item['total_stock'] = $total_stock_map[$item['id']] ?? 0;

    $min = (float)($item['min_stock'] ?? 0);
    // Nếu món đó chưa cài định mức riêng, dùng định mức chung từ Cài đặt
    if ($min <= 0) $min = $cfg_low_stock;

    // CHỈ TÍNH CẢNH BÁO CHO NHỮNG MÓN ĐANG HOẠT ĐỘNG (is_active = 1)
    if ($item['is_active'] == 1) {
        if ($item['total_stock'] <= $min) $low_stock_count++;
        if (!empty($item['expiry_date'])) {
            if ($item['expiry_date'] < $today) {
                $expired_count++;
            } elseif ($item['expiry_date'] <= $warn_date) {
                $expiry_warn_count++;
            }
        }
    }
}
unset($item);
$inv = $inv_items;

// Danh sách Cần Đặt Hàng (PO) cũng sẽ bỏ qua các món đã Ẩn
$reorder_list = array_filter($inv, fn($i) => $i['min_stock'] > 0 && $i['total_stock'] <= $i['min_stock'] && $i['is_active'] == 1);
usort($reorder_list, fn($a, $b) => ($b['min_stock'] - $b['total_stock']) <=> ($a['min_stock'] - $a['total_stock']));

$top_used = $db->query("SELECT i.item_name, SUM(h.quantity) as total, i.unit_name FROM inventory_history h JOIN inventory i ON h.ingredient_id = i.id WHERE h.type = 'export' GROUP BY i.id ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$cats = $db->query("SELECT * FROM inventory_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$units = $db->query("SELECT * FROM inventory_units ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $db->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$history = $db->query("SELECT h.*, i.item_name, i.unit_name, w.name as warehouse_name FROM inventory_history h JOIN inventory i ON h.ingredient_id = i.id LEFT JOIN warehouses w ON h.warehouse_id = w.id ORDER BY h.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$transfers = $db->query("
    SELECT t.*, 
           w1.name as from_warehouse_name, 
           w2.name as to_warehouse_name,
           (SELECT GROUP_CONCAT(CONCAT(i.item_name, ' (', td.quantity, ')') SEPARATOR ', ') 
            FROM transfer_details td 
            JOIN inventory i ON td.ingredient_id = i.id 
            WHERE td.transfer_id = t.id) as items_summary
    FROM inventory_transfers t
    JOIN warehouses w1 ON t.from_warehouse_id = w1.id
    JOIN warehouses w2 ON t.to_warehouse_id = w2.id
    ORDER BY t.transfer_date DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
$pending_transfers_count = (int)$db->query("SELECT COUNT(*) FROM inventory_transfers WHERE status = 'pending'")->fetchColumn();
$chart_raw = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as mo, SUM(CASE WHEN type='import' THEN quantity ELSE 0 END) as ti, SUM(CASE WHEN type='export' THEN quantity ELSE 0 END) as te, SUM(CASE WHEN type='loss' THEN quantity ELSE 0 END) as tl FROM inventory_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH) GROUP BY mo ORDER BY mo ASC")->fetchAll(PDO::FETCH_ASSOC);

// BÁO CÁO GIÁ TRỊ TỒN KHO THEO TỪNG KHO (VALUE PER WAREHOUSE)
$warehouse_values = $db->query("
    SELECT w.id, w.name, 
           SUM(s.quantity * i.cost_price) as total_value,
           COUNT(s.ingredient_id) as item_count
    FROM warehouses w
    LEFT JOIN inventory_stocks s ON w.id = s.warehouse_id
    LEFT JOIN inventory i ON s.ingredient_id = i.id
    WHERE w.status = 1
    GROUP BY w.id, w.name
")->fetchAll(PDO::FETCH_ASSOC);

// DỮ LIỆU PHIẾU ĐẶT HÀNG (PO)
$pos = $db->query("
    SELECT p.*, s.name as supplier_name 
    FROM purchase_orders p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// DANH SÁCH NGUYÊN LIỆU CHO MODAL TẠO PO
$ingredients = $db->query("SELECT id, item_name, unit_name, cost_price FROM inventory WHERE is_active = 1 ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';

// Gọi View
require_once __DIR__ . '/../../admin/views/inventory/inventory_view.php';

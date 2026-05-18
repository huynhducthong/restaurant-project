<?php
// File: admin/controllers/POController.php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); 
    exit;
}
$current_user = $_SESSION['username'] ?? 'Admin';

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// ==========================================================
// 1. XỬ LÝ TẠO PHIẾU ĐẶT HÀNG (PO) MỚI
// ==========================================================
if (isset($_POST['create_po'])) {
    $supplier_id = (int)$_POST['supplier_id'];
    $po_code = 'PO-' . date('YmdHis'); // Tự động sinh mã, VD: PO-20260506143000
    $total_amount = 0;

    // Tính tổng tiền dựa trên số lượng và giá nhập
    foreach ($_POST['qty'] as $key => $qty) {
        $total_amount += (float)$qty * (float)$_POST['price'][$key];
    }

    $db->beginTransaction();
    try {
        // Lưu thông tin chung của Phiếu đặt hàng
        $stmt = $db->prepare("INSERT INTO purchase_orders (po_code, supplier_id, total_amount, status, created_by) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->execute([$po_code, $supplier_id, $total_amount, $current_user]);
        $po_id = $db->lastInsertId();

        // DÒNG ĐÃ ĐƯỢC SỬA ĐÚNG TÊN CỘT:
        $stmt_detail = $db->prepare("INSERT INTO purchase_order_details (po_id, ingredient_id, expected_qty, expected_price) VALUES (?, ?, ?, ?)");
        
        foreach ($_POST['item_id'] as $key => $item_id) {
            $stmt_detail->execute([
                $po_id, 
                (int)$item_id, 
                (float)$_POST['qty'][$key], 
                (float)$_POST['price'][$key]
            ]);
        }
        
        $db->commit();
        header("Location: POController.php?msg=po_created");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die("Lỗi khi tạo PO: " . $e->getMessage());
    }
}

// ==========================================================
// 1.5 XỬ LÝ AJAX LẤY CHI TIẾT PHIẾU ĐẶT HÀNG (ĐỂ XEM)
// ==========================================================
if (isset($_POST['action']) && $_POST['action'] === 'get_details') {
    header('Content-Type: application/json');
    $po_id = (int)$_POST['po_id'];
    try {
        // Kết hợp 2 bảng để lấy tên và đơn vị tính của nguyên liệu
        $stmt = $db->prepare("
            SELECT d.*, 
                   IFNULL(i.item_name, CONCAT('Nguyên liệu đã xóa (ID: ', d.ingredient_id, ')')) as item_name, 
                   IFNULL(i.unit_name, '-') as unit_name 
            FROM purchase_order_details d
            LEFT JOIN inventory i ON d.ingredient_id = i.id
            WHERE d.po_id = ?
        ");
        $stmt->execute([$po_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $details]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// ================= XỬ LÝ QUICK ADD INGREDIENT (AJAX) =================
if (isset($_POST['action']) && $_POST['action'] === 'quick_add_ingredient') {
    header('Content-Type: application/json');
    $name = trim($_POST['name'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $cat  = trim($_POST['category'] ?? 'Khác');

    try {
        if (!$name || !$unit) throw new Exception("Thiếu thông tin bắt buộc.");

        // Kiểm tra xem đã tồn tại chưa
        $stmt_check = $db->prepare("SELECT id FROM inventory WHERE item_name = ?");
        $stmt_check->execute([$name]);
        if ($stmt_check->fetch()) throw new Exception("Nguyên liệu này đã tồn tại trong danh mục.");

        // Thêm vào bảng inventory chính
        $stmt_ins = $db->prepare("INSERT INTO inventory (item_name, unit_name, category, is_active) VALUES (?, ?, ?, 1)");
        $stmt_ins->execute([$name, $unit, $cat]);
        $new_id = $db->lastInsertId();

        echo json_encode(['status' => 'success', 'id' => $new_id]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ==========================================================
// 2. XỬ LÝ NHẬN HÀNG CHI TIẾT (TỪ MODAL)
// ==========================================================
if (isset($_POST['receive_po_final'])) {
    $po_id = (int)$_POST['po_id'];
    $main_warehouse_id = 1; // 1 = ID của Kho Tổng (Central Warehouse)
    
    $db->beginTransaction();
    try {
        // Kiểm tra xem PO có tồn tại và đang ở trạng thái pending không
        $check_po = $db->prepare("SELECT status FROM purchase_orders WHERE id = ?");
        $check_po->execute([$po_id]);
        if ($check_po->fetchColumn() !== 'pending') {
            throw new Exception("Phiếu nhập này đã được xử lý từ trước!");
        }

        // Các câu lệnh chuẩn bị sẵn
        $upd_stock   = $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $ins_history = $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)");
        $upd_inv     = $db->prepare("UPDATE inventory SET cost_price = ?, expiry_date = ? WHERE id = ?");
        $ins_batch   = $db->prepare("INSERT INTO inventory_batches (ingredient_id, warehouse_id, batch_code, quantity, expiry_date, cost_price) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($_POST['ingredient_id'] as $key => $ing_id) {
            $ing_id    = (int)$ing_id;
            $new_qty   = (float)$_POST['received_qty'][$key];
            $new_price = (float)str_replace(',', '', $_POST['received_price'][$key]);
            $hsd       = !empty($_POST['expiry_date'][$key]) ? $_POST['expiry_date'][$key] : null;

            if ($new_qty <= 0) continue;

            // 1. Tính giá vốn BQGQ
            $stmt_old_stock = $db->prepare("SELECT IFNULL(SUM(quantity), 0) FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id IN (1,2,3,4,5)");
            $stmt_old_stock->execute([$ing_id]);
            $old_total_stock = (float)$stmt_old_stock->fetchColumn();

            $stmt_old_price = $db->prepare("SELECT cost_price FROM inventory WHERE id = ?");
            $stmt_old_price->execute([$ing_id]);
            $old_price = (float)$stmt_old_price->fetchColumn();

            // Công thức: (Tồn cũ × Giá cũ + Nhập mới × Giá mới) / (Tồn cũ + Nhập mới)
            $avg_price = ($old_total_stock + $new_qty) > 0
                ? (($old_total_stock * $old_price) + ($new_qty * $new_price)) / ($old_total_stock + $new_qty)
                : $new_price;

            // 2. Cập nhật lô hàng (Batch)
            $po_code_res = $db->query("SELECT po_code FROM purchase_orders WHERE id = $po_id")->fetchColumn();
            $ins_batch->execute([$ing_id, $main_warehouse_id, $po_code_res, $new_qty, $hsd, $new_price]);

            // 3. Cập nhật HSD tổng (Lấy ngày sớm nhất của các lô còn hàng)
            $stmt_min_hsd = $db->prepare("SELECT MIN(expiry_date) FROM inventory_batches WHERE ingredient_id = ? AND quantity > 0 AND expiry_date IS NOT NULL");
            $stmt_min_hsd->execute([$ing_id]);
            $earliest_hsd = $stmt_min_hsd->fetchColumn() ?: $hsd;

            // 4. Cập nhật giá và HSD vào bảng chính
            $upd_inv->execute([$avg_price, $earliest_hsd, $ing_id]);

            // 5. Cộng số lượng vào Kho Tổng (ID = 1)
            $upd_stock->execute([$main_warehouse_id, $ing_id, $new_qty, $new_qty]);

            // 6. Ghi lịch sử giao dịch
            $ins_history->execute([$ing_id, $main_warehouse_id, $new_qty, $current_user . " (Nhận hàng PO #" . $po_id . ")"]);
        }

        // Đổi trạng thái PO thành hoàn tất
        $db->prepare("UPDATE purchase_orders SET status = 'completed' WHERE id = ?")->execute([$po_id]);

        $db->commit();
        header("Location: POController.php?msg=success");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die("Lỗi hệ thống khi nhận hàng: " . $e->getMessage());
    }
}

// ==========================================================
// 3. TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ LÊN VIEW
// ==========================================================

// Danh sách Phiếu đặt hàng (Hiển thị ngoài bảng)
$pos = $db->query("
    SELECT p.*, s.name as supplier_name 
    FROM purchase_orders p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Danh sách phục vụ cho Modal Tạo PO Mới
$suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$ingredients = $db->query("SELECT id, item_name, unit_name, cost_price FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Gọi giao diện
require_once __DIR__ . '/../views/po/po_list.php';
?>
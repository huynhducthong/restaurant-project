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
            SELECT d.*, i.item_name, i.unit_name 
            FROM purchase_order_details d
            JOIN inventory i ON d.ingredient_id = i.id
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

// ==========================================================
// 2. XỬ LÝ NHẬN HÀNG VÀ TỰ ĐỘNG CỘNG VÀO "KHO TỔNG"
// ==========================================================
if (isset($_GET['action']) && $_GET['action'] == 'receive' && isset($_GET['id'])) {
    $po_id = (int)$_GET['id'];
    $main_warehouse_id = 1; // 1 = ID của Kho Tổng (Central Warehouse)
    
    $db->beginTransaction();
    try {
        // Kiểm tra xem PO có tồn tại và đang ở trạng thái pending không
        $check_po = $db->prepare("SELECT status FROM purchase_orders WHERE id = ?");
        $check_po->execute([$po_id]);
        if ($check_po->fetchColumn() !== 'pending') {
            throw new Exception("Phiếu nhập này đã được xử lý từ trước!");
        }

        // Lấy danh sách chi tiết hàng hóa trong PO
        $stmt = $db->prepare("SELECT * FROM purchase_order_details WHERE po_id = ?");
        $stmt->execute([$po_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Các câu lệnh chuẩn bị sẵn
        $upd_stock   = $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $ins_history = $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)");

        foreach ($details as $d) {
            // Hỗ trợ cả 2 chuẩn tên cột
            $new_qty   = isset($d['quantity'])      ? (float)$d['quantity']       : (float)$d['expected_qty'];
            $new_price = isset($d['price'])         ? (float)$d['price']          : (float)$d['expected_price'];
            $ing_id    = (int)$d['ingredient_id'];

            // 1. Tính giá vốn BQGQ (Bình quân gia quyền) — ĐỒNG NHẤT VỚI InventoryController
            $stmt_old_stock = $db->prepare("SELECT IFNULL(SUM(quantity), 0) FROM inventory_stocks WHERE ingredient_id = ?");
            $stmt_old_stock->execute([$ing_id]);
            $old_total_stock = (float)$stmt_old_stock->fetchColumn();

            $stmt_old_price = $db->prepare("SELECT cost_price FROM inventory WHERE id = ?");
            $stmt_old_price->execute([$ing_id]);
            $old_price = (float)$stmt_old_price->fetchColumn();

            // Công thức: (Tồn cũ × Giá cũ + Nhập mới × Giá mới) / (Tồn cũ + Nhập mới)
            $avg_price = ($old_total_stock + $new_qty) > 0
                ? (($old_total_stock * $old_price) + ($new_qty * $new_price)) / ($old_total_stock + $new_qty)
                : $new_price;

            $db->prepare("UPDATE inventory SET cost_price = ? WHERE id = ?")->execute([$avg_price, $ing_id]);

            // 2. Cộng số lượng vào Kho Tổng (ID = 1)
            $upd_stock->execute([$main_warehouse_id, $ing_id, $new_qty, $new_qty]);

            // 3. Ghi lịch sử giao dịch
            $ins_history->execute([$ing_id, $main_warehouse_id, $new_qty, $current_user . " (Nhận hàng từ PO #" . $po_id . ")"]);
        }

        // Đổi trạng thái PO thành hoàn tất (Đã nhập kho)
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
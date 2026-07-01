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
// 0. XUẤT EXCEL TỒN KHO & HSD (PO EXPORT)
// ==========================================================
if (isset($_GET['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="BaoCao_NhapHang_PO_' . date('Ymd_His') . '.xls"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $sql = "SELECT 
                po.created_at, po.po_code, IFNULL(s.name, 'Nhà cung cấp lẻ') as supplier_name, po.status,
                i.item_name, i.unit_name,
                pod.expected_qty, pod.expected_price,
                ib.quantity as received_qty, ib.cost_price as received_price, 
                ib.expiry_date, ib.receiving_temperature
            FROM purchase_orders po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            JOIN purchase_order_details pod ON po.id = pod.po_id
            JOIN inventory i ON pod.ingredient_id = i.id
            LEFT JOIN inventory_batches ib ON ib.batch_code COLLATE utf8mb4_unicode_ci = po.po_code COLLATE utf8mb4_unicode_ci AND ib.ingredient_id = pod.ingredient_id
            ORDER BY po.created_at DESC";
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="utf-8"></head><body>';
    echo '<table border="1" style="border-collapse:collapse; text-align:center;">';
    echo '<tr style="background-color:#2c3e50; color:white; font-weight:bold;">';
    echo '<th>Ngày Lập PO</th>';
    echo '<th>Mã Phiếu (PO Code)</th>';
    echo '<th>Nhà Cung Cấp</th>';
    echo '<th>Trạng Thái</th>';
    echo '<th>Tên Nguyên Liệu</th>';
    echo '<th>SL Đặt</th>';
    echo '<th>Giá Đặt (VNĐ)</th>';
    echo '<th>SL Thực Nhận</th>';
    echo '<th>Giá Nhập Thực (VNĐ)</th>';
    echo '<th style="background-color:#e74c3c;">Ngày Hết Hạn (HSD)</th>';
    echo '<th>Nhiệt Độ Nhận</th>';
    echo '</tr>';

    foreach ($data as $row) {
        $status_text = $row['status'] === 'completed' ? 'Đã Nhập' : ($row['status'] === 'pending' ? 'Chưa Nhập' : 'Đã Hủy');
        echo '<tr>';
        echo '<td>' . date('d/m/Y H:i', strtotime($row['created_at'])) . '</td>';
        echo '<td style="mso-number-format:\'@\';">' . htmlspecialchars($row['po_code']) . '</td>';
        echo '<td>' . htmlspecialchars($row['supplier_name']) . '</td>';
        echo '<td>' . $status_text . '</td>';
        echo '<td style="text-align:left;">' . htmlspecialchars($row['item_name']) . '</td>';
        echo '<td>' . (float)$row['expected_qty'] . ' ' . $row['unit_name'] . '</td>';
        echo '<td>' . number_format($row['expected_price']) . '</td>';
        echo '<td>' . ($row['received_qty'] !== null ? (float)$row['received_qty'] . ' ' . $row['unit_name'] : '-') . '</td>';
        echo '<td>' . ($row['received_price'] !== null ? number_format($row['received_price']) : '-') . '</td>';
        echo '<td style="font-weight:bold; color:#e74c3c;">' . ($row['expiry_date'] ? date('d/m/Y', strtotime($row['expiry_date'])) : 'Không có') . '</td>';
        echo '<td>' . htmlspecialchars($row['receiving_temperature'] ?? '-') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

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
        header("Location: InventoryController.php?tab=po&msg=po_created");
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

        $po_info = $db->prepare("SELECT p.batch_cert_file, p.supplier_id FROM purchase_orders p WHERE p.id = ?");
        $po_info->execute([$po_id]);
        $po_data = $po_info->fetch(PDO::FETCH_ASSOC) ?: ['batch_cert_file' => null, 'supplier_id' => null];

        $supplier_certs = [];
        if (!empty($po_data['supplier_id'])) {
            $stmt_certs = $db->prepare("SELECT * FROM supplier_certificates WHERE supplier_id = ?");
            $stmt_certs->execute([$po_data['supplier_id']]);
            $supplier_certs = $stmt_certs->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'status' => 'success', 
            'data' => $details, 
            'batch_cert_file' => $po_data['batch_cert_file'],
            'supplier_certs' => $supplier_certs
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// ================= XỬ LÝ HỦY PHIẾU ĐẶT HÀNG (AJAX) =================
if (isset($_POST['action']) && $_POST['action'] === 'cancel_po') {
    header('Content-Type: application/json');
    $po_id = (int)($_POST['po_id'] ?? 0);
    
    try {
        if (!$po_id) throw new Exception("Thiếu thông tin mã phiếu.");
        
        $check_po = $db->prepare("SELECT status FROM purchase_orders WHERE id = ?");
        $check_po->execute([$po_id]);
        if ($check_po->fetchColumn() !== 'pending') {
            throw new Exception("Chỉ có thể hủy phiếu đang ở trạng thái 'Chờ nhận'!");
        }

        $db->prepare("UPDATE purchase_orders SET status = 'cancelled' WHERE id = ?")->execute([$po_id]);
        echo json_encode(['status' => 'success', 'message' => 'Hủy phiếu thành công.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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
        $ins_batch   = $db->prepare("INSERT INTO inventory_batches (ingredient_id, warehouse_id, batch_code, quantity, expiry_date, cost_price, receiving_temperature, supplier_batch_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins_inspection = $db->prepare("INSERT INTO po_receipt_inspections (po_id, ingredient_id, check_packaging, check_color, check_odor, check_freshness, check_size, check_weight, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($_POST['ingredient_id'] as $key => $ing_id) {
            $ing_id    = (int)$ing_id;
            $new_qty   = (float)$_POST['received_qty'][$key];
            $new_price = (float)str_replace(',', '', $_POST['received_price'][$key]);
            $hsd       = !empty($_POST['expiry_date'][$key]) ? $_POST['expiry_date'][$key] : null;
            $temp      = !empty($_POST['receiving_temperature'][$key]) ? trim($_POST['receiving_temperature'][$key]) : null;

            $supplier_batch_number = !empty($_POST['supplier_batch_number'][$key]) ? trim($_POST['supplier_batch_number'][$key]) : null;

            if ($new_qty <= 0) continue;

            // 1. Tính giá vốn BQGQ
            $stmt_old_stock = $db->prepare("SELECT IFNULL(SUM(quantity), 0) FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id IN (1,2,3,4,5,8)");
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
            $ins_batch->execute([$ing_id, $main_warehouse_id, $po_code_res, $new_qty, $hsd, $new_price, $temp, $supplier_batch_number]);

            // 3. Cập nhật HSD tổng (Lấy ngày sớm nhất của các lô còn hàng)
            $stmt_min_hsd = $db->prepare("SELECT MIN(expiry_date) FROM inventory_batches WHERE ingredient_id = ? AND quantity > 0 AND expiry_date IS NOT NULL AND warehouse_id NOT IN (6, 7)");
            $stmt_min_hsd->execute([$ing_id]);
            $earliest_hsd = $stmt_min_hsd->fetchColumn() ?: $hsd;

            // 4. Cập nhật giá và HSD vào bảng chính
            $upd_inv->execute([$avg_price, $earliest_hsd, $ing_id]);

            // 5. Cộng số lượng vào Kho Tổng (ID = 1)
            $upd_stock->execute([$main_warehouse_id, $ing_id, $new_qty, $new_qty]);

            // 6. Ghi lịch sử giao dịch
            $ins_history->execute([$ing_id, $main_warehouse_id, $new_qty, $current_user . " (Nhận hàng PO #" . $po_id . ")"]);
            
            // 7. Lưu Inspection Checklist & Ảnh
            $chk_packaging = isset($_POST['chk_packaging'][$ing_id]) ? 1 : 0;
            $chk_color     = isset($_POST['chk_color'][$ing_id]) ? 1 : 0;
            $chk_odor      = isset($_POST['chk_odor'][$ing_id]) ? 1 : 0;
            $chk_freshness = isset($_POST['chk_freshness'][$ing_id]) ? 1 : 0;
            $chk_size      = isset($_POST['chk_size'][$ing_id]) ? 1 : 0;
            $chk_weight    = isset($_POST['chk_weight'][$ing_id]) ? 1 : 0;
            
            $image_path = null;
            $file_input_name = "inspection_image_" . $ing_id;
            if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION);
                $image_path = 'insp_' . $po_id . '_' . $ing_id . '_' . time() . '.' . $ext;
                if (!is_dir(__DIR__ . '/../../uploads/inspections/')) {
                    mkdir(__DIR__ . '/../../uploads/inspections/', 0777, true);
                }
                move_uploaded_file($_FILES[$file_input_name]['tmp_name'], __DIR__ . '/../../uploads/inspections/' . $image_path);
            }
            
            $ins_inspection->execute([$po_id, $ing_id, $chk_packaging, $chk_color, $chk_odor, $chk_freshness, $chk_size, $chk_weight, $image_path]);
        }

        // Xử lý upload Giấy kiểm dịch (Batch Certificate)
        $batch_cert = null;
        if (isset($_FILES['po_batch_cert']) && $_FILES['po_batch_cert']['error'] == UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['po_batch_cert']['name'], PATHINFO_EXTENSION);
            $batch_cert = 'cert_po_' . $po_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['po_batch_cert']['tmp_name'], __DIR__ . '/../../uploads/po_certs/' . $batch_cert);
        }

        // Đổi trạng thái PO thành hoàn tất và lưu file
        if ($batch_cert) {
            $db->prepare("UPDATE purchase_orders SET status = 'completed', batch_cert_file = ? WHERE id = ?")->execute([$batch_cert, $po_id]);
        } else {
            $db->prepare("UPDATE purchase_orders SET status = 'completed' WHERE id = ?")->execute([$po_id]);
        }

        // Gửi thông báo Telegram
        require_once __DIR__ . '/../../config/notification_helper.php';
        $po_code_res = $db->query("SELECT po_code FROM purchase_orders WHERE id = $po_id")->fetchColumn();
        $who = htmlspecialchars((string)$current_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        $msg = "✅ <b>NHẬN HÀNG (DUYỆT PO)</b>\n\n";
        $msg .= "🧾 Mã phiếu: <b>{$po_code_res}</b>\n";
        $msg .= "👤 Người nhận: <b>{$who}</b>\n";
        $msg .= "🏭 Vào kho: <b>Kho Tổng (Tiếp nhận hàng)</b>\n";
        sendTelegramNotification($msg);

        $db->commit();
        header("Location: InventoryController.php?tab=po&msg=success");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die("Lỗi hệ thống khi nhận hàng: " . $e->getMessage());
    }
}

// ==========================================================
// 3. REDIRECT ON GET REQUEST
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Chuyển hướng người dùng về trang Quản lý Kho, tab PO
    $query = $_SERVER['QUERY_STRING'] ? '&' . $_SERVER['QUERY_STRING'] : '';
    header("Location: InventoryController.php?tab=po" . $query);
    exit;
}
?>
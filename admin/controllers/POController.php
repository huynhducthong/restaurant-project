<?php
// admin/controllers/POController.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}
$current_user = $_SESSION['username'] ?? 'Admin';

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// --- XỬ LÝ NHẬN HÀNG (CỘNG KHO TỰ ĐỘNG) ---
if (isset($_GET['action']) && $_GET['action'] == 'receive' && isset($_GET['id'])) {
    $po_id = (int)$_GET['id'];
    
    $db->beginTransaction();
    try {
        // 1. Lấy chi tiết PO
        $stmt = $db->prepare("SELECT ingredient_id, expected_qty, expected_price FROM purchase_order_details WHERE po_id = ?");
        $stmt->execute([$po_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Vòng lặp cập nhật kho và lưu lịch sử
        $upd_stock = $db->prepare("UPDATE inventory SET stock_quantity = stock_quantity + ?, cost_price = ? WHERE id = ?");
        $ins_history = $db->prepare("INSERT INTO inventory_history (ingredient_id, type, quantity, performed_by) VALUES (?, 'import', ?, ?)");
        
        foreach ($details as $d) {
            $upd_stock->execute([$d['expected_qty'], $d['expected_price'], $d['ingredient_id']]);
            $ins_history->execute([$d['ingredient_id'], $d['expected_qty'], $current_user . " (Nhập từ PO)"]);
        }

        // 3. Chuyển trạng thái PO thành completed
        $db->prepare("UPDATE purchase_orders SET status = 'completed' WHERE id = ?")->execute([$po_id]);

        $db->commit();
        header("Location: POController.php?msg=success");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die("Lỗi khi nhận hàng: " . $e->getMessage());
    }
}

// --- TRUY VẤN DỮ LIỆU HIỂN THỊ ---
$pos = $db->query("
    SELECT p.*, s.name as supplier_name 
    FROM purchase_orders p
    JOIN suppliers s ON p.supplier_id = s.id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../views/po/po_list.php';
?>
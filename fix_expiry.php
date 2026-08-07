<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

// Kiểm tra quyền admin nếu cần (tùy chọn)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Bạn không có quyền truy cập!";
    exit;
}

$db = (new Database())->getConnection();

try {
    $stmt = $db->prepare("UPDATE inventory_batches SET expiry_date = DATE_ADD(expiry_date, INTERVAL 1 YEAR) WHERE expiry_date <= '2026-12-31'");
    $stmt->execute();
    $rowCount = $stmt->rowCount();
    echo "<h1 style='color:green;'>Thành công!</h1>";
    echo "<p>Đã gia hạn HSD thêm 1 năm cho $rowCount lô hàng.</p>";
    echo "<a href='admin/inventory_dashboard.php'>Quay lại trang Quản lý kho</a>";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>

<?php
// =====================================================
// Xuất danh sách đăng ký newsletter ra CSV
// File: admin/export_subscribers.php
// =====================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: ../public/login.php"); exit(); }

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

try {
    $subs = $db->query("SELECT id, email, subscribed_at, is_active FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Lỗi truy vấn database: ' . $e->getMessage());
}

$filename = 'newsletter_subscribers_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM for Excel UTF-8
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, ['ID', 'Email', 'Ngày Đăng Ký', 'Trạng Thái']);

foreach ($subs as $sub) {
    fputcsv($out, [
        $sub['id'],
        $sub['email'],
        date('d/m/Y H:i', strtotime($sub['subscribed_at'])),
        $sub['is_active'] ? 'Hoạt động' : 'Đã hủy',
    ]);
}

fclose($out);
exit();
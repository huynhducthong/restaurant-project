<?php
// =====================================================
// File: admin/export_subscribers.php
// Xuất danh sách đăng ký newsletter ra CSV
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ FIX: Đường dẫn login nhất quán với toàn project
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

try {
    $subs = $db->query(
        "SELECT id, email, subscribed_at, is_active
         FROM newsletter_subscribers
         ORDER BY subscribed_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    header('Location: admin_dashboard.php?error=export_failed');
    exit;
}

$total    = count($subs);
$active   = count(array_filter($subs, fn($r) => $r['is_active']));
$inactive = $total - $active;
$filename = 'newsletter_subscribers_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 cho Excel đọc đúng tiếng Việt
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

// ✅ THÊM: Dòng tổng cuối file
fputcsv($out, []);
fputcsv($out, ['Xuất lúc:', date('d/m/Y H:i'), '', '']);
fputcsv($out, ['Tổng cộng:', $total . ' email', 'Hoạt động: ' . $active, 'Đã hủy: ' . $inactive]);

fclose($out);
exit;

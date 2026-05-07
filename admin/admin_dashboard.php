<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$total_foods = $total_users = $total_bookings = $selected_revenue = 0;
$chart_revenue = array_fill(0, 12, 0);
$status_data = [0, 0, 0];

$filter_date = $_GET['date'] ?? '';
$filter_month = (int) ($_GET['month'] ?? 0);
$filter_quarter = (int) ($_GET['quarter'] ?? 0);
$filter_year = (int) ($_GET['year'] ?? date('Y'));

$year_revenue = (int) ($_GET['year_revenue'] ?? $filter_year);
$year_booking = (int) ($_GET['year_booking'] ?? $filter_year);

$conditions = ["YEAR(created_at) = ?"];
$params = [$filter_year];
if ($filter_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date)) {
    $conditions[] = "DATE(created_at) = ?";
    $params[] = $filter_date;
}
if ($filter_month >= 1 && $filter_month <= 12) {
    $conditions[] = "MONTH(created_at) = ?";
    $params[] = $filter_month;
}
if ($filter_quarter >= 1 && $filter_quarter <= 4) {
    $conditions[] = "QUARTER(created_at) = ?";
    $params[] = $filter_quarter;
}
$where_sql = implode(" AND ", $conditions);

try {
    $total_foods = $db->query("SELECT COUNT(*) FROM foods")->fetchColumn() ?: 0;
    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE YEAR(date) = ?");
    $stmt->execute([$year_booking]);
    $total_bookings = $stmt->fetchColumn() ?: 0;
    $stmt = $db->prepare("SELECT SUM(total_price) FROM orders WHERE $where_sql");
    $stmt->execute($params);
    $selected_revenue = $stmt->fetchColumn() ?: 0;
    for ($m = 1; $m <= 12; $m++) {
        $stmt = $db->prepare("SELECT SUM(total_price) FROM orders WHERE MONTH(created_at)=? AND YEAR(created_at)=?");
        $stmt->execute([$m, $year_revenue]);
        $chart_revenue[$m - 1] = (int) $stmt->fetchColumn();
    }
    foreach (['Completed', 'Pending', 'Cancelled'] as $i => $st) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE status=? AND YEAR(date)=?");
        $stmt->execute([$st, $year_booking]);
        $status_data[$i] = (int) $stmt->fetchColumn();
    }
} catch (Exception $e) {
}
// Phần HTML/Chart.js giữ nguyên của bạn
?>
<!-- sao chép nguyên phần HTML/JS từ file gốc của bạn, chỉ bỏ phần PHP logic cũ -->
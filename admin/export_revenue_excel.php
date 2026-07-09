<?php
// admin/export_revenue_excel.php
require_once __DIR__ . '/auth_check.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

// Query logic similar to dashboard
$conditions = [];
$params = [];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $conditions[] = "DATE(created_at) >= ?";
    $params[] = $start_date;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $conditions[] = "DATE(created_at) <= ?";
    $params[] = $end_date;
}
$where_sql = count($conditions) > 0 ? implode(" AND ", $conditions) : "1=1";

// 1. Get Revenue (Bookings)
$stmt = $db->prepare("SELECT id, booking_date as date, 'Booking' as type, total_amount, status FROM service_bookings WHERE status != 'Cancelled' AND $where_sql ORDER BY created_at ASC");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Get Revenue (POS)
$stmt = $db->prepare("SELECT id, created_at as date, 'POS' as type, total_amount, status FROM pos_orders WHERE status = 'paid' AND booking_id IS NULL AND $where_sql ORDER BY created_at ASC");
$stmt->execute($params);
$pos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Get Expenses
$expense_conditions = [];
$expense_params = [];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $expense_conditions[] = "expense_date >= ?";
    $expense_params[] = $start_date;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $expense_conditions[] = "expense_date <= ?";
    $expense_params[] = $end_date;
}
$where_expense = count($expense_conditions) > 0 ? implode(" AND ", $expense_conditions) : "1=1";
$stmt = $db->prepare("SELECT expense_date as date, note, amount, category FROM restaurant_expenses WHERE $where_expense ORDER BY expense_date ASC");
$stmt->execute($expense_params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_revenue = 0;
foreach ($bookings as $b) $total_revenue += (float)$b['total_amount'];
foreach ($pos as $p) $total_revenue += (float)$p['total_amount'];

$total_expense = 0;
foreach ($expenses as $e) $total_expense += (float)$e['amount'];

$profit = $total_revenue - $total_expense;

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Bao_Cao_Doanh_Thu_" . $start_date . "_" . $end_date . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Báo Cáo</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '</head><body>';

echo "<h2>BÁO CÁO DOANH THU & CHI PHÍ</h2>";
echo "<p>Thời gian: $start_date đến $end_date</p>";
echo "<table border='1'>";
echo "<tr><th style='background:#f1c40f'>TỔNG DOANH THU</th><th style='background:#e74c3c; color:white;'>TỔNG CHI PHÍ</th><th style='background:#2ecc71; color:white;'>LỢI NHUẬN TỊNH</th></tr>";
echo "<tr><td><b style='font-size:16px'>" . number_format($total_revenue) . " đ</b></td><td><b style='font-size:16px'>" . number_format($total_expense) . " đ</b></td><td><b style='font-size:16px'>" . number_format($profit) . " đ</b></td></tr>";
echo "</table><br><br>";

echo "<h3>CHI TIẾT DOANH THU</h3>";
echo "<table border='1'>";
echo "<tr style='background:#2c3e50; color:white;'><th>Ngày / Giờ</th><th>Mã Đơn / Bàn</th><th>Loại</th><th>Trạng Thái</th><th>Số Tiền (đ)</th></tr>";
foreach ($bookings as $b) {
    echo "<tr><td>{$b['date']}</td><td>#{$b['id']}</td><td>Đặt bàn</td><td>{$b['status']}</td><td>" . number_format($b['total_amount']) . "</td></tr>";
}
foreach ($pos as $p) {
    echo "<tr><td>{$p['date']}</td><td>#{$p['id']}</td><td>Gọi món POS</td><td>{$p['status']}</td><td>" . number_format($p['total_amount']) . "</td></tr>";
}
if (count($bookings) == 0 && count($pos) == 0) echo "<tr><td colspan='5'>Không có dữ liệu doanh thu</td></tr>";
echo "</table><br><br>";

echo "<h3>CHI TIẾT CHI PHÍ</h3>";
echo "<table border='1'>";
echo "<tr style='background:#2c3e50; color:white;'><th>Ngày</th><th>Hạng Mục</th><th>Mô Tả</th><th>Số Tiền (đ)</th></tr>";
foreach ($expenses as $e) {
    echo "<tr><td>{$e['date']}</td><td>{$e['category']}</td><td>{$e['note']}</td><td>" . number_format($e['amount']) . "</td></tr>";
}
if (count($expenses) == 0) echo "<tr><td colspan='4'>Không có dữ liệu chi phí</td></tr>";
echo "</table>";

echo '</body></html>';

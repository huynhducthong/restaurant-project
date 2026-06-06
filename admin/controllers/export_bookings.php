<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$db = (new Database())->getConnection();

// Lọc dữ liệu giống như trang quản lý
$filter = $_GET['filter'] ?? 'all';
if ($filter == 'all') {
    $stmt = $db->prepare("SELECT * FROM service_bookings ORDER BY created_at DESC");
    $stmt->execute();
} elseif ($filter == 'bespoke') {
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE combo_id = -1 ORDER BY created_at DESC");
    $stmt->execute();
} else {
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE service_type = :type ORDER BY created_at DESC");
    $stmt->execute([':type' => $filter]);
}
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin Tên bàn để xuất
$tables_stmt = $db->query("SELECT id, table_code FROM restaurant_tables");
$tables = $tables_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="DanhSachDatBan_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

fputcsv($output, ['ID', 'Tên Khách Hàng', 'SĐT', 'Loại Dịch Vụ', 'Thời Gian', 'Số Khách', 'Tổng Tiền (VNĐ)', 'Đã Cọc (VNĐ)', 'Trạng Thái', 'Bàn/Phòng', 'Ghi Chú', 'Y/c Đầu Bếp', 'Ngày Tạo']);

foreach ($bookings as $b) {
    $svc = 'Khác';
    if ($b['service_type'] == 'table') $svc = 'Đặt Bàn Tiêu Chuẩn';
    if ($b['service_type'] == 'birthday') $svc = 'Tiệc Kỷ Niệm';
    if ($b['service_type'] == 'chef') $svc = 'Đầu Bếp Tại Gia';
    if ($b['service_type'] == 'bespoke' || $b['combo_id'] == '-1') $svc = 'Thiết Kế Riêng';

    $status = 'Khác';
    if ($b['status'] == 'Pending') $status = 'Chờ duyệt';
    if ($b['status'] == 'Confirmed') $status = 'Đã xác nhận';
    if ($b['status'] == 'Completed') $status = 'Đã hoàn thành';
    if ($b['status'] == 'Cancelled') $status = 'Đã hủy';

    $table_code = 'Không chọn';
    if (!empty($b['table_id']) && isset($tables[$b['table_id']])) {
        $table_code = $tables[$b['table_id']];
    }

    fputcsv($output, [
        $b['id'],
        $b['customer_name'],
        '="' . $b['customer_phone'] . '"', // Ép kiểu chuỗi cho Excel để không mất số 0
        $svc,
        date('H:i d/m/Y', strtotime($b['booking_date'])),
        $b['guests'],
        $b['total_amount'],
        $b['deposit_amount'],
        $status,
        $table_code,
        str_replace(["\r\n", "\n", "\r"], " | ", $b['message']),
        str_replace(["\r\n", "\n", "\r"], " | ", $b['chef_requirements']),
        date('H:i d/m/Y', strtotime($b['created_at']))
    ]);
}
fclose($output);
exit;

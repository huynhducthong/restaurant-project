<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and has admin role (you can adjust this auth logic based on your auth_check.php)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

$db = (new Database())->getConnection();

// Lấy danh sách nhân sự
$stmt = $db->query("SELECT id, full_name, phone, email, identity_card, gender, dob, address, position, salary, status, created_at FROM employees ORDER BY id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đặt header để trình duyệt hiểu đây là file CSV để tải về
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="DanhSachNhanSu_' . date('Ymd_His') . '.csv"');

// Mở output stream
$output = fopen('php://output', 'w');

// Thêm BOM (Byte Order Mark) để Excel nhận diện đúng tiếng Việt UTF-8
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Ghi dòng tiêu đề
fputcsv($output, ['ID', 'Họ và Tên', 'SĐT', 'Email', 'CCCD', 'Giới tính', 'Ngày sinh', 'Địa chỉ', 'Vị trí', 'Mức Lương/Ngày', 'Trạng thái', 'Ngày tham gia']);

// Ghi dữ liệu
foreach ($employees as $emp) {
    // Format giới tính
    $gender = 'Khác';
    if ($emp['gender'] === 'male') $gender = 'Nam';
    if ($emp['gender'] === 'female') $gender = 'Nữ';
    
    // Format trạng thái
    $status = 'Đã nghỉ việc';
    if ($emp['status'] === 'working') $status = 'Đang làm việc';
    if ($emp['status'] === 'on_leave') $status = 'Đang nghỉ phép';
    
    fputcsv($output, [
        $emp['id'],
        $emp['full_name'],
        $emp['phone'],
        $emp['email'],
        $emp['identity_card'],
        $gender,
        $emp['dob'],
        $emp['address'],
        $emp['position'],
        number_format($emp['salary']) . ' VNĐ',
        $status,
        $emp['created_at']
    ]);
}

fclose($output);
exit;

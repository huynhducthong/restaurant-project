<?php
session_start();
// Chỉ Admin mới được phép vào trang này
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Bạn không có quyền truy cập trang này!");
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// 1. XỬ LÝ KHÓA/MỞ KHÓA TÀI KHOẢN (AJAX)
if (isset($_POST['toggle_status'])) {
    header('Content-Type: application/json');
    $user_id = (int)$_POST['user_id'];

    // Không cho phép tự khóa tài khoản của chính mình
    if ($user_id === (int)$_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'msg' => 'Không thể tự khóa tài khoản của bạn!']);
        exit;
    }

    $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$user_id]);
    $new_status = $db->prepare("SELECT is_active FROM users WHERE id = ?");
    $new_status->execute([$user_id]);
    echo json_encode(['status' => 'success', 'is_active' => (int)$new_status->fetchColumn()]);
    exit;
}

// 2. XỬ LÝ XÓA NGƯỜI DÙNG
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Kiểm tra bảo mật: Không tự xóa chính mình
    if ($del_id === (int)$_SESSION['user_id']) {
        $_SESSION['error'] = "Lỗi bảo mật: Không thể tự xóa tài khoản của chính bạn!";
    } else {
        try {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
            $_SESSION['msg'] = "Đã xóa người dùng khỏi hệ thống!";
        } catch (Exception $e) {
            // Nếu User đã có lịch sử nhập/xuất kho hoặc bán hàng, CSDL sẽ chặn xóa để đảm bảo toàn vẹn dữ liệu
            $_SESSION['error'] = "Không thể xóa: Nhân viên này đã có dữ liệu liên quan trong hệ thống (lịch sử bán hàng, nhập kho...). Khuyên dùng chức năng 'Tắt trạng thái' thay vì xóa vĩnh viễn.";
        }
    }
    header('Location: UserController.php');
    exit;
}

// 3. XỬ LÝ THÊM / CẬP NHẬT USER
if (isset($_POST['save_user'])) {
    $id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    try {
        if ($id) {
            // CẬP NHẬT
            if (!empty($password)) {
                $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET full_name=?, phone=?, email=?, role=?, password=? WHERE id=?");
                $stmt->execute([$full_name, $phone, $email, $role, $hashed_pass, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name=?, phone=?, email=?, role=? WHERE id=?");
                $stmt->execute([$full_name, $phone, $email, $role, $id]);
            }
            
            // ĐỒNG BỘ NHÂN SỰ KHI CẬP NHẬT
            $staff_roles = ['waiter', 'chef', 'cashier', 'staff', 'admin'];
            $u_info = $db->prepare("SELECT employee_id FROM users WHERE id = ?");
            $u_info->execute([$id]);
            $current_employee_id = $u_info->fetchColumn();

            if (in_array($role, $staff_roles)) {
                if (!$current_employee_id) {
                    // Trở thành nhân viên mới
                    $stmt_emp = $db->prepare("INSERT INTO employees (full_name, phone, email, position, status) VALUES (?, ?, ?, ?, 'working')");
                    $stmt_emp->execute([$full_name, $phone, $email, $role]);
                    $employee_id = $db->lastInsertId();
                    $db->prepare("UPDATE users SET employee_id = ? WHERE id = ?")->execute([$employee_id, $id]);
                } else {
                    // Cập nhật chức vụ nếu đã là nhân viên
                    $stmt_emp = $db->prepare("UPDATE employees SET full_name=?, phone=?, email=?, position=?, status='working' WHERE id=?");
                    $stmt_emp->execute([$full_name, $phone, $email, $role, $current_employee_id]);
                }
            } else {
                // Hạ cấp thành khách hàng -> Nghỉ việc
                if ($current_employee_id) {
                    $db->prepare("UPDATE employees SET status='resigned' WHERE id=?")->execute([$current_employee_id]);
                }
            }
            
            $_SESSION['msg'] = "Cập nhật người dùng thành công!";
        } else {
            // THÊM MỚI
            // Kiểm tra username trùng
            $check = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->rowCount() > 0) {
                throw new Exception("Tên đăng nhập đã tồn tại!");
            }

            $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, phone, email, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_pass, $full_name, $phone, $email, $role]);
            $user_id = $db->lastInsertId();

            // Tự động tạo hồ sơ nhân viên
            $staff_roles = ['waiter', 'chef', 'cashier', 'staff', 'admin'];
            if (in_array($role, $staff_roles)) {
                $stmt_emp = $db->prepare("INSERT INTO employees (full_name, phone, email, position, status) VALUES (?, ?, ?, ?, 'working')");
                $stmt_emp->execute([$full_name, $phone, $email, $role]);
                $employee_id = $db->lastInsertId();
                $db->prepare("UPDATE users SET employee_id = ? WHERE id = ?")->execute([$employee_id, $user_id]);
            }

            $_SESSION['msg'] = "Thêm người dùng mới thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: UserController.php');
    exit;
}

// 4. TRUY VẤN DANH SÁCH NGƯỜI DÙNG
// 4. TRUY VẤN DANH SÁCH NGƯỜI DÙNG (TÌM KIẾM & PHÂN TRANG)
$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(u.full_name LIKE ? OR u.phone LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_role !== '') {
    $where[] = "u.role = ?";
    $params[] = $filter_role;
}

// 4.5 XỬ LÝ XUẤT EXCEL TẤT CẢ DỮ LIỆU ĐÃ LỌC (KHÔNG PHÂN TRANG)
if (isset($_GET['export_excel'])) {
    $sql_export = "SELECT u.* 
                   FROM users u 
                   WHERE " . implode(" AND ", $where) . " 
                   ORDER BY u.id ASC";
    $export_stmt = $db->prepare($sql_export);
    $export_stmt->execute($params);
    $all_users = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="DanhSachNguoiDung_' . date('Ymd_His') . '.xls"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo "<table border='1'>";
    echo "<tr style='background-color:#0d6efd;color:white;'>";
    echo "<th>STT</th><th>ID</th><th>Họ Tên</th><th>Tên đăng nhập</th><th>Số điện thoại</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th>";
    echo "</tr>";
    
    $stt = 1;
    foreach ($all_users as $row) {
        $roles = [
            'admin' => 'Quản trị (Admin)',
            'cashier' => 'Thu ngân',
            'chef' => 'Bếp',
            'waiter' => 'Phục vụ',
            'customer' => 'Khách hàng'
        ];
        $role_str = $roles[$row['role']] ?? 'Người dùng';
        $status_str = $row['is_active'] ? 'Hoạt động' : 'Đã khóa';
        $date_str = date('d/m/Y H:i', strtotime($row['created_at']));
        
        echo "<tr>";
        echo "<td>{$stt}</td>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>@" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
        echo "<td>{$role_str}</td>";
        echo "<td>{$status_str}</td>";
        echo "<td>{$date_str}</td>";
        echo "</tr>";
        $stt++;
    }
    echo "</table>";
    exit;
}

$where_clause = implode(' AND ', $where);

// Đếm tổng để phân trang
$count_query = "SELECT COUNT(*) FROM users u WHERE $where_clause";
$stmt_count = $db->prepare($count_query);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

$query = "
    SELECT u.*, 
           COUNT(DISTINCT sb.id) as total_bookings, 
           SUM(CASE WHEN sb.status = 'Completed' THEN sb.total_amount ELSE 0 END) as total_spent,
           vp.name as vip_plan_name
    FROM users u
    LEFT JOIN service_bookings sb ON u.id = sb.user_id
    LEFT JOIN user_vip uv ON u.id = uv.user_id AND uv.status = 'active' AND uv.end_date >= NOW()
    LEFT JOIN vip_plans vp ON uv.plan_id = vp.id
    WHERE $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt_users = $db->prepare($query);
$stmt_users->execute($params);
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Gọi View
require_once __DIR__ . '/../views/users/user_view.php';
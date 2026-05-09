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
                // Nếu có nhập pass mới -> Mã hóa pass mới và cập nhật
                $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET full_name=?, phone=?, email=?, role=?, password=? WHERE id=?");
                $stmt->execute([$full_name, $phone, $email, $role, $hashed_pass, $id]);
            } else {
                // Không nhập pass -> Chỉ cập nhật thông tin
                $stmt = $db->prepare("UPDATE users SET full_name=?, phone=?, email=?, role=? WHERE id=?");
                $stmt->execute([$full_name, $phone, $email, $role, $id]);
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
            $_SESSION['msg'] = "Thêm người dùng mới thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: UserController.php');
    exit;
}

// 4. TRUY VẤN DANH SÁCH NGƯỜI DÙNG
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Gọi View
require_once __DIR__ . '/../views/users/user_view.php';
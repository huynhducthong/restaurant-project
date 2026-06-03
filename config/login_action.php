<?php
session_start();
require_once 'database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        header("Location: ../public/login.php?error=empty");
        exit();
    }

    // 1. Truy vấn user theo email hoặc username (Dùng ? để tránh lỗi lặp tên tham số)
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. Kiểm tra mật khẩu bằng password_verify
        if (password_verify($pass, $user['password'])) {
            
            // Đăng nhập thành công - Thiết lập Session
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['role']      = $user['role'];

            // 3. Điều hướng dựa trên quyền (role)
            if (in_array($user['role'], ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2])) {
                
                // --- THÔNG BÁO BẢO MẬT ĐĂNG NHẬP ---
                require_once 'notification_helper.php';
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
                $login_time = date('H:i:s d/m/Y');
                $user_name = $user['username'];
                
                $role_name_vn = 'Nhân viên';
                if ($user['role'] == 1 || $user['role'] == 'admin') $role_name_vn = 'Quản trị viên';
                elseif ($user['role'] == 'chef') $role_name_vn = 'Bếp trưởng / Đầu bếp';
                elseif ($user['role'] == 'cashier') $role_name_vn = 'Thu ngân';
                elseif ($user['role'] == 'waiter') $role_name_vn = 'Phục vụ / Lễ tân';
                
                if ($user['role'] == 1 || $user['role'] == 'admin') {
                    $security_msg = "🚨 <b>CẢNH BÁO BẢO MẬT</b>\n\n";
                } else {
                    $security_msg = "🟢 <b>THÔNG BÁO ĐĂNG NHẬP</b>\n\n";
                }
                
                $security_msg .= "Tài khoản <b>{$role_name_vn}</b> vừa đăng nhập thành công.\n";
                $security_msg .= "👤 User: <b>{$user_name}</b>\n";
                $security_msg .= "⏰ Thời gian: {$login_time}\n";
                $security_msg .= "🌐 Địa chỉ IP: <code>{$ip_address}</code>\n";
                
                if ($user['role'] == 1 || $user['role'] == 'admin') {
                    $security_msg .= "\n<i>Nếu không phải bạn, hãy kiểm tra hệ thống ngay lập tức!</i>";
                }
                
                @sendTelegramNotification($security_msg);

                header("Location: ../admin/admin_dashboard.php");
            } else {
                header("Location: ../index.php");
            }
            exit();
        } else {
            // Sai mật khẩu
            header("Location: ../public/login.php?error=wrong_password");
            exit();
        }
    } else {
        // Không tìm thấy email
        header("Location: ../public/login.php?error=user_not_found");
        exit();
    }
}
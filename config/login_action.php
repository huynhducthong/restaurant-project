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

    // 1. Truy vấn user theo email
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
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
            if ($user['role'] == 1 || $user['role'] == 'admin') {
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
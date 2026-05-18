<?php
session_start();
require_once 'database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Làm sạch dữ liệu đầu vào
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $re_pass  = $_POST['re-password'] ?? '';

    // 1. Kiểm tra trống
    if (empty($fullname) || empty($email) || empty($pass)) {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin.";
        header("Location: ../public/register.php");
        exit();
    }

    // 2. Kiểm tra mật khẩu khớp nhau
    if ($pass !== $re_pass) {
        $_SESSION['error'] = "Mật khẩu xác nhận không khớp.";
        header("Location: ../public/register.php");
        exit();
    }

    // 3. Kiểm tra email đã tồn tại chưa
    $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->rowCount() > 0) {
        $_SESSION['error'] = "Email này đã được đăng ký. Vui lòng đăng nhập!";
        header("Location: ../public/register.php");
        exit();
    }

    // 4. Mã hóa mật khẩu bằng chuẩn PASSWORD_DEFAULT (Bcrypt)
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // 5. Lưu vào database (Cột 'name', Role 'customer')
    try {
        
        $sql = "INSERT INTO users (username, email, password, role) VALUES (:name, :email, :pass, 'customer')";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $fullname,
            ':email' => $email,
            ':pass' => $hashed_password
        ]);
        
        // 6. Đăng ký thành công -> Lưu session tự động đăng nhập luôn
        $_SESSION['user_id'] = $db->lastInsertId();
        $_SESSION['user_name'] = $fullname;
        $_SESSION['role'] = 'customer';

        // Bật popup thông báo và chuyển hướng về trang chủ
        echo "<script>
            alert('Đăng ký tài khoản thành công! Chào mừng bạn đến với Restaurantly.');
            window.location.href = '../index.php';
        </script>";
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../public/register.php");
        exit();
    }
} else {
    // Không cho phép truy cập file bằng đường dẫn trực tiếp (GET)
    header("Location: ../public/register.php");
    exit();
}
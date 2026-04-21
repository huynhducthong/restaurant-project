<?php
session_start();
require_once 'database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $re_pass  = $_POST['re-password'] ?? '';

    // Kiểm tra dữ liệu rỗng
    if (empty($fullname) || empty($email) || empty($pass)) {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin.";
        header("Location: ../public/register.php");
        exit();
    }

    // Kiểm tra khớp mật khẩu
    if ($pass !== $re_pass) {
        $_SESSION['error'] = "Mật khẩu xác nhận không khớp.";
        header("Location: ../public/register.php");
        exit();
    }

    // Kiểm tra trùng Email
    $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->rowCount() > 0) {
        $_SESSION['error'] = "Email này đã được đăng ký. Vui lòng đăng nhập!";
        header("Location: ../public/register.php");
        exit();
    }

    // Mã hóa mật khẩu
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    try {
        // Lưu vào Database
        $sql = "INSERT INTO users (username, email, password, role) VALUES (:name, :email, :pass, 'customer')";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $fullname,
            ':email' => $email,
            ':pass' => $hashed_password
        ]);
        
        // --- SỬA Ở ĐÂY: KHÔNG TỰ ĐỘNG ĐĂNG NHẬP NỮA ---
        // Tạo thông báo thành công và chuyển hướng về trang Đăng nhập
        $_SESSION['success'] = "Tạo tài khoản thành công! Vui lòng đăng nhập.";
        header("Location: ../public/login.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../public/register.php");
        exit();
    }
}
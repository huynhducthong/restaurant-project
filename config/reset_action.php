<?php
session_start();
require_once 'database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_SESSION['reset_email'] ?? '';
    $pass     = $_POST['password'] ?? '';
    $re_pass  = $_POST['re-password'] ?? '';

    // Kiểm tra dữ liệu rỗng
    if (empty($email) || empty($pass)) {
        $_SESSION['error'] = "Dữ liệu không hợp lệ.";
        header("Location: ../public/reset_password.php");
        exit();
    }

    // Kiểm tra khớp mật khẩu
    if ($pass !== $re_pass) {
        $_SESSION['error'] = "Mật khẩu xác nhận không khớp.";
        header("Location: ../public/reset_password.php");
        exit();
    }

    // Mã hóa mật khẩu
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    try {
        // Cập nhật Database (Dùng UPDATE thay vì INSERT)
        $sql = "UPDATE users SET password = :pass WHERE email = :email";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':pass' => $hashed_password
        ]);
        
        // Tạo thông báo thành công và chuyển hướng về trang Đăng nhập
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_otp_verified']);
        $_SESSION['success'] = "Đặt lại mật khẩu thành công! Vui lòng đăng nhập.";
        header("Location: ../public/login.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../public/reset_password.php");
        exit();
    }
}
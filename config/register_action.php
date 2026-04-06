<?php
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
        die("Vui lòng nhập đầy đủ thông tin.");
    }

    // 2. Kiểm tra mật khẩu khớp nhau
    if ($pass !== $re_pass) {
        die("Mật khẩu xác nhận không khớp.");
    }

    // 3. Kiểm tra email đã tồn tại chưa
    $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->rowCount() > 0) {
        die("Email này đã được đăng ký.");
    }

    // 4. Mã hóa mật khẩu bằng chuẩn PASSWORD_DEFAULT (Bcrypt)
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // 5. Lưu vào database
    try {
        $sql = "INSERT INTO users (username, email, password, role) VALUES (:name, :email, :pass, 'staff')";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $hashed_password);
        
        if ($stmt->execute()) {
            header("Location: ../public/login.php?msg=register_success");
            exit();
        }
    } catch (PDOException $e) {
        die("Lỗi đăng ký: " . $e->getMessage());
    }
}
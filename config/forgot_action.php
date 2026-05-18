<?php
session_start();
require_once 'database.php';
require_once 'send_otp.php'; // File chứa hàm sendOTP() đã hướng dẫn trước đó

// Nạp các biến từ .env (Nếu bạn dùng thư viện Dotenv)
// require_once '../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
// $dotenv->load();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $_SESSION['error'] = "Vui lòng nhập địa chỉ email.";
        header("Location: ../public/forgot_password.php");
        exit();
    }

    // 1. Kiểm tra email có trong hệ thống không
    $stmt = $db->prepare("SELECT id, username FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. Tạo mã OTP ngẫu nhiên 6 số
        $otp = rand(100000, 999999);
        
        // 3. Lưu vào Session để kiểm tra ở bước sau
        $_SESSION['reset_otp']   = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_time']    = time(); // Lưu thời điểm tạo để tính hết hạn (vd: 5 phút)

        // 4. Gửi Mail
        if (sendOTP($email, $otp)) {
            $_SESSION['success'] = "Mã xác nhận đã được gửi đến email của bạn.";
            header("Location: ../public/verify_otp.php");
            exit();
        } else {
            $_SESSION['error'] = "Không thể gửi email lúc này. Vui lòng thử lại sau.";
            header("Location: ../public/forgot_password.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Email này không tồn tại trong hệ thống.";
        header("Location: ../public/forgot_password.php");
        exit();
    }
}
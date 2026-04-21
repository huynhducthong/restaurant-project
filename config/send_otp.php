<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Nạp thư viện (Đảm bảo đường dẫn tới vendor đúng với cấu trúc dự án của bạn)
require '../vendor/autoload.php'; 

function sendOTP($emailNguoiNhan, $maOTP) {
    $mail = new PHPMailer(true);

    try {
        // Cấu hình bằng file .env (Dùng $_ENV hoặc env() tùy framework)
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME']; // Email của bạn trong .env
        $mail->Password   = $_ENV['MAIL_PASSWORD']; // Mật khẩu ứng dụng trong .env
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        // Người gửi & Người nhận
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], 'Restaurantly Admin');
        $mail->addAddress($emailNguoiNhan);

        // Nội dung
        $mail->isHTML(true);
        $mail->Subject = 'Mã xác nhận OTP - Restaurantly';
        $mail->Body    = "
            <div style='border: 2px solid #cda45e; padding: 20px; font-family: sans-serif;'>
                <h2 style='color: #cda45e;'>Xác nhận tài khoản</h2>
                <p>Mã OTP của bạn là: <b style='font-size: 24px; color: #ff0000;'>$maOTP</b></p>
                <p>Vui lòng không chia sẻ mã này với bất kỳ ai.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ghi log lỗi nếu cần: $e->getMessage();
        return false;
    }
}
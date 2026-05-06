<?php
require_once 'config/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if ($email) {
        try {
            $db->prepare("INSERT IGNORE INTO newsletters (email) VALUES (?)")->execute([$email]);
            $msg = 'Đăng ký nhận tin thành công!';
        } catch (Exception $e) { $msg = 'Lỗi hệ thống, thử lại sau.'; }
    } else { $msg = 'Email không hợp lệ.'; }
    echo "<script>alert('$msg'); window.history.back();</script>";
    exit;
}
header("Location: index.php");
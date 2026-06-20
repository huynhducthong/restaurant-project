<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cancel_vip'])) {
    header("Location: ../profile.php?tab=vip");
    exit;
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../app/models/UserVip.php';
require_once __DIR__ . '/notification_helper.php';

$db = (new Database())->getConnection();
$userVipModel = new UserVip($db);

$user_id = $_SESSION['user_id'];

$user_stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :id");
$user_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

$success = $userVipModel->cancelVip($user_id);

if ($success) {
    $_SESSION['success_msg'] = "Bạn đã hủy gia hạn thẻ VIP thành công.";
    
    // Gửi email thông báo hủy VIP
    if ($user_info && !empty($user_info['email'])) {
        @sendVipCancellationEmail($user_info['email'], $user_info['full_name']);
    }

    // Gửi Telegram (tùy chọn)
    $uname = $_SESSION['user_name'] ?? 'Khách hàng';
    $msg_tele = "<b>⚠️ HỦY GÓI VIP</b>\n\n";
    $msg_tele .= "Khách hàng <b>{$uname}</b> vừa hủy gia hạn thẻ VIP của họ.\n";
    @sendTelegramNotification($msg_tele);

} else {
    $_SESSION['error_msg'] = "Đã xảy ra lỗi trong quá trình hủy thẻ VIP. Vui lòng thử lại.";
}

header("Location: ../profile.php?tab=vip");
exit;

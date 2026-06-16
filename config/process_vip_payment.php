<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile.php?tab=vip");
    exit;
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../app/models/UserVip.php';
require_once __DIR__ . '/../app/models/VipPlan.php';

$db = (new Database())->getConnection();
$userVipModel = new UserVip($db);
$vipPlanModel = new VipPlan($db);

$user_id = $_SESSION['user_id'];
$plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
$payment_method = $_POST['payment_method'] ?? 'credit_card';

$plan = $vipPlanModel->getPlanById($plan_id);

if (!$plan) {
    $_SESSION['error_msg'] = "Gói VIP không hợp lệ hoặc không tồn tại.";
    header("Location: ../profile.php?tab=vip");
    exit;
}

// Giả lập thời gian delay xử lý thanh toán (2 giây)
sleep(2);

// MOCKUP THANH TOÁN THÀNH CÔNG
// Kích hoạt VIP cho user
$success = $userVipModel->upgradeVip($user_id, $plan_id, $plan['duration_days']);

if ($success) {
    // Lưu session message để thông báo bên file profile.php
    $_SESSION['success_msg'] = "Thanh toán thành công! Chúc mừng bạn đã chính thức trở thành Hội viên VIP gói " . htmlspecialchars($plan['name']) . ".";
    
    // Nếu có Telegram notification
    require_once __DIR__ . '/notification_helper.php';
    $uname = $_SESSION['user_name'] ?? 'Khách hàng';
    $msg_tele = "<b>💎 VIP MEMBERSHIP UPGRADE</b>\n\n";
    $msg_tele .= "Khách hàng <b>{$uname}</b> vừa nâng cấp thẻ VIP!\n";
    $msg_tele .= "- Gói: " . $plan['name'] . "\n";
    $msg_tele .= "- Giá: " . number_format($plan['price'], 0, ',', '.') . " VNĐ\n";
    $msg_tele .= "- Phương thức: " . ($payment_method == 'credit_card' ? 'Thẻ Tín Dụng' : 'Chuyển Khoản') . "\n";
    @sendTelegramNotification($msg_tele);

} else {
    $_SESSION['error_msg'] = "Đã xảy ra lỗi trong quá trình kích hoạt thẻ VIP. Vui lòng liên hệ CSKH.";
}

header("Location: ../profile.php?tab=vip");
exit;

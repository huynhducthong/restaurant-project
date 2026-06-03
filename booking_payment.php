<?php
session_start();
require_once 'config/database.php';
$db = (new Database())->getConnection();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$booking_id = (int)$_GET['id'];

// Xử lý POST (Xác nhận đã thanh toán)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    // Xóa cơ chế Auto-Confirm (Tự động Xác nhận). Hệ thống sẽ giữ nguyên trạng thái Pending để Admin duyệt.
    // Lễ tân/Admin sẽ duyệt thủ công trên trang Quản lý.
    
    // Gửi thông báo Telegram
    require_once 'config/notification_helper.php';
    $stmt = $db->prepare("SELECT customer_name, deposit_amount FROM service_bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $bk = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($bk) {
        $msg = "💰 <b>ĐÃ NHẬN TIỀN CỌC TỰ ĐỘNG</b>\n\n";
        $msg .= "🧾 Mã đơn: <b>#{$booking_id}</b>\n";
        $msg .= "👤 Khách hàng: <b>{$bk['customer_name']}</b>\n";
        $msg .= "💵 Số tiền: <b>" . number_format($bk['deposit_amount']) . " VNĐ</b>\n";
        $msg .= "✅ Hệ thống đang chờ Admin duyệt (Pending) trên hệ thống.";
        @sendTelegramNotification($msg);
    }
    
    header("Location: booking_success.php?success=1&id=" . $booking_id);
    exit;
}

// Lấy thông tin hiển thị
$stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking || $booking['deposit_amount'] <= 0) {
    header("Location: index.php");
    exit;
}

// Cấu hình mã QR (Tài khoản mẫu)
$bank_code = "vcb"; // Vietcombank
$account_no = "0123456789";
$account_name = "NHA HANG FINE DINING";
$amount = (int)$booking['deposit_amount'];
$add_info = "Thanh toan coc don " . $booking_id;
$qr_url = "https://img.vietqr.io/image/{$bank_code}-{$account_no}-compact2.png?amount={$amount}&addInfo=" . urlencode($add_info) . "&accountName=" . urlencode($account_name);

include 'views/client/layouts/header.php';
?>
<style>
/* CSS Tương tự trang success/booking */
.payment-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #111 0%, #1a1a1a 100%);
    padding: 100px 20px 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', sans-serif;
}
.payment-card {
    background: #222;
    border: 1px solid rgba(212, 176, 106, 0.2);
    border-radius: 12px;
    padding: 40px;
    max-width: 500px;
    width: 100%;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    color: #fff;
}
.qr-container {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    display: inline-block;
    margin: 20px 0;
}
.qr-container img {
    max-width: 250px;
    display: block;
}
.btn-gold {
    background: linear-gradient(135deg, #d4b06a 0%, #b89346 100%);
    color: #fff;
    border: none;
    padding: 14px 30px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    width: 100%;
    margin-top: 20px;
    text-transform: uppercase;
}
.btn-gold:hover {
    box-shadow: 0 5px 15px rgba(212,176,106,0.4);
    transform: translateY(-2px);
}
.pay-info {
    font-size: 14px;
    color: #aaa;
    margin-bottom: 10px;
}
.pay-val {
    font-size: 16px;
    color: #d4b06a;
    font-weight: bold;
}
.amount-highlight {
    font-size: 32px;
    color: #d4b06a;
    font-weight: 700;
    margin: 15px 0;
    font-family: 'Cormorant Garamond', serif;
}
</style>

<div class="payment-wrapper">
    <div class="payment-card">
        <i class="fas fa-qrcode" style="font-size: 40px; color: #d4b06a; margin-bottom: 15px;"></i>
        <h2 style="color: #d4b06a; font-family: 'Cormorant Garamond', serif; margin-bottom: 10px;">Thanh Toán Tiền Cọc</h2>
        <p style="color: #aaa; font-size: 14px; margin-bottom: 25px;">Vui lòng dùng ứng dụng Ngân hàng để quét mã QR và hoàn tất việc đặt cọc giữ chỗ.</p>
        
        <div class="amount-highlight"><?= number_format($amount) ?> VNĐ</div>

        <div class="qr-container">
            <img src="<?= $qr_url ?>" alt="VietQR">
        </div>

        <div style="text-align: left; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; margin-top: 10px;">
            <div class="pay-info">Ngân hàng: <span class="pay-val">Vietcombank</span></div>
            <div class="pay-info">Chủ tài khoản: <span class="pay-val"><?= $account_name ?></span></div>
            <div class="pay-info">Số tài khoản: <span class="pay-val"><?= $account_no ?></span></div>
            <div class="pay-info" style="margin-bottom:0;">Nội dung CK: <span class="pay-val"><?= $add_info ?></span></div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="confirm_payment" value="1">
            <button type="submit" class="btn-gold" id="btn-confirm">
                <i class="fas fa-check-circle me-2"></i> Tôi đã chuyển khoản
            </button>
        </form>
        
        <p style="font-size: 12px; color: #777; margin-top: 20px;">Nhà hàng sẽ tự động ghi nhận khi giao dịch hoàn tất.</p>
    </div>
</div>

<?php include 'views/client/layouts/footer.php'; ?>

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
$account_no = "1012345678"; // Tạm thời dùng 10 số để QR render tốt hơn
$account_name = "NHA HANG FINE DINING";
$amount = (int)$booking['deposit_amount'];
$add_info = "Thanh toan coc don " . $booking_id;
$qr_url = "https://img.vietqr.io/image/{$bank_code}-{$account_no}-compact2.png?amount={$amount}&addInfo=" . urlencode($add_info) . "&accountName=" . urlencode($account_name);

include 'views/client/layouts/header.php';
?>
<style>
.payment-wrapper {
    min-height: 100vh;
    background: #fdfbf7;
    padding: 120px 20px 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Open Sans', sans-serif;
}
.payment-card {
    background: #FFFFFF;
    border: 1px solid rgba(212, 176, 106, 0.3);
    border-radius: 0;
    padding: 40px;
    max-width: 500px;
    width: 100%;
    text-align: center;
    box-shadow: 0 15px 40px rgba(0,0,0,0.05);
    color: #333;
}
.qr-container {
    background: #FFFFFF;
    padding: 15px;
    display: inline-block;
    margin: 20px 0;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.qr-container img {
    max-width: 250px;
    display: block;
}
.btn-gold {
    background: #111;
    color: #d4b06a;
    border: 1px solid #d4b06a;
    padding: 16px 30px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.4s;
    width: 100%;
    margin-top: 20px;
    text-transform: uppercase;
    font-family: 'Cormorant Garamond', serif;
    letter-spacing: 1px;
}
.btn-gold:hover {
    background: #d4b06a;
    color: #fff;
}
.pay-info {
    font-size: 14px;
    color: #666;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    border-bottom: 1px dashed #eee;
    padding-bottom: 8px;
}
.pay-val {
    font-size: 15px;
    color: #111;
    font-weight: 600;
}
.amount-highlight {
    font-size: 40px;
    color: #111;
    font-weight: 700;
    margin: 15px 0;
    font-family: 'Cormorant Garamond', serif;
}
</style>

<div class="payment-wrapper">
    <div class="payment-card">
        <i class="fas fa-qrcode" style="font-size: 40px; color: #d4b06a; margin-bottom: 15px;"></i>
        <h2 style="color: #d4b06a; font-family: 'Cormorant Garamond', serif; margin-bottom: 10px; font-weight: 600;">Thanh Toán Tiền Cọc</h2>
        <p style="color: #777; font-size: 14px; margin-bottom: 25px;">Vui lòng dùng ứng dụng Ngân hàng để quét mã QR và hoàn tất việc đặt cọc giữ chỗ.</p>
        
        <div class="amount-highlight"><?= number_format($amount) ?> VNĐ</div>

        <div class="qr-container">
            <img src="<?= $qr_url ?>" alt="VietQR QR Code" onerror="this.onerror=null; this.src='https://img.vietqr.io/image/vcb-1012345678-compact2.png?amount=<?= $amount ?>&addInfo=<?= urlencode($add_info) ?>&accountName=FINE%20DINING';">
        </div>

        <div style="text-align: left; background: #FFFFFF; padding: 20px; margin-top: 10px; border: 1px solid #f0f0f0;">
            <div class="pay-info"><span>Ngân hàng:</span> <span class="pay-val">Vietcombank</span></div>
            <div class="pay-info"><span>Chủ tài khoản:</span> <span class="pay-val"><?= $account_name ?></span></div>
            <div class="pay-info"><span>Số tài khoản:</span> <span class="pay-val"><?= $account_no ?></span></div>
            <div class="pay-info" style="border-bottom:none; margin-bottom:0; padding-bottom:0;"><span>Nội dung CK:</span> <span class="pay-val"><?= $add_info ?></span></div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="confirm_payment" value="1">
            <button type="submit" class="btn-gold" id="btn-confirm">
                Tôi đã chuyển khoản <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>
        
        <p style="font-size: 12.5px; color: #888; margin-top: 20px;"><i class="fas fa-shield-alt me-1"></i> Giao dịch an toàn & bảo mật. Trạng thái đơn sẽ được tự động cập nhật.</p>
    </div>
</div>

<?php include 'views/client/layouts/footer.php'; ?>

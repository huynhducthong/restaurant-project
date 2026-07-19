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


<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
    .bk-pay-lux {
        background: url('public/assets/img/hero/1776687242_hero-bg.jpg') center/cover no-repeat fixed;
        min-height: 100vh;
        position: relative;
        color: #fff;
        padding-top: 140px;
        padding-bottom: 100px;
    }
    .bk-pay-lux::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(17, 17, 17, 0.8) 0%, rgba(20, 10, 10, 0.95) 100%);
        z-index: 0;
    }
    .lux-pay-card {
        background: rgba(30, 30, 30, 0.6);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(212, 176, 106, 0.2);
        border-radius: 20px;
        padding: 50px 60px;
        box-shadow: 0 40px 80px rgba(0,0,0,0.6), inset 0 0 0 1px rgba(255,255,255,0.05);
        max-width: 600px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
        text-align: center;
    }
    .lux-pay-title {
        color: #d4b06a;
        font-family: 'Cormorant Garamond', serif;
        font-size: 36px;
        margin-bottom: 10px;
    }
    .lux-pay-amount {
        font-family: 'Cormorant Garamond', serif;
        font-size: 42px;
        font-weight: 600;
        color: #fff;
        margin: 20px 0;
        text-shadow: 0 2px 10px rgba(212,176,106,0.3);
    }
    .lux-qr-box {
        background: #fff;
        padding: 20px;
        border-radius: 16px;
        display: inline-block;
        margin-bottom: 30px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        position: relative;
    }
    .lux-qr-box::before {
        content: '';
        position: absolute;
        inset: -2px;
        background: linear-gradient(45deg, #d4b06a, transparent, #d4b06a);
        z-index: -1;
        border-radius: 18px;
        opacity: 0.5;
    }
    .lux-qr-box img {
        width: 250px;
        height: auto;
        display: block;
        border-radius: 8px;
    }
    .lux-bank-info {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 25px;
        text-align: left;
        margin-bottom: 30px;
    }
    .lux-bank-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .lux-bank-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .lux-bank-row:first-child {
        padding-top: 0;
    }
    .lux-bank-lbl {
        color: rgba(255,255,255,0.5);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .lux-bank-val {
        color: #fff;
        font-weight: 600;
        font-family: 'Source Sans 3', sans-serif;
    }
    .lux-btn-confirm {
        background: #d4b06a;
        color: #111;
        width: 100%;
        padding: 16px;
        border-radius: 8px;
        border: none;
        font-family: 'Cormorant Garamond', serif;
        font-size: 18px;
        font-weight: 600;
        transition: 0.3s;
        cursor: pointer;
    }
    .lux-btn-confirm:hover {
        background: #b89650;
        box-shadow: 0 10px 20px rgba(212,176,106,0.3);
    }
</style>

<div class="bk-pay-lux">
    <div class="container">
        <div class="lux-pay-card">
            <i class="fas fa-gem" style="font-size: 32px; color: #d4b06a; margin-bottom: 20px;"></i>
            <h2 class="lux-pay-title">Thanh Toán Tiền Cọc</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 15px;">Vui lòng quét mã QR dưới đây để hoàn tất việc giữ chỗ.</p>
            
            <div class="lux-pay-amount"><?= number_format($amount) ?> VNĐ</div>

            <div class="lux-qr-box">
                <img src="<?= $qr_url ?>" alt="VietQR QR Code" onerror="this.onerror=null; this.src='https://img.vietqr.io/image/vcb-1012345678-compact2.png?amount=<?= $amount ?>&addInfo=<?= urlencode($add_info) ?>&accountName=FINE%20DINING';">
            </div>

            <div class="lux-bank-info">
                <div class="lux-bank-row">
                    <span class="lux-bank-lbl">Ngân hàng</span>
                    <span class="lux-bank-val">Vietcombank</span>
                </div>
                <div class="lux-bank-row">
                    <span class="lux-bank-lbl">Chủ tài khoản</span>
                    <span class="lux-bank-val"><?= $account_name ?></span>
                </div>
                <div class="lux-bank-row">
                    <span class="lux-bank-lbl">Số tài khoản</span>
                    <span class="lux-bank-val" style="color:#d4b06a; font-size: 18px; font-weight:700;"><?= $account_no ?></span>
                </div>
                <div class="lux-bank-row">
                    <span class="lux-bank-lbl">Nội dung CK</span>
                    <span class="lux-bank-val"><?= $add_info ?></span>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="confirm_payment" value="1">
                <button type="submit" class="lux-btn-confirm" id="btn-confirm">
                    TÔI ĐÃ CHUYỂN KHOẢN <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>
            
            <p style="font-size: 13px; color: rgba(255,255,255,0.4); margin-top: 25px; font-style:italic;">
                <i class="fas fa-shield-alt me-1"></i> Giao dịch an toàn & bảo mật. Trạng thái đơn sẽ tự động cập nhật.
            </p>
        </div>
    </div>
</div>

<?php include 'views/client/layouts/footer.php'; ?>

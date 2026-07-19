<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php');
    exit;
}
$db  = (new Database())->getConnection();
$type = $_GET['type'] ?? 'table';
$svc  = [
    'table'    => ['title' => 'Đặt Bàn', 'sub' => 'Trải nghiệm ẩm thực đỉnh cao', 'icon' => 'table'],
    'birthday' => ['title' => 'Kỷ Niệm', 'sub' => 'Không gian riêng tư, đặc biệt', 'icon' => 'birthday'],
    'chef'     => ['title' => 'Đầu Bếp Riêng', 'sub' => 'Phục vụ tận bàn theo yêu cầu', 'icon' => 'chef'],
];
$cfg   = $svc[$type] ?? $svc['table'];
$combos = $db->query(
    "SELECT c.*, GROUP_CONCAT(f.name SEPARATOR '|') as list_foods
     FROM combos c LEFT JOIN combo_items ci ON c.id=ci.combo_id
     LEFT JOIN foods f ON ci.food_id=f.id WHERE c.status=1 GROUP BY c.id"
)->fetchAll(PDO::FETCH_ASSOC);
$t_open = $db->query("SELECT * FROM restaurant_tables WHERE category='open' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$t_room = $db->query("SELECT * FROM restaurant_tables WHERE category='room'  ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$foods  = $db->query("SELECT * FROM foods WHERE status=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include 'views/client/layouts/header.php';

$is_success = isset($_GET['success']) && isset($_GET['id']);
$booking = null;
$booking_items = [];
if ($is_success) {
    $stmt = $db->prepare("SELECT s.*, t.table_code FROM service_bookings s LEFT JOIN restaurant_tables t ON s.table_id = t.id WHERE s.id = ?");
    $stmt->execute([$_GET['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        $detail_stmt = $db->prepare("
            SELECT bd.quantity, bd.notes, bd.toppings_info, f.name as food_name, f.price as food_price
            FROM booking_details bd
            JOIN foods f ON bd.menu_id = f.id
            WHERE bd.booking_id = ?
        ");
        $detail_stmt->execute([$_GET['id']]);
        $booking_items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($booking_items as &$it) {
            $it['toppings_list'] = [];
            $it['toppings_total_price'] = 0;
            if (!empty($it['toppings_info'])) {
                $t_ids = explode(',', $it['toppings_info']);
                $t_ids_str = implode(',', array_map('intval', $t_ids));
                if (!empty($t_ids_str)) {
                    $toppings_query = $db->query("SELECT name, price FROM toppings WHERE id IN ($t_ids_str)")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($toppings_query as $tq) {
                        $it['toppings_list'][] = $tq['name'] . " (+" . number_format($tq['price']) . "đ)";
                        $it['toppings_total_price'] += $tq['price'];
                    }
                }
            }
            $it['final_unit_price'] = $it['food_price'] + $it['toppings_total_price'];
            $it['final_subtotal'] = $it['final_unit_price'] * $it['quantity'];
        }
        unset($it);
    }
}
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
    .bk-page-lux {
        background: url('public/assets/img/hero/1776687242_hero-bg.jpg') center/cover no-repeat fixed;
        min-height: 100vh;
        position: relative;
        color: #fff;
        padding-bottom: 100px;
    }
    .bk-page-lux::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(17, 17, 17, 0.8) 0%, rgba(20, 10, 10, 0.95) 100%);
        z-index: 0;
    }
    .lux-hero {
        padding: 160px 0 120px;
        position: relative;
        z-index: 1;
        text-align: center;
    }
    .lux-success-card {
        background: rgba(30, 30, 30, 0.6);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(212, 176, 106, 0.2);
        border-radius: 20px;
        padding: 60px;
        box-shadow: 0 40px 80px rgba(0,0,0,0.6), inset 0 0 0 1px rgba(255,255,255,0.05);
        max-width: 900px;
        margin: -80px auto 0;
        position: relative;
        z-index: 2;
    }
    .lux-icon-wrap {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, rgba(212, 176, 106, 0.2), rgba(212, 176, 106, 0.05));
        border: 1px solid rgba(212, 176, 106, 0.4);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        color: #d4b06a;
        font-size: 36px;
        box-shadow: 0 0 40px rgba(212, 176, 106, 0.2);
        animation: pulseGold 2s infinite;
    }
    @keyframes pulseGold {
        0% { box-shadow: 0 0 0 0 rgba(212, 176, 106, 0.4); }
        70% { box-shadow: 0 0 0 20px rgba(212, 176, 106, 0); }
        100% { box-shadow: 0 0 0 0 rgba(212, 176, 106, 0); }
    }
    .lux-badge {
        background: rgba(212, 176, 106, 0.1);
        border: 1px solid rgba(212, 176, 106, 0.3);
        color: #d4b06a;
        padding: 6px 20px;
        border-radius: 30px;
        font-size: 11px;
        letter-spacing: 3px;
        text-transform: uppercase;
        display: inline-block;
        margin-bottom: 25px;
        font-family: 'Source Sans 3', sans-serif;
    }
    .lux-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 48px;
        font-weight: 400;
        color: #fff;
        line-height: 1.3;
        margin-bottom: 20px;
    }
    .lux-title em {
        color: #d4b06a;
        font-style: italic;
    }
    .lux-sub {
        font-family: 'Source Sans 3', sans-serif;
        font-size: 16px;
        color: rgba(255,255,255,0.7);
        max-width: 600px;
        margin: 0 auto 40px;
        line-height: 1.6;
    }
    .lux-detail-grid {
        display: flex;
        flex-wrap: nowrap;
        gap: 20px;
        margin-bottom: 40px;
    }
    .lux-detail-box {
        flex: 1;
        min-width: 0; /* allows text to shrink/wrap if needed */
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 25px 15px;
        border-left: 3px solid #d4b06a;
        transition: 0.3s ease;
    }
    .lux-detail-box:hover {
        background: rgba(212, 176, 106, 0.05);
        border-color: rgba(212, 176, 106, 0.2);
        transform: translateY(-3px);
    }
    .lux-detail-label {
        color: rgba(255,255,255,0.5);
        font-size: 10px;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 8px;
        font-family: 'Source Sans 3', sans-serif;
    }
    .lux-detail-val {
        font-size: 1.2rem;
        font-weight: 500;
        color: #fff;
        font-family: 'Cormorant Garamond', serif;
    }
    .lux-section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 24px;
        color: #d4b06a;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .lux-section-title::after {
        content: '';
        flex-grow: 1;
        height: 1px;
        background: linear-gradient(90deg, rgba(212,176,106,0.3) 0%, transparent 100%);
    }
    .lux-table {
        width: 100%;
        border-collapse: collapse;
        color: #fff;
    }
    .lux-table th {
        border-bottom: 1px solid rgba(212,176,106,0.2);
        padding: 15px 10px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
        font-weight: normal;
    }
    .lux-table td {
        padding: 20px 10px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        vertical-align: middle;
    }
    .lux-total-box {
        background: linear-gradient(135deg, rgba(212,176,106,0.1) 0%, rgba(212,176,106,0.02) 100%);
        border: 1px solid rgba(212,176,106,0.2);
        border-radius: 12px;
        padding: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
    }
    .btn-lux-primary {
        background: #d4b06a;
        color: #111;
        padding: 15px 40px;
        border-radius: 4px;
        text-decoration: none;
        font-family: 'Cormorant Garamond', serif;
        font-size: 18px;
        font-weight: 600;
        transition: 0.3s;
        display: inline-block;
        border: 1px solid #d4b06a;
    }
    .btn-lux-primary:hover {
        background: #b89650;
        color: #111;
        box-shadow: 0 10px 20px rgba(212,176,106,0.3);
    }
    .btn-lux-outline {
        background: transparent;
        color: #fff;
        padding: 15px 40px;
        border-radius: 4px;
        text-decoration: none;
        font-family: 'Cormorant Garamond', serif;
        font-size: 18px;
        font-weight: 600;
        transition: 0.3s;
        display: inline-block;
        border: 1px solid rgba(255,255,255,0.3);
    }
    .btn-lux-outline:hover {
        background: rgba(255,255,255,0.1);
        border-color: #fff;
        color: #fff;
    }
</style>

<div class="bk-page-lux">
    <?php if ($is_success && $booking): ?>
    <section class="lux-hero">
        <div class="container">
            <div class="lux-icon-wrap">
                <i class="fas fa-check"></i>
            </div>
            <div class="lux-badge">Đặt Chỗ Thành Công</div>
            <h1 class="lux-title">
                Cảm ơn <em><?= htmlspecialchars($booking['customer_name']) ?></em>!<br>
                Yêu cầu của quý khách đã được ghi nhận
            </h1>
            <p class="lux-sub">
                Mã số đặt chỗ: <span style="color: #d4b06a; font-weight: 600;">#SVR-<?= htmlspecialchars($booking['id']) ?></span>. 
                Đội ngũ của chúng tôi sẽ liên hệ qua số <span style="color: #fff; font-weight: 600;"><?= htmlspecialchars($booking['customer_phone']) ?></span> để xác nhận trong thời gian sớm nhất.
            </p>
            <div style="display:flex; gap:15px; justify-content:center; flex-wrap:wrap;">
                <a href="admin/export_pdf.php?id=<?= $booking['id'] ?>" class="btn-lux-primary">
                    <i class="fas fa-file-pdf me-2"></i> Tải Phiếu Xác Nhận
                </a>
                <a href="index.php" class="btn-lux-outline">
                    Về Trang Chủ
                </a>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="lux-success-card">
            
            <h3 class="lux-section-title"><i class="fas fa-info-circle"></i> Chi tiết đặt dịch vụ</h3>
            
            <div class="lux-detail-grid">
                <div class="lux-detail-box">
                    <div class="lux-detail-label">DỊCH VỤ</div>
                    <div class="lux-detail-val" style="color: #d4b06a;"><?= htmlspecialchars(strtoupper($booking['service_type'])) ?></div>
                </div>
                <div class="lux-detail-box">
                    <div class="lux-detail-label">THỜI GIAN</div>
                    <div class="lux-detail-val"><?= date('H:i · d/m/Y', strtotime($booking['booking_date'])) ?></div>
                </div>
                <div class="lux-detail-box">
                    <div class="lux-detail-label">SỐ KHÁCH</div>
                    <div class="lux-detail-val"><?= htmlspecialchars($booking['guests']) ?> NGƯỜI</div>
                </div>
                <?php if ($booking['table_code']): ?>
                <div class="lux-detail-box">
                    <div class="lux-detail-label">VỊ TRÍ</div>
                    <div class="lux-detail-val" style="color: #d4b06a;"><?= htmlspecialchars($booking['table_code']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php 
                $is_custom_music = !empty($booking['music_playlist']) && stripos($booking['music_playlist'], 'Mặc định') === false;
                $is_custom_light = !empty($booking['light_tone']) && stripos($booking['light_tone'], 'Mặc định') === false;
                $has_bespoke = !empty($booking['has_candle']) || !empty($booking['has_handwritten_card']) || !empty($booking['has_flower']) || !empty($booking['event_type']) || $is_custom_music || $is_custom_light;
                
                if ($has_bespoke): 
            ?>
            <h3 class="lux-section-title" style="margin-top: 40px;"><i class="fas fa-magic"></i> Trải Nghiệm Cá Nhân Hóa (Bespoke)</h3>
            <div style="background: rgba(0,0,0,0.2); border: 1px dashed rgba(212,176,106,0.3); border-radius: 12px; padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px;">
                    <?php if (!empty($booking['event_type'])): ?>
                        <div>
                            <span style="font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Dịp Đặc Biệt</span>
                            <span style="font-size: 15px; font-weight: 500; color: #fff; font-family:'Cormorant Garamond', serif;"><?= htmlspecialchars($booking['event_type']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($booking['has_candle'])): ?>
                        <div>
                            <span style="font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Trang Trí</span>
                            <span style="font-size: 15px; font-weight: 500; color: #fff; font-family:'Cormorant Garamond', serif;">🕯 Nến thơm nghệ thuật</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($booking['has_handwritten_card'])): ?>
                        <div>
                            <span style="font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Thiệp Viết Tay</span>
                            <span style="font-size: 15px; font-weight: 500; color: #fff; font-family:'Cormorant Garamond', serif;">✉️ <?= htmlspecialchars($booking['card_message'] ?: 'Chúc mừng') ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($booking['has_flower'])): ?>
                        <div>
                            <span style="font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Hoa Tươi Thiết Kế</span>
                            <span style="font-size: 15px; font-weight: 500; color: #fff; font-family:'Cormorant Garamond', serif;">💐 <?= htmlspecialchars($booking['flower_preference'] ?: 'Hoa theo mùa') ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_custom_music || $is_custom_light): ?>
                        <div>
                            <span style="font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Cấu Hình Phòng VIP</span>
                            <span style="font-size: 15px; font-weight: 500; color: #fff; font-family:'Cormorant Garamond', serif;">
                                <?php if ($is_custom_music): ?>🎵 <?= htmlspecialchars($booking['music_playlist']) ?><?php endif; ?> 
                                <?php if ($is_custom_music && $is_custom_light): ?> · <?php endif; ?>
                                <?php if ($is_custom_light): ?>💡 <?= htmlspecialchars($booking['light_tone']) ?><?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($booking_items)): ?>
            <h3 class="lux-section-title" style="margin-top: 40px;"><i class="fas fa-utensils"></i> Thực Đơn Đã Chọn</h3>
            <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px; overflow-x: auto;">
                <table class="lux-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Món ăn</th>
                            <th style="text-align: center;">SL</th>
                            <th style="text-align: right;">Đơn giá</th>
                            <th style="text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booking_items as $it): ?>
                        <tr>
                            <td>
                                <strong style="color: #fff; font-size: 16px; font-family:'Cormorant Garamond', serif;"><?= htmlspecialchars($it['food_name']) ?></strong>
                                <?php if (!empty($it['toppings_list'])): ?>
                                    <div style="font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 6px;">
                                        <i class="fas fa-plus-circle me-1" style="color:#d4b06a;"></i> <?= implode(', ', $it['toppings_list']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($it['notes'])): ?>
                                    <div style="font-size: 12px; color: #e74c3c; margin-top: 4px; font-style: italic;">
                                        <i class="fas fa-pen me-1"></i><?= htmlspecialchars($it['notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; color: rgba(255,255,255,0.7);">x<?= $it['quantity'] ?></td>
                            <td style="text-align: right; color: rgba(255,255,255,0.7);"><?= number_format($it['final_unit_price']) ?>đ</td>
                            <td style="text-align: right; font-weight: 600; color: #d4b06a; font-size: 16px;"><?= number_format($it['final_subtotal']) ?>đ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="lux-total-box">
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin-bottom: 5px;">Tổng giá trị dịch vụ</div>
                    <div style="font-size: 1.8rem; font-weight: 600; color: #fff; font-family:'Cormorant Garamond', serif;"><?= number_format($booking['total_amount']) ?>đ</div>
                </div>
                <div style="text-align: right;">
                    <?php if ($booking['status'] === 'Confirmed'): ?>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #10b981; margin-bottom: 5px;"><i class="fas fa-check-circle me-1"></i>Đã thanh toán cọc</div>
                        <div style="font-size: 1.8rem; font-weight: 600; color: #10b981; font-family:'Cormorant Garamond', serif;"><?= number_format($booking['deposit_amount']) ?>đ</div>
                    <?php else: ?>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin-bottom: 5px;">Tiền cọc cần thanh toán</div>
                        <div style="font-size: 1.8rem; font-weight: 600; color: #ef4444; font-family:'Cormorant Garamond', serif;"><?= number_format($booking['deposit_amount']) ?>đ</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center;">
                <?php if ($booking['status'] === 'Confirmed'): ?>
                    <p style="font-size: 16px; color: #d4b06a; font-family:'Cormorant Garamond', serif; line-height: 1.6; font-style: italic;">
                        <i class="fas fa-glass-cheers me-2"></i> Đơn đặt bàn đã được xác nhận thanh toán thành công.<br>
                        Hẹn gặp lại quý khách tại <strong style="font-weight:600;">Restaurantly</strong>.
                    </p>
                <?php else: ?>
                    <p style="font-size: 15px; color: rgba(255,255,255,0.6); font-family:'Cormorant Garamond', serif; line-height: 1.6; font-style: italic;">
                        Quý khách vui lòng kiểm tra email hoặc điện thoại để nhận thông tin thanh toán.<br>
                        Cảm ơn quý khách đã tin tưởng lựa chọn <strong style="color:#fff; font-weight:600;">Restaurantly</strong>.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <section class="bk-hero">
        <div class="container" style="position:relative;z-index:1">
            <div class="bk-hero-eyebrow">
                <?php
                $icons = ['table' => '🍽', 'birthday' => '🎂', 'chef' => '👨‍🍳'];
                echo $icons[$type] ?? '🍽';
                ?> Đặt dịch vụ online
            </div>
            <h1>
                <em><?= htmlspecialchars($cfg['title']) ?></em><br>
                tại Restaurantly
            </h1>
            <p class="bk-hero-sub"><?= htmlspecialchars($cfg['sub']) ?></p>
            <div class="svc-tabs">
                <a href="?type=table" class="svc-tab <?= $type === 'table' ? 'active' : '' ?>">🍽 Đặt Bàn</a>
                <a href="?type=chef" class="svc-tab <?= $type === 'chef' ? 'active' : '' ?>">👨‍🍳 Đầu Bếp</a>
            </div>
        </div>
    </section>

    <div class="bk-wrap" id="main">
        <div>
            <form method="POST" action="config/process_service_booking.php" id="bk-form">
                <input type="hidden" name="service_type" value="<?= htmlspecialchars($type) ?>">
                <input type="hidden" name="selected_combo_id" id="sid" value="0">
                <input type="hidden" name="table_id" id="tid" value="">

                <div class="seamless-box">

                    <div>
                        <h3 class="section-heading">Thông tin liên hệ</h3>
                        <div class="frow">
                            <div class="fg">
                                <label class="fl">Họ và tên <span class="req">*</span></label>
                                <input type="text" name="customer_name" class="fi"
                                    value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>"
                                    required placeholder="Nguyễn Văn A" oninput="us()">
                            </div>
                            <div class="fg">
                                <label class="fl">Số điện thoại <span class="req">*</span></label>
                                <input type="tel" name="customer_phone" class="fi" required
                                    placeholder="09xx xxx xxx" oninput="us()">
                            </div>
                        </div>
                        <div class="frow">
                            <div class="fg">
                                <label class="fl">Ngày & Giờ đến <span class="req">*</span></label>
                                <input type="datetime-local" name="booking_date" id="bd" class="fi" required onchange="us()">
                            </div>
                            <div class="fg">
                                <label class="fl">Số lượng khách</label>
                                <div class="gctr">
                                    <button type="button" class="gctr-btn" onclick="cg(-1)">−</button>
                                    <input type="number" name="guests" id="gi" class="gctr-val"
                                        value="2" min="1" max="50" onchange="us()">
                                    <button type="button" class="gctr-btn" onclick="cg(1)">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <h3 class="section-heading">Vị trí & Chỗ ngồi</h3>
                        <button type="button" class="map-open-btn" data-bs-toggle="modal" data-bs-target="#mapModal">
                            <svg viewBox="0 0 24 24">
                                <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Xem sơ đồ nhà hàng & chọn bàn
                        </button>

                        <div class="seat-pill" id="sp">
                            <span class="seat-pill-badge" id="sp-code"></span>
                            <span class="seat-pill-price" id="sp-price"></span>
                            <span style="font-size:12px;color:var(--txt-muted)">đã chọn</span>
                            <button type="button" class="seat-pill-clear" onclick="clrSeat()">✕</button>
                        </div>

                        <select id="tsel" class="fi" onchange="fromDrop(this)">
                            <option value="" data-price="0">— hoặc chọn nhanh từ danh sách —</option>
                            <optgroup label="Sảnh chính">
                                <?php foreach ($t_open as $t): ?>
                                    <option value="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="Bàn <?= htmlspecialchars($t['table_code']) ?>" <?= !$t['is_available'] ? 'disabled' : '' ?>>
                                        Bàn <?= htmlspecialchars($t['table_code']) ?> — <?= number_format($t['price']) ?>đ <?= !$t['is_available'] ? '(Đã đặt)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Phòng VIP">
                                <?php foreach ($t_room as $r): ?>
                                    <option value="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="Phòng <?= htmlspecialchars($r['table_code']) ?>" <?= !$r['is_available'] ? 'disabled' : '' ?>>
                                        Phòng <?= htmlspecialchars($r['table_code']) ?> — <?= number_format($r['price']) ?>đ <?= !$r['is_available'] ? '(Đã đặt)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <h3 class="section-heading">Gói Combo ưu đãi <span class="opt-badge">Tùy chọn</span></h3>
                        <div class="combo-grid">
                            <div class="cc active" data-price="0" onclick="selCombo(0,this)">
                                <div class="cc-check">✓</div>
                                <div class="cc-name">Không dùng combo</div>
                                <div class="cc-price" style="color:var(--txt-muted);font-size:13px;font-weight:400">Gọi món tự do</div>
                            </div>
                            <?php foreach ($combos as $cb): ?>
                                <div class="cc" data-price="<?= (float)$cb['price'] ?>" onclick="selCombo(<?= $cb['id'] ?>,this)">
                                    <div class="cc-check">✓</div>
                                    <div class="cc-name"><?= htmlspecialchars($cb['name']) ?></div>
                                    <div class="cc-price"><?= number_format($cb['price']) ?>đ</div>
                                    <?php if (!empty($cb['list_foods'])): ?>
                                        <div class="cc-foods"><?= htmlspecialchars(str_replace('|', ' · ', $cb['list_foods'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($foods)): ?>
                        <hr class="section-divider">
                        <div>
                            <h3 class="section-heading">Thực đơn chọn trước <span class="opt-badge">Tùy chọn</span></h3>
                            <div class="menu-scroll">
                                <?php foreach ($foods as $fd): ?>
                                    <div class="mrow" id="mr<?= $fd['id'] ?>">
                                        <input type="checkbox" class="mrow-cb"
                                            name="menu_items[]" value="<?= $fd['id'] ?>"
                                            onchange="togMrow(this,<?= $fd['id'] ?>,<?= (float)$fd['price'] ?>)">
                                        <span class="mrow-name"><?= htmlspecialchars($fd['name']) ?></span>
                                        <span class="mrow-price"><?= number_format($fd['price']) ?>đ</span>
                                        <input type="number" class="mrow-qty fi" style="width:56px;margin-bottom:0;padding:6px 8px;"
                                            name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>"
                                            value="1" min="1" onchange="us()">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="section-divider">

                    <div>
                        <h3 class="section-heading">Yêu cầu bổ sung <span class="opt-badge">Tùy chọn</span></h3>
                        <textarea name="message" class="fi" rows="3" style="resize:vertical;line-height:1.6"
                            placeholder="Dị ứng thực phẩm, trang trí sinh nhật, yêu cầu đặc biệt..."></textarea>
                    </div>

                </div>
            </form>
        </div>

        <div>
            <div class="sidebar-wrap">
                <div class="sb-card">
                    <div class="sb-head">
                        <h5>Tóm tắt đặt chỗ</h5>
                        <p><?= htmlspecialchars($cfg['title'] . ' · ' . $cfg['sub']) ?></p>
                    </div>
                    <div class="sb-body">
                        <div class="sb-row"><span class="sb-row-l">Khách hàng</span><span class="sb-row-r" id="sn">—</span></div>
                        <div class="sb-row"><span class="sb-row-l">Ngày & Giờ</span><span class="sb-row-r" id="sd">—</span></div>
                        <div class="sb-row"><span class="sb-row-l">Số khách</span><span class="sb-row-r" id="sg">2 người</span></div>
                        <div class="sb-row"><span class="sb-row-l">Vị trí</span><span class="sb-row-r hi" id="ss">Chưa chọn</span></div>
                        <div class="sb-sep"></div>
                        <div class="sb-row"><span class="sb-row-l">Phí vị trí</span><span class="sb-row-r" id="sp2">0đ</span></div>
                        <div class="sb-row"><span class="sb-row-l">Combo / Món</span><span class="sb-row-r" id="sm">0đ</span></div>
                        <div class="sb-sep"></div>
                        <div class="deposit-block">
                            <div class="deposit-label">Tiền cọc trước (30%)</div>
                            <div class="deposit-num" id="sdep">0đ</div>
                            <div class="deposit-note">Thanh toán phần còn lại tại nhà hàng</div>
                        </div>
                        <button type="submit" form="bk-form" class="btn-go" id="btn-go">
                            <span id="btn-txt">Gửi yêu cầu đặt chỗ</span>
                            <span id="btn-spin" style="display:none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <p class="btn-go-note">Chúng tôi xác nhận trong vòng 30 phút</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sơ đồ vị trí nhà hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:24px">
                <div class="map-legend">
                    <div class="legend-item">
                        <div class="leg-dot" style="background:rgba(16,185,129,.4)"></div>Còn trống
                    </div>
                    <div class="legend-item">
                        <div class="leg-dot" style="background:rgba(239,68,68,.3)"></div>Đã đặt
                    </div>
                    <div class="legend-item">
                        <div class="leg-dot" style="background:rgba(205,164,94,.4)"></div>Đang chọn
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1.5fr 1px 1fr;gap:0;align-items:start">
                    <div>
                        <div class="map-zone-label">Sảnh chính — tối đa 6 khách/bàn</div>
                        <div class="seats-g" id="sg-open">
                            <?php foreach ($t_open as $t):
                                $st = $t['is_available'] ? 'available' : 'booked'; ?>
                                <div class="mseat <?= $st ?>"
                                    data-id="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>"
                                    data-code="Bàn <?= htmlspecialchars($t['table_code']) ?>">
                                    <span class="mseat-code"><?= htmlspecialchars($t['table_code']) ?></span>
                                    <span class="mseat-sub">≤6 khách</span>
                                    <span class="mseat-price"><?= number_format($t['price']) ?>đ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="background:rgba(205,164,94,.15);min-height:200px;margin:0 20px"></div>
                    <div>
                        <div class="map-zone-label">Phòng VIP — tối đa 16 khách</div>
                        <div class="seats-g vip" id="sg-vip">
                            <?php foreach ($t_room as $r):
                                $st = $r['is_available'] ? 'available' : 'booked'; ?>
                                <div class="mseat <?= $st ?>"
                                    style="min-height:80px"
                                    data-id="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>"
                                    data-code="Phòng <?= htmlspecialchars($r['table_code']) ?>">
                                    <span class="mseat-code"><?= htmlspecialchars($r['table_code']) ?></span>
                                    <span class="mseat-sub">≤16 khách</span>
                                    <span class="mseat-price"><?= number_format($r['price']) ?>đ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="modal-hint" id="mhint">Chưa chọn vị trí nào</span>
                <button type="button" class="btn btn-secondary"
                    style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);color:#fff;border-radius:8px"
                    data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="map-confirm-btn btn" id="mapConfirm" data-bs-dismiss="modal">
                    Xác nhận vị trí
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* STATE */
    var selId = 0,
        selCode = '',
        selPrice = 0,
        selComboPr = 0,
        menuPr = {};

    /* GUEST COUNTER */
    function cg(d) {
        var i = document.getElementById('gi');
        i.value = Math.max(1, Math.min(50, parseInt(i.value || 2) + d));
        us();
    }

    /* COMBO SELECT */
    function selCombo(id, el) {
        document.querySelectorAll('.cc').forEach(function(c) {
            c.classList.remove('active');
        });
        el.classList.add('active');
        document.getElementById('sid').value = id;
        selComboPr = parseFloat(el.dataset.price || 0);
        us();
    }

    /* MENU TOGGLE */
    function togMrow(cb, id, pr) {
        var row = document.getElementById('mr' + id);
        if (cb.checked) {
            row.classList.add('checked');
            menuPr[id] = pr;
        } else {
            row.classList.remove('checked');
            delete menuPr[id];
        }
        us();
    }

    /* SEAT FROM MAP */
    document.querySelectorAll('.mseat.available').forEach(function(s) {
        s.addEventListener('click', function() {
            document.querySelectorAll('.mseat').forEach(function(x) {
                x.classList.remove('selected');
            });
            s.classList.add('selected');
            selId = s.dataset.id;
            selCode = s.dataset.code;
            selPrice = parseFloat(s.dataset.price || 0);
            var h = document.getElementById('mhint');
            if (h) h.textContent = '✓ Đã chọn: ' + selCode + ' · ' + selPrice.toLocaleString('vi-VN') + 'đ';
        });
    });

    /* CONFIRM FROM MAP MODAL */
    document.getElementById('mapConfirm').addEventListener('click', function() {
        if (!selId) {
            return;
        }
        applyseat();
    });

    /* SEAT FROM DROPDOWN */
    function fromDrop(sel) {
        var opt = sel.options[sel.selectedIndex];
        selId = sel.value;
        selPrice = parseFloat(opt.dataset.price || 0);
        selCode = opt.dataset.code || opt.text.split('—')[0].trim();
        document.getElementById('tid').value = selId;
        document.querySelectorAll('.mseat').forEach(function(x) {
            x.classList.remove('selected');
        });
        var m = document.querySelector('.mseat[data-id="' + selId + '"]');
        if (m) m.classList.add('selected');
        showPill();
        us();
    }

    function applyseat() {
        document.getElementById('tid').value = selId;
        document.getElementById('tsel').value = selId;
        showPill();
        us();
    }

    function showPill() {
        if (!selId) {
            return;
        }
        var p = document.getElementById('sp');
        p.classList.add('show');
        document.getElementById('sp-code').textContent = selCode;
        document.getElementById('sp-price').textContent = selPrice.toLocaleString('vi-VN') + 'đ';
    }

    function clrSeat() {
        selId = '';
        selCode = '';
        selPrice = 0;
        document.getElementById('tid').value = '';
        document.getElementById('tsel').value = '';
        document.getElementById('sp').classList.remove('show');
        document.querySelectorAll('.mseat').forEach(function(x) {
            x.classList.remove('selected');
        });
        us();
    }

    /* UPDATE SUMMARY */
    function us() {
        var n = document.querySelector('[name="customer_name"]');
        document.getElementById('sn').textContent = n && n.value ? n.value : '—';

        var d = document.getElementById('bd');
        if (d && d.value) {
            var dt = new Date(d.value);
            document.getElementById('sd').textContent =
                dt.toLocaleDateString('vi-VN', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }) +
                ' · ' + dt.toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
        } else document.getElementById('sd').textContent = '—';

        document.getElementById('sg').textContent = document.getElementById('gi').value + ' người';
        document.getElementById('ss').textContent = selCode || 'Chưa chọn';
        document.getElementById('sp2').textContent = selPrice.toLocaleString('vi-VN') + 'đ';

        var mt = 0;
        for (var id in menuPr) {
            var qe = document.getElementById('q' + id);
            mt += menuPr[id] * (qe ? parseInt(qe.value) || 1 : 1);
        }
        var food = selComboPr > 0 ? selComboPr : mt;
        document.getElementById('sm').textContent = food.toLocaleString('vi-VN') + 'đ';
        document.getElementById('sdep').textContent =
            Math.ceil((selPrice + food) * .3).toLocaleString('vi-VN') + 'đ';
    }

    /* SUBMIT */
    document.getElementById('bk-form').addEventListener('submit', function() {
        var b = document.getElementById('btn-go');
        b.disabled = true;
        document.getElementById('btn-txt').style.display = 'none';
        document.getElementById('btn-spin').style.display = 'block';
    });

    /* INIT: set datetime min = now+2h */
    (function() {
        var inp = document.getElementById('bd');
        var now = new Date();
        now.setHours(now.getHours() + 2);
        if (inp) inp.min = now.toISOString().slice(0, 16);
        us();
    })();
</script>
    <?php endif; ?>
</div>

<?php include 'views/client/layouts/footer.php'; ?>
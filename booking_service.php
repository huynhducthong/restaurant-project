<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php'); exit;
}
$db  = (new Database())->getConnection();
$type = $_GET['type'] ?? 'table';
$svc  = [
    'table'    => ['title'=>'Đặt Chỗ Cao Cấp','sub'=>'Ẩm thực đỉnh cao chuẩn Michelin','icon'=>'table'],
    'birthday' => ['title'=>'Không Gian Kỷ Niệm','sub'=>'Riêng tư, sang trọng và đẳng cấp','icon'=>'birthday'],
    'chef'     => ['title'=>'Đầu Bếp Tại Gia','sub'=>'Mang tinh hoa 5 sao về gian bếp nhà bạn','icon'=>'chef'],
];
$cfg   = $svc[$type] ?? $svc['table'];
$combos = $db->query(
    "SELECT c.*, GROUP_CONCAT(f.name SEPARATOR '|') as list_foods
     FROM combos c LEFT JOIN combo_items ci ON c.id=ci.combo_id
     LEFT JOIN foods f ON ci.food_id=f.id WHERE c.status=1 GROUP BY c.id"
)->fetchAll(PDO::FETCH_ASSOC);

// Chỉ lấy dữ liệu bàn nếu KHÔNG phải dịch vụ Đầu bếp
$t_open = []; $t_room = [];
if ($type !== 'chef') {
    $t_open = $db->query("SELECT * FROM restaurant_tables WHERE category='open' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $t_room = $db->query("SELECT * FROM restaurant_tables WHERE category='room'  ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

$foods  = $db->query("SELECT * FROM foods WHERE status=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include 'views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* === LUXURY TOKENS === */
:root {
  --bg-dark: #0a1715;
  --forest: #143B36; /* Màu chủ đạo */
  --forest-light: #1d5750;
  --gold: #D4B06A;
  --gold-glow: rgba(212, 176, 106, 0.3);
  --glass-bg: rgba(20, 59, 54, 0.4);
  --glass-border: rgba(212, 176, 106, 0.15);
  --text-main: #fdfcf0;
  --text-muted: rgba(253, 252, 240, 0.5);
  --ease: cubic-bezier(0.25, 1, 0.5, 1);
}

body {
    background-color: var(--bg-dark);
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
}

/* === CINEMATIC HERO (GIỐNG HÌNH MẪU CỦA BẠN) === */
.hero-luxury {
    position: relative;
    height: 85vh;
    min-height: 600px;
    display: flex;
    align-items: center;
    background: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat fixed; 
}
.hero-luxury::before {
    content: '';
    position: absolute; inset: 0;
    /* Phủ Gradient bóng râm từ trái qua phải và từ dưới lên để nổi bật Text & Form */
    background: linear-gradient(90deg, rgba(10,23,21,0.9) 0%, rgba(10,23,21,0.5) 50%, transparent 100%),
                linear-gradient(0deg, var(--bg-dark) 0%, transparent 35%);
}
.hero-content {
    position: relative; z-index: 2;
    max-width: 1200px; margin: 0 auto; width: 100%; padding: 0 20px;
    text-align: left; /* CĂN TRÁI THEO HÌNH MẪU */
}
.hero-tagline {
    font-size: 11px; letter-spacing: 0.35em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 20px; display: inline-flex; align-items: center;
}
.hero-tagline::after {
    content: ''; display: inline-block; width: 40px; height: 1px; background: var(--gold); margin-left: 15px;
}
.hero-luxury h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(3.5rem, 6vw, 5rem); font-weight: 600; line-height: 1.1;
    margin-bottom: 20px; color: #fff;
}
.hero-sub { 
    color: rgba(255,255,255,0.75); font-weight: 300; font-size: 1.05rem; line-height: 1.6; 
    max-width: 550px; margin-bottom: 40px;
}
.hero-btns {
    display: flex; gap: 15px; align-items: center; flex-wrap: wrap;
}
.btn-hero-primary {
    background: linear-gradient(135deg, #E6C887 0%, #D4B06A 50%, #A5803A 100%);
    color: var(--bg-dark); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
    padding: 14px 35px; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    transition: all 0.3s var(--ease); border: none;
}
.btn-hero-primary:hover {
    transform: translateY(-3px); box-shadow: 0 10px 25px var(--gold-glow); color: var(--bg-dark);
}
.btn-hero-secondary {
    background: rgba(20,59,54,0.3); border: 1px solid rgba(255,255,255,0.25); backdrop-filter: blur(8px);
    color: #fff; font-weight: 500; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
    padding: 14px 35px; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    transition: all 0.3s var(--ease);
}
.btn-hero-secondary:hover {
    background: rgba(212,176,106,0.1); border-color: var(--gold); color: var(--gold);
}

/* === TABS DỊCH VỤ NHÚNG VÀO FORM === */
.service-selector-inline {
    display: flex; gap: 12px; margin-bottom: 30px; flex-wrap: wrap;
}
.svc-card-inline {
    background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
    padding: 10px 20px; border-radius: 50px; color: var(--text-muted); font-size: 12px; font-weight: 500; 
    text-transform: uppercase; text-decoration: none; transition: 0.3s; letter-spacing: 1px;
}
.svc-card-inline:hover { border-color: var(--gold); color: var(--gold); }
.svc-card-inline.active { background: var(--gold); border-color: var(--gold); color: var(--bg-dark); font-weight: 600; box-shadow: 0 0 15px var(--gold-glow); }


/* === MAIN BOOKING AREA === */
.booking-section {
    position: relative; max-width: 1200px; margin: -80px auto 100px; padding: 0 20px; z-index: 10;
    display: grid; grid-template-columns: 1fr 400px; gap: 30px;
}
@media (max-width: 992px) {
    .booking-section { grid-template-columns: 1fr; margin-top: 0; padding-top: 30px; }
}

.luxury-panel {
    background: var(--forest); border: 1px solid rgba(255,255,255,0.05);
    border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); overflow: hidden;
}
.panel-section { padding: 35px 40px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.panel-section:last-child { border-bottom: none; }

.section-title-lux {
    font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; color: #fff;
    margin-bottom: 25px; display: flex; align-items: center; gap: 15px;
}

/* === LUXURY INPUTS === */
.row-lux { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:600px) { .row-lux { grid-template-columns: 1fr; } }

.input-group-lux { position: relative; margin-bottom: 20px; }
.input-lux {
    width: 100%; background: rgba(0,0,0,0.25); border: 1px solid transparent;
    border-bottom: 1px solid rgba(255,255,255,0.2); padding: 16px 20px;
    color: #fff; font-family: 'Inter', sans-serif; font-size: 14px;
    border-radius: 8px 8px 0 0; transition: all 0.3s ease; outline: none;
}
.input-lux:focus { border-bottom-color: var(--gold); background: rgba(212, 176, 106, 0.05); }
.input-lux::placeholder { color: transparent; }
.label-lux {
    position: absolute; top: 16px; left: 20px; color: var(--text-muted); font-size: 14px;
    pointer-events: none; transition: 0.3s ease;
}
.input-lux:focus ~ .label-lux, .input-lux:not(:placeholder-shown) ~ .label-lux {
    top: -8px; left: 15px; font-size: 11px; color: var(--gold);
    background: var(--forest); padding: 0 5px; letter-spacing: 1px; text-transform: uppercase;
}
.input-lux option { background: var(--bg-dark); color: #fff; }

/* Guest Counter */
.guest-lux {
    display: flex; align-items: center; background: rgba(0,0,0,0.25);
    border-radius: 50px; padding: 5px; border: 1px solid rgba(255,255,255,0.1);
}
.btn-qty {
    width: 38px; height: 38px; border-radius: 50%; border: none;
    background: var(--forest-light); color: #fff; cursor: pointer; transition: 0.3s;
}
.btn-qty:hover { background: var(--gold); color: #000; }
.guest-lux input {
    flex: 1; text-align: center; background: transparent; border: none; color: #fff;
    font-size: 1.1rem; font-weight: 500; pointer-events: none;
}

/* === MAP BTN & CARDS === */
.map-btn-lux {
    width: 100%; padding: 25px; border: 1px dashed var(--gold); border-radius: 12px;
    color: var(--gold); text-transform: uppercase; letter-spacing: 2px; font-size: 12px;
    cursor: pointer; transition: 0.3s; text-align: center; background: rgba(212, 176, 106, 0.05);
}
.map-btn-lux:hover { background: rgba(212, 176, 106, 0.15); box-shadow: 0 0 20px var(--gold-glow); }

.card-select {
    border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);
    border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s var(--ease);
    position: relative; overflow: hidden;
}
.card-select:hover { border-color: rgba(212, 176, 106, 0.5); background: rgba(212, 176, 106, 0.05); }
.card-select.active {
    border-color: var(--gold); background: rgba(212, 176, 106, 0.1);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.card-select.active::after { content: '✓'; position: absolute; top: 15px; right: 15px; color: var(--gold); }

/* Thực đơn Add-on */
.menu-item-lux {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 15px; border-bottom: 1px dashed rgba(255,255,255,0.1);
    transition: 0.3s; border-radius: 8px;
}
.menu-item-lux:hover { background: rgba(255,255,255,0.02); }
.menu-checkbox {
    appearance: none; width: 18px; height: 18px; border: 1px solid rgba(255,255,255,0.3);
    border-radius: 3px; cursor: pointer; position: relative; transition: 0.2s;
}
.menu-checkbox:checked { background: var(--gold); border-color: var(--gold); }
.menu-checkbox:checked::after {
    content: '✓'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    color: var(--bg-dark); font-size: 10px; font-weight: bold;
}
.menu-qty-input {
    width: 50px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2);
    color: #fff; text-align: center; border-radius: 4px; padding: 4px; display: none;
}
.menu-item-lux.checked .menu-qty-input { display: block; }

/* === FLOATING SUMMARY === */
.summary-floating {
    position: sticky; top: 100px; background: var(--forest);
    border: 1px solid var(--glass-border);
    border-radius: 16px; padding: 35px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}
.sum-title {
    font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: #fff;
    border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px;
}
.sum-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px; color: var(--text-muted); }
.sum-val { color: #fff; font-weight: 400; text-align: right;}
.sum-val.highlight { color: var(--gold); font-weight: 500;}

.total-box { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; }
.deposit-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px; }
.deposit-amount { font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; color: var(--gold); line-height: 1.2; margin: 5px 0;}

.btn-gold-grad {
    width: 100%; padding: 16px; background: linear-gradient(135deg, #E6C887 0%, #D4B06A 50%, #A5803A 100%);
    border: none; border-radius: 8px; color: var(--bg-dark); font-weight: 600; letter-spacing: 1px;
    text-transform: uppercase; font-size: 13px; cursor: pointer; transition: all 0.4s ease;
}
.btn-gold-grad:hover { transform: translateY(-2px); box-shadow: 0 15px 30px var(--gold-glow); }

/* === MAP MODAL VIP === */
.modal-content.dark-lux { background: var(--bg-dark); border: 1px solid var(--gold); border-radius: 16px; color: #fff; }
.modal-header.lux { border-bottom: 1px solid rgba(255,255,255,0.1); padding: 20px 30px; }
.modal-title.lux { font-family: 'Cormorant Garamond', serif; color: var(--gold); font-size: 1.5rem; }
.btn-close-lux { filter: invert(1); opacity: 0.5; }
.cinematic-map { padding: 40px; background: radial-gradient(circle at center, rgba(20,59,54,0.6) 0%, transparent 80%); }
.map-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
.vip-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }

.seat-lux {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px; padding: 15px 10px; text-align: center; cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative;
}
.seat-lux.available:hover { border-color: var(--gold); transform: scale(1.05); box-shadow: 0 0 15px var(--gold-glow); z-index: 2; }
.seat-lux.booked { opacity: 0.3; cursor: not-allowed; }
.seat-lux.selected { background: rgba(212, 176, 106, 0.15); border-color: var(--gold); box-shadow: 0 0 20px var(--gold-glow); transform: scale(1.05); z-index: 3; }
.seat-code { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; color: #fff; display: block;}
.seat-lux.selected .seat-code { color: var(--gold); }
.seat-info { font-size: 10px; color: var(--text-muted); display: block; margin-top: 5px;}
</style>

<section class="hero-luxury">
    <div class="hero-content">
        <span class="hero-tagline">LUXURY FINE DINING EXPERIENCE</span>
        <h1>Trải Nghiệm Ẩm<br>Thực Đẳng Cấp</h1>
        <p class="hero-sub">Đặt bàn nhanh chóng với không gian fine dining sang trọng, đầu bếp chuyên nghiệp và trải nghiệm cao cấp dành riêng cho bạn.</p>
        <div class="hero-btns">
            <a href="#booking-form-area" class="btn-hero-primary"><i class="fas fa-calendar-check fs-6"></i> Đặt Ngay</a>
            <a href="menu.php" class="btn-hero-secondary"><i class="fas fa-utensils fs-6"></i> Khám Phá Thực Đơn</a>
        </div>
    </div>
</section>

<section class="booking-section" id="booking-form-area">
    <div class="luxury-panel">
        <form method="POST" action="config/process_service_booking.php" id="bk-form">
            <input type="hidden" name="service_type" value="<?= htmlspecialchars($type) ?>">
            <input type="hidden" name="selected_combo_id" id="sid" value="0">
            <input type="hidden" name="table_id" id="tid" value="">

            <div class="panel-section" style="padding-bottom: 10px; border-bottom: none;">
                <h2 class="section-title-lux" style="font-size: 2.2rem; margin-bottom: 25px; border:none; color: #fff;">
                    <?= htmlspecialchars($cfg['title']) ?>
                </h2>
                <div class="service-selector-inline">
                    <a href="?type=table" class="svc-card-inline <?= $type==='table'?'active':'' ?>"><i class="fas fa-utensils me-1"></i> Đặt Bàn Tiêu Chuẩn</a>
                    <a href="?type=birthday" class="svc-card-inline <?= $type==='birthday'?'active':'' ?>"><i class="fas fa-glass-cheers me-1"></i> Tiệc Kỷ Niệm</a>
                    <a href="?type=chef" class="svc-card-inline <?= $type==='chef'?'active':'' ?>"><i class="fas fa-fire-burner me-1"></i> Đầu Bếp Tại Gia</a>
                </div>
            </div>

            <div class="panel-section pt-0">
                <div class="row-lux mt-3">
                    <div class="input-group-lux">
                        <input type="text" name="customer_name" class="input-lux" placeholder=" " value="<?= htmlspecialchars($_SESSION['user_name']??'') ?>" required oninput="us()">
                        <label class="label-lux">Họ và tên *</label>
                    </div>
                    <div class="input-group-lux">
                        <input type="tel" name="customer_phone" class="input-lux" placeholder=" " required oninput="us()">
                        <label class="label-lux">Số điện thoại *</label>
                    </div>
                </div>
                <div class="row-lux">
                    <div class="input-group-lux">
                        <input type="datetime-local" name="booking_date" id="bd" class="input-lux" placeholder=" " required onchange="us()">
                        <label class="label-lux">Ngày & Giờ <?= $type==='chef' ? 'phục vụ' : 'đến' ?> *</label>
                    </div>
                    <div>
                        <label class="label-lux" style="position:relative; top:0; left:0; font-size:11px; margin-bottom:8px; display:block;">Số lượng khách *</label>
                        <div class="guest-lux">
                            <button type="button" class="btn-qty" onclick="cg(-1)">-</button>
                            <input type="number" name="guests" id="gi" value="2" min="1" max="50" readonly onchange="us()">
                            <button type="button" class="btn-qty" onclick="cg(1)">+</button>
                        </div>
                    </div>
                </div>

                <?php if ($type === 'birthday'): ?>
                <!-- PHẦN DÀNH RIÊNG CHO TIỆC KỶ NIỆM -->
                <div class="mt-4 pt-4 border-top border-secondary">
                    <h3 class="section-title-lux" style="font-size: 1.2rem; color: var(--gold); margin-bottom:15px;"><i class="fas fa-gift me-2"></i> Thông Tin Tiệc Kỷ Niệm</h3>
                    <div class="row-lux">
                        <div class="input-group-lux">
                            <select name="event_type" class="input-lux" onchange="us()">
                                <option value="Sinh nhật">Tiệc Sinh Nhật</option>
                                <option value="Kỷ niệm ngày cưới">Kỷ Niệm Ngày Cưới</option>
                                <option value="Tiệc tỏ tình/Cầu hôn">Tỏ Tình / Cầu Hôn</option>
                                <option value="Tiệc công ty/Họp mặt">Họp Mặt / Công Ty</option>
                                <option value="Khác">Khác</option>
                            </select>
                            <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: var(--forest); padding: 0 5px; letter-spacing: 1px; text-transform: uppercase;">Loại hình kỷ niệm</label>
                        </div>
                        <div class="input-group-lux">
                            <select name="decor_package" class="input-lux" onchange="us()">
                                <option value="Mặc định">Gói mặc định (Nến & Hoa bàn)</option>
                                <option value="Lãng mạn">Gói lãng mạn (+ Bóng bay, nhạc nhẹ)</option>
                                <option value="Hoàng gia">Gói hoàng gia (+ Rượu vang, backdrop)</option>
                            </select>
                            <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: var(--forest); padding: 0 5px; letter-spacing: 1px; text-transform: uppercase;">Gói trang trí</label>
                        </div>
                    </div>
                    <div class="d-flex gap-4 mt-2">
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="checkbox" name="has_cake" value="1" class="menu-checkbox" onchange="us()">
                            <span class="small">Đặt bánh kem kỷ niệm</span>
                        </label>
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="checkbox" name="has_flower" value="1" class="menu-checkbox" onchange="us()">
                            <span class="small">Đặt hoa tươi thiết kế</span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="panel-section">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--gold);"><?= $type === 'chef' ? 'Địa Điểm Phục Vụ' : 'Không Gian & Vị Trí' ?></h3>
                
                <?php if ($type !== 'chef'): ?>
                    <div class="map-btn-lux mb-3" data-bs-toggle="modal" data-bs-target="#mapModal">
                        <i class="fas fa-map-marked-alt me-2"></i> Xem Sơ Đồ Nhà Hàng & Chọn Bàn
                    </div>
                    <div id="selected-seat-display" class="card-select active" style="display:none; text-align:center;">
                        <div class="seat-code" id="sp-code"></div>
                        <div class="seat-info" style="color:var(--gold)" id="sp-price"></div>
                        <button type="button" class="btn-qty mt-2 mx-auto" style="width:25px;height:25px;font-size:10px;" onclick="clrSeat()"><i class="fas fa-times"></i></button>
                    </div>
                    <select id="tsel" class="input-lux" style="display:none;" onchange="fromDrop(this)">
                        <option value="" data-price="0"></option>
                        <?php foreach($t_open as $t): ?>
                            <option value="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="<?= $t['table_code'] ?>"></option>
                        <?php endforeach; ?>
                        <?php foreach($t_room as $r): ?>
                            <option value="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="VIP <?= $r['table_code'] ?>"></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="input-group-lux">
                        <textarea name="service_address" id="saddr" class="input-lux" rows="2" placeholder=" " required oninput="us()"></textarea>
                        <label class="label-lux">Địa chỉ chi tiết nơi Đầu bếp đến *</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel-section">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--gold);">Tinh Hoa Ẩm Thực</h3>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Gói Combo Đặc Biệt</p>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 25px;">
                    <div class="card-select cc active" data-price="0" onclick="selCombo(0,this)">
                        <div style="color:var(--gold); font-size:15px; margin-bottom:5px;">Gọi Món Tự Do</div>
                        <div style="font-size:11px; color:var(--text-muted)">A la Carte</div>
                    </div>
                    <?php foreach($combos as $cb): ?>
                        <div class="card-select cc" data-price="<?= (float)$cb['price'] ?>" onclick="selCombo(<?= $cb['id'] ?>,this)">
                            <div style="color:var(--gold); font-size:15px; margin-bottom:5px;"><?= htmlspecialchars($cb['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-main)"><?= number_format($cb['price']) ?> đ</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if(!empty($foods)): ?>
                    <p style="font-size:12px; color:var(--text-muted); margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Thực Đơn Chọn Trước (Add-on)</p>
                    <div style="max-height: 250px; overflow-y: auto; padding-right:10px;">
                        <?php foreach($foods as $fd): ?>
                        <div class="menu-item-lux" id="mr<?= $fd['id'] ?>">
                            <div style="display:flex; align-items:center; gap:15px;">
                                <input type="checkbox" class="menu-checkbox" name="menu_items[]" value="<?= $fd['id'] ?>" onchange="togMrow(this,<?= $fd['id'] ?>,<?= (float)$fd['price'] ?>)">
                                <div>
                                    <div style="font-size:14px;"><?= htmlspecialchars($fd['name']) ?></div>
                                    <div style="font-size:12px; color:var(--gold)"><?= number_format($fd['price']) ?> đ</div>
                                </div>
                            </div>
                            <input type="number" class="menu-qty-input" name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>" value="1" min="1" onchange="us()">
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel-section">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--gold);">Yêu Cầu Đặc Biệt</h3>
                <div class="input-group-lux mb-0">
                    <textarea name="message" class="input-lux" rows="3" placeholder=" "></textarea>
                    <label class="label-lux">Dị ứng thực phẩm, trang trí, yêu cầu khác...</label>
                </div>
            </div>
        </form>
    </div>

    <div>
        <div class="summary-floating">
            <h4 class="sum-title">Tóm Tắt Đặt Chỗ</h4>
            <div class="sum-row"><span>Khách hàng</span> <span class="sum-val" id="sn">—</span></div>
            <div class="sum-row"><span>Thời gian</span> <span class="sum-val" id="sd">—</span></div>
            <div class="sum-row"><span>Số khách</span> <span class="sum-val highlight" id="sg">2 Người</span></div>
            
            <?php if ($type !== 'chef'): ?>
                <div class="sum-row"><span>Vị trí bàn</span> <span class="sum-val highlight" id="ss">Chưa chọn</span></div>
                <div class="sum-row"><span>Phí vị trí</span> <span class="sum-val" id="sp2">0 đ</span></div>
            <?php else: ?>
                <div class="sum-row"><span>Địa điểm</span> <span class="sum-val highlight" id="saddr-sum" style="text-align:right; max-width:60%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Chưa nhập</span></div>
            <?php endif; ?>
            
            <?php if ($type === 'birthday'): ?>
                <div class="sum-row"><span>Loại kỷ niệm</span> <span class="sum-val highlight" id="m-event-sum">Sinh nhật</span></div>
                <div class="sum-row"><span>Dịch vụ tiệc</span> <span class="sum-val" id="m-addon-sum">Mặc định</span></div>
            <?php endif; ?>
            
            <div class="sum-row"><span>Combo / Món</span> <span class="sum-val" id="sm">0 đ</span></div>
            
            <div class="total-box">
                <div class="deposit-label">TIỀN CỌC TRƯỚC (30%)</div>
                <div class="deposit-amount" id="sdep">0<span style="font-size:1.2rem; color:#fff;"> đ</span></div>
                <p style="font-size:11px; color:var(--text-muted); margin-top:5px; font-style:italic;">Thanh toán phần còn lại tại nhà hàng.</p>
                
                <button type="submit" form="bk-form" class="btn-gold-grad mt-4" id="btn-go">
                    <span id="btn-txt">Gửi Yêu Cầu Đặt Chỗ</span>
                    <span id="btn-spin" style="display:none"><i class="fas fa-spinner fa-spin"></i> Đang xử lý...</span>
                </button>
            </div>
        </div>
    </div>
</section>

<?php if ($type !== 'chef'): ?>
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content dark-lux">
            <div class="modal-header lux">
                <h5 class="modal-title lux">Sơ Đồ Nhà Hàng</h5>
                <button type="button" class="btn-close btn-close-lux" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body cinematic-map">
                <div class="row">
                    <?php if ($type !== 'birthday'): ?>
                    <div class="col-md-8 border-end border-secondary">
                        <p style="color:var(--gold); font-size:12px; letter-spacing:2px; text-transform:uppercase; margin-bottom:20px; text-align:center;">Khu Vực Sảnh Chính</p>
                        <div class="map-grid">
                            <?php foreach($t_open as $t): $st=$t['is_available']?'available':'booked'; ?>
                            <div class="seat-lux <?= $st ?>" data-id="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="Bàn <?= htmlspecialchars($t['table_code']) ?>">
                                <span class="seat-code"><?= htmlspecialchars($t['table_code']) ?></span>
                                <span class="seat-info">Tối đa 6 khách<br><?= number_format($t['price']) ?>đ</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="<?= $type === 'birthday' ? 'col-md-12' : 'col-md-4' ?>">
                        <p style="color:var(--gold); font-size:12px; letter-spacing:2px; text-transform:uppercase; margin-bottom:20px; text-align:center;">Hệ Thống Phòng VIP</p>
                        <div class="<?= $type === 'birthday' ? 'map-grid' : 'vip-grid' ?>">
                            <?php foreach($t_room as $r): $st=$r['is_available']?'available':'booked'; ?>
                            <div class="seat-lux <?= $st ?>" style="padding: 25px 10px;" data-id="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="Phòng VIP <?= htmlspecialchars($r['table_code']) ?>">
                                <span class="seat-code">VIP <?= htmlspecialchars($r['table_code']) ?></span>
                                <span class="seat-info">Tối đa 16 khách<br><?= number_format($r['price']) ?>đ</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; gap:20px; font-size:12px; color:var(--text-muted)">
                    <span><span style="display:inline-block; width:12px; height:12px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.2); margin-right:5px;"></span> Còn trống</span>
                    <span><span style="display:inline-block; width:12px; height:12px; background:rgba(212,176,106,0.2); border:1px solid var(--gold); margin-right:5px;"></span> Đang chọn</span>
                    <span><span style="display:inline-block; width:12px; height:12px; background:#222; margin-right:5px;"></span> Đã đặt</span>
                </div>
                <div>
                    <button type="button" class="btn-outline-lux me-2" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="button" class="btn-gold-grad" id="mapConfirm" data-bs-dismiss="modal" style="width:auto; padding:10px 30px;">Xác Nhận Chọn Bàn</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ==========================================
// KỊCH BẢN JAVASCRIPT GIỮ NGUYÊN HOÀN TOÀN
// ==========================================
var selId=0, selCode='', selPrice=0, selComboPr=0, menuPr={};

function cg(d){
    var i=document.getElementById('gi');
    i.value=Math.max(1,Math.min(50,parseInt(i.value||2)+d));
    us();
}

function selCombo(id,el){
    document.querySelectorAll('.cc').forEach(function(c){c.classList.remove('active');});
    el.classList.add('active');
    document.getElementById('sid').value=id;
    selComboPr=parseFloat(el.dataset.price||0);
    us();
}

function togMrow(cb,id,pr){
    var row=document.getElementById('mr'+id);
    if(cb.checked){row.classList.add('checked');menuPr[id]=pr;}
    else{row.classList.remove('checked');delete menuPr[id];}
    us();
}

/* SỰ KIỆN CHỌN BÀN */
if (document.querySelectorAll('.seat-lux.available').length > 0) {
    document.querySelectorAll('.seat-lux.available').forEach(function(s){
        s.addEventListener('click',function(){
            document.querySelectorAll('.seat-lux').forEach(function(x){x.classList.remove('selected');});
            s.classList.add('selected');
            selId=s.dataset.id; selCode=s.dataset.code; selPrice=parseFloat(s.dataset.price||0);
        });
    });

    document.getElementById('mapConfirm').addEventListener('click',function(){
        if(!selId){return;}
        applyseat();
    });
}

function fromDrop(sel){
    var opt=sel.options[sel.selectedIndex];
    selId=sel.value;
    selPrice=parseFloat(opt.dataset.price||0);
    selCode=opt.dataset.code||opt.text.split('—')[0].trim();
    document.getElementById('tid').value=selId;
    document.querySelectorAll('.seat-lux').forEach(function(x){x.classList.remove('selected');});
    var m=document.querySelector('.seat-lux[data-id="'+selId+'"]');
    if(m) m.classList.add('selected');
    showPill();us();
}

function applyseat(){
    document.getElementById('tid').value=selId;
    if(document.getElementById('tsel')) document.getElementById('tsel').value=selId;
    showPill(); us();
}

function showPill(){
    if(!selId){return;}
    var btnMap = document.querySelector('.map-btn-lux');
    if(btnMap) btnMap.style.display = 'none';
    
    var p = document.getElementById('selected-seat-display');
    if(p) {
        p.style.display = 'block';
        document.getElementById('sp-code').textContent = selCode;
        document.getElementById('sp-price').textContent = selPrice.toLocaleString('vi-VN')+' đ';
    }
}

function clrSeat(){
    selId=''; selCode=''; selPrice=0;
    document.getElementById('tid').value='';
    if(document.getElementById('tsel')) document.getElementById('tsel').value='';
    
    var p = document.getElementById('selected-seat-display');
    if(p) p.style.display = 'none';
    
    var btnMap = document.querySelector('.map-btn-lux');
    if(btnMap) btnMap.style.display = 'block';
    
    document.querySelectorAll('.seat-lux').forEach(function(x){x.classList.remove('selected');});
    us();
}

/* CẬP NHẬT TÓM TẮT */
function us(){
    var n=document.querySelector('[name="customer_name"]');
    document.getElementById('sn').textContent=n&&n.value?n.value:'—';

    var d=document.getElementById('bd');
    if(d&&d.value){
        var dt=new Date(d.value);
        document.getElementById('sd').textContent = dt.toLocaleDateString('vi-VN') + ' ' + dt.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
    } else document.getElementById('sd').textContent='—';

    document.getElementById('sg').textContent=document.getElementById('gi').value+' Người';
  
    // Địa chỉ (nếu là Chef)
    var addr = document.getElementById('saddr');
    if (addr) {
        document.getElementById('saddr-sum').textContent = addr.value || 'Chưa nhập';
    }

    // Bàn (nếu là Table/Birthday)
    var ss = document.getElementById('ss');
    if (ss) {
        ss.textContent = selCode || 'Chưa chọn';
        document.getElementById('sp2').textContent = selPrice.toLocaleString('vi-VN')+' đ';
    }

    var mt=0;
    for(var id in menuPr){
        var qe=document.getElementById('q'+id);
        mt+=menuPr[id]*(qe?parseInt(qe.value)||1:1);
    }
    var food=selComboPr>0?selComboPr:mt;
    document.getElementById('sm').textContent=food.toLocaleString('vi-VN')+' đ';
  
    var total = food + (typeof selPrice !== 'undefined' ? selPrice : 0);
    document.getElementById('sdep').innerHTML = Math.ceil(total*.3).toLocaleString('vi-VN')+'<span style="font-size:1.2rem; color:#fff;"> đ</span>';

    // Cập nhật tóm tắt Tiệc (nếu có)
    var evType = document.querySelector('[name="event_type"]');
    if (evType) {
        document.getElementById('m-event-sum').textContent = evType.value;
        
        let decor = document.querySelector('[name="decor_package"]').value;
        let cake = document.querySelector('[name="has_cake"]').checked;
        let flower = document.querySelector('[name="has_flower"]').checked;
        
        let addonTxt = decor.split(' ')[0];
        if (cake) addonTxt += ' + Bánh';
        if (flower) addonTxt += ' + Hoa';
        document.getElementById('m-addon-sum').textContent = addonTxt;
    }
}

/* NGĂN CHẶN DOUBLE SUBMIT */
document.getElementById('bk-form').addEventListener('submit',function(){
    var b=document.getElementById('btn-go');
    b.style.pointerEvents = 'none';
    b.style.opacity = '0.7';
    document.getElementById('btn-txt').style.display='none';
    document.getElementById('btn-spin').style.display='inline-block';
});

/* KHỞI TẠO TIME TỐI THIỂU */
(function(){
    var inp=document.getElementById('bd');
    var now=new Date(); now.setHours(now.getHours()+2);
    if(inp) inp.min=now.toISOString().slice(0,16);
    us();
})();
</script>

<?php include 'views/client/layouts/footer.php'; ?>
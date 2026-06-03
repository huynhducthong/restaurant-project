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
    'chef'     => ['title'=>'Đầu Bếp Tại Gia','sub'=>'Trải nghiệm ẩm thực thượng lưu phục vụ tại tư gia','icon'=>'chef'],
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

// Lấy thông tin người dùng và sổ địa chỉ nếu đã đăng nhập
$user_info = null;
$user_addresses = [];
if (isset($_SESSION['user_id'])) {
    $u_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $user_info = $u_stmt->fetch(PDO::FETCH_ASSOC);

    $a_stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
    $a_stmt->execute([$_SESSION['user_id']]);
    $user_addresses = $a_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$user_flavor = [];
$user_fav = [];
if ($user_info) {
    if ($user_info['flavor_profile']) $user_flavor = array_map('trim', explode(',', mb_strtolower($user_info['flavor_profile'], 'UTF-8')));
    if ($user_info['fav_ingredients']) $user_fav = array_map('trim', explode(',', mb_strtolower($user_info['fav_ingredients'], 'UTF-8')));
}

function removeVietnameseAccentsBooking($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    return $str;
}

foreach ($foods as &$f) {
    $score = 0;
    $f_tags = removeVietnameseAccentsBooking($f['tags'] ?? '');
    $f_ingr = removeVietnameseAccentsBooking($f['ingredients'] ?? '');
    $f_name = removeVietnameseAccentsBooking($f['name'] ?? '');

    foreach ($user_flavor as $flav) {
        $flav = removeVietnameseAccentsBooking($flav);
        if (!empty($flav) && (strpos($f_tags, $flav) !== false || strpos($f_name, $flav) !== false || strpos($f_ingr, $flav) !== false)) $score += 2;
    }
    foreach ($user_fav as $fav) {
        $fav = removeVietnameseAccentsBooking($fav);
        if (!empty($fav) && (strpos($f_ingr, $fav) !== false || strpos($f_name, $fav) !== false || strpos($f_tags, $fav) !== false)) $score += 3;
    }
    $f['ai_score'] = $score;
}
unset($f);

usort($foods, function($a, $b) {
    if ($a['ai_score'] == $b['ai_score']) return $b['id'] <=> $a['id'];
    return $b['ai_score'] <=> $a['ai_score'];
});

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
    pointer-events: none; transition: 0.3s ease; z-index: 2;
}
.input-lux:focus ~ .label-lux, 
.input-lux:not(:placeholder-shown) ~ .label-lux,
select.input-lux ~ .label-lux {
    top: -8px; left: 15px; font-size: 11px; color: var(--gold);
    background: var(--forest); padding: 0 5px; letter-spacing: 1px; text-transform: uppercase;
    z-index: 2;
}
select.input-lux {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4B06A'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 20px center;
    background-size: 18px;
    padding-right: 45px;
    cursor: pointer;
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
                        <input type="tel" name="customer_phone" class="input-lux" placeholder=" " value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>" required oninput="us()">
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
                            <option value="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="<?= $t['table_code'] ?>" data-loc="<?= htmlspecialchars($t['table_location']??'') ?>" data-cat="open"></option>
                        <?php endforeach; ?>
                        <?php foreach($t_room as $r): ?>
                            <option value="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="VIP <?= $r['table_code'] ?>" data-loc="<?= htmlspecialchars($r['table_location']??'') ?>" data-cat="room"></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <!-- Địa điểm phục vụ (Địa chỉ) -->
                    <div class="input-group-lux mb-4">
                        <select id="saddr_select" class="input-lux" onchange="toggleAddrInput(); us();">
                            <?php if (empty($user_addresses)): ?>
                                <option value="custom">Nhập địa chỉ mới...</option>
                            <?php else: ?>
                                <?php foreach ($user_addresses as $addr): 
                                    $full_addr = $addr['address_detail'];
                                ?>
                                    <option value="<?= htmlspecialchars($full_addr) ?>">
                                        <?= htmlspecialchars(ucfirst($addr['address_type'] ?? 'Địa chỉ') . ' - ' . $full_addr) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom">Nhập địa chỉ mới...</option>
                            <?php endif; ?>
                        </select>
                        <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: var(--forest); padding: 0 5px; letter-spacing: 1px; text-transform: uppercase;">Địa điểm phục vụ</label>
                    </div>

                    <div class="input-group-lux mb-4" id="custom-addr-wrap" style="display: <?= empty($user_addresses) ? 'block' : 'none' ?>;">
                        <input type="text" id="custom_saddr" class="input-lux" placeholder=" " oninput="updateSaddrVal(); us();">
                        <label class="label-lux">Địa chỉ phục vụ chi tiết *</label>
                    </div>

                    <!-- Hidden input to hold the final address and match saddr ID -->
                    <input type="hidden" name="service_address" id="saddr" value="">

                    <script>
                    function toggleAddrInput() {
                        var select = document.getElementById('saddr_select');
                        var wrap = document.getElementById('custom-addr-wrap');
                        var customInput = document.getElementById('custom_saddr');
                        if (select) {
                            if (select.value === 'custom') {
                                if (wrap) wrap.style.display = 'block';
                                if (customInput) customInput.setAttribute('required', 'required');
                            } else {
                                if (wrap) wrap.style.display = 'none';
                                if (customInput) customInput.removeAttribute('required');
                            }
                        }
                        updateSaddrVal();
                        if (typeof updateChefReq === 'function') updateChefReq();
                    }
                    function updateSaddrVal() {
                        var select = document.getElementById('saddr_select');
                        var customInput = document.getElementById('custom_saddr');
                        var finalAddr = document.getElementById('saddr');
                        if (finalAddr) {
                            if (select && select.value === 'custom') {
                                finalAddr.value = customInput ? customInput.value : '';
                            } else if (select) {
                                finalAddr.value = select.value;
                            }
                        }
                        if (typeof updateChefReq === 'function') updateChefReq();
                    }
                    // Run once on load
                    window.addEventListener('DOMContentLoaded', function() {
                        toggleAddrInput();
                    });
                    </script>
                <?php endif; ?>
            </div>



            <div class="panel-section">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--gold);">Tinh Hoa Ẩm Thực</h3>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Bộ Sưu Tập Hương Vị</p>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; margin-bottom: 25px;">
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
                    <div class="card-select cc" id="opt-bespoke-menu" data-price="0" onclick="selCombo(-1,this)" style="border: 1px dashed var(--gold); background: linear-gradient(135deg, rgba(212,176,106,0.08), rgba(20,59,54,0.3));">
                        <div style="color:var(--gold); font-size:15px; margin-bottom:5px;"><i class="fas fa-scroll me-1"></i> Thiết Kế Riêng</div>
                        <div style="font-size:11px; color:var(--gold)">Bespoke Tasting Menu</div>
                    </div>
                </div>

                <div id="bespoke-menu-fields" style="display:none; margin-top:20px; border: 1px solid rgba(212,176,106,0.2); padding:20px; border-radius:8px; background:rgba(20,59,54,0.15);">
                    <h4 style="font-family:'Cormorant Garamond',serif; color:var(--gold); font-size:1.2rem; margin-bottom:15px; text-transform:uppercase; letter-spacing:1px;"><i class="fas fa-scroll me-2"></i> Yêu cầu Thiết kế Thực đơn riêng</h4>
                    <div class="row-lux mb-3">
                        <div class="input-group-lux">
                            <select name="chef_budget" id="chef_budget" class="input-lux" onchange="updateChefReq(); us();">
                                <option value="Thỏa thuận sau khi thiết kế thực đơn">Thỏa thuận sau khi thiết kế</option>
                                <option value="Dưới 1.500.000 đ / khách">Dưới 1.500.000 đ / khách</option>
                                <option value="1.500.000 đ - 3.000.000 đ / khách">1.500.000 đ - 3.000.000 đ / khách</option>
                                <option value="3.000.000 đ - 5.000.000 đ / khách">3.000.000 đ - 5.000.000 đ / khách</option>
                                <option value="Trên 5.000.000 đ / khách (Siêu cao cấp)">Trên 5.000.000 đ / khách (Siêu cao cấp)</option>
                            </select>
                            <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: var(--forest); padding: 0 5px; letter-spacing: 1px; text-transform: uppercase;">Ngân sách dự kiến</label>
                        </div>
                        <div class="input-group-lux">
                            <select name="chef_style" id="chef_style" class="input-lux" onchange="updateChefReq(); us();">
                                <option value="Tùy Bếp trưởng đề xuất">Tùy Bếp trưởng đề xuất</option>
                                <option value="Ẩm thực Việt Nam Đương Đại (Contemporary Vietnamese)">Ẩm thực Việt Nam Đương Đại</option>
                                <option value="Ẩm thực Việt Nam Cổ Điển (Traditional Vietnamese)">Ẩm thực Việt Nam Cổ Điển</option>
                                <option value="Ẩm thực Pháp - Việt Đông Dương (Indochine Fusion)">Ẩm thực Pháp - Việt Đông Dương</option>
                                <option value="Hải sản Cao cấp (Premium Seafood)">Hải sản Cao cấp</option>
                                <option value="Thực dưỡng & Chay Thượng hạng (Fine Vegetarian)">Thực dưỡng & Chay Thượng hạng</option>
                            </select>
                            <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: var(--forest); padding: 0 5px; letter-spacing: 1px; text-transform: uppercase;">Phong cách ẩm thực</label>
                        </div>
                    </div>
                    <div class="input-group-lux mb-0">
                        <textarea name="chef_requirements_detail" id="creq_detail" class="input-lux" rows="3" placeholder=" " oninput="updateChefReq(); us();"></textarea>
                        <label class="label-lux">Yêu cầu chi tiết (Nguyên liệu đặc biệt, dị ứng, khẩu vị ưa thích...)</label>
                    </div>
                </div>
                <textarea name="chef_requirements" id="creq" style="display:none;"></textarea>

                <div id="addon-foods-section">
                    <?php if(!empty($foods)): ?>
                        <p style="font-size:12px; color:var(--text-muted); margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Thực Đơn Chọn Trước (Add-on)</p>
                        <div style="max-height: 250px; overflow-y: auto; padding-right:10px;">
                            <?php foreach($foods as $fd): ?>
                            <div class="menu-item-lux" id="mr<?= $fd['id'] ?>" data-name="<?= htmlspecialchars($fd['name']) ?>">
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <input type="checkbox" class="menu-checkbox" name="menu_items[]" value="<?= $fd['id'] ?>" onchange="togMrow(this,<?= $fd['id'] ?>,<?= (float)$fd['price'] ?>)">
                                    <div>
                                        <div style="font-size:14px;">
                                            <?= htmlspecialchars($fd['name']) ?>
                                            <?php if(isset($fd['ai_score']) && $fd['ai_score'] > 0): ?>
                                                <span class="badge bg-warning text-dark ms-2" style="font-size: 10px; border: 1px solid var(--gold);"><i class="fas fa-magic me-1"></i> Gợi ý VIP</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--gold)"><?= number_format($fd['price']) ?> đ</div>
                                    </div>
                                </div>
                                <input type="number" class="menu-qty-input" name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>" value="1" min="1" onchange="us()">
                                <!-- Hidden input for food notes -->
                                <input type="hidden" name="food_notes[<?= $fd['id'] ?>]" id="fn<?= $fd['id'] ?>" value="">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>



            <div class="panel-section" id="bespoke-section">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--gold);"><i class="fas fa-gem me-2"></i> Trải nghiệm Cá nhân hóa</h3>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Bespoke Booking Experience</p>
                
                <div class="d-flex flex-column gap-3">
                    <label class="d-flex align-items-center gap-3 p-2 rounded" style="cursor:pointer; background:rgba(212,176,106,0.05); border:1px solid rgba(212,176,106,0.2); transition:0.3s;">
                        <input type="checkbox" name="has_candle" id="bespoke-candle" class="menu-checkbox" onchange="us()">
                        <div>
                            <div style="font-size:14px; font-weight:600; color:var(--gold);"><i class="fas fa-fire me-2"></i>Chuẩn bị Nến thơm thư giãn</div>
                            <div style="font-size:12px; color:var(--text-muted);">Tạo không gian lung linh, lãng mạn (+50.000 đ)</div>
                        </div>
                    </label>

                    <div class="p-2 rounded" style="background:rgba(212,176,106,0.05); border:1px solid rgba(212,176,106,0.2); transition:0.3s;">
                        <label class="d-flex align-items-center gap-3" style="cursor:pointer; margin-bottom:0;" onclick="document.getElementById('flower-input-wrap').style.display = document.getElementById('bespoke-flower').checked ? 'block' : 'none'; us();">
                            <input type="checkbox" name="has_bespoke_flower" id="bespoke-flower" class="menu-checkbox">
                            <div>
                                <div style="font-size:14px; font-weight:600; color:var(--gold);"><i class="fas fa-seedling me-2"></i>Hoa tươi thiết kế riêng</div>
                                <div style="font-size:12px; color:var(--text-muted);">Chuẩn bị loài hoa hoặc màu sắc bạn yêu thích (+200.000 đ)</div>
                            </div>
                        </label>
                        <div id="flower-input-wrap" style="display:none; margin-top:15px; padding-left:35px;">
                            <div class="input-group-lux mb-0">
                                <input type="text" name="flower_preference" class="input-lux" placeholder=" " style="font-size:13px; padding:8px 12px;">
                                <label class="label-lux" style="font-size:12px;">Loài hoa / Màu sắc yêu thích</label>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 rounded" style="background:rgba(212,176,106,0.05); border:1px solid rgba(212,176,106,0.2); transition:0.3s;">
                        <label class="d-flex align-items-center gap-3" style="cursor:pointer; margin-bottom:0;" onclick="document.getElementById('card-input-wrap').style.display = document.getElementById('bespoke-card').checked ? 'block' : 'none'; us();">
                            <input type="checkbox" name="has_handwritten_card" id="bespoke-card" class="menu-checkbox">
                            <div>
                                <div style="font-size:14px; font-weight:600; color:var(--gold);"><i class="fas fa-envelope-open-text me-2"></i>Viết Thiệp tay chúc mừng</div>
                                <div style="font-size:12px; color:var(--text-muted);">Thiệp thiết kế cao cấp kèm lời chúc viết tay (+30.000 đ)</div>
                            </div>
                        </label>
                        <div id="card-input-wrap" style="display:none; margin-top:15px; padding-left:35px;">
                            <div class="input-group-lux mb-0">
                                <textarea name="card_message" class="input-lux" rows="2" placeholder=" " style="font-size:13px; padding:8px 12px;"></textarea>
                                <label class="label-lux" style="font-size:12px;">Nội dung lời chúc</label>
                            </div>
                        </div>
                    </div>

                    <div id="vip-config-section" style="display:none; margin-top:10px; padding-top:15px; border-top:1px dashed rgba(212,176,106,0.3);">
                        <h4 style="font-size:13px; color:var(--gold); margin-bottom:15px; text-transform:uppercase;"><i class="fas fa-sliders-h me-2"></i>Cấu hình Không gian (Dành cho Phòng VIP)</h4>
                        <div class="row-lux mb-0">
                            <div class="input-group-lux">
                                <select name="music_playlist" class="input-lux" style="font-size:13px;">
                                    <option value="Mặc định nhà hàng">Mặc định nhà hàng</option>
                                    <option value="Classic Jazz (Cổ điển)">Classic Jazz (Cổ điển)</option>
                                    <option value="Elegant Acoustic (Tinh tế)">Elegant Acoustic (Tinh tế)</option>
                                    <option value="Romantic Instrumental">Romantic Instrumental (Lãng mạn)</option>
                                    <option value="Không bật nhạc">Không bật nhạc (Cần yên tĩnh)</option>
                                </select>
                                <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: var(--forest); padding: 0 5px; text-transform: uppercase;">Playlist Âm nhạc</label>
                            </div>
                            <div class="input-group-lux">
                                <select name="light_tone" class="input-lux" style="font-size:13px;">
                                    <option value="Mặc định">Mặc định</option>
                                    <option value="Warm (Ấm áp, Mờ ảo)">Warm (Ấm áp, Mờ ảo lãng mạn)</option>
                                    <option value="Natural (Sáng tự nhiên)">Natural (Sáng tự nhiên)</option>
                                </select>
                                <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: var(--forest); padding: 0 5px; text-transform: uppercase;">Tông màu Ánh sáng</label>
                            </div>
                        </div>
                    </div>
                </div>
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
            

            <div class="sum-row"><span>Bespoke Dịch vụ</span> <span class="sum-val highlight" id="s-bespoke">0 đ</span></div>
            <div class="sum-row"><span>Bộ Sưu Tập Hương Vị / Món</span> <span class="sum-val" id="sm">0 đ</span></div>
            
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

<!-- MODAL TÙY CHỌN MÓN ĂN -->
<div class="modal fade" id="foodOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content" style="background:var(--bg-dark); border:1px solid var(--gold); border-radius:12px;">
      <div class="modal-header" style="border-bottom:1px solid rgba(212,176,106,0.2);">
        <h5 class="modal-title" style="color:var(--gold); font-family:'Playfair Display', serif;"><i class="fas fa-utensils me-2"></i>Tùy chọn <span id="foodOptName">Món</span></h5>
        <button type="button" class="btn-close btn-close-white" onclick="cancelFoodOption()"></button>
      </div>
      <div class="modal-body">
        <div id="donenessWrap" style="display:none; margin-bottom:15px;">
            <label style="color:var(--gold); font-size:13px; font-weight:500; margin-bottom:5px;">Mức độ chín (Dành cho bò/steak):</label>
            <select id="foodOptDoneness" class="form-select form-select-sm" style="background:var(--glass-bg); color:var(--text-main); border:1px solid var(--glass-border);">
                <option value="">-- Mặc định --</option>
                <option value="Rare (Tái)">Rare (Tái)</option>
                <option value="Medium Rare (Tái vừa)">Medium Rare (Tái vừa)</option>
                <option value="Medium (Chín vừa)">Medium (Chín vừa)</option>
                <option value="Medium Well (Chín tới)">Medium Well (Chín tới)</option>
                <option value="Well Done (Chín kỹ)">Well Done (Chín kỹ)</option>
            </select>
        </div>
        <div style="margin-bottom:10px;">
            <label style="color:var(--gold); font-size:13px; font-weight:500; margin-bottom:5px;">Ghi chú / Topping thêm:</label>
            <textarea id="foodOptNote" class="form-control" rows="2" placeholder="Ví dụ: Không hành, thêm phô mai, ít cay..." style="background:var(--glass-bg); color:var(--text-main); border:1px solid var(--glass-border); font-size:13px;"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid rgba(212,176,106,0.2); border-bottom-left-radius:12px; border-bottom-right-radius:12px; padding:10px;">
        <button type="button" class="btn btn-sm" style="color:var(--text-muted);" onclick="cancelFoodOption()">Hủy bỏ</button>
        <button type="button" class="btn btn-sm" style="background:var(--gold); color:var(--bg-dark); font-weight:600;" onclick="saveFoodOption()">Xác nhận</button>
      </div>
    </div>
  </div>
</div>
<!-- KẾT THÚC MODAL TÙY CHỌN MÓN ĂN -->

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
                            <div class="seat-lux <?= $st ?>" data-id="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="Bàn <?= htmlspecialchars($t['table_code']) ?>" data-cat="open">
                                <span class="seat-code"><?= htmlspecialchars($t['table_code']) ?></span>
                                <span class="seat-info"><?= htmlspecialchars($t['table_location']??'') ?><br>Tối đa 6 khách<br><?= number_format($t['price']) ?>đ</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="<?= $type === 'birthday' ? 'col-md-12' : 'col-md-4' ?>">
                        <p style="color:var(--gold); font-size:12px; letter-spacing:2px; text-transform:uppercase; margin-bottom:20px; text-align:center;">Hệ Thống Phòng VIP</p>
                        <div class="<?= $type === 'birthday' ? 'map-grid' : 'vip-grid' ?>">
                            <?php foreach($t_room as $r): $st=$r['is_available']?'available':'booked'; ?>
                            <div class="seat-lux <?= $st ?>" style="padding: 25px 10px;" data-id="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="Phòng VIP <?= htmlspecialchars($r['table_code']) ?>" data-cat="room">
                                <span class="seat-code">VIP <?= htmlspecialchars($r['table_code']) ?></span>
                                <span class="seat-info"><?= htmlspecialchars($r['table_location']??'') ?><br>Tối đa 16 khách<br><?= number_format($r['price']) ?>đ</span>
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
var selId=0, selCode='', selPrice=0, selCat='', selComboPr=0, menuPr={};

function togBespoke(cb) {
    var extra = cb.closest('.menu-item-lux').querySelector('.bespoke-extra');
    if (extra) { extra.style.display = cb.checked ? 'block' : 'none'; }
}

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

    var bespokeFields = document.getElementById('bespoke-menu-fields');
    var addonSection = document.getElementById('addon-foods-section');
    var detailInput = document.getElementById('creq_detail');

    if (id === -1) {
        if (bespokeFields) bespokeFields.style.display = 'block';
        if (addonSection) addonSection.style.display = 'none';
        if (detailInput) detailInput.setAttribute('required', 'required');
    } else {
        if (bespokeFields) bespokeFields.style.display = 'none';
        if (addonSection) addonSection.style.display = 'block';
        if (detailInput) detailInput.removeAttribute('required');
    }
    updateChefReq();
    us();
}

function updateChefReq() {
    var select = document.getElementById('saddr_select');
    var customInput = document.getElementById('custom_saddr');
    var budget = document.getElementById('chef_budget') ? document.getElementById('chef_budget').value : '';
    var style = document.getElementById('chef_style') ? document.getElementById('chef_style').value : '';
    var detail = document.getElementById('creq_detail') ? document.getElementById('creq_detail').value : '';

    var address = '';
    if (select) {
        address = (select.value === 'custom') ? (customInput ? customInput.value : '') : select.value;
    }

    var parts = [];
    if (address) parts.push("Địa điểm phục vụ: " + address);

    var sid = document.getElementById('sid') ? parseInt(document.getElementById('sid').value) : 0;
    if (sid === -1) {
        if (budget) parts.push("Ngân sách: " + budget);
        if (style) parts.push("Phong cách: " + style);
        if (detail) parts.push("Chi tiết: " + detail);
    }

    var finalInput = document.getElementById('creq');
    if (finalInput) {
        finalInput.value = parts.join("\n");
    }
}

function togMrow(cb,id,pr){
    var row = document.getElementById('mr'+id);
    if(cb.checked){
        // Mở popup tùy chọn
        openFoodOption(cb, id, pr);
    } else {
        // Hủy chọn
        row.classList.remove('checked');
        delete menuPr[id];
        document.getElementById('fn'+id).value = ''; // clear note
        us();
    }
}

var curFoodCb = null, curFoodId = 0, curFoodPr = 0;
function openFoodOption(cb, id, pr) {
    curFoodCb = cb; curFoodId = id; curFoodPr = pr;
    var row = document.getElementById('mr'+id);
    var foodName = row.getAttribute('data-name').toLowerCase();
    
    document.getElementById('foodOptName').textContent = row.getAttribute('data-name');
    document.getElementById('foodOptNote').value = '';
    document.getElementById('foodOptDoneness').value = '';
    
    // Nếu là Bò / Steak thì hiện chọn độ chín
    if (foodName.includes('bò') || foodName.includes('steak') || foodName.includes('beef')) {
        document.getElementById('donenessWrap').style.display = 'block';
    } else {
        document.getElementById('donenessWrap').style.display = 'none';
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('foodOptionModal'));
    myModal.show();
}

function cancelFoodOption() {
    if(curFoodCb) curFoodCb.checked = false; // rollback checkbox
    bootstrap.Modal.getInstance(document.getElementById('foodOptionModal')).hide();
}

function saveFoodOption() {
    var doneness = document.getElementById('foodOptDoneness').value;
    var note = document.getElementById('foodOptNote').value;
    var finalNote = '';
    if (doneness) finalNote += "Độ chín: " + doneness + ". ";
    if (note) finalNote += note;
    
    document.getElementById('fn'+curFoodId).value = finalNote.trim();
    
    var row = document.getElementById('mr'+curFoodId);
    row.classList.add('checked');
    menuPr[curFoodId] = curFoodPr;
    us();
    
    // Thêm style nhỏ hiển thị có note
    if (finalNote.trim() !== '') {
        var noteDisplay = row.querySelector('.opt-note-display');
        if(!noteDisplay) {
            noteDisplay = document.createElement('div');
            noteDisplay.className = 'opt-note-display';
            noteDisplay.style.fontSize = '11px';
            noteDisplay.style.color = 'var(--gold)';
            noteDisplay.style.fontStyle = 'italic';
            noteDisplay.style.marginTop = '2px';
            row.querySelector('.menu-qty-input').insertAdjacentElement('beforebegin', noteDisplay);
        }
        noteDisplay.textContent = "📝 " + finalNote.trim();
    }
    
    bootstrap.Modal.getInstance(document.getElementById('foodOptionModal')).hide();
}

/* SỰ KIỆN CHỌN BÀN */
if (document.querySelectorAll('.seat-lux.available').length > 0) {
    document.querySelectorAll('.seat-lux.available').forEach(function(s){
        s.addEventListener('click',function(){
            document.querySelectorAll('.seat-lux').forEach(function(x){x.classList.remove('selected');});
            s.classList.add('selected');
            selId=s.dataset.id; selCode=s.dataset.code; selPrice=parseFloat(s.dataset.price||0); selCat=s.dataset.cat||'';
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
    selCat=opt.dataset.cat||'';
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
    
    var vipSec = document.getElementById('vip-config-section');
    if (vipSec) { vipSec.style.display = (selCat === 'room') ? 'block' : 'none'; }
}

function clrSeat(){
    selId=''; selCode=''; selPrice=0; selCat='';
    document.getElementById('tid').value='';
    if(document.getElementById('tsel')) document.getElementById('tsel').value='';
    
    var p = document.getElementById('selected-seat-display');
    if(p) p.style.display = 'none';
    
    var btnMap = document.querySelector('.map-btn-lux');
    if(btnMap) btnMap.style.display = 'block';
    
    var vipSec = document.getElementById('vip-config-section');
    if (vipSec) vipSec.style.display = 'none';
    
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

    var sid = document.getElementById('sid') ? parseInt(document.getElementById('sid').value) : 0;
    var food = 0;
    if (sid === -1) {
        document.getElementById('sm').textContent = "Thiết kế riêng (Liên hệ báo giá)";
        food = 0;
    } else {
        var mt=0;
        for(var id in menuPr){
            var qe=document.getElementById('q'+id);
            mt+=menuPr[id]*(qe?parseInt(qe.value)||1:1);
        }
        food=selComboPr>0?selComboPr:mt;
        document.getElementById('sm').textContent=food.toLocaleString('vi-VN')+' đ';
    }
  
    var evType = document.querySelector('[name="event_type"]');
    var decorPrice = 0;
    if (evType) {
        document.getElementById('m-event-sum').textContent = evType.value;
        
        let decor = document.querySelector('[name="decor_package"]').value;
        let cake = document.querySelector('[name="has_cake"]').checked;
        let flower = document.querySelector('[name="has_flower"]').checked;
        
        if (decor.includes('Mặc định')) decorPrice = 500000;
        else if (decor.includes('Lãng mạn')) decorPrice = 1500000;
        else if (decor.includes('Hoàng gia')) decorPrice = 3000000;
        
        if (cake) decorPrice += 300000;
        if (flower) decorPrice += 200000;

        let addonTxt = decor.split(' ')[0];
        if (cake) addonTxt += ' + Bánh';
        if (flower) addonTxt += ' + Hoa';
        document.getElementById('m-addon-sum').textContent = addonTxt;
    }

    var bespokePrice = 0;
    var cCandle = document.getElementById('bespoke-candle');
    var cFlower = document.getElementById('bespoke-flower');
    var cCard = document.getElementById('bespoke-card');
    
    if (cCandle && cCandle.checked) bespokePrice += 50000;
    if (cFlower && cFlower.checked) bespokePrice += 200000;
    if (cCard && cCard.checked) bespokePrice += 30000;
    
    var sBespoke = document.getElementById('s-bespoke');
    if (sBespoke) sBespoke.textContent = bespokePrice > 0 ? bespokePrice.toLocaleString('vi-VN') + ' đ' : '0 đ';

    var total = food + (typeof selPrice !== 'undefined' ? selPrice : 0) + decorPrice + bespokePrice;
    
    // Cập nhật số tiền đặt cọc 30%
    var btnGo = document.getElementById('btn-go');
    if (sid === -1 && total === 0) {
        document.getElementById('sdep').innerHTML = '<span style="font-size:1.2rem; color:var(--gold);">Liên hệ báo giá cọc</span>';
        if (btnGo) {
            var btnTxt = document.getElementById('btn-txt');
            if (btnTxt) {
                btnTxt.innerHTML = 'Gửi Yêu Cầu Thiết Kế Thực Đơn';
            }
        }
    } else {
        var deposit = Math.ceil(total * 0.3);
        document.getElementById('sdep').innerHTML = deposit.toLocaleString('vi-VN')+'<span style="font-size:1.2rem; color:#fff;"> đ</span>';
        
        if (btnGo) {
            var btnTxt = document.getElementById('btn-txt');
            if (btnTxt) {
                if (deposit > 0) {
                    btnTxt.innerHTML = 'Xác nhận & Thanh toán cọc (' + deposit.toLocaleString('vi-VN') + ' đ)';
                } else {
                    btnTxt.innerHTML = 'Gửi Yêu Cầu Đặt Chỗ';
                }
            }
        }
    }
}

/* NGĂN CHẶN DOUBLE SUBMIT */
document.getElementById('bk-form').addEventListener('submit',function(){
    var b=document.getElementById('btn-go');
    b.style.pointerEvents = 'none';
    b.style.opacity = '0.7';
    document.getElementById('btn-txt').style.display='none';
    document.getElementById('btn-spin').style.display='inline-block';
    
    // Để mock thanh toán, ta đổi text loading thành đang xử lý thanh toán
    document.getElementById('btn-spin').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý thanh toán...';
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
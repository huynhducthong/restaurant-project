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
    'bespoke'  => ['title'=>'Thiết Kế Riêng','sub'=>'Trải nghiệm ẩm thực độc bản được may đo riêng','icon'=>'gem'],
];
$cfg   = $svc[$type] ?? $svc['table'];
$combos_raw = $db->query(
    "SELECT c.*, t.name as theme_name, t.description as theme_desc, t.image as theme_img, GROUP_CONCAT(f.name SEPARATOR '|') as list_foods
     FROM combos c 
     LEFT JOIN themes t ON c.theme_id = t.id
     LEFT JOIN combo_items ci ON c.id=ci.combo_id
     LEFT JOIN foods f ON ci.food_id=f.id 
     WHERE c.status=1 GROUP BY c.id ORDER BY t.created_at DESC, c.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$grouped_combos = [];
foreach($combos_raw as $cb) {
    $t_name = $cb['theme_name'] ? $cb['theme_name'] : 'Thực Đơn Tiêu Chuẩn';
    if(!isset($grouped_combos[$t_name])) {
        $grouped_combos[$t_name] = [
            'desc' => $cb['theme_desc'] ?? '',
            'img' => $cb['theme_img'] ?? '',
            'combos' => []
        ];
    }
    $grouped_combos[$t_name]['combos'][] = $cb;
}

// Chỉ lấy dữ liệu bàn nếu KHÔNG phải dịch vụ Đầu bếp
$t_open = []; $t_room = [];
if ($type !== 'chef') {
    $t_open = $db->query("SELECT * FROM restaurant_tables WHERE category='open' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $t_room = $db->query("SELECT * FROM restaurant_tables WHERE category='room'  ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

$foods_raw  = $db->query("
    SELECT f.*, t.name as theme_name 
    FROM foods f 
    LEFT JOIN themes t ON f.theme_id = t.id 
    WHERE f.status=1 ORDER BY t.created_at DESC, f.name ASC
")->fetchAll(PDO::FETCH_ASSOC);



$chefs = $db->query("SELECT id, name FROM chefs WHERE is_active = 1 ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin người dùng và sổ địa chỉ nếu đã đăng nhập
$user_info = null;
$user_addresses = [];
$user_history_counts = [];

if (isset($_SESSION['user_id'])) {
    $u_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $user_info = $u_stmt->fetch(PDO::FETCH_ASSOC);

    $a_stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
    $a_stmt->execute([$_SESSION['user_id']]);
    $user_addresses = $a_stmt->fetchAll(PDO::FETCH_ASSOC);

    $h_stmt = $db->prepare("
        SELECT bd.menu_id, SUM(bd.quantity) as total_qty
        FROM booking_details bd
        JOIN service_bookings sb ON bd.booking_id = sb.id
        WHERE sb.user_id = ? AND bd.item_type = 'food'
        GROUP BY bd.menu_id
    ");
    $h_stmt->execute([$_SESSION['user_id']]);
    $user_history_counts = $h_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
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

foreach ($foods_raw as &$f) {
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

    // Cộng điểm dựa trên Lịch sử gọi món (Tần suất)
    if (isset($user_history_counts[$f['id']])) {
        // Mỗi lần gọi trong quá khứ cộng 2 điểm (Max 10 điểm để tránh việc lặp lại món cũ quá mức)
        $history_score = min(10, $user_history_counts[$f['id']] * 2);
        $score += $history_score;
    }

    $f['ai_score'] = $score;
}
unset($f);

usort($foods_raw, function($a, $b) {
    if ($a['ai_score'] == $b['ai_score']) return $b['id'] <=> $a['id'];
    return $b['ai_score'] <=> $a['ai_score'];
});

$grouped_foods = [];
foreach($foods_raw as $fd) {
    $t_name = $fd['theme_name'] ? $fd['theme_name'] : 'Món Lẻ (A La Carte)';
    $grouped_foods[$t_name][] = $fd;
}

include 'views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
/* === MINIMALIST TOKENS === */
:root {
  --bg-cream: #F6F2E9;
  --forest: #4F5B3A; /* Olive */
  --forest-light: #6A7A4E;
  --gold: #C9A66B;
  --gold-glow: rgba(201, 166, 107, 0.2);
  --glass-bg: #FFFFFF;
  --glass-border: #E0DDD5;
  --text-main: #222222;
  --text-muted: #666666;
  --ease: cubic-bezier(0.25, 1, 0.5, 1);
}

body {
    background-color: var(--bg-cream);
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
}

/* === CINEMATIC HERO === */
.hero-luxury {
    position: relative; height: 85vh; min-height: 600px; display: flex; align-items: center;
    background: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat fixed; 
}
.hero-luxury::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(90deg, rgba(246,242,233,0.95) 0%, rgba(246,242,233,0.7) 50%, transparent 100%),
                linear-gradient(0deg, var(--bg-cream) 0%, transparent 35%);
}
.hero-content {
    position: relative; z-index: 2; max-width: 1200px; margin: 0 auto; width: 100%; padding: 0 20px; text-align: left;
}
.hero-tagline {
    font-size: 11px; letter-spacing: 0.35em; text-transform: uppercase; color: var(--forest); margin-bottom: 20px; display: inline-flex; align-items: center; font-weight: 600;
}
.hero-tagline::after { content: ''; display: inline-block; width: 40px; height: 1px; background: var(--forest); margin-left: 15px; }
.hero-luxury h1 {
    font-family: 'Cormorant Garamond', serif; font-size: clamp(3.5rem, 6vw, 5rem); font-weight: 600; line-height: 1.1; margin-bottom: 20px; color: var(--text-main);
}
.hero-sub { color: var(--text-muted); font-weight: 400; font-size: 1.05rem; line-height: 1.6; max-width: 550px; margin-bottom: 40px; }
.hero-btns { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
.btn-hero-primary {
    background: var(--forest); color: #fff; font-weight: 500; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
    padding: 14px 35px; border-radius: 0; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s var(--ease); border: 1px solid var(--forest);
}
.btn-hero-primary:hover { background: #fff; color: var(--forest); }
.btn-hero-secondary {
    background: transparent; border: 1px solid var(--forest); color: var(--forest); font-weight: 500; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
    padding: 14px 35px; border-radius: 0; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s var(--ease);
}
.btn-hero-secondary:hover { background: var(--forest); color: #fff; }

/* === TABS DỊCH VỤ === */
.service-selector-inline { display: flex; gap: 8px; margin-bottom: 30px; flex-wrap: wrap; }
.svc-card-inline {
    background: #fff; border: 1px solid var(--glass-border); padding: 10px 12px; border-radius: 0; color: var(--text-muted); font-size: 11px; font-weight: 500; text-transform: uppercase; text-decoration: none; transition: 0.3s; letter-spacing: 1px; flex-grow: 1; text-align: center;
}
.svc-card-inline:hover { border-color: var(--forest); color: var(--forest); }
.svc-card-inline.active { background: var(--forest); border-color: var(--forest); color: #fff; font-weight: 600; }

/* === MAIN BOOKING AREA === */
.booking-section { position: relative; max-width: 1200px; margin: -80px auto 100px; padding: 0 20px; z-index: 10; display: grid; grid-template-columns: 1fr 400px; gap: 30px; }
@media (max-width: 992px) { .booking-section { grid-template-columns: 1fr; margin-top: 0; padding-top: 30px; } }

.luxury-panel { background: #fff; border: 1px solid var(--glass-border); border-radius: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
.panel-section { padding: 35px 40px; border-bottom: 1px solid var(--glass-border); }
.panel-section:last-child { border-bottom: none; }
.section-title-lux { font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; color: var(--forest); margin-bottom: 25px; display: flex; align-items: center; gap: 15px; font-weight: 600;}

/* === INPUTS === */
.row-lux { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:600px) { .row-lux { grid-template-columns: 1fr; } }
.input-group-lux { position: relative; margin-bottom: 20px; }
.input-lux {
    width: 100%; background: #fff; border: 1px solid var(--glass-border); padding: 16px 20px;
    color: var(--text-main); font-family: 'Inter', sans-serif; font-size: 14px; border-radius: 0; transition: all 0.3s ease; outline: none;
    height: 54px; box-sizing: border-box;
}
.input-lux:focus { border-color: var(--gold); }
.input-lux::placeholder { color: transparent; }
.label-lux {
    position: absolute; top: 18px; left: 20px; color: var(--text-muted); font-size: 14px; pointer-events: none; transition: 0.3s ease; z-index: 2;
}
.input-lux:focus ~ .label-lux, .input-lux:not(:placeholder-shown) ~ .label-lux, select.input-lux ~ .label-lux {
    top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: #fff; padding: 0 5px; letter-spacing: 1px; text-transform: uppercase; z-index: 2; font-weight: 600;
}
select.input-lux {
    appearance: none; -webkit-appearance: none; -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23C9A66B'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 20px center; background-size: 18px; padding-right: 45px; cursor: pointer;
}
.input-lux option { background: #fff; color: var(--text-main); }

/* Guest Counter */
.guest-lux { display: flex; align-items: center; background: #fff; border: 1px solid var(--glass-border); border-radius: 0; padding: 0; height: 54px; box-sizing: border-box; }
.btn-qty { width: 44px; height: 100%; border-radius: 0; border: none; background: #fafafa; color: var(--text-main); cursor: pointer; transition: 0.3s; font-size: 16px; }
.btn-qty:first-child { border-right: 1px solid var(--glass-border); }
.btn-qty:last-child { border-left: 1px solid var(--glass-border); }
.btn-qty:hover { background: var(--forest); color: #fff; border-color: var(--forest); }
.guest-lux input { flex: 1; text-align: center; background: transparent; border: none; color: var(--text-main); font-size: 1.1rem; font-weight: 500; pointer-events: none; }

/* === MAP BTN & CARDS === */
.map-btn-lux {
    width: 100%; padding: 25px; border: 1px solid var(--forest); border-radius: 0; color: var(--forest); text-transform: uppercase; letter-spacing: 2px; font-size: 12px; cursor: pointer; transition: 0.3s; text-align: center; background: #fff; font-weight: 600;
}
.map-btn-lux:hover { background: var(--forest); color: #fff; }

.card-select {
    border: 1px solid var(--glass-border); background: #fff; border-radius: 0; padding: 20px; cursor: pointer; transition: all 0.3s var(--ease); position: relative; overflow: hidden;
}
.card-select:hover { border-color: var(--forest); }
.card-select.active { border-color: var(--forest); background: rgba(79, 91, 58, 0.05); }
.card-select.active::after { content: '✓'; position: absolute; top: 15px; right: 15px; color: var(--forest); font-weight: bold; }

/* Thực đơn Add-on */
.menu-item-lux { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; border-bottom: 1px solid var(--glass-border); transition: 0.3s; border-radius: 0; background: #fff; }
.menu-item-lux:hover { background: #fafafa; }
.menu-checkbox { appearance: none; width: 18px; height: 18px; border: 1px solid var(--glass-border); border-radius: 0; cursor: pointer; position: relative; transition: 0.2s; }
.menu-checkbox:checked { background: var(--forest); border-color: var(--forest); }
.menu-checkbox:checked::after { content: '✓'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; font-size: 10px; font-weight: bold; }
.menu-qty-input { width: 60px; background: #fff; border: 1px solid var(--glass-border); color: var(--text-main); text-align: center; border-radius: 0; padding: 4px; opacity: 0.3; pointer-events: none; transition: 0.3s; }
.menu-note-input { width: 200px; background: #fff; border: 1px solid var(--glass-border); color: var(--text-main); border-radius: 0; padding: 4px; opacity: 0.3; pointer-events: none; transition: 0.3s; }
.menu-item-lux.checked .menu-qty-input, .menu-item-lux.checked .menu-note-input { opacity: 1; pointer-events: auto; border-color: var(--forest); }

/* === FLOATING SUMMARY === */
.summary-floating {
    position: sticky; top: 100px; background: var(--forest); border: none; border-radius: 0; padding: 35px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); color: #fff;
}
.sum-title { font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: var(--gold); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; }
.sum-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px; color: rgba(255,255,255,0.7); }
.sum-val { color: #fff; font-weight: 400; text-align: right;}
.sum-val.highlight { color: var(--gold); font-weight: 600;}

.total-box { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; }
.deposit-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-bottom: 5px; }
.deposit-amount { font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; color: var(--gold); line-height: 1.2; margin: 5px 0;}

.btn-gold-grad {
    width: 100%; padding: 16px; background: var(--gold); border: 1px solid var(--gold); border-radius: 0; color: #fff; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; font-size: 13px; cursor: pointer; transition: all 0.3s ease;
}
.btn-gold-grad:hover { background: transparent; color: var(--gold); }

/* === MAP MODAL VIP === */
.modal-content.dark-lux { background: #fff; border: 1px solid var(--forest); border-radius: 0; color: var(--text-main); }
.modal-header.lux { border-bottom: 1px solid var(--glass-border); padding: 20px 30px; }
.modal-title.lux { font-family: 'Cormorant Garamond', serif; color: var(--forest); font-size: 1.5rem; font-weight: 600; }
.btn-close-lux { opacity: 0.5; }
.cinematic-map { padding: 40px; background: var(--bg-cream); }
.map-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
.vip-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }

.seat-lux {
    background: #fff; border: 1px solid var(--glass-border); border-radius: 0; padding: 15px 10px; text-align: center; cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative;
}
.seat-lux.available:hover { border-color: var(--forest); transform: scale(1.05); z-index: 2; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.seat-lux.booked { opacity: 0.5; cursor: not-allowed; background: #f0f0f0;}
.seat-lux.selected { background: var(--forest); border-color: var(--forest); transform: scale(1.05); z-index: 3; }
.seat-code { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; color: var(--text-main); display: block; font-weight: 600;}
.seat-lux.selected .seat-code { color: #fff; }
.seat-info { font-size: 10px; color: var(--text-muted); display: block; margin-top: 5px;}
.seat-lux.selected .seat-info { color: rgba(255,255,255,0.7); }
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
            <input type="hidden" name="selected_combo_id" id="sid" value="<?= $type === 'bespoke' ? '-1' : '0' ?>">
            <input type="hidden" name="table_id" id="tid" value="">

            <div class="panel-section" style="padding-bottom: 10px; border-bottom: none;">
                <h2 class="section-title-lux" style="font-size: 2.2rem; margin-bottom: 25px; border:none; color: #fff;">
                    <?= htmlspecialchars($cfg['title']) ?>
                </h2>
                <div class="service-selector-inline">
                    <a href="?type=table" class="svc-card-inline <?= $type==='table'?'active':'' ?>"><i class="fas fa-utensils me-1"></i> Đặt Bàn Tiêu Chuẩn</a>
                    <a href="?type=birthday" class="svc-card-inline <?= $type==='birthday'?'active':'' ?>"><i class="fas fa-glass-cheers me-1"></i> Tiệc Kỷ Niệm</a>
                    <a href="?type=chef" class="svc-card-inline <?= $type==='chef'?'active':'' ?>"><i class="fas fa-fire-burner me-1"></i> Đầu Bếp Tại Gia</a>
                    <a href="?type=bespoke" class="svc-card-inline <?= $type==='bespoke'?'active':'' ?>"><i class="fas fa-gem me-1"></i> Thiết Kế Riêng</a>
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
                        <input type="text" name="booking_date" id="bd" class="input-lux" placeholder="Chọn Ngày & Giờ *" required onchange="us()">
                        <label class="label-lux">Ngày & Giờ <?= $type==='chef' ? 'phục vụ' : 'đến' ?> *</label>
                    </div>
                    <div class="input-group-lux">
                        <div class="guest-lux">
                            <button type="button" class="btn-qty" onclick="cg(-1)">-</button>
                            <input type="number" name="guests" id="gi" value="2" min="1" max="16" readonly onchange="us()">
                            <button type="button" class="btn-qty" onclick="cg(1)">+</button>
                        </div>
                        <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--gold); background: #fff; padding: 0 5px; letter-spacing: 1px; text-transform: uppercase; z-index: 2; font-weight: 600;">Số lượng khách *</label>
                        <?php if ($type === 'chef'): ?>
                            <div class="mt-2 p-2" style="background: #fdfdfd; border: 1px solid var(--glass-border); font-size: 11px; color: var(--text-main); line-height: 1.5; border-radius: 0;">
                                <i class="fas fa-info-circle me-1" style="color: var(--forest);"></i> <strong style="color: var(--forest);">Phí phục vụ Bếp trưởng</strong> thay đổi theo số lượng khách:<br>
                                • ≤ 2 khách: 250.000đ<br>
                                • 3-6 khách: 500.000đ<br>
                                • 7-12 khách: 1.000.000đ<br>
                                • Trên 12 khách: 1.200.000đ
                            </div>
                        <?php endif; ?>
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
                            <label class="label-lux" >Loại hình kỷ niệm</label>
                        </div>
                        <div class="input-group-lux">
                            <select name="decor_package" class="input-lux" onchange="us()">
                                <option value="Mặc định">Gói mặc định (Nến & Hoa bàn)</option>
                                <option value="Lãng mạn">Gói lãng mạn (+ Bóng bay, nhạc nhẹ)</option>
                                <option value="Hoàng gia">Gói hoàng gia (+ Rượu vang, backdrop)</option>
                            </select>
                            <label class="label-lux" >Gói trang trí</label>
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
                    <!-- Chọn Bếp trưởng -->
                    <div class="input-group-lux mb-4">
                        <select id="selected_chef" class="input-lux" onchange="updateChefReq(); us();">
                            <option value="">-- Nhà hàng tự sắp xếp --</option>
                            <?php foreach ($chefs as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>">Chef <?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="label-lux" >Bếp trưởng chỉ định</label>
                    </div>

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
                        <label class="label-lux" >Địa điểm phục vụ</label>
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
                
                <?php if ($type !== 'bespoke'): ?>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Bộ Sưu Tập Hương Vị</p>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; margin-bottom: 25px;">
                    <div class="card-select cc active" data-price="0" onclick="selCombo(0,this)">
                        <div style="color:var(--gold); font-size:15px; margin-bottom:5px;">Gọi Món Tự Do</div>
                        <div style="font-size:11px; color:var(--text-muted)">A la Carte</div>
                    </div>
                </div>

                <?php foreach($grouped_combos as $theme_name => $theme_data): 
                    $themeJson = htmlspecialchars(json_encode([
                        'name' => $theme_name,
                        'desc' => $theme_data['desc'],
                        'img'  => $theme_data['img']
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <p style="font-size:13px; color:var(--gold); margin-bottom:10px; font-family:'Playfair Display', serif; text-transform:uppercase; letter-spacing:1px; border-bottom: 1px dashed rgba(212,176,106,0.3); padding-bottom:5px; cursor:pointer;" onclick='showThemeInfo(<?= $themeJson ?>)'>
                    SET MENU TỪ CHỦ ĐỀ: <?= htmlspecialchars($theme_name) ?> <i class="fas fa-info-circle ms-1" style="font-size:11px; opacity:0.7;"></i>
                </p>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; margin-bottom: 25px;">
                    <?php foreach($theme_data['combos'] as $cb): ?>
                        <div class="card-select cc" data-price="<?= (float)$cb['price'] ?>" onclick="selCombo(<?= $cb['id'] ?>,this)">
                            <div style="color:var(--gold); font-size:15px; margin-bottom:5px;"><?= htmlspecialchars($cb['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-main)"><?= number_format($cb['price']) ?> đ</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>


                <?php endif; ?>

                <div id="bespoke-menu-fields" style="display:<?= $type === 'bespoke' ? 'block' : 'none' ?>; margin-top:20px; border: 1px solid var(--glass-border); padding:20px; background:#fafafa;">
                    <h4 style="font-family:'Cormorant Garamond',serif; color:var(--gold); font-size:1.2rem; margin-bottom:15px; text-transform:uppercase; letter-spacing:1px;"><i class="fas fa-scroll me-2"></i> Yêu cầu Thiết kế Thực đơn riêng</h4>
                    <div class="row-lux mb-3">
                        <div class="input-group-lux">
                            <select name="chef_budget" id="chef_budget" class="input-lux" onchange="updateChefReq(); us(); calcTotal();">
                                <option value="Thỏa thuận sau khi thiết kế thực đơn" data-price="0">Thỏa thuận sau khi thiết kế</option>
                                <option value="Dưới 1.500.000 đ / khách" data-price="1500000">Dưới 1.500.000 đ / khách</option>
                                <option value="1.500.000 đ - 3.000.000 đ / khách" data-price="2000000">1.500.000 đ - 3.000.000 đ / khách</option>
                                <option value="3.000.000 đ - 5.000.000 đ / khách" data-price="4000000">3.000.000 đ - 5.000.000 đ / khách</option>
                                <option value="Trên 5.000.000 đ / khách (Siêu cao cấp)" data-price="5000000">Trên 5.000.000 đ / khách (Siêu cao cấp)</option>
                            </select>
                            <label class="label-lux" >Ngân sách dự kiến</label>
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
                            <label class="label-lux" >Phong cách ẩm thực</label>
                        </div>
                    </div>
                    <div class="input-group-lux mb-0">
                        <textarea name="chef_requirements_detail" id="creq_detail" class="input-lux" rows="3" placeholder=" " oninput="updateChefReq(); us();"></textarea>
                        <label class="label-lux"><i class="fas fa-utensils me-1"></i> Yêu cầu chi tiết cho Thực đơn (Chủ đề, nguyên liệu đặc biệt...)</label>
                    </div>
                </div>
                <textarea name="chef_requirements" id="creq" style="display:none;"></textarea>

                <?php if ($type !== 'bespoke'): ?>
                <div id="addon-foods-section">
                    <?php if(!empty($grouped_foods)): ?>
                        <p style="font-size:12px; color:var(--text-muted); margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Thực Đơn Chọn Trước (Add-on)</p>
                        <div style="max-height: 400px; overflow-y: auto; padding-right:10px;">
                            <?php foreach($grouped_foods as $t_name => $t_foods): ?>
                                <div style="margin-bottom: 20px;">
                                    <h5 style="color:var(--gold); font-size:12px; text-transform:uppercase; border-bottom:1px dashed rgba(212,176,106,0.3); padding-bottom:5px; margin-bottom:10px;">MÓN LẺ THUỘC CHỦ ĐỀ: <?= htmlspecialchars($t_name) ?></h5>
                                    <?php foreach($t_foods as $fd): ?>
                                    <div class="menu-item-lux" id="mr<?= $fd['id'] ?>" data-name="<?= htmlspecialchars($fd['name']) ?>">
                                        <div style="display:flex; align-items:center; gap:15px;">
                                            <input type="checkbox" class="menu-checkbox" name="menu_items[]" value="<?= $fd['id'] ?>" onchange="togMrow(this,<?= $fd['id'] ?>,<?= (float)$fd['price'] ?>)">
                                            <div>
                                                <div style="font-size:14px;">
                                                    <?= htmlspecialchars($fd['name']) ?>
                                                    <?php 
                                                        $is_hist = isset($user_history_counts[$fd['id']]);
                                                        $flav_score = isset($fd['ai_score']) ? $fd['ai_score'] - ($is_hist ? min(10, $user_history_counts[$fd['id']] * 2) : 0) : 0;
                                                    ?>
                                                    <?php if($is_hist): ?>
                                                        <span class="badge bg-info text-white ms-2" style="font-size: 10px;"><i class="fas fa-history me-1"></i> Đã từng gọi</span>
                                                    <?php endif; ?>
                                                    <?php if($flav_score > 0): ?>
                                                        <span class="badge bg-warning text-dark ms-2" style="font-size: 10px; border: 1px solid var(--gold);"><i class="fas fa-magic me-1"></i> Gợi ý</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size:12px; color:var(--gold)"><?= number_format($fd['price']) ?> đ</div>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <input type="text" class="menu-note-input" name="food_notes[<?= $fd['id'] ?>]" id="fn<?= $fd['id'] ?>" placeholder="Ghi chú (vd: không hành, cho khách...)" style="font-size:11px; padding: 4px 8px; border: 1px solid var(--glass-border); border-radius: 0; outline: none;">
                                            <input type="number" class="menu-qty-input" name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>" value="1" min="1" onchange="us()">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; // end if type !== bespoke ?>
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

                    <div class="p-2 rounded" style="background:#fff; border:1px solid var(--glass-border); transition:0.3s; border-radius:0;">
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

                    <div class="p-2 rounded" style="background:#fff; border:1px solid var(--glass-border); transition:0.3s; border-radius:0;">
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
                    <div class="input-group-lux mt-3 mb-0">
                        <select name="dedicated_server" class="input-lux" style="font-size:13px;" onchange="if(this.value=='other') { document.getElementById('server-name-wrap').style.display='block'; } else { document.getElementById('server-name-wrap').style.display='none'; }">
                            <option value="">Không yêu cầu (Nhà hàng tự sắp xếp)</option>
                            <option value="Phục vụ Nam">Ưu tiên Phục vụ Nam</option>
                            <option value="Phục vụ Nữ">Ưu tiên Phục vụ Nữ</option>
                            <option value="other">Yêu cầu đích danh (Khách quen)</option>
                        </select>
                        <label class="label-lux" style="font-size:12px;">💁 Yêu cầu Phục vụ riêng</label>
                    </div>
                    <div id="server-name-wrap" style="display:none; margin-top:10px;">
                        <div class="input-group-lux mb-0">
                            <input type="text" name="dedicated_server_name" class="input-lux" placeholder=" " style="font-size:13px; padding:8px 12px;">
                            <label class="label-lux" style="font-size:12px;">Tên nhân viên yêu cầu</label>
                        </div>
                    </div>

                    <div id="vip-config-section" style="display:none; margin-top:15px; padding-top:15px; border-top:1px dashed rgba(212,176,106,0.3);">
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
                                <label class="label-lux" >Playlist Âm nhạc</label>
                            </div>
                            <div class="input-group-lux">
                                <select name="light_tone" class="input-lux" style="font-size:13px;">
                                    <option value="Mặc định">Mặc định</option>
                                    <option value="Warm (Ấm áp, Mờ ảo)">Warm (Ấm áp, Mờ ảo lãng mạn)</option>
                                    <option value="Natural (Sáng tự nhiên)">Natural (Sáng tự nhiên)</option>
                                </select>
                                <label class="label-lux" >Tông màu Ánh sáng</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-section">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--gold);">Hồ Sơ Yêu Cầu & Khẩu Vị</h3>
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="fw-bold mb-3" style="color: var(--gold); font-family: 'Cormorant Garamond', serif; font-size: 1.1rem;"><i class="fas fa-exclamation-triangle me-1"></i> Dị ứng thực phẩm</label>
                        <div class="d-flex flex-column gap-2" style="font-size: 0.95rem;">
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Hải sản" class="form-check-input me-2" style="cursor:pointer"> Hải sản</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Sữa" class="form-check-input me-2" style="cursor:pointer"> Sữa</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Gluten" class="form-check-input me-2" style="cursor:pointer"> Gluten</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Đậu phộng" class="form-check-input me-2" style="cursor:pointer"> Đậu phộng</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Trứng" class="form-check-input me-2" style="cursor:pointer"> Trứng</label>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="fw-bold mb-3" style="color: var(--gold); font-family: 'Cormorant Garamond', serif; font-size: 1.1rem;"><i class="fas fa-leaf me-1"></i> Chế độ ăn</label>
                        <div class="d-flex flex-column gap-2" style="font-size: 0.95rem;">
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Healthy" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Healthy</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Vegetarian" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Vegetarian</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Vegan" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Vegan</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Keto" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Keto</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-bold mb-3" style="color: var(--gold); font-family: 'Cormorant Garamond', serif; font-size: 1.1rem;"><i class="fas fa-glass-cheers me-1"></i> Mục đích</label>
                        <div class="d-flex flex-column gap-2" style="font-size: 0.95rem;">
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Hẹn hò" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Hẹn hò</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Sinh nhật" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Sinh nhật</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Kỷ niệm" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Kỷ niệm</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Tiếp khách" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Tiếp khách</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Cầu hôn" class="form-check-input me-2 toggle-radio" style="cursor:pointer"> Cầu hôn</label>
                        </div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    let radios = document.querySelectorAll('.toggle-radio');
                    radios.forEach(radio => {
                        radio.addEventListener('click', function(e) {
                            if (this.dataset.wasChecked === 'true') {
                                this.checked = false;
                                this.dataset.wasChecked = 'false';
                            } else {
                                // Reset all other radios in the same group
                                document.querySelectorAll(`input[name="${this.name}"]`).forEach(r => r.dataset.wasChecked = 'false');
                                this.dataset.wasChecked = 'true';
                            }
                        });
                    });
                });
                </script>

                <div class="input-group-lux mb-0 mt-4">
                    <textarea name="message" class="input-lux" rows="2" placeholder=" "></textarea>
                    <label class="label-lux"><i class="fas fa-comment-dots me-1"></i> Ghi chú / Yêu cầu đặc biệt khác</label>
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
                <div class="sum-row"><span>Phí phục vụ (Đầu bếp)</span> <span class="sum-val" id="schef-fee">0 đ</span></div>
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
    <div class="modal-content" style="background:#fff; border:1px solid var(--forest); border-radius:0;">
      <div class="modal-header" style="border-bottom:1px solid rgba(212,176,106,0.2);">
        <h5 class="modal-title" style="color:var(--gold); font-family:'Playfair Display', serif;"><i class="fas fa-utensils me-2"></i>Tùy chọn: <span id="foodOptName" style="margin-left: 5px;">Món</span></h5>
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

<!-- MODAL CHI TIẾT CHỦ ĐỀ -->
<div class="modal fade" id="themeInfoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
    <div class="modal-content" style="background:#111; border:1px solid var(--forest); border-radius:0;">
      <div class="modal-header" style="border-bottom:1px solid rgba(212,176,106,0.2);">
        <h5 class="modal-title" style="color:var(--gold); font-family:'Playfair Display', serif;"><i class="fas fa-book-open me-2"></i>Chi tiết Chủ đề</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:0;">
          <img id="themeInfoImg" src="" alt="Theme Image" style="width:100%; height:250px; object-fit:cover; display:none;">
          <div style="padding:20px;">
              <h4 id="themeInfoName" style="color:var(--gold); font-family:'Playfair Display', serif; margin-bottom:15px; font-size:1.5rem;"></h4>
              <p id="themeInfoDesc" style="color:var(--text-muted); font-size:15px; line-height:1.6; font-style:italic; font-family:'Cormorant Garamond', serif;"></p>
          </div>
      </div>
    </div>
  </div>
</div>

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
                                <span class="seat-code"><?= htmlspecialchars($r['table_code']) ?></span>
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
<script>
// ==========================================
// KỊCH BẢN JAVASCRIPT GIỮ NGUYÊN HOÀN TOÀN
// ==========================================

function showThemeInfo(data) {
    if(data.name === 'Thực Đơn Tiêu Chuẩn') return;
    document.getElementById('themeInfoName').innerText = data.name;
    document.getElementById('themeInfoDesc').innerText = data.desc || 'Chưa có mô tả cho chủ đề này.';
    let imgEl = document.getElementById('themeInfoImg');
    if(data.img) {
        imgEl.src = data.img; 
        imgEl.style.display = 'block';
    } else {
        imgEl.style.display = 'none';
    }
    var modal = new bootstrap.Modal(document.getElementById('themeInfoModal'));
    modal.show();
}

let comboId = 0, selId=0, selCode='', selPrice=0, selCat='', selComboPr=0, menuPr={};

function togBespoke(cb) {
    var extra = cb.closest('.menu-item-lux').querySelector('.bespoke-extra');
    if (extra) { extra.style.display = cb.checked ? 'block' : 'none'; }
}

function cg(d){
    var i=document.getElementById('gi');
    var guests = Math.max(1,Math.min(16,parseInt(i.value||2)+d));
    i.value = guests;
    if (guests > 6 && selCat === 'open') {
        alert('Bàn thường chỉ chứa tối đa 6 khách. Vui lòng chọn Phòng VIP hoặc giảm số lượng khách.');
        clrSeat();
    }
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
    var reqInput = document.getElementById('creq');
    if (!reqInput) return;
    
    var select = document.getElementById('saddr_select');
    var customInput = document.getElementById('custom_saddr');
    var budget = document.getElementById('chef_budget') ? document.getElementById('chef_budget').value : '';
    var style = document.getElementById('chef_style') ? document.getElementById('chef_style').value : '';
    var detail = document.getElementById('creq_detail') ? document.getElementById('creq_detail').value : '';
    var chef = document.getElementById('selected_chef') ? document.getElementById('selected_chef').value : '';

    var address = '';
    if (select) {
        address = (select.value === 'custom') ? (customInput ? customInput.value : '') : select.value;
    }

    var parts = [];
    if (address) parts.push("Địa điểm phục vụ: " + address);
    if (chef) parts.push("Bếp trưởng chỉ định: " + chef);

    var sid = document.getElementById('sid') ? parseInt(document.getElementById('sid').value) : 0;
    if (sid === -1) {
        if (budget) parts.push("Ngân sách: " + budget);
        if (style) parts.push("Phong cách: " + style);
        if (detail) parts.push("Chi tiết: " + detail);
    }

    if (reqInput) {
        reqInput.value = parts.join("\n");
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

/* SỰ KIỆN CHỌN BÀN & AJAX KHẢ DỤNG ĐỘNG */
    document.querySelectorAll('.seat-lux').forEach(function(s){
        s.addEventListener('click',function(){
            if(!s.classList.contains('available')) return;
            var cat = s.dataset.cat || '';
            var guests = parseInt(document.getElementById('gi').value) || 1;
            if (cat === 'open' && guests > 6) {
                alert('Bàn thường chỉ chứa tối đa 6 khách. Khách hàng đi trên 6 người vui lòng chọn Phòng VIP.');
                return;
            }
            document.querySelectorAll('.seat-lux').forEach(function(x){x.classList.remove('selected');});
            s.classList.add('selected');
            selId=s.dataset.id; selCode=s.dataset.code; selPrice=parseFloat(s.dataset.price||0); selCat=cat;
        });
    });

    var mapConfirmBtn = document.getElementById('mapConfirm');
    if (mapConfirmBtn) {
        mapConfirmBtn.addEventListener('click',function(){
            if(!selId){return;}
            applyseat();
        });
    }

    function checkTableAvailability() {
        var d = document.getElementById('bd');
        if (!d || !d.value) return;
        
        fetch('api/check_table_availability.php?datetime=' + encodeURIComponent(d.value))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    var unav = data.unavailable_tables || [];
                    document.querySelectorAll('.seat-lux').forEach(function(s) {
                        var id = parseInt(s.dataset.id);
                        if (unav.includes(id)) {
                            s.classList.remove('available');
                            s.classList.add('booked');
                            if (selId == id) clrSeat();
                        } else {
                            s.classList.add('available');
                            s.classList.remove('booked');
                        }
                    });
                }
            })
            .catch(err => console.error(err));
    }

function fromDrop(sel){
    var opt=sel.options[sel.selectedIndex];
    var cat = opt.dataset.cat||'';
    var guests = parseInt(document.getElementById('gi').value) || 1;
    if (cat === 'open' && guests > 6) {
        alert('Bàn thường chỉ chứa tối đa 6 khách. Khách hàng đi trên 6 người vui lòng chọn Phòng VIP.');
        sel.value = '';
        return;
    }
    selId=sel.value;
    selPrice=parseFloat(opt.dataset.price||0);
    selCode=opt.dataset.code||opt.text.split('—')[0].trim();
    selCat=cat;
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
    // Check table availability remotely
    checkTableAvailability();

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

    // Tính phí Đầu bếp tại gia dựa trên số khách
    var typeStr = "<?= $type ?>";
    var guestsNum = parseInt(document.getElementById('gi').value) || 2;
    var chefServiceFee = 0;
    if (typeStr === 'chef') {
        if (guestsNum <= 2) chefServiceFee = 250000;
        else if (guestsNum <= 6) chefServiceFee = 500000;
        else if (guestsNum <= 12) chefServiceFee = 1000000;
        else chefServiceFee = 1200000;
        
        var schef = document.getElementById('schef-fee');
        if (schef) schef.textContent = chefServiceFee.toLocaleString('vi-VN') + ' đ';
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
    
    // Tính ngân sách thiết kế riêng (nếu có)
    var budgetSel = document.getElementById('chef_budget');
    if (budgetSel && typeStr === 'bespoke') {
        var opt = budgetSel.options[budgetSel.selectedIndex];
        var bPrice = parseInt(opt.getAttribute('data-price')) || 0;
        bespokePrice += bPrice * guestsNum;
    }
    var cCandle = document.getElementById('bespoke-candle');
    var cFlower = document.getElementById('bespoke-flower');
    var cCard = document.getElementById('bespoke-card');
    
    if (cCandle && cCandle.checked) bespokePrice += 50000;
    if (cFlower && cFlower.checked) bespokePrice += 200000;
    if (cCard && cCard.checked) bespokePrice += 30000;
    
    var sBespoke = document.getElementById('s-bespoke');
    if (sBespoke) sBespoke.textContent = bespokePrice > 0 ? bespokePrice.toLocaleString('vi-VN') + ' đ' : '0 đ';

    var total = food + (typeof selPrice !== 'undefined' ? selPrice : 0) + decorPrice + bespokePrice + chefServiceFee;
    
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
    var inp = document.getElementById('bd');
    var now = new Date(); 
    now.setHours(now.getHours() + 2);
    
    if(inp) {
        flatpickr(inp, {
            locale: "vn",
            enableTime: true,
            dateFormat: "Y-m-d\\TH:i",
            altInput: true,
            altFormat: "d/m/Y h:i K", // Hiển thị AM/PM chuẩn xác
            minDate: now,
            time_24hr: false,
            minuteIncrement: 15,
            disableMobile: "true", // Bắt buộc dùng flatpickr trên điện thoại
            onChange: function() { us(); }
        });
    }
    us();
})();
</script>

<?php include 'views/client/layouts/footer.php'; ?>

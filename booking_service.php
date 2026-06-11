<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'config/inventory_helper.php';
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
    SELECT f.*, t.name as theme_name, c.name as cat_name,
           (SELECT GROUP_CONCAT(CONCAT(tp.id, '::', tp.name, '::', tp.price, '::', IFNULL(tp.image, ''), '::', tp.selection_type, '::', IFNULL(tp.topping_group, ''), '::', IFNULL(tp.description, '')) SEPARATOR '|')
            FROM food_toppings ft
            JOIN toppings tp ON ft.topping_id = tp.id
            WHERE ft.food_id = f.id AND tp.status = 1) as list_toppings
    FROM foods f 
    LEFT JOIN themes t ON f.theme_id = t.id 
    LEFT JOIN categories c ON f.category_id = c.id
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
$user_allergies = [];
if ($user_info) {
    if ($user_info['flavor_profile']) $user_flavor = array_map('trim', explode(',', mb_strtolower($user_info['flavor_profile'], 'UTF-8')));
    if ($user_info['fav_ingredients']) $user_fav = array_map('trim', explode(',', mb_strtolower($user_info['fav_ingredients'], 'UTF-8')));
    if (!empty($user_info['allergies'])) $user_allergies = array_map('trim', explode(',', mb_strtolower($user_info['allergies'], 'UTF-8')));
}

function hasAllergenBooking($food, $user_allergies) {
    if (empty($user_allergies)) return false;
    $allergens = array_map('trim', explode(',', mb_strtolower($food['allergens'] ?? '', 'UTF-8')));
    foreach ($user_allergies as $ua) {
        if (in_array($ua, $allergens)) return true;
    }
    return false;
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
    border: 1px solid var(--glass-border); background: #fff; border-radius: 0; padding: 20px; cursor: pointer; transition: all 0.3s ease; position: relative; margin-bottom: 15px; overflow: hidden;
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

.btn-outline-lux {
    background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted); padding: 10px 25px; border-radius: 0; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; font-size: 13px; cursor: pointer; transition: all 0.3s ease;
}
.btn-outline-lux:hover { background: var(--text-muted); color: #fff; }

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
                        <input type="datetime-local" name="booking_date" id="bd" class="input-lux" placeholder=" " required onchange="us()">
                        <label class="label-lux">Ngày & Giờ <?= $type==='chef' ? 'phục vụ' : 'đến' ?> *</label>
                    </div>
                    <div class="input-group-lux">
                        <div class="guest-lux">
                            <button type="button" class="btn-qty" onclick="cg(-1)">-</button>
                            <input type="number" name="guests" id="gi" value="2" min="1" max="50" readonly onchange="us()">
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
                        <p id="addon-foods-label" style="font-size:12px; color:var(--text-muted); margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Thực Đơn Chọn Trước (Add-on)</p>
                        <div style="max-height: 400px; overflow-y: auto; padding-right:10px;">
                            <?php foreach($grouped_foods as $t_name => $t_foods): ?>
                                <div class="addon-group-block" style="margin-bottom: 20px;">
                                    <h5 style="color:var(--gold); font-size:12px; text-transform:uppercase; border-bottom:1px dashed rgba(212,176,106,0.3); padding-bottom:5px; margin-bottom:10px;">MÓN LẺ THUỘC CHỦ ĐỀ: <?= htmlspecialchars($t_name) ?></h5>
                                    <?php foreach($t_foods as $fd): 
                                         $stock = getFoodInventory($db, $fd['id']);
                                         $is_out_of_stock = ($stock <= 0);
                                     ?>
                                     <div class="menu-item-lux flex-column align-items-stretch" id="mr<?= $fd['id'] ?>" 
                                          data-name="<?= htmlspecialchars($fd['name']) ?>"
                                          data-price="<?= (float)$fd['price'] ?>"
                                          data-img="public/assets/img/menu/<?= htmlspecialchars($fd['image'] ?: 'default.jpg') ?>"
                                          data-desc="<?= htmlspecialchars($fd['description'] ?? '') ?>"
                                          data-ingredients="<?= htmlspecialchars(($fd['ingredients'] ?? '') . (!empty($fd['recipe_ingredients']) ? ', ' . $fd['recipe_ingredients'] : '')) ?>"
                                          data-toppings-raw="<?= htmlspecialchars($fd['list_toppings'] ?? '') ?>"
                                          data-category="<?= htmlspecialchars($fd['cat_name'] ?? 'Món lẻ') ?>"
                                          data-max-toppings="<?= (int)($fd['max_toppings'] ?? 4) ?>"
                                          data-stock="<?= $stock ?>"
                                          style="border-bottom: 1px solid var(--glass-border); padding: 15px 0; display: flex;">
                                         <div class="d-flex justify-content-between align-items-center w-100">
                                             <div style="display:flex; align-items:center; gap:15px; flex-grow: 1;">
                                                 <input type="checkbox" class="menu-checkbox" name="menu_items[]" value="<?= $fd['id'] ?>" <?= $is_out_of_stock ? 'disabled' : '' ?> onchange="togMrow(this,<?= $fd['id'] ?>,<?= (float)$fd['price'] ?>)">
                                                 <div class="food-details-clickable" onclick="<?= $is_out_of_stock ? 'void(0)' : 'openFoodOptionModal(' . $fd['id'] . ')' ?>" style="cursor:<?= $is_out_of_stock ? 'not-allowed' : 'pointer' ?>; flex-grow: 1;">
                                                     <div style="font-size:14px; display:flex; align-items:center; font-weight: 500;">
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
                                                         <?php if(hasAllergenBooking($fd, $user_allergies)): ?>
                                                             <span class="badge bg-danger text-white ms-2" style="font-size: 10px;"><i class="fas fa-exclamation-triangle me-1"></i> Dị ứng</span>
                                                         <?php endif; ?>
                                                         <?php if ($is_out_of_stock): ?>
                                                             <span class="badge bg-secondary text-white ms-2" style="font-size: 10px;"><i class="fas fa-ban me-1"></i> Hết món</span>
                                                         <?php endif; ?>
                                                     </div>
                                                     <div style="font-size:12px; color:var(--gold); margin-top: 2px;">
                                                         <?= number_format($fd['price']) ?> đ
                                                         <span style="font-size:11px; color:var(--text-muted); margin-left:10px;">
                                                             (<?= $is_out_of_stock ? '<span class="text-danger">Hết hàng</span>' : '<span class="text-success">Còn hàng</span>' ?>)
                                                         </span>
                                                     </div>
                                                 </div>
                                             </div>
                                              <input type="hidden" name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>" value="1">
                                         </div>
                                         <input type="hidden" name="food_notes[<?= $fd['id'] ?>]" id="fn<?= $fd['id'] ?>" value="">
                                         <div class="opt-note-display mt-2" style="font-size:11px; color:var(--gold); font-style:italic; display:none; padding-left:33px;"></div>
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
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Healthy" class="form-check-input me-2" style="cursor:pointer"> Healthy</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Vegetarian" class="form-check-input me-2" style="cursor:pointer"> Vegetarian</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Vegan" class="form-check-input me-2" style="cursor:pointer"> Vegan</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Keto" class="form-check-input me-2" style="cursor:pointer"> Keto</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Không yêu cầu" class="form-check-input me-2" style="cursor:pointer" checked> Không yêu cầu</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-bold mb-3" style="color: var(--gold); font-family: 'Cormorant Garamond', serif; font-size: 1.1rem;"><i class="fas fa-glass-cheers me-1"></i> Mục đích</label>
                        <div class="d-flex flex-column gap-2" style="font-size: 0.95rem;">
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Hẹn hò" class="form-check-input me-2" style="cursor:pointer"> Hẹn hò</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Sinh nhật" class="form-check-input me-2" style="cursor:pointer"> Sinh nhật</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Kỷ niệm" class="form-check-input me-2" style="cursor:pointer"> Kỷ niệm</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Tiếp khách" class="form-check-input me-2" style="cursor:pointer"> Tiếp khách</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="purpose" value="Cầu hôn" class="form-check-input me-2" style="cursor:pointer"> Cầu hôn</label>
                        </div>
                    </div>
                </div>

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
            
            <div id="selected-foods-list" style="margin-top: 15px; border-top: 1px dashed rgba(255,255,255,0.15); padding-top: 15px; display: none;">
                <div style="font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-bottom: 10px;">Chi tiết món đã chọn:</div>
                <div id="selected-foods-container" style="display: flex; flex-direction: column; gap: 10px; max-height: 250px; overflow-y: auto; padding-right: 5px;">
                    <!-- Dynamic selected food items will go here -->
                </div>
            </div>
            
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

<!-- MODAL CHI TIẾT & TÙY CHỌN MÓN ĂN -->
<div class="modal fade" id="foodOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 750px;">
    <div class="modal-content" style="background:#fff; border:1px solid var(--forest); border-radius:0; position:relative; overflow:hidden;">
      <div class="modal-body p-0">
        <button type="button" class="btn-close" onclick="cancelFoodOption()" style="position:absolute; top:15px; right:15px; z-index:10; background:none; border:none; font-size:20px; color:var(--text-muted); line-height:1;">✕</button>
        <div class="row g-0">
          <div class="col-md-5" style="position:relative; min-height:350px; background:#f6f2e9;">
            <img id="foodOptImg" src="" style="width:100%; height:100%; object-fit:cover; position:absolute; inset:0;" alt="Food Image" onerror="this.onerror=null; this.src='https://placehold.co/800x600/F6F2E9/4F5B3A?text=No+Image'">
          </div>
          <div class="col-md-7" style="padding: 40px; display:flex; flex-direction:column; justify-content:space-between; background:#fff; max-height: 85vh; overflow-y: auto;">
            <div>
              <div style="font-size:10px; font-family:var(--font-sans); letter-spacing:2px; text-transform:uppercase; color:var(--gold); margin-bottom:5px;">Chi tiết món ăn</div>
              <h4 id="foodOptName" style="color:var(--forest); font-family:'Cormorant Garamond', serif; font-size:1.8rem; font-weight:600; margin-bottom:5px;">Tên món</h4>
              <div id="foodOptCategory" style="font-size:12px; color:var(--text-muted); margin-bottom:5px;">Danh mục: ...</div>
              <div id="foodOptStatus" style="font-size:12px; margin-bottom:10px; font-weight:bold;">Trạng thái: ...</div>
              <div id="foodOptPrice" style="font-size:1.1rem; color:var(--gold); font-weight:600; margin-bottom:15px;">Giá gốc: 0 đ</div>
              <p id="foodOptDesc" style="font-size:13px; color:var(--text-muted); font-style:italic; line-height:1.6; margin-bottom:20px;"></p>
              
              <!-- Ingredients List -->
              <div id="ingredientsWrap" style="margin-bottom:20px; display:none;">
                <label style="color:var(--forest); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:8px;">Thành phần có sẵn:</label>
                <div id="ingredientsList" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
              </div>
              
              <!-- Doneness Wrap -->
              <div id="donenessWrap" style="display:none; margin-bottom:20px;">
                <label style="color:var(--forest); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:8px;">Độ chín thịt (Meat Doneness):</label>
                <select id="foodOptDoneness" class="form-select form-select-sm" style="border:1px solid var(--glass-border); border-radius:0; font-size:13px; background:#fff;" onchange="updateModalPrice()">
                    <option value="">-- Mặc định --</option>
                    <option value="Rare (Tái)">Rare (Tái)</option>
                    <option value="Medium Rare (Tái vừa)">Medium Rare (Tái vừa)</option>
                    <option value="Medium (Chín vừa)">Medium (Chín vừa)</option>
                    <option value="Medium Well (Chín tới)">Medium Well (Chín tới)</option>
                    <option value="Well Done (Chín kỹ)">Well Done (Chín kỹ)</option>
                </select>
              </div>
              
              <!-- Toppings Wrap -->
              <div id="toppingsWrap" style="margin-bottom:20px;">
                <label style="color:var(--forest); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:8px;">Topping tùy chọn:</label>
                <div id="toppingsList" style="display:flex; flex-direction:column; gap:8px;"></div>
                <div id="noToppingsMsg" style="font-size:12px; color:var(--text-muted); font-style:italic; display:none;">Món này không có topping bổ sung</div>
                <div id="maxToppingsLimitMsg" style="font-size:11px; color:#c0392b; margin-top:5px; display:none; font-weight:600;"></div>
              </div>
              
              <!-- Note Wrap -->
              <div style="margin-bottom:20px;">
                <label style="color:var(--forest); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:8px;">Ghi chú cho nhà bếp:</label>
                <textarea id="foodOptNote" class="form-control" rows="2" maxlength="255" placeholder="Ví dụ: ít cay, không hành, thêm sốt riêng..." style="border:1px solid var(--glass-border); border-radius:0; font-size:13px; background:#fff;"></textarea>
              </div>
            </div>
            
            <div style="border-top:1px dashed var(--glass-border); padding-top:20px; display:flex; justify-content:space-between; align-items:center;">
              <div>
                <div style="font-size:11px; color:var(--text-muted);">Số lượng:</div>
                <div style="display:flex; align-items:center; border:1px solid var(--glass-border); margin-top:5px; width:fit-content; background:#fff;">
                  <button type="button" class="btn btn-sm" onclick="adjustModalQty(-1)" style="padding:4px 12px; border:none; background:none; font-weight:bold;">-</button>
                  <input type="number" id="foodOptQty" value="1" min="1" style="width:40px; text-align:center; border:none; background:none; font-weight:600; font-size:13px;" readonly>
                  <button type="button" class="btn btn-sm" onclick="adjustModalQty(1)" style="padding:4px 12px; border:none; background:none; font-weight:bold;">+</button>
                </div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:11px; color:var(--text-muted);">Tổng tạm tính:</div>
                <div id="foodOptTotalDisplay" style="font-size:1.4rem; color:var(--gold); font-weight:600; margin-bottom:8px;">0 đ</div>
                <button type="button" class="btn-reserve-solid" onclick="saveFoodOption()" style="padding:10px 20px; font-size:11px; display:inline-block; border-radius:0; width:fit-content;">Thêm vào đơn</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- KẾT THÚC MODAL CHI TIẾT & TÙY CHỌN MÓN ĂN -->

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
            <div class="modal-body cinematic-map" style="padding: 0; background: var(--bg-cream);">
                <?php
                    $is_admin = false; // Client view
                    include 'views/shared/floor_plan.php';
                ?>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1); display:flex; justify-content:flex-end; align-items:center;">
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

<?php
// Build combo->foods map for JS filtering
$combo_food_map = [];
$cfRows = $db->query("SELECT combo_id, food_id FROM combo_items ORDER BY combo_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cfRows as $row) {
    $combo_food_map[$row['combo_id']][] = (int)$row['food_id'];
}
// Build combo name map
$combo_name_map = [];
foreach ($combos_raw as $cb) {
    $combo_name_map[$cb['id']] = $cb['name'];
}
?>
const comboFoods = <?= json_encode($combo_food_map, JSON_UNESCAPED_UNICODE) ?>;
const comboNames = <?= json_encode($combo_name_map, JSON_UNESCAPED_UNICODE) ?>;
let comboId = 0, selId=0, selCode='', selPrice=0, selCat='', selComboPr=0, menuPr={};

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
    var addonSection  = document.getElementById('addon-foods-section');
    var detailInput   = document.getElementById('creq_detail');

    if (id === -1) {
        if (bespokeFields) bespokeFields.style.display = 'block';
        if (addonSection)  addonSection.style.display  = 'none';
        if (detailInput)   detailInput.setAttribute('required','required');
    } else {
        if (bespokeFields) bespokeFields.style.display = 'none';
        if (addonSection)  addonSection.style.display  = 'block';
        if (detailInput)   detailInput.removeAttribute('required');
    }

    filterFoodsByCombo(id);
    updateChefReq();
    us();
}

function filterFoodsByCombo(id) {
    var allRows     = document.querySelectorAll('.menu-item-lux[id^="mr"]');
    var groupBlocks = document.querySelectorAll('.addon-group-block');
    var label       = document.getElementById('addon-foods-label');

    if (id === 0) {
        // Gọi Món Tự Do → hiện tất cả
        allRows.forEach(function(r){ r.style.display = 'flex'; });
        groupBlocks.forEach(function(g){ g.style.display = 'block'; });
        if (label) label.textContent = 'Thực Đơn Chọn Trước (Add-on)';
        return;
    }

    var allowed = comboFoods[id] || [];

    // Ẩn/hiện từng món
    allRows.forEach(function(row){
        var fid = parseInt(row.id.replace('mr',''));
        if (allowed.indexOf(fid) !== -1) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
            // Bỏ tích nếu món đang được chọn nhưng không thuộc set
            var cb = row.querySelector('.menu-checkbox');
            if (cb && cb.checked) { cb.checked = false; cb.dispatchEvent(new Event('change')); }
        }
    });

    // Ẩn/hiện block nhóm dựa vào có món nào visible không
    groupBlocks.forEach(function(g){
        var hasVisible = Array.from(g.querySelectorAll('.menu-item-lux[id^="mr"]'))
                              .some(function(r){ return r.style.display !== 'none'; });
        g.style.display = hasVisible ? 'block' : 'none';
    });

    // Cập nhật tiêu đề
    if (label) {
        var name = comboNames[id] || ('Set Menu #' + id);
        label.innerHTML = '<i class="fas fa-utensils me-1"></i> Món trong set "<strong>' + name + '</strong>" — chọn thêm tùy thích';
    }
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
function togMrow(cb, id, pr) {
    var row = document.getElementById('mr'+id);
    if(cb.checked){
        openFoodOptionModal(id);
    } else {
        row.classList.remove('checked');
        delete menuPr[id];
        
        // Reset inputs
        document.getElementById('fn'+id).value = '';
        var noteEl = document.getElementById('note-'+id);
        if(noteEl) noteEl.value = '';
        var doneEl = document.getElementById('doneness-'+id);
        if(doneEl) doneEl.value = '';
        row.querySelectorAll('.topping-inline-cb').forEach(function(c){ c.checked = false; });
        row.querySelectorAll('input[name="food_toppings['+id+'][]"]').forEach(function(el){el.remove();});
        var disp = row.querySelector('.opt-note-display');
        if(disp) disp.style.display = 'none';
        
        us();
    }
}

function validateQty(input, id) {
    var max = parseInt(input.getAttribute('max') || 99);
    var val = parseInt(input.value || 1);
    if (val > max) {
        alert("Số lượng vượt quá tồn kho khả dụng (" + max + ").");
        input.value = max;
    }
    if (val < 1) {
        input.value = 1;
    }
}

function openFoodOptionModal(id) {
    var row = document.getElementById('mr' + id);
    if (!row) return;
    
    var name = row.getAttribute('data-name');
    var price = parseFloat(row.getAttribute('data-price') || 0);
    var img = row.getAttribute('data-img');
    var desc = row.getAttribute('data-desc');
    var ingredients = row.getAttribute('data-ingredients');
    var toppingsRaw = row.getAttribute('data-toppings-raw');
    var category = row.getAttribute('data-category');
    var maxToppings = parseInt(row.getAttribute('data-max-toppings') || 4);
    var stock = parseInt(row.getAttribute('data-stock') || 99);
    
    // Set modal content
    document.getElementById('foodOptName').textContent = name;
    document.getElementById('foodOptCategory').textContent = "Danh mục: " + category;
    
    var statusEl = document.getElementById('foodOptStatus');
    if (stock > 0) {
        statusEl.innerHTML = 'Trạng thái: <span style="color:#28a745;"><i class="fas fa-check-circle"></i> Còn món</span>';
    } else {
        statusEl.innerHTML = 'Trạng thái: <span style="color:#dc3545;"><i class="fas fa-times-circle"></i> Hết món</span>';
    }
    
    document.getElementById('foodOptPrice').textContent = "Giá gốc: " + price.toLocaleString('vi-VN') + " đ";
    document.getElementById('foodOptDesc').textContent = desc || "Món ăn thượng hạng được chuẩn bị bởi đầu bếp Restaurantly.";
    document.getElementById('foodOptImg').src = img;
    
    // Ingredients
    var ingList = document.getElementById('ingredientsList');
    ingList.innerHTML = '';
    if (ingredients && ingredients.trim() !== '') {
        var ingArray = ingredients.split(',');
        var hasIng = false;
        ingArray.forEach(function(ing) {
            var trimIng = ing.trim().split(',')[0].trim();
            if (trimIng !== '') {
                hasIng = true;
                var span = document.createElement('span');
                span.style.cssText = "background:#f6f2e9; color:var(--forest); padding:4px 10px; font-size:12px; font-weight:500; border-radius:0; border: 1px solid var(--glass-border);";
                span.textContent = trimIng;
                ingList.appendChild(span);
            }
        });
        if (hasIng) {
            document.getElementById('ingredientsWrap').style.display = 'block';
        } else {
            document.getElementById('ingredientsWrap').style.display = 'none';
        }
    } else {
        document.getElementById('ingredientsWrap').style.display = 'none';
        var ingWrap = document.getElementById('ingredientsWrap');
        ingWrap.style.display = 'block';
        var span = document.createElement('span');
        span.style.cssText = "font-size:12px; color:var(--text-muted); font-style:italic;";
        span.textContent = "Thông tin thành phần đang cập nhật.";
        ingList.appendChild(span);
    }
    
    // Toppings
    var topList = document.getElementById('toppingsList');
    topList.innerHTML = '';
    var noTops = document.getElementById('noToppingsMsg');
    var limitMsg = document.getElementById('maxToppingsLimitMsg');
    if (maxToppings > 0) {
        limitMsg.textContent = "Chỉ được chọn tối đa " + maxToppings + " topping.";
        limitMsg.style.display = 'block';
    } else {
        limitMsg.style.display = 'none';
    }
    
    var selectedToppings = [];
    row.querySelectorAll('input[name="food_toppings['+id+'][]"]').forEach(function(input) {
        selectedToppings.push(input.value);
    });
    
    var hasToppings = false;
    if (toppingsRaw && toppingsRaw.trim() !== '') {
        var tops = toppingsRaw.split('|');
        
        var grouped = {};
        tops.forEach(function(tp) {
            var parts = tp.split('::');
            if (parts.length >= 3) {
                var tpId = parts[0];
                var tpName = parts[1];
                var tpPrice = parseFloat(parts[2]);
                var tpImg = parts.length > 3 ? parts[3] : '';
                var tpType = parts.length > 4 ? parts[4] : 'checkbox';
                var tpGroup = parts.length > 5 ? parts[5] : 'Topping';
                var tpDesc = parts.length > 6 ? parts[6] : '';
                
                if (tpGroup === 'Độ chín') {
                    tpGroup = 'Yêu cầu đặc biệt (Độ chín)';
                }
                
                if (!grouped[tpGroup]) grouped[tpGroup] = [];
                grouped[tpGroup].push({id: tpId, name: tpName, price: tpPrice, img: tpImg, type: tpType, desc: tpDesc});
            }
        });
        
        // Sort grouped keys so 'Yêu cầu đặc biệt' appears first
        var groupKeys = Object.keys(grouped).sort(function(a, b) {
            if (a.includes('Yêu cầu đặc biệt')) return -1;
            if (b.includes('Yêu cầu đặc biệt')) return 1;
            return a.localeCompare(b);
        });

        for (var i = 0; i < groupKeys.length; i++) {
            var groupName = groupKeys[i];
            hasToppings = true;
            var groupHeader = document.createElement('div');
            var isSpecial = groupName.includes('Yêu cầu đặc biệt');
            groupHeader.style.cssText = "font-weight:600; font-size:12px; border-bottom: 1px dashed var(--glass-border); padding-bottom:3px; margin-top:10px; margin-bottom:5px;";
            if (isSpecial) {
                groupHeader.style.color = '#dc3545';
                groupHeader.innerHTML = '<i class="fas fa-star me-1"></i>' + groupName;
            } else {
                groupHeader.style.color = 'var(--forest)';
                groupHeader.textContent = groupName;
            }
            topList.appendChild(groupHeader);
            
            grouped[groupName].forEach(function(tp) {
                var wrapper = document.createElement('div');
                wrapper.style.cssText = 'margin-bottom: 6px;';
                
                var label = document.createElement('label');
                label.style.cssText = 'font-size:13px; display:flex; align-items:center; gap:10px; cursor:pointer; color:var(--text-main); width:100%; justify-content:space-between;';
                
                var isChecked = selectedToppings.includes(tp.id) ? 'checked' : '';
                var inputHtml = '';
                if (tp.type === 'radio') {
                    inputHtml = '<input type="radio" class="modal-topping-cb" name="modal_toppings_group_' + groupName.replace(/\s+/g, '_') + '" value="' + tp.id + '" data-name="' + tp.name + '" data-price="' + tp.price + '" data-group="' + groupName + '" data-type="radio" ' + isChecked + ' onchange="updateModalPrice()">';
                } else {
                    inputHtml = '<input type="checkbox" class="modal-topping-cb" value="' + tp.id + '" data-name="' + tp.name + '" data-price="' + tp.price + '" data-group="' + groupName + '" data-type="checkbox" ' + isChecked + ' onchange="validateToppingLimit(this, ' + maxToppings + ')">';
                }
                
                var imgHtml = tp.img ? '<img src="public/assets/img/toppings/' + tp.img + '" style="width:25px; height:25px; object-fit:cover; border-radius:4px;" onerror="this.style.display=\'none\'">' : '';
                
                label.innerHTML = '<div style="display:flex; align-items:center; gap:8px;">' + inputHtml + imgHtml + '<span>' + tp.name + '</span></div><strong style="color:var(--gold);">+' + tp.price.toLocaleString('vi-VN') + 'đ</strong>';
                topList.appendChild(label);
            });
        }
    }
    
    if (hasToppings) {
        noTops.style.display = 'none';
        topList.style.display = 'flex';
    } else {
        noTops.style.display = 'none';
        topList.style.display = 'block';
        var noMsg = document.createElement('div');
        noMsg.style.cssText = "font-size:12px; color:var(--text-muted); font-style:italic;";
        noMsg.textContent = "Món ăn này không hỗ trợ topping bổ sung.";
        topList.appendChild(noMsg);
    }
    
    // Note
    var noteEl = document.getElementById('fn' + id);
    var rawNote = noteEl ? noteEl.value : '';
    var cleanNote = rawNote;
    if (rawNote.includes('] ')) {
        cleanNote = rawNote.split('] ').slice(1).join('] ');
    } else if (rawNote.startsWith('[') && rawNote.endsWith(']')) {
        cleanNote = '';
    }
    document.getElementById('foodOptNote').value = cleanNote;
    
    // Doneness
    var donenessWrap = document.getElementById('donenessWrap');
    var donenessEl = document.getElementById('foodOptDoneness');
    var foodNameLower = name.toLowerCase();
    if (foodNameLower.includes('bò') || foodNameLower.includes('steak') || foodNameLower.includes('beef') || foodNameLower.includes('cừu') || foodNameLower.includes('lamb') || foodNameLower.includes('vịt') || foodNameLower.includes('duck')) {
        donenessWrap.style.display = 'block';
        var match = rawNote.match(/Độ chín:\s*([^.]+)\./);
        donenessEl.value = match ? match[1] : '';
    } else {
        donenessWrap.style.display = 'none';
        if (donenessEl) donenessEl.value = '';
    }
    
    // Quantity
    var currentQty = parseInt(document.getElementById('q' + id).value || 1);
    document.getElementById('foodOptQty').value = currentQty;
    
    var modalEl = document.getElementById('foodOptionModal');
    modalEl.dataset.currentFoodId = id;
    modalEl.dataset.basePrice = price;
    modalEl.dataset.maxToppings = maxToppings;
    modalEl.dataset.stock = stock;
    
    updateModalPrice();
    
    var modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function validateToppingLimit(checkbox, limit) {
    if (limit <= 0) {
        updateModalPrice();
        return;
    }
    
    var checkedCheckboxes = document.querySelectorAll('.modal-topping-cb[data-type="checkbox"]:checked');
    if (checkedCheckboxes.length > limit) {
        alert("Bạn chỉ có thể chọn tối đa " + limit + " topping thêm.");
        checkbox.checked = false;
    }
    updateModalPrice();
}

function updateModalPrice() {
    var modalEl = document.getElementById('foodOptionModal');
    var basePrice = parseFloat(modalEl.dataset.basePrice || 0);
    var qty = parseInt(document.getElementById('foodOptQty').value || 1);
    var addedPrice = 0;
    
    document.querySelectorAll('.modal-topping-cb:checked').forEach(function(cb) {
        addedPrice += parseFloat(cb.getAttribute('data-price') || 0);
    });
    
    var singleTotal = basePrice + addedPrice;
    var grandTotal = singleTotal * qty;
    
    document.getElementById('foodOptTotalDisplay').textContent = grandTotal.toLocaleString('vi-VN') + ' đ';
}

function adjustModalQty(delta) {
    var modalEl = document.getElementById('foodOptionModal');
    var stock = parseInt(modalEl.dataset.stock || 99);
    var qtyEl = document.getElementById('foodOptQty');
    var val = parseInt(qtyEl.value || 1) + delta;
    if (val < 1) val = 1;
    if (val > stock) {
        alert("Số lượng vượt quá tồn kho khả dụng (" + stock + ").");
        val = stock;
    }
    qtyEl.value = val;
    updateModalPrice();
}

function saveFoodOption() {
    var modalEl = document.getElementById('foodOptionModal');
    var id = modalEl.dataset.currentFoodId;
    var basePrice = parseFloat(modalEl.dataset.basePrice || 0);
    var stock = parseInt(modalEl.dataset.stock || 99);
    
    var qty = parseInt(document.getElementById('foodOptQty').value || 1);
    if (qty > stock) {
        alert("Số lượng vượt quá tồn kho khả dụng (" + stock + ").");
        qty = stock;
        document.getElementById('foodOptQty').value = qty;
        updateModalPrice();
        return;
    }
    
    var note = document.getElementById('foodOptNote').value.trim();
    if (note.length > 255) {
        alert("Ghi chú tối đa 255 ký tự.");
        return;
    }
    
    var donenessEl = document.getElementById('foodOptDoneness');
    var doneness = (donenessEl && donenessEl.value) ? donenessEl.value : '';
    
    var row = document.getElementById('mr' + id);
    var cb = row.querySelector('.menu-checkbox');
    
    document.getElementById('q' + id).value = qty;
    
    row.querySelectorAll('input[name="food_toppings['+id+'][]"]').forEach(function(el){el.remove();});
    
    var toppingCbs = document.querySelectorAll('.modal-topping-cb:checked');
    var addedPrice = 0;
    var tpNotes = [];
    
    toppingCbs.forEach(function(mCb) {
        var tId = mCb.value;
        var tName = mCb.getAttribute('data-name');
        var tPrice = parseFloat(mCb.getAttribute('data-price'));
        
        tpNotes.push(tName);
        addedPrice += tPrice;
        
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'food_toppings['+id+'][]';
        hidden.value = tId;
        row.appendChild(hidden);
    });
    
    var finalNote = '';
    if (doneness) finalNote += "Độ chín: " + doneness + ". ";
    if (tpNotes.length > 0) finalNote += "[Topping: " + tpNotes.join(', ') + "] ";
    if (note) finalNote += note;
    
    document.getElementById('fn' + id).value = finalNote.trim();
    
    var disp = row.querySelector('.opt-note-display');
    if (disp) {
        if (finalNote.trim() !== '') {
            disp.style.display = 'block';
            disp.innerHTML = '<strong>📝 Ghi chú:</strong> ' + finalNote.trim();
        } else {
            disp.style.display = 'none';
        }
    }
    
    cb.checked = true;
    row.classList.add('checked');
    
    menuPr[id] = basePrice + addedPrice;
    
    bootstrap.Modal.getInstance(modalEl).hide();
    us();
}

function cancelFoodOption() {
    var modalEl = document.getElementById('foodOptionModal');
    var id = modalEl.dataset.currentFoodId;
    
    var row = document.getElementById('mr' + id);
    var cb = row.querySelector('.menu-checkbox');
    
    if (!row.classList.contains('checked')) {
        cb.checked = false;
    }
    
    bootstrap.Modal.getInstance(modalEl).hide();
}

/* SỰ KIỆN CHỌN BÀN & AJAX KHẢ DỤNG ĐỘNG */
    document.querySelectorAll('.seat-lux').forEach(function(s){
        s.addEventListener('click',function(){
            if(!s.classList.contains('available')) return;
            document.querySelectorAll('.seat-lux').forEach(function(x){x.classList.remove('selected');});
            s.classList.add('selected');
            selId=s.dataset.id; selCode=s.dataset.code; selPrice=parseFloat(s.dataset.price||0); selCat=s.dataset.cat||'';
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
        var listWrap = document.getElementById('selected-foods-list');
        if (listWrap) listWrap.style.display = 'none';
    } else {
        var mt=0;
        for(var id in menuPr){
            var qe=document.getElementById('q'+id);
            mt+=menuPr[id]*(qe?parseInt(qe.value)||1:1);
        }
        food=selComboPr>0?selComboPr:mt;
        document.getElementById('sm').textContent=food.toLocaleString('vi-VN')+' đ';
        
        // Cập nhật chi tiết danh sách món đã chọn ở sidebar
        var listWrap = document.getElementById('selected-foods-list');
        var listContainer = document.getElementById('selected-foods-container');
        if (listWrap && listContainer) {
            listContainer.innerHTML = '';
            var hasSelectedFoods = false;
            
            for (var id in menuPr) {
                var row = document.getElementById('mr' + id);
                if (!row) continue;
                
                var cb = row.querySelector('.menu-checkbox');
                if (!cb || !cb.checked) continue;
                
                hasSelectedFoods = true;
                
                var name = row.getAttribute('data-name');
                var qty = parseInt(document.getElementById('q' + id).value || 1);
                var itemPrice = menuPr[id];
                var totalPrice = itemPrice * qty;
                var note = document.getElementById('fn' + id).value;
                
                var itemDiv = document.createElement('div');
                itemDiv.style.cssText = "background: rgba(255,255,255,0.05); padding: 10px; border-left: 3px solid var(--gold); font-size: 12px; margin-bottom: 5px;";
                
                var titleDiv = document.createElement('div');
                titleDiv.style.cssText = "display: flex; justify-content: space-between; font-weight: 600; color: #fff;";
                titleDiv.innerHTML = '<span>' + name + ' x' + qty + '</span><span>' + totalPrice.toLocaleString('vi-VN') + ' đ</span>';
                itemDiv.appendChild(titleDiv);
                
                if (note && note.trim() !== '') {
                    var noteDiv = document.createElement('div');
                    noteDiv.style.cssText = "color: rgba(255,255,255,0.7); font-size: 11px; margin-top: 4px; font-style: italic; word-break: break-word;";
                    noteDiv.textContent = note;
                    itemDiv.appendChild(noteDiv);
                }
                
                var actionDiv = document.createElement('div');
                actionDiv.style.cssText = "display: flex; gap: 10px; margin-top: 8px; justify-content: flex-end;";
                
                var editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.style.cssText = "background: none; border: none; color: var(--gold); font-size: 11px; cursor: pointer; padding: 0;";
                editBtn.innerHTML = '<i class="fas fa-edit me-1"></i>Sửa';
                editBtn.onclick = (function(foodId) {
                    return function() { openFoodOptionModal(foodId); };
                })(id);
                
                var deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.style.cssText = "background: none; border: none; color: #ff6b6b; font-size: 11px; cursor: pointer; padding: 0;";
                deleteBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Xóa';
                deleteBtn.onclick = (function(foodId) {
                    return function() {
                        var foodRow = document.getElementById('mr' + foodId);
                        var foodCb = foodRow.querySelector('.menu-checkbox');
                        if (foodCb) {
                            foodCb.checked = false;
                            togMrow(foodCb, foodId, parseFloat(foodRow.getAttribute('data-price') || 0));
                        }
                    };
                })(id);
                
                actionDiv.appendChild(editBtn);
                actionDiv.appendChild(deleteBtn);
                itemDiv.appendChild(actionDiv);
                
                listContainer.appendChild(itemDiv);
            }
            
            if (hasSelectedFoods) {
                listWrap.style.display = 'block';
            } else {
                listWrap.style.display = 'none';
            }
        }
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
    var inp=document.getElementById('bd');
    var now=new Date(); now.setHours(now.getHours()+2);
    if(inp) inp.min=now.toISOString().slice(0,16);
    us();
})();
</script>

<?php include 'views/client/layouts/footer.php'; ?>

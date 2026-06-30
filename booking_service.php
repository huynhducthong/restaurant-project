<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'config/inventory_helper.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php'); exit;
}
$db  = (new Database())->getConnection();

// Lấy phần thưởng Cột Mốc (Milestone) giảm giá tự động
$ms_discount_percent = 0;
$ms_reward_title = '';
if (isset($_SESSION['user_id'])) {
    $stmt_ms = $db->prepare("
        SELECT m.discount_percent, m.reward_title
        FROM user_milestones um
        JOIN milestones m ON um.milestone_id = m.id
        WHERE um.user_id = ? AND um.is_redeemed = 0 AND m.discount_percent > 0
        ORDER BY m.discount_percent DESC
        LIMIT 1
    ");
    $stmt_ms->execute([$_SESSION['user_id']]);
    $unredeemed = $stmt_ms->fetch(PDO::FETCH_ASSOC);
    if ($unredeemed) {
        $ms_discount_percent = $unredeemed['discount_percent'];
        $ms_reward_title = $unredeemed['reward_title'];
    }
}

// Lấy Giờ mở cửa từ Cài đặt chung
$setting_stmt = $db->query("SELECT key_value FROM settings WHERE key_name = 'open_time'");
$open_time_setting = $setting_stmt->fetchColumn() ?: '09:00 AM - 11:00 PM';
$time_parts = explode('-', $open_time_setting);
$restaurant_start_time = count($time_parts) == 2 ? date('H:i', strtotime(trim($time_parts[0]))) : '09:00';
$end_str = count($time_parts) == 2 ? trim($time_parts[1]) : '';
$restaurant_end_time = $end_str ? date('H:i', strtotime($end_str)) : '23:00';
if (stripos($end_str, '12:00 PM') !== false || stripos($end_str, '12:00 AM') !== false) {
    $restaurant_end_time = '23:59';
}

$type = $_GET['type'] ?? 'table';
if ($type === 'birthday') $type = 'table';
$chef_id = isset($_GET['chef_id']) ? (int)$_GET['chef_id'] : 0;
$autofill_chef_msg = '';
$autofilled_chef_name = '';
if ($chef_id > 0) {
    $stmt_c = $db->prepare("SELECT name FROM chefs WHERE id = ? AND is_active = 1");
    $stmt_c->execute([$chef_id]);
    $autofilled_chef = $stmt_c->fetch(PDO::FETCH_ASSOC);
    if ($autofilled_chef) {
        $autofilled_chef_name = $autofilled_chef['name'];
        $autofill_chef_msg = "Yêu cầu Bếp trưởng " . $autofilled_chef['name'] . " phục vụ.";
    }
}

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

$active_themes = $db->query("SELECT * FROM themes WHERE is_active = 1 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$grouped_combos = [];
foreach ($active_themes as $t) {
    $grouped_combos[$t['name']] = [
        'id' => $t['id'],
        'desc' => $t['description'],
        'img' => $t['image'],
        'combos' => []
    ];
}

foreach($combos_raw as $cb) {
    $t_name = $cb['theme_name'] ? $cb['theme_name'] : 'Thực Đơn Tiêu Chuẩn';
    if(!isset($grouped_combos[$t_name])) {
        $grouped_combos[$t_name] = [
            'id' => $cb['theme_id'] ?? 0,
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
    $t_room = $db->query("SELECT * FROM restaurant_tables WHERE category='room' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

$foods_raw  = $db->query("
    SELECT f.*, t.name as theme_name, c.name as cat_name,
           (SELECT GROUP_CONCAT(i.item_name SEPARATOR ',') FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id WHERE r.food_id = f.id) as recipe_ingredients,
           (SELECT GROUP_CONCAT(CONCAT(tp.id, '::', tp.name, '::', tp.price, '::', IFNULL(tp.image, ''), '::', tp.selection_type, '::', IFNULL(tp.topping_group, ''), '::', IFNULL(tp.description, '')) SEPARATOR '|')
            FROM food_toppings ft
            JOIN toppings tp ON ft.topping_id = tp.id
            WHERE ft.food_id = f.id AND tp.status = 1) as list_toppings
    FROM foods f 
    LEFT JOIN themes t ON f.theme_id = t.id 
    LEFT JOIN categories c ON f.category_id = c.id
    WHERE f.status=1 ORDER BY t.created_at DESC, f.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$event_types = $db->query("SELECT * FROM event_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$decor_pkgs = $db->query("SELECT * FROM decor_packages ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

$bespoke_budgets = $db->query("SELECT * FROM bespoke_budgets ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$bespoke_styles = $db->query("SELECT * FROM bespoke_styles ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$bespoke_occasions = $db->query("SELECT * FROM bespoke_occasions ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

$chefs = $db->query("SELECT id, name FROM chefs WHERE is_active = 1 ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin người dùng và sổ địa chỉ nếu đã đăng nhập
$user_info = null;
$user_addresses = [];
$user_history_counts = [];
$last_booking = null;
$last_booking_date = '';
$last_booking_guests = 2;
$last_booking_table_id = '';
$last_booking_service_type = '';
$last_booking_combo_id = 0;
$last_booking_items = [];

if (isset($_SESSION['user_id'])) {
    $u_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $user_info = $u_stmt->fetch(PDO::FETCH_ASSOC);

    $a_stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
    $a_stmt->execute([$_SESSION['user_id']]);
    $user_addresses = $a_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Truy vấn thông tin đặt bàn gần nhất của khách hàng
    $stmt_last = $db->prepare("SELECT * FROM service_bookings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt_last->execute([$_SESSION['user_id']]);
    $last_booking = $stmt_last->fetch(PDO::FETCH_ASSOC);

    if ($last_booking) {
        $b_time = strtotime($last_booking['booking_date']);
        if ($b_time < time()) {
            $time_part = date('H:i', $b_time);
            $tomorrow_date = date('Y-m-d', strtotime('+1 day'));
            $last_booking_date = $tomorrow_date . 'T' . $time_part;
        } else {
            $last_booking_date = date('Y-m-d\TH:i', $b_time);
        }
        $last_booking_guests = (int)$last_booking['guests'];
        $last_booking_table_id = $last_booking['table_id'];
        $last_booking_service_type = $last_booking['service_type'];
        $last_booking_combo_id = (int)$last_booking['combo_id'];

        // Truy vấn các món ăn đã chọn trong đơn đặt bàn gần nhất này
        $stmt_details = $db->prepare("SELECT * FROM booking_details WHERE booking_id = ? AND item_type = 'food'");
        $stmt_details->execute([$last_booking['id']]);
        $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
        foreach ($details as $d) {
            $last_booking_items[] = [
                'food_id' => (int)$d['menu_id'],
                'quantity' => (int)$d['quantity'],
                'notes' => $d['notes'],
                'toppings_info' => $d['toppings_info']
            ];
        }
    }

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
    $all_food_ingredients = ($food['allergens'] ?? '') . ',' . ($food['recipe_ingredients'] ?? '') . ',' . ($food['cat_name'] ?? '') . ',' . ($food['name'] ?? '');
    $food_allergens = array_map('trim', explode(',', mb_strtolower($all_food_ingredients, 'UTF-8')));
    
    $aliases = [
        'hải sản' => ['tôm', 'cua', 'ghẹ', 'cá', 'mực', 'bạch tuộc', 'ốc', 'hàu', 'sò', 'nghêu', 'tuna', 'salmon', 'scallop'],
        'sữa' => ['bơ', 'phô mai', 'cheese', 'cream', 'sữa tươi', 'sữa đặc', 'yoghurt', 'sữa chua'],
        'đậu phộng' => ['lạc', 'peanut'],
        'gluten' => ['lúa mì', 'bột mì', 'wheat', 'bread', 'bánh mì', 'pasta', 'pizza'],
        'trứng' => ['egg', 'trứng gà', 'trứng vịt', 'trứng cút']
    ];

    foreach ($user_allergies as $ua) {
        if (empty($ua)) continue;
        
        $check_terms = [$ua];
        if (isset($aliases[$ua])) {
            $check_terms = array_merge($check_terms, $aliases[$ua]);
        }
        
        foreach($food_allergens as $fa) {
            if (empty($fa)) continue;
            foreach ($check_terms as $term) {
                if (strpos($fa, $term) !== false) return true;
            }
        }
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
    $c_name = $fd['cat_name'] ? $fd['cat_name'] : 'Khác';
    $grouped_foods[$c_name][] = $fd;
}

$category_order = [
    'khai vị' => 1,
    'món chính' => 2,
    'món ăn kèm' => 3,
    'tráng miệng' => 4,
    'đồ uống' => 5
];
uksort($grouped_foods, function($a, $b) use ($category_order) {
    $a_lower = mb_strtolower($a, 'UTF-8');
    $b_lower = mb_strtolower($b, 'UTF-8');
    $order_a = 99;
    foreach($category_order as $k => $v) { if (strpos($a_lower, $k) !== false) { $order_a = $v; break; } }
    $order_b = 99;
    foreach($category_order as $k => $v) { if (strpos($b_lower, $k) !== false) { $order_b = $v; break; } }
    if ($order_a == $order_b) return strcmp($a, $b);
    return $order_a <=> $order_b;
});

include 'views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
/* === MINIMALIST TOKENS === */
:root {
  --bg-cream: #F9F9F9;
  --forest: #A88746; /* Olive */
  --forest-light: #8B6D36;
  --accent-burgundy: #A88746;
  --accent-burgundy-glow: rgba(168, 135, 70, 0.2);
  --glass-bg: #FFFFFF;
  --glass-border: #E5E5E5;
  --text-main: #222222;
  --text-muted: #777777; /* Brightened from #666666 for better visibility */
  --ease: cubic-bezier(0.25, 1, 0.5, 1);
}

body {
    background-color: var(--bg-cream);
    color: var(--text-main);
    font-family: 'Source Sans 3', sans-serif;
}

/* === CINEMATIC HERO === */
.hero-luxury {
    position: relative; height: 85vh; min-height: 600px; display: flex; align-items: center;
    background: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat fixed; 
}
.hero-luxury::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(90deg, rgba(26, 26, 29,0.95) 0%, rgba(26, 26, 29,0.7) 50%, transparent 100%),
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
    font-family: 'Cormorant Garamond', serif; font-size: clamp(3.5rem, 6vw, 5rem); font-weight: 600; line-height: 1.1; margin-bottom: 20px; color: #ffffff;
}
.hero-sub { color: rgba(255,255,255,0.8); font-weight: 400; font-size: 1.05rem; line-height: 1.6; max-width: 550px; margin-bottom: 40px; }
.hero-btns { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
.btn-hero-primary {
    background: var(--forest); color: #fff; font-weight: 500; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
    padding: 14px 35px; border-radius: 0; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s var(--ease); border: 1px solid var(--forest);
}
.btn-hero-primary:hover { background: #FFFFFF; color: var(--forest); }
.btn-hero-secondary {
    background: transparent; border: 1px solid var(--forest); color: var(--forest); font-weight: 500; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
    padding: 14px 35px; border-radius: 0; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s var(--ease);
}
.btn-hero-secondary:hover { background: var(--forest); color: #fff; }

/* === TABS DỊCH VỤ === */
.service-selector-inline { display: flex; gap: 8px; margin-bottom: 30px; flex-wrap: wrap; }
.svc-card-inline {
    background: #FFFFFF; border: 1px solid var(--glass-border); padding: 10px 12px; border-radius: 0; color: var(--text-muted); font-size: 11px; font-weight: 500; text-transform: uppercase; text-decoration: none; transition: 0.3s; letter-spacing: 1px; flex-grow: 1; text-align: center;
}
.svc-card-inline:hover { border-color: var(--forest); color: var(--forest); }
.svc-card-inline.active { background: var(--forest); border-color: var(--forest); color: #fff; font-weight: 600; }

/* === WIZARD STEPS === */
.booking-step { display: none; animation: fadeInStep 0.4s ease; }
.booking-step.active { display: block; }
@keyframes fadeInStep { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.wizard-header { display: flex; justify-content: space-between; position: relative; margin-bottom: 30px; padding: 0 10px; }
.wizard-header::before { content: ''; position: absolute; top: 15px; left: 30px; right: 30px; height: 2px; background: var(--glass-border); z-index: 1; }
.step-indicator { position: relative; z-index: 2; text-align: center; display: flex; flex-direction: column; align-items: center; transition: 0.3s; }
.step-circle { width: 32px; height: 32px; border-radius: 50%; background: #FFFFFF; border: 2px solid var(--glass-border); color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; margin-bottom: 8px; transition: 0.3s; }
.step-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; transition: 0.3s; }

.step-indicator.active .step-circle { border-color: var(--accent-burgundy); background: var(--accent-burgundy); color: #fff; }
.step-indicator.active .step-label { color: var(--accent-burgundy); }
.step-indicator.completed .step-circle { border-color: var(--forest); background: var(--forest); color: #fff; cursor: pointer; }
.step-indicator.completed .step-label { color: var(--forest); cursor: pointer; }

.wizard-nav { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px dashed var(--glass-border); }
.btn-wizard-next { background: var(--forest); color: #fff; border: none; padding: 12px 30px; border-radius: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; cursor: pointer; transition: 0.3s; }
.btn-wizard-next:disabled { background: #dcdcdc; color: #999; cursor: not-allowed; }
.btn-wizard-next:not(:disabled):hover { background: var(--accent-burgundy); }
.btn-wizard-prev { background: transparent; color: var(--text-muted); border: 1px solid var(--glass-border); padding: 12px 30px; border-radius: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; cursor: pointer; transition: 0.3s; }
.btn-wizard-prev:hover { border-color: var(--forest); color: var(--forest); }

/* === MAIN BOOKING AREA === */
.booking-section { position: relative; max-width: 1450px; margin: -80px auto 100px; padding: 0 20px; z-index: 10; display: grid; grid-template-columns: 1fr 450px; gap: 40px; }
@media (max-width: 992px) { .booking-section { grid-template-columns: 1fr; margin-top: 0; padding-top: 30px; gap: 20px; } }

.luxury-panel { background: #FFFFFF; border: 1px solid var(--glass-border); border-radius: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
.panel-section { padding: 35px 40px; border-bottom: 1px solid var(--glass-border); }
.panel-section:last-child { border-bottom: none; }
.section-title-lux { font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; color: var(--forest); margin-bottom: 25px; display: flex; align-items: center; gap: 15px; font-weight: 600;}

/* === INPUTS === */
.row-lux { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:600px) { .row-lux { grid-template-columns: 1fr; } }
.input-group-lux { position: relative; margin-bottom: 20px; }
.input-lux {
    width: 100%; background: #FFFFFF; border: 1px solid var(--glass-border); padding: 16px 20px;
    color: var(--text-main); font-family: 'Source Sans 3', sans-serif; font-size: 14px; border-radius: 0; transition: all 0.3s ease; outline: none;
    height: 54px; box-sizing: border-box;
}
.input-lux:focus { border-color: var(--accent-burgundy); }
.input-lux::placeholder { color: transparent; }
.label-lux {
    position: absolute; top: 18px; left: 20px; color: #cccccc; font-size: 14px; pointer-events: none; transition: 0.3s ease; z-index: 2;
}
.input-lux:focus ~ .label-lux, .input-lux:not(:placeholder-shown) ~ .label-lux, select.input-lux ~ .label-lux {
    top: -8px; left: 15px; font-size: 11px; color: #D4AF37; /* Brighter gold for labels */ background: #FFFFFF; padding: 0 5px; letter-spacing: 1px; text-transform: uppercase; z-index: 2; font-weight: 600;
}
select.input-lux {
    appearance: none; -webkit-appearance: none; -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23C9A66B'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 20px center; background-size: 18px; padding-right: 45px; cursor: pointer;
}
.input-lux option { background: #FFFFFF; color: var(--text-main); }

/* Guest Counter */
.guest-lux { display: flex; align-items: center; background: #FFFFFF; border: 1px solid var(--glass-border); border-radius: 0; padding: 0; height: 54px; box-sizing: border-box; }
.btn-qty { width: 44px; height: 100%; border-radius: 0; border: none; background: #FFFFFF; color: var(--text-main); cursor: pointer; transition: 0.3s; font-size: 16px; }
.btn-qty:first-child { border-right: 1px solid var(--glass-border); }
.btn-qty:last-child { border-left: 1px solid var(--glass-border); }
.btn-qty:hover { background: var(--forest); color: #fff; border-color: var(--forest); }
.guest-lux input { flex: 1; text-align: center; background: transparent; border: none; color: var(--text-main); font-size: 1.1rem; font-weight: 500; pointer-events: none; }

/* === MAP BTN & CARDS === */
.map-btn-lux {
    width: 100%; padding: 25px; border: 1px solid var(--forest); border-radius: 0; color: var(--forest); text-transform: uppercase; letter-spacing: 2px; font-size: 12px; cursor: pointer; transition: 0.3s; text-align: center; background: #FFFFFF; font-weight: 600;
}
.map-btn-lux:hover { background: var(--forest); color: #fff; }

.card-select {
    border: 1px solid var(--glass-border); background: #FFFFFF; border-radius: 0; padding: 20px; cursor: pointer; transition: all 0.3s ease; position: relative; margin-bottom: 15px; overflow: hidden;
}
.card-select:hover { border-color: var(--forest); }
.card-select.active { border-color: var(--forest); background: rgba(168, 135, 70, 0.05); }
.card-select.active::after { content: '✓'; position: absolute; top: 15px; right: 15px; color: var(--forest); font-weight: bold; }

/* Thực đơn Add-on */
.menu-item-lux { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; border-bottom: 1px solid var(--glass-border); transition: 0.3s; border-radius: 0; background: #FFFFFF; }
.menu-item-lux:hover { background: #FFFFFF; }
.menu-checkbox { appearance: none; width: 18px; height: 18px; border: 1px solid var(--glass-border); border-radius: 0; cursor: pointer; position: relative; transition: 0.2s; }
.menu-checkbox:checked { background: var(--forest); border-color: var(--forest); }
.menu-checkbox:checked::after { content: '✓'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; font-size: 10px; font-weight: bold; }
.menu-qty-input { width: 60px; background: #FFFFFF; border: 1px solid var(--glass-border); color: var(--text-main); text-align: center; border-radius: 0; padding: 4px; opacity: 0.3; pointer-events: none; transition: 0.3s; }
.menu-note-input { width: 200px; background: #FFFFFF; border: 1px solid var(--glass-border); color: var(--text-main); border-radius: 0; padding: 4px; opacity: 0.3; pointer-events: none; transition: 0.3s; }
.menu-item-lux.checked .menu-qty-input, .menu-item-lux.checked .menu-note-input { opacity: 1; pointer-events: auto; border-color: var(--forest); }

/* === FLOATING SUMMARY === */
.summary-floating {
    position: sticky; top: 100px; background: #ffffff; border: 1px solid var(--glass-border); border-radius: 0; padding: 35px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); color: #000000;
}
.sum-title { font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: #000000; font-weight: 700; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 15px; margin-bottom: 20px; }
.sum-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: #000000; font-weight: 500; }
.sum-val { color: #000000; font-weight: 400; text-align: right;}
.sum-val.highlight { color: #000000; font-weight: 700;}

/* Override for billing summary (yellow background) */
.billing-summary .sum-row { color: rgba(0, 0, 0, 0.75); font-weight: 500; border-color: rgba(0,0,0,0.1); }
.billing-summary .sum-val { color: #F9F9F9; font-weight: 700; }
.billing-summary .sum-val.highlight { color: #000; font-weight: 800; }
.billing-summary p { color: #F9F9F9 !important; }

.total-box { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.1); text-align: center; }
.deposit-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: rgba(0,0,0,0.5); margin-bottom: 5px; }
.deposit-amount { font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; color: var(--accent-burgundy); line-height: 1.2; margin: 5px 0;}
.deposit-note { font-size: 11px; font-style: italic; color: rgba(0,0,0,0.5); }

/* Overrides for billing summary total box */
.billing-summary .total-box { border-top-color: rgba(0,0,0,0.1); }
.billing-summary .deposit-label { color: rgba(255,255,255,0.8); font-weight: 600; }
.billing-summary .deposit-amount { color: #ffffff; font-weight: bold; text-shadow: 1px 1px 3px rgba(0,0,0,0.2); }
.billing-summary .deposit-note { color: rgba(255,255,255,0.6); }

.btn-gold-grad {
    width: 100%; padding: 16px; background: var(--accent-burgundy); border: 1px solid var(--accent-burgundy); border-radius: 0; color: #fff; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; font-size: 13px; cursor: pointer; transition: all 0.3s ease;
}
.btn-gold-grad:hover { background: #1a1a1a; color: #ffffff; border-color: #1a1a1a; }
.billing-summary .btn-gold-grad { background: #ffffff; color: #000000; border: 1px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.billing-summary .btn-gold-grad:hover { background: #1a1a1a; color: #ffffff; border-color: #1a1a1a; }

.btn-outline-lux {
    background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted); padding: 10px 25px; border-radius: 0; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; font-size: 13px; cursor: pointer; transition: all 0.3s ease;
}
.btn-outline-lux:hover { background: var(--text-muted); color: #fff; }

/* === MAP MODAL VIP === */
.modal-content.dark-lux { background: #FFFFFF; border: 1px solid var(--forest); border-radius: 0; color: var(--text-main); }
.modal-header.lux { border-bottom: 1px solid var(--glass-border); padding: 20px 30px; }
.modal-title.lux { font-family: 'Cormorant Garamond', serif; color: var(--forest); font-size: 1.5rem; font-weight: 600; }
.btn-close-lux { opacity: 0.5; }
.cinematic-map { padding: 40px; background: var(--bg-cream); }
.map-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
.vip-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }

.seat-lux {
    background: #FFFFFF; border: 1px solid var(--glass-border); border-radius: 0; padding: 15px 10px; text-align: center; cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative;
}
.seat-lux.available:hover { border-color: var(--forest); transform: scale(1.05); z-index: 2; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.seat-lux.booked { opacity: 0.5; cursor: not-allowed; background: #f0f0f0;}
.seat-lux.selected { background: var(--forest); border-color: var(--forest); transform: scale(1.05); z-index: 3; }
.seat-code { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; color: var(--text-main); display: block; font-weight: 600;}
.seat-lux.selected .seat-code { color: #fff; }
.seat-info { font-size: 10px; color: var(--text-muted); display: block; margin-top: 5px;}
.seat-lux.selected .seat-info { color: rgba(0,0,0,0.7); }
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
                    <a href="?type=table#booking-form-area" class="svc-card-inline <?= $type==='table'?'active':'' ?>"><i class="fas fa-utensils me-1"></i> Đặt Bàn Tiêu Chuẩn</a>
                    <a href="?type=chef#booking-form-area" class="svc-card-inline <?= $type==='chef'?'active':'' ?>"><i class="fas fa-fire-burner me-1"></i> Đầu Bếp Tại Gia</a>
                </div>
            </div>



            <!-- STEP INDICATORS -->
            <div class="panel-section pt-0 pb-0" style="border-bottom: none;">
                <div class="wizard-header">
                    <div class="step-indicator active" id="ind-1" onclick="goToStep(1)">
                        <div class="step-circle">1</div>
                        <div class="step-label">Thông tin</div>
                    </div>
                    <div class="step-indicator" id="ind-2" onclick="goToStep(2)">
                        <div class="step-circle">2</div>
                        <div class="step-label">Thực đơn</div>
                    </div>
                    <div class="step-indicator" id="ind-3" onclick="goToStep(3)">
                        <div class="step-circle">3</div>
                        <div class="step-label">Yêu cầu</div>
                    </div>
                </div>
            </div>

            <!-- BƯỚC 1 -->
            <div id="step-1" class="booking-step active">
            <div class="panel-section pt-0">
                <div class="menu-type-toggle mb-4" style="display: flex; gap: 10px; background: rgba(0,0,0,0.03); padding: 5px; border-radius: 8px;">
                    <button type="button" id="btn-menu-std" class="btn-menu-toggle active" style="flex: 1; padding: 10px; border: none; background: #fff; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); font-weight: 600; color: var(--accent-burgundy); transition: all 0.3s;" onclick="toggleMenuType(0)">
                        <i class="fas fa-book-open me-2"></i>Chọn Món Tiêu Chuẩn
                    </button>
                    <button type="button" id="btn-menu-bespoke" class="btn-menu-toggle" style="flex: 1; padding: 10px; border: none; background: transparent; border-radius: 6px; font-weight: 600; color: var(--text-muted); transition: all 0.3s;" onclick="toggleMenuType(1)">
                        <i class="fas fa-scroll me-2"></i>Thiết Kế Riêng (Bespoke)
                    </button>
                </div>
                <input type="hidden" name="is_bespoke_menu" id="is_bespoke_menu" value="0">

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
                        <label class="label-lux" style="top: -8px; left: 15px; font-size: 11px; color: var(--accent-burgundy); background: #FFFFFF; padding: 0 5px; letter-spacing: 1px; text-transform: uppercase; z-index: 2; font-weight: 600;">Số lượng khách *</label>
                        <?php if ($type === 'chef'): ?>
                            <div class="mt-2 p-3" style="background: rgba(168, 135, 70, 0.08); border: 1px solid rgba(168, 135, 70, 0.3); font-size: 12px; color: var(--text-main); line-height: 1.6; border-radius: 4px;">
                                <i class="fas fa-info-circle me-1" style="color: var(--forest);"></i> <strong style="color: var(--forest);">Phí phục vụ Bếp trưởng</strong> thay đổi theo số lượng khách:<br>
                                • ≤ 2 khách: 250.000đ<br>
                                • 3-6 khách: 500.000đ<br>
                                • 7-12 khách: 1.000.000đ<br>
                                • Trên 12 khách: 1.200.000đ
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($type === 'table'): ?>
                <!-- PHẦN TÙY CHỌN TIỆC KỶ NIỆM -->
                <div class="mt-4 pt-4 border-top border-secondary">
                    <label class="d-flex align-items-center gap-2 mb-3" style="cursor:pointer;">
                        <input type="checkbox" id="add_anniversary_service" name="add_anniversary_service" value="1" onchange="toggleAnniversary(); us(); if(typeof validateStep1 === 'function') validateStep1();">
                        <span style="font-size: 1.1rem; color: var(--accent-burgundy); font-weight: 600;"><i class="fas fa-gift me-2"></i> Thêm Dịch Vụ Kỷ Niệm / Trang Trí</span>
                    </label>
                    <div id="anniversary-fields" style="display: none;">
                    <div class="row-lux">
                        <div class="input-group-lux">
                            <select name="event_type" id="event_type" class="input-lux" onchange="selEvent(); us();">
                                <option value="" data-id="" data-img="">-- Không chọn --</option>
                                <?php foreach($event_types as $et): ?>
                                    <option value="<?= htmlspecialchars($et['name']) ?>" data-id="<?= $et['id'] ?>" data-img="<?= htmlspecialchars($et['image_url']) ?>"><?= htmlspecialchars($et['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="label-lux" >Loại hình kỷ niệm</label>
                            <div id="event_img_wrap" style="display:none; margin-top:15px; text-align:center;">
                                <img id="event_img_preview" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" style="width:100%; height:140px; object-fit:cover; border-radius:10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--glass-border); display:none;">
                            </div>
                        </div>
                        <div class="input-group-lux w-100">
                            <select name="decor_package" id="decor_package" class="input-lux" onchange="us()">
                                <option value="" data-price="0" data-name="" data-img="">-- Không chọn gói trang trí --</option>
                            </select>
                            <label class="label-lux">Gói trang trí</label>
                            <div id="decor_img_wrap" style="display:none; margin-top:15px; text-align:center;">
                                <img id="decor_img_preview" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" style="width:100%; height:140px; object-fit:cover; border-radius:10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--glass-border); display:none;">
                            </div>
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
                </div>
                <?php endif; ?>
            </div>

            <div class="panel-section" style="padding-top: 20px; padding-bottom: 20px;">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--accent-burgundy); margin-bottom: 15px;"><?= $type === 'chef' ? 'Địa Điểm Phục Vụ' : 'Không Gian & Vị Trí' ?></h3>
                
                <?php if ($type !== 'chef'): ?>
                    <div class="map-btn-lux mb-3" data-bs-toggle="modal" data-bs-target="#mapModal">
                        <i class="fas fa-map-marked-alt me-2"></i> Xem Sơ Đồ Nhà Hàng & Chọn Bàn
                    </div>
                    <div id="selected-seat-display" class="card-select active" style="display:none; text-align:center;">
                        <div class="seat-code" id="sp-code"></div>
                        <div class="seat-info" style="color:var(--accent-burgundy)" id="sp-price"></div>
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
                                <option value="<?= htmlspecialchars($c['name']) ?>" <?= ($autofilled_chef_name === $c['name']) ? 'selected' : '' ?>>Chef <?= htmlspecialchars($c['name']) ?></option>
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
                <div class="panel-section" style="border-bottom: none; padding-top:0;">
                    <div class="wizard-nav">
                        <div></div>
                        <button type="button" class="btn-wizard-next" id="btn-next-1" onclick="nextStep(1)">Tiếp theo <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </div> <!-- End Step 1 -->

            <!-- BƯỚC 2 -->
            <div id="step-2" class="booking-step">
            <div class="panel-section" style="border-bottom: none;">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--accent-burgundy);">Thực Đơn</h3>
                
                <div id="standard-menu-fields">
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Bộ Sưu Tập Hương Vị</p>
                <div style="display:grid; grid-template-columns: 1fr; max-width: 350px; gap:15px; margin-bottom: 25px;">
                    <div class="card-select cc active" data-price="0" onclick="selCombo(0,this)">
                        <div style="color:var(--accent-burgundy); font-size:15px; margin-bottom:5px;">Gọi Món Tự Do</div>
                        <div style="font-size:11px; color:var(--text-muted)">Món tự chọn</div>
                    </div>
                </div>

                <?php foreach($grouped_combos as $theme_name => $theme_data): 
                    $themeJson = htmlspecialchars(json_encode([
                        'name' => $theme_name,
                        'desc' => $theme_data['desc'],
                        'img'  => $theme_data['img']
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <p style="font-size:13px; color:var(--accent-burgundy); margin-bottom:10px; font-family:'Cormorant Garamond', serif; text-transform:uppercase; letter-spacing:1px; border-bottom: 1px dashed rgba(212,176,106,0.3); padding-bottom:5px; cursor:pointer;" onclick='showThemeInfo(<?= $themeJson ?>)'>
                    SET MENU TỪ CHỦ ĐỀ: <?= htmlspecialchars($theme_name) ?> <i class="fas fa-info-circle ms-1" style="font-size:11px; opacity:0.7;"></i>
                </p>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:15px; margin-bottom: 25px;">
                    <?php foreach($theme_data['combos'] as $cb): ?>
                        <div class="card-select cc" data-price="<?= (float)$cb['price'] ?>" onclick="selCombo(<?= $cb['id'] ?>,this)">
                            <div style="color:var(--accent-burgundy); font-size:15px; margin-bottom:5px;"><?= htmlspecialchars($cb['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-main)"><?= number_format($cb['price']) ?> đ</div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($theme_data['id'] != 0): ?>
                    <div class="card-select cc" data-price="0" onclick="selThemeFoods(<?= $theme_data['id'] ?>, this)">
                        <div style="color:var(--accent-burgundy); font-size:15px; margin-bottom:5px;">Món tự chọn</div>
                        <div style="font-size:12px; color:var(--text-muted)">Các món thuộc chủ đề <?= htmlspecialchars($theme_name) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>


                </div> <!-- end standard-menu-fields -->

                <div id="bespoke-menu-fields" style="display:none; margin-top:20px; border: 1px solid var(--glass-border); padding:20px; background: #FFFFFF;">
                    <h4 style="font-family:'Cormorant Garamond', serif; color:var(--accent-burgundy); font-size:1.2rem; margin-bottom:15px; text-transform:uppercase; letter-spacing:1px;"><i class="fas fa-scroll me-2"></i> Yêu cầu Thiết kế Thực đơn riêng</h4>
                    <div class="row-lux mb-3">
                        <div class="input-group-lux" style="flex:100%;">
                            <select name="chef_occasion" id="chef_occasion" class="input-lux" onchange="updateChefReq(); us();">
                                <?php foreach($bespoke_occasions as $bo): ?>
                                    <option value="<?= htmlspecialchars($bo['name']) ?>"><?= htmlspecialchars($bo['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="label-lux" >Dịp tổ chức</label>
                        </div>
                    </div>
                    <div class="row-lux mb-3">
                        <div class="input-group-lux">
                            <select name="chef_budget" id="chef_budget" class="input-lux" onchange="updateChefReq(); us(); calcTotal();">
                                <?php foreach($bespoke_budgets as $bb): ?>
                                    <option value="<?= htmlspecialchars($bb['label']) ?>" data-price="<?= $bb['price_value'] ?>"><?= htmlspecialchars($bb['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="label-lux" >Ngân sách dự kiến</label>
                        </div>
                        <div class="input-group-lux">
                            <select name="chef_style" id="chef_style" class="input-lux" onchange="updateChefReq(); us();">
                                <?php foreach($bespoke_styles as $bs): ?>
                                    <option value="<?= htmlspecialchars($bs['name']) ?>"><?= htmlspecialchars($bs['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="label-lux" >Phong cách ẩm thực</label>
                        </div>
                    </div>
                    <div class="input-group-lux mb-0">
                        <textarea name="chef_requirements_detail" id="creq_detail" class="input-lux" rows="3" placeholder=" " oninput="updateChefReq(); us();"></textarea>
                        <label class="label-lux"><i class="fas fa-utensils me-1"></i> Yêu cầu chi tiết cho Thực đơn (Chủ đề, nguyên liệu đặc biệt...)</label>
                    </div>
                    
                    <div class="bespoke-process mt-4 pt-3 border-top">
                        <h5 style="color:var(--accent-burgundy); font-size:13px; text-transform:uppercase; margin-bottom:15px; font-weight:600;"><i class="fas fa-project-diagram me-2"></i>Quy trình Bespoke Dining</h5>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <!-- BƯỚC 1: ACTIVE -->
                            <div style="display:flex; align-items:center; gap:15px;">
                                <div style="width:30px; height:30px; border-radius:50%; background:#d4b06a; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold; flex-shrink:0; box-shadow: 0 0 10px rgba(212,176,106,0.5);">1</div>
                                <div style="font-size:14px; color:var(--text-dark); font-weight:600;">Gửi yêu cầu thiết kế <span style="font-size:11px; color:#d4b06a; font-style:italic; font-weight:normal;">(Bạn đang ở bước này)</span></div>
                            </div>
                            <div style="width:2px; height:15px; background:rgba(212,176,106,0.3); margin-left:14px;"></div>
                            
                            <!-- BƯỚC 2: UPCOMING -->
                            <div style="display:flex; align-items:center; gap:15px; opacity:0.6;">
                                <div style="width:30px; height:30px; border-radius:50%; border: 2px solid #d4b06a; background:transparent; color:#d4b06a; display:flex; align-items:center; justify-content:center; font-weight:bold; flex-shrink:0;">2</div>
                                <div style="font-size:13px; color:var(--text-muted);">Bếp trưởng tư vấn & Lên menu <span style="font-size:11px; font-style:italic;">(Nhà hàng sẽ liên hệ sau)</span></div>
                            </div>
                            <div style="width:2px; height:15px; background:rgba(212,176,106,0.2); margin-left:14px;"></div>
                            
                            <!-- BƯỚC 3: UPCOMING -->
                            <div style="display:flex; align-items:center; gap:15px; opacity:0.6;">
                                <div style="width:30px; height:30px; border-radius:50%; border: 2px solid #d4b06a; background:transparent; color:#d4b06a; display:flex; align-items:center; justify-content:center; font-weight:bold; flex-shrink:0;">3</div>
                                <div style="font-size:13px; color:var(--text-muted);">Xác nhận thực đơn & Báo giá</div>
                            </div>
                            <div style="width:2px; height:15px; background:rgba(212,176,106,0.2); margin-left:14px;"></div>
                            
                            <!-- BƯỚC 4: UPCOMING -->
                            <div style="display:flex; align-items:center; gap:15px; opacity:0.6;">
                                <div style="width:30px; height:30px; border-radius:50%; border: 2px solid #d4b06a; background:transparent; color:#d4b06a; display:flex; align-items:center; justify-content:center; font-weight:bold; flex-shrink:0;">4</div>
                                <div style="font-size:13px; color:var(--text-muted);">Thanh toán cọc & Chuẩn bị</div>
                            </div>
                        </div>
                    </div>
                </div>
                <textarea name="chef_requirements" id="creq" style="display:none;"></textarea>


                <div id="addon-foods-section">
                    <?php if(!empty($grouped_foods)): ?>
                        <p id="addon-foods-label" style="font-size:12px; color:var(--text-muted); margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Thực Đơn Chọn Trước (Add-on)</p>
                        <div style="max-height: 400px; overflow-y: auto; padding-right:10px;">
                            <?php foreach($grouped_foods as $c_name => $t_foods): ?>
                                <div class="addon-group-block" style="margin-bottom: 20px;">
                                    <h5 style="color:var(--accent-burgundy); font-size:13px; text-transform:uppercase; border-bottom:1px dashed rgba(212,176,106,0.3); padding-bottom:5px; margin-bottom:10px; font-weight:600;"><i class="fas fa-utensils me-2"></i> <?= htmlspecialchars($c_name) ?></h5>
                                    <?php foreach($t_foods as $fd): 
                                         $stock = getFoodInventory($db, $fd['id']);
                                         $is_out_of_stock = ($stock <= 0);
                                     ?>
                                     <div class="menu-item-lux flex-column align-items-stretch" id="mr<?= $fd['id'] ?>" 
                                          data-name="<?= htmlspecialchars($fd['name']) ?>"
                                          data-theme="<?= $fd['theme_id'] ?>"
                                          data-price="<?= (float)$fd['price'] ?>"
                                          data-img="public/assets/img/menu/<?= htmlspecialchars($fd['image'] ?: 'default.jpg') ?>"
                                          data-desc="<?= htmlspecialchars($fd['description'] ?? '') ?>"
                                          data-chefnote="<?= htmlspecialchars($fd['chef_note'] ?? '') ?>"
                                          data-ingredients="<?= htmlspecialchars(($fd['ingredients'] ?? '') . (!empty($fd['recipe_ingredients']) ? ', ' . $fd['recipe_ingredients'] : '')) ?>"
                                          data-toppings-raw="<?= htmlspecialchars($fd['list_toppings'] ?? '') ?>"
                                          data-category="<?= htmlspecialchars($fd['cat_name'] ?? 'Món tự chọn') ?>"
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
                                                             <span class="badge bg-warning text-dark ms-2" style="font-size: 10px; border: 1px solid var(--accent-burgundy);"><i class="fas fa-magic me-1"></i> Gợi ý</span>
                                                         <?php endif; ?>
                                                         <?php if(hasAllergenBooking($fd, $user_allergies)): ?>
                                                             <span class="badge bg-danger text-white ms-2" style="font-size: 10px;"><i class="fas fa-exclamation-triangle me-1"></i> Dị ứng</span>
                                                         <?php endif; ?>
                                                         <?php if ($is_out_of_stock): ?>
                                                             <span class="badge bg-secondary text-white ms-2" style="font-size: 10px;"><i class="fas fa-ban me-1"></i> Hết món</span>
                                                         <?php endif; ?>
                                                     </div>
                                                     <div style="font-size:12px; color:var(--accent-burgundy); margin-top: 2px;">
                                                         <?= number_format($fd['price']) ?> đ
                                                         <span style="font-size:11px; color:var(--text-muted); margin-left:10px;">
                                                             (<?= $is_out_of_stock ? '<span class="text-danger">Hết món</span>' : '<span class="text-success">Còn món</span>' ?>)
                                                         </span>
                                                     </div>
                                                 </div>
                                             </div>
                                              <input type="hidden" name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>" value="1">
                                         </div>
                                         <input type="hidden" name="food_notes[<?= $fd['id'] ?>]" id="fn<?= $fd['id'] ?>" value="">
                                         <div class="opt-note-display mt-2" style="font-size:11px; color:var(--accent-burgundy); font-style:italic; display:none; padding-left:33px;"></div>
                                     </div>
                                     <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
                <div class="panel-section" style="border-bottom: none; padding-top:0;">
                    <div class="wizard-nav">
                        <button type="button" class="btn-wizard-prev" onclick="prevStep(2)"><i class="fas fa-arrow-left me-2"></i> Quay lại</button>
                        <button type="button" class="btn-wizard-next" id="btn-next-2" onclick="nextStep(2)">Tiếp theo <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </div> <!-- End Step 2 -->

            <!-- BƯỚC 3 -->
            <div id="step-3" class="booking-step">
            <div class="panel-section" id="bespoke-section">
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--accent-burgundy);"><i class="fas fa-gem me-2"></i> Trải nghiệm Cá nhân hóa</h3>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Bespoke Booking Experience</p>
                
                <div class="d-flex flex-column gap-3">
                    <label class="d-flex align-items-center gap-3 p-2 rounded" style="cursor:pointer; background:rgba(212,176,106,0.05); border:1px solid rgba(212,176,106,0.2); transition:0.3s;">
                        <input type="checkbox" name="has_candle" id="bespoke-candle" class="menu-checkbox" onchange="us()">
                        <div>
                            <div style="font-size:14px; font-weight:600; color:var(--accent-burgundy);"><i class="fas fa-fire me-2"></i>Chuẩn bị Nến thơm thư giãn</div>
                            <div style="font-size:12px; color:var(--text-muted);">Tạo không gian lung linh, lãng mạn (+50.000 đ)</div>
                        </div>
                    </label>

                    <div class="p-2 rounded" style="background: #FFFFFF; border:1px solid var(--glass-border); transition:0.3s; border-radius:0;">
                        <label class="d-flex align-items-center gap-3" style="cursor:pointer; margin-bottom:0;" onclick="document.getElementById('flower-input-wrap').style.display = document.getElementById('bespoke-flower').checked ? 'block' : 'none'; us();">
                            <input type="checkbox" name="has_bespoke_flower" id="bespoke-flower" class="menu-checkbox">
                            <div>
                                <div style="font-size:14px; font-weight:600; color:var(--accent-burgundy);"><i class="fas fa-seedling me-2"></i>Hoa tươi thiết kế riêng</div>
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

                    <div class="p-2 rounded" style="background: #FFFFFF; border:1px solid var(--glass-border); transition:0.3s; border-radius:0;">
                        <label class="d-flex align-items-center gap-3" style="cursor:pointer; margin-bottom:0;" onclick="document.getElementById('card-input-wrap').style.display = document.getElementById('bespoke-card').checked ? 'block' : 'none'; us();">
                            <input type="checkbox" name="has_handwritten_card" id="bespoke-card" class="menu-checkbox">
                            <div>
                                <div style="font-size:14px; font-weight:600; color:var(--accent-burgundy);"><i class="fas fa-envelope-open-text me-2"></i>Viết Thiệp tay chúc mừng</div>
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
                        <h4 style="font-size:13px; color:var(--accent-burgundy); margin-bottom:15px; text-transform:uppercase;"><i class="fas fa-sliders-h me-2"></i>Cấu hình Không gian (Dành cho Phòng VIP)</h4>
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
                <h3 class="section-title-lux" style="font-size: 1.4rem; color: var(--accent-burgundy);">Hồ Sơ Yêu Cầu & Khẩu Vị</h3>
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="fw-bold mb-3" style="color: var(--accent-burgundy); font-family: 'Cormorant Garamond', serif; font-size: 1.1rem;"><i class="fas fa-exclamation-triangle me-1"></i> Dị ứng thực phẩm</label>
                        <div class="d-flex flex-column gap-2" style="font-size: 0.95rem;">
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Hải sản" class="form-check-input me-2" style="cursor:pointer"> Hải sản</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Sữa" class="form-check-input me-2" style="cursor:pointer"> Sữa</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Gluten" class="form-check-input me-2" style="cursor:pointer"> Gluten</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Đậu phộng" class="form-check-input me-2" style="cursor:pointer"> Đậu phộng</label>
                            <label class="form-check-label cursor-pointer"><input type="checkbox" name="allergies[]" value="Trứng" class="form-check-input me-2" style="cursor:pointer"> Trứng</label>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="fw-bold mb-3" style="color: var(--accent-burgundy); font-family: 'Cormorant Garamond', serif; font-size: 1.1rem;"><i class="fas fa-leaf me-1"></i> Chế độ ăn</label>
                        <div class="d-flex flex-column gap-2" style="font-size: 0.95rem;">
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Healthy" class="form-check-input me-2" style="cursor:pointer"> Healthy</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Vegetarian" class="form-check-input me-2" style="cursor:pointer"> Vegetarian</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Vegan" class="form-check-input me-2" style="cursor:pointer"> Vegan</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Keto" class="form-check-input me-2" style="cursor:pointer"> Keto</label>
                            <label class="form-check-label cursor-pointer"><input type="radio" name="diet" value="Không yêu cầu" class="form-check-input me-2" style="cursor:pointer" checked> Không yêu cầu</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-bold mb-3" style="color: var(--accent-burgundy); font-family: 'Cormorant Garamond', serif; font-size: 1.1rem;"><i class="fas fa-glass-cheers me-1"></i> Mục đích</label>
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
                    <textarea name="message" class="input-lux" rows="2" placeholder=" "><?= htmlspecialchars($autofill_chef_msg) ?></textarea>
                    <label class="label-lux"><i class="fas fa-comment-dots me-1"></i> Ghi chú / Yêu cầu đặc biệt khác</label>
                </div>
            </div> <!-- Close panel-section (Hồ sơ yêu cầu) -->
                <div class="panel-section" style="border-bottom: none; padding-top:0;">
                    <div class="wizard-nav">
                        <button type="button" class="btn-wizard-prev" onclick="prevStep(3)"><i class="fas fa-arrow-left me-2"></i> Quay lại</button>
                        <div style="font-size:12px; color:var(--text-muted); align-self:center; font-style:italic;">Hoàn tất tại bảng tóm tắt bên phải <i class="fas fa-arrow-right ms-1"></i></div>
                    </div>
                </div>

            </div> <!-- End Step 3 -->
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
            
            <?php if ($ms_discount_percent > 0): ?>
                <div class="sum-row" style="color: #000000; font-weight: 700; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 12px; margin-top: 12px;">
                    <span><i class="fas fa-gift text-danger"></i> <?= htmlspecialchars($ms_reward_title) ?> (-<?= floatval($ms_discount_percent) ?>%)</span> 
                    <span class="sum-val" id="s-ms-discount" style="color: #000000;">0 đ</span>
                </div>
            <?php endif; ?>

            <?php if (isset($is_birthday) && $is_birthday): ?>
                <div class="sum-row" style="color: #000000; font-weight: 700; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 12px; margin-top: 12px;">
                    <span><i class="fas fa-birthday-cake text-danger"></i> Tặng Sinh Nhật (-10%)</span> 
                    <span class="sum-val" id="s-bd-discount" style="color: #000000;">0 đ</span>
                </div>
            <?php endif; ?>
            
            <div id="selected-foods-list" style="margin-top: 15px; border-top: 1px dashed rgba(0,0,0,0.15); padding-top: 15px; display: none;">
                <div style="font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: rgba(0,0,0,0.5); margin-bottom: 10px;">Chi tiết món đã chọn:</div>
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

    <!-- WIZARD JS LOGIC -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.location.hash === '#booking-form-area') {
            setTimeout(() => {
                window.scrollTo({ top: document.getElementById('booking-form-area').offsetTop - 80, behavior: 'auto' });
            }, 10);
        }
    });

    let currentStep = 1;
    function goToStep(step) {
        if (step > currentStep) {
            if (step === 2 && !validateStep1()) return;
            if (step === 3 && (!validateStep1() || !validateStep2())) return;
        }
        showStep(step);
    }
    function nextStep(step) {
        if (step === 1 && validateStep1()) showStep(2);
        if (step === 2 && validateStep2()) showStep(3);
    }
    function prevStep(step) { showStep(step - 1); }
    function showStep(step) {
        document.querySelectorAll('.booking-step').forEach(el => el.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        for (let i = 1; i <= 3; i++) {
            let ind = document.getElementById('ind-' + i);
            ind.classList.remove('active', 'completed');
            if (i < step) ind.classList.add('completed');
            if (i === step) ind.classList.add('active');
        }
        currentStep = step;
        checkSubmitButton();
        window.scrollTo({ top: document.getElementById('booking-form-area').offsetTop - 100, behavior: 'smooth' });
    }
    function validateStep1() {
        let name = document.querySelector('[name="customer_name"]').value.trim();
        let phone = document.querySelector('[name="customer_phone"]').value.trim();
        let dateInput = document.getElementById('bd');
        let dateVal = dateInput.value;
        let isValid = name !== '' && phone !== '' && dateVal !== '';
        
        let dateErrorMsg = document.getElementById('date-error-msg');
        if (!dateErrorMsg) {
            dateErrorMsg = document.createElement('small');
            dateErrorMsg.id = 'date-error-msg';
            dateErrorMsg.className = 'text-danger mt-1 d-block fw-bold';
            dateInput.parentNode.appendChild(dateErrorMsg);
        }
        dateErrorMsg.innerText = '';
        
        if (dateVal !== '') {
            let bookingDate = new Date(dateVal);
            let now = new Date();
            let bookingType = '<?= $type ?>';
            let isBespoke = document.getElementById('is_bespoke_menu') && document.getElementById('is_bespoke_menu').value === '1';
            let isAnniversary = document.getElementById('add_anniversary_service') && document.getElementById('add_anniversary_service').checked;
            let minHours = 1;
            let errorMsgText = 'Quý khách vui lòng chọn giờ đến sau thời điểm hiện tại ít nhất 1 tiếng.';

            if (isBespoke) {
                minHours = 48;
                errorMsgText = 'Dịch vụ Thiết kế riêng đòi hỏi sự chuẩn bị hoàn mỹ nhất, quý khách vui lòng đặt trước ít nhất 48 tiếng.';
            } else if (isAnniversary) {
                minHours = 3;
                errorMsgText = 'Dịch vụ Tiệc kỷ niệm yêu cầu chuẩn bị chu đáo, quý khách vui lòng đặt trước ít nhất 3 tiếng.';
            } else if (bookingType === 'home') {
                minHours = 24;
                errorMsgText = 'Dịch vụ Đầu bếp tại gia cần chọn lọc nguyên liệu riêng, quý khách vui lòng đặt trước ít nhất 24 tiếng.';
            }

            let minAllowedTime = new Date(now.getTime() + minHours * 60 * 60000); 
            
            if (bookingDate < minAllowedTime) {
                isValid = false;
                dateErrorMsg.innerText = errorMsgText;
            } else {
                let hours = bookingDate.getHours().toString().padStart(2, '0');
                let mins = bookingDate.getMinutes().toString().padStart(2, '0');
                let timeStr = hours + ':' + mins;
                let startTime = '<?= $restaurant_start_time ?>';
                let endTime = '<?= $restaurant_end_time ?>';
                
                let isOutsideHours = false;
                if (endTime < startTime) {
                    if (timeStr < startTime && timeStr > endTime) isOutsideHours = true;
                } else {
                    if (timeStr < startTime || timeStr > endTime) isOutsideHours = true;
                }

                if (isOutsideHours) {
                    isValid = false;
                    dateErrorMsg.innerText = 'Nhà hàng hân hạnh phục vụ quý khách trong khung giờ ' + '<?= $open_time_setting ?>' + '.';
                }
            }
        }
        
        let btnNext = document.getElementById('btn-next-1');
        if (btnNext) btnNext.disabled = !isValid;
        return isValid;
    }
    function validateStep2() {
        let isValid = true;
        let btnNext = document.getElementById('btn-next-2');
        if (btnNext) btnNext.disabled = false;
        return isValid;
    }
    function checkSubmitButton() {
        let btnGo = document.getElementById('btn-go');
        if (!btnGo) return;
        if (currentStep === 3) {
            btnGo.style.opacity = '1';
            btnGo.style.pointerEvents = 'auto';
            if (btnGo.hasAttribute('data-original-text')) {
                btnGo.innerHTML = btnGo.getAttribute('data-original-text');
            }
        } else {
            if (!btnGo.hasAttribute('data-original-text')) {
                btnGo.setAttribute('data-original-text', btnGo.innerHTML);
            }
            btnGo.style.opacity = '0.5';
            btnGo.style.pointerEvents = 'none';
            btnGo.innerHTML = '<i class="fas fa-lock"></i> Vui lòng hoàn thành các bước';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const originalUs = window.us || function(){};
        window.us = function() {
            originalUs();
            if (currentStep === 1) validateStep1();
            if (currentStep === 2) validateStep2();
        };
        // bind manual inputs just in case us() misses them
        document.querySelector('[name="customer_name"]').addEventListener('input', validateStep1);
        document.querySelector('[name="customer_phone"]').addEventListener('input', validateStep1);
        document.getElementById('bd').addEventListener('change', validateStep1);
        
        validateStep1();
        validateStep2();
        checkSubmitButton();
        if (typeof updateChefReq === 'function') {
            updateChefReq();
        }
    });
    </script>

<!-- MODAL CHI TIẾT & TÙY CHỌN MÓN ĂN -->
<div class="modal fade" id="foodOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 750px;">
    <div class="modal-content" style="background: #FFFFFF; border:1px solid var(--forest); border-radius:0; position:relative; overflow:hidden;">
      <div class="modal-body p-0">
        <button type="button" class="btn-close" onclick="cancelFoodOption()" style="position:absolute; top:15px; right:15px; z-index:10; background:none; border:none; font-size:20px; color:var(--text-muted); line-height:1;">✕</button>
        <div class="row g-0">
          <div class="col-md-5" style="position:relative; min-height:350px; background:#F9F9F9;">
            <img id="foodOptImg" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" style="width:100%; height:100%; object-fit:cover; position:absolute; inset:0;" alt="Food Image" onerror="this.onerror=null; this.src='https://placehold.co/800x600/262629/A88746?text=No+Image'">
          </div>
          <div class="col-md-7" style="padding: 40px; display:flex; flex-direction:column; justify-content:space-between; background: #FFFFFF; max-height: 85vh; overflow-y: auto;">
            <div>
              <div style="font-size:10px; font-family:var(--font-sans); letter-spacing:2px; text-transform:uppercase; color:var(--accent-burgundy); margin-bottom:5px;">Chi tiết món ăn</div>
              <h4 id="foodOptName" style="color:var(--forest); font-family:'Cormorant Garamond', serif; font-size:1.8rem; font-weight:600; margin-bottom:5px;">Tên món</h4>
              <div id="foodOptCategory" style="font-size:12px; color:var(--text-muted); margin-bottom:5px;">Danh mục: ...</div>
              <div id="foodOptStatus" style="font-size:12px; margin-bottom:10px; font-weight:bold;">Trạng thái: ...</div>
              <div id="foodOptPrice" style="font-size:1.1rem; color:var(--accent-burgundy); font-weight:600; margin-bottom:15px;">Giá gốc: 0 đ</div>
              <p id="foodOptDesc" style="font-size:13px; color:var(--text-muted); font-style:italic; line-height:1.6; margin-bottom:20px;"></p>
              
              <!-- Chef Note Wrap -->
              <div id="chefNoteWrap" style="display:none; margin-bottom:20px; background: rgba(212, 175, 55, 0.05); border-left: 3px solid var(--accent-burgundy); padding: 12px 15px;">
                <div style="font-family:'Cormorant Garamond', serif; font-size:16px; font-weight:600; color:var(--accent-burgundy); margin-bottom:5px;"><i class="fas fa-pen-nib me-2"></i>Câu chuyện của Bếp trưởng</div>
                <div id="foodOptChefNote" style="font-size:13px; color:var(--forest); line-height:1.5; font-style:italic;"></div>
              </div>
              
              <!-- Ingredients List -->
              <div id="ingredientsWrap" style="margin-bottom:20px; display:none;">
                <label style="color:var(--forest); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:8px;">Thành phần có sẵn:</label>
                <div id="ingredientsList" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
              </div>
              
              <!-- Doneness Wrap -->
              <div id="donenessWrap" style="display:none; margin-bottom:20px;">
                <label style="color:var(--forest); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:8px;">Độ chín thịt (Meat Doneness):</label>
                <select id="foodOptDoneness" class="form-select form-select-sm" style="border:1px solid var(--glass-border); border-radius:0; font-size:13px; background: #FFFFFF;" onchange="updateModalPrice()">
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
                <textarea id="foodOptNote" class="form-control" rows="2" maxlength="255" placeholder="Ví dụ: ít cay, không hành, thêm sốt riêng..." style="border:1px solid var(--glass-border); border-radius:0; font-size:13px; background: #FFFFFF;"></textarea>
              </div>
            </div>
            
            <div style="border-top:1px dashed var(--glass-border); padding-top:20px; display:flex; justify-content:space-between; align-items:center;">
              <div>
                <div style="font-size:11px; color:var(--text-muted);">Số lượng:</div>
                <div style="display:flex; align-items:center; border:1px solid var(--glass-border); margin-top:5px; width:fit-content; background: #FFFFFF;">
                  <button type="button" class="btn btn-sm" onclick="adjustModalQty(-1)" style="padding:4px 12px; border:none; background:none; font-weight:bold;">-</button>
                  <input type="number" id="foodOptQty" value="1" min="1" style="width:40px; text-align:center; border:none; background:none; font-weight:600; font-size:13px;" readonly>
                  <button type="button" class="btn btn-sm" onclick="adjustModalQty(1)" style="padding:4px 12px; border:none; background:none; font-weight:bold;">+</button>
                </div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:11px; color:var(--text-muted);">Tổng tạm tính:</div>
                <div id="foodOptTotalDisplay" style="font-size:1.4rem; color:var(--accent-burgundy); font-weight:600; margin-bottom:8px;">0 đ</div>
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
        <h5 class="modal-title" style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif;"><i class="fas fa-book-open me-2"></i>Chi tiết Chủ đề</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:0;">
          <img id="themeInfoImg" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="Theme Image" style="width:100%; height:250px; object-fit:cover; display:none;">
          <div style="padding:20px;">
              <h4 id="themeInfoName" style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; margin-bottom:15px; font-size:1.5rem;"></h4>
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
            <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.1); display:flex; justify-content:flex-end; align-items:center;">
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
    i.value=Math.max(1,Math.min(16,parseInt(i.value||2)+d));
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

function selThemeFoods(themeId, btn) {
    document.querySelectorAll('.card-select.cc').forEach(function(c) { c.classList.remove('active'); });
    btn.classList.add('active');
    
    // Ẩn/hiện phần bổ sung nếu là bespoke
    var bespokeFields = document.getElementById('bespoke-menu-fields');
    var addonSection  = document.getElementById('addon-foods-section');
    var detailInput   = document.getElementById('booking_detail');
    
    if (bespokeFields) bespokeFields.style.display = 'none';
    if (addonSection)  addonSection.style.display  = 'block';
    if (detailInput)   detailInput.removeAttribute('required');

    var allRows     = document.querySelectorAll('.menu-item-lux[id^="mr"]');
    var groupBlocks = document.querySelectorAll('.addon-group-block');
    var label       = document.getElementById('addon-foods-label');

    // Filter by theme
    allRows.forEach(function(row){
        var rowTheme = parseInt(row.getAttribute('data-theme')) || 0;
        if (rowTheme === themeId) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
            var cb = row.querySelector('.menu-checkbox');
            if (cb && cb.checked) { cb.checked = false; cb.dispatchEvent(new Event('change')); }
        }
    });

    groupBlocks.forEach(function(g){
        var hasVisible = Array.from(g.querySelectorAll('.menu-item-lux[id^="mr"]'))
                              .some(function(r){ return r.style.display !== 'none'; });
        g.style.display = hasVisible ? 'block' : 'none';
    });

    if (label) {
        label.innerHTML = '<i class="fas fa-utensils me-1"></i> Món tự chọn thuộc chủ đề đã chọn';
    }
    
    comboId = 0; 
    document.getElementById('hidden_combo_id').value = '';
    calcTotal();
}

function updateChefReq() {
    var reqInput = document.getElementById('creq');
    if (!reqInput) return;
    
    var select = document.getElementById('saddr_select');
    var customInput = document.getElementById('custom_saddr');
    var budget = document.getElementById('chef_budget') ? document.getElementById('chef_budget').value : '';
    var style = document.getElementById('chef_style') ? document.getElementById('chef_style').value : '';
    var occasion = document.getElementById('chef_occasion') ? document.getElementById('chef_occasion').value : '';
    var detail = document.getElementById('creq_detail') ? document.getElementById('creq_detail').value : '';
    var chef = document.getElementById('selected_chef') ? document.getElementById('selected_chef').value : '';

    var address = '';
    if (select) {
        address = (select.value === 'custom') ? (customInput ? customInput.value : '') : select.value;
    }

    var parts = [];
    if (address) parts.push("Địa điểm phục vụ: " + address);
    if (chef) parts.push("Bếp trưởng chỉ định: " + chef);

    var isBespokeMenu = document.getElementById('is_bespoke_menu') ? document.getElementById('is_bespoke_menu').value : '0';
    var sid = document.getElementById('sid') ? parseInt(document.getElementById('sid').value) : 0;
    if (sid === -1 || isBespokeMenu === '1') {
        if (occasion) parts.push("Dịp: " + occasion);
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
    var chefNote = row.getAttribute('data-chefnote');
    
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
    
    var chefNoteWrap = document.getElementById('chefNoteWrap');
    if (chefNote && chefNote.trim() !== '') {
        chefNoteWrap.style.display = 'block';
        document.getElementById('foodOptChefNote').textContent = chefNote;
    } else {
        chefNoteWrap.style.display = 'none';
    }

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
                span.style.cssText = "background:#F9F9F9; color:var(--forest); padding:4px 10px; font-size:12px; font-weight:500; border-radius:0; border: 1px solid var(--glass-border);";
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
                
                label.innerHTML = '<div style="display:flex; align-items:center; gap:8px;">' + inputHtml + imgHtml + '<span>' + tp.name + '</span></div><strong style="color:var(--accent-burgundy);">+' + tp.price.toLocaleString('vi-VN') + 'đ</strong>';
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
            if(!s.classList.contains('available')) {
                alert('Bàn này đã được khách hàng khác đặt trong khung giờ bạn chọn. Vui lòng chọn bàn trống (màu trắng).');
                return;
            }
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
        
        var ts = new Date().getTime();
        fetch('api/check_table_availability.php?datetime=' + encodeURIComponent(d.value) + '&_=' + ts)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    var unav = data.unavailable_tables || [];
                    document.querySelectorAll('.seat-lux').forEach(function(s) {
                        var id = parseInt(s.dataset.id);
                        if (unav.includes(id)) {
                            s.classList.remove('available');
                            s.classList.add('booked');
                            if (selId == id) {
                                alert('Rất tiếc! Bàn bạn vừa chọn đã có khách đặt vào thời gian này. Vui lòng chọn một bàn khác.');
                                clrSeat();
                            }
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
const decorPackages = <?= json_encode($decor_pkgs) ?>;

function selEvent() {
    try {
        let evtSelect = document.getElementById('event_type');
        if (!evtSelect || evtSelect.selectedIndex === -1) return;
        
        let opt = evtSelect.options[evtSelect.selectedIndex];
        let eventId = opt.getAttribute('data-id');
        let decorSelect = document.getElementById('decor_package');
        
        if (!decorSelect) return;
        
        let html = '<option value="" data-price="0" data-name="" data-img="">-- Không chọn gói trang trí --</option>';
        if (!eventId) {
            decorSelect.innerHTML = html;
            us();
            return;
        }

        let hasDecor = false;
        decorPackages.forEach(dp => {
            if (dp.event_type_id == eventId) {
                let numPrice = parseInt(dp.price) || 0;
                let priceStr = numPrice > 0 ? ' (+ ' + new Intl.NumberFormat('vi-VN').format(numPrice) + 'đ)' : '';
                let imgAttr = dp.image_url ? ' data-img="' + dp.image_url + '"' : ' data-img=""';
                html += '<option value="' + dp.id + '" data-price="' + numPrice + '" data-name="' + dp.name + '"' + imgAttr + '>' + dp.name + priceStr + '</option>';
                hasDecor = true;
            }
        });
        
        if (!hasDecor) {
            html = '<option value="" data-price="0" data-name="" data-img="">Không có gói trang trí nào</option>';
        }
        decorSelect.innerHTML = html;
        us();
    } catch (e) {
        console.error("Lỗi selEvent:", e);
    }
}

// Gọi us() và tự động điền thông tin lần đặt gần nhất ngay khi trang vừa tải
document.addEventListener('DOMContentLoaded', function() {
    var lastBookingData = <?php echo json_encode([
        'date' => $last_booking_date,
        'guests' => $last_booking_guests,
        'tableId' => $last_booking_table_id,
        'serviceType' => $last_booking_service_type,
        'comboId' => $last_booking_combo_id,
        'items' => $last_booking_items
    ]); ?>;

    if (lastBookingData && lastBookingData.serviceType === <?= json_encode($type) ?>) {
        // Tự động điền ngày giờ (sử dụng cùng giờ đặt của lần trước, nhưng dời ngày lên ngày mai nếu đã qua thời gian đó)
        var dateInput = document.getElementById('bd');
        if (dateInput && lastBookingData.date) {
            dateInput.value = lastBookingData.date;
        }
        
        // Tự động điền số lượng khách
        var guestsInput = document.getElementById('gi');
        if (guestsInput && lastBookingData.guests) {
            guestsInput.value = lastBookingData.guests;
        }
        
        // Tự động chọn bàn gần nhất
        if (lastBookingData.tableId) {
            var tsel = document.getElementById('tsel');
            if (tsel) {
                tsel.value = lastBookingData.tableId;
                if (tsel.selectedIndex !== -1) {
                    fromDrop(tsel);
                } else {
                    tsel.value = '';
                }
            }
        }

        // Tự động chọn Combo gần nhất
        if (typeof lastBookingData.comboId !== 'undefined') {
            selectComboProgrammatically(lastBookingData.comboId);
        }

        // Tự động chọn danh sách món ăn gần nhất
        if (lastBookingData.items && lastBookingData.items.length > 0) {
            lastBookingData.items.forEach(function(item) {
                var toppingIds = item.toppings_info ? item.toppings_info.split(',').map(Number) : [];
                selectFoodProgrammatically(item.food_id, item.quantity, toppingIds, item.notes);
            });
        }
    }
    us();
});

// Helper chọn món lập trình từ lịch sử
function selectFoodProgrammatically(foodId, qty, toppingIds, finalNote) {
    var row = document.getElementById('mr' + foodId);
    if (!row) return;
    
    var cb = row.querySelector('.menu-checkbox');
    if (!cb || cb.disabled) return; // Bỏ qua nếu món hết hàng hoặc không tìm thấy checkbox
    
    var basePrice = parseFloat(row.getAttribute('data-price') || 0);
    var toppingsRaw = row.getAttribute('data-toppings-raw') || '';
    
    // Đặt số lượng
    var qtyInput = document.getElementById('q' + foodId);
    if (qtyInput) qtyInput.value = qty;
    
    // Xóa toppings cũ
    row.querySelectorAll('input[name="food_toppings['+foodId+'][]"]').forEach(function(el){el.remove();});
    
    // Duyệt và thêm hidden inputs cho topping, tính giá thêm
    var addedPrice = 0;
    var tpNotes = [];
    
    if (toppingIds && toppingIds.length > 0 && toppingsRaw) {
        var tops = toppingsRaw.split('|');
        tops.forEach(function(tp) {
            var parts = tp.split('::');
            if (parts.length >= 3) {
                var tpId = parts[0];
                var tpName = parts[1];
                var tpPrice = parseFloat(parts[2]);
                
                if (toppingIds.indexOf(parseInt(tpId)) !== -1 || toppingIds.indexOf(tpId.toString()) !== -1) {
                    tpNotes.push(tpName);
                    addedPrice += tpPrice;
                    
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'food_toppings['+foodId+'][]';
                    hidden.value = tpId;
                    row.appendChild(hidden);
                }
            }
        });
    }
    
    // Ghi chú
    var noteInput = document.getElementById('fn' + foodId);
    if (noteInput) noteInput.value = finalNote || '';
    
    var disp = row.querySelector('.opt-note-display');
    if (disp) {
        if (finalNote && finalNote.trim() !== '') {
            disp.style.display = 'block';
            disp.innerHTML = '<strong>📝 Ghi chú:</strong> ' + finalNote.trim();
        } else {
            disp.style.display = 'none';
        }
    }
    
    cb.checked = true;
    row.classList.add('checked');
    
    menuPr[foodId] = basePrice + addedPrice;
}

// Helper chọn combo lập trình từ lịch sử
function selectComboProgrammatically(comboId) {
    var cards = document.querySelectorAll('.card-select.cc');
    var found = false;
    for (var i = 0; i < cards.length; i++) {
        var card = cards[i];
        var attr = card.getAttribute('onclick') || '';
        if (attr.includes('selCombo(' + comboId + ',')) {
            selCombo(comboId, card);
            found = true;
            break;
        }
    }
    if (!found && comboId === 0) {
        var freeCard = document.querySelector('.card-select.cc[onclick*="selCombo(0,"]');
        if (freeCard) {
            selCombo(0, freeCard);
        }
    }
}

function toggleMenuType(isBespoke) {
    document.getElementById('is_bespoke_menu').value = isBespoke;
    if (isBespoke == 1) {
        document.getElementById('btn-menu-bespoke').style.background = '#fff';
        document.getElementById('btn-menu-bespoke').style.color = 'var(--accent-burgundy)';
        document.getElementById('btn-menu-bespoke').style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
        
        document.getElementById('btn-menu-std').style.background = 'transparent';
        document.getElementById('btn-menu-std').style.color = 'var(--text-muted)';
        document.getElementById('btn-menu-std').style.boxShadow = 'none';
        
        document.getElementById('standard-menu-fields').style.display = 'none';
        var addonSec = document.getElementById('addon-foods-section');
        if(addonSec) addonSec.style.display = 'none';
        document.getElementById('bespoke-menu-fields').style.display = 'block';
    } else {
        document.getElementById('btn-menu-std').style.background = '#fff';
        document.getElementById('btn-menu-std').style.color = 'var(--accent-burgundy)';
        document.getElementById('btn-menu-std').style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
        
        document.getElementById('btn-menu-bespoke').style.background = 'transparent';
        document.getElementById('btn-menu-bespoke').style.color = 'var(--text-muted)';
        document.getElementById('btn-menu-bespoke').style.boxShadow = 'none';
        
        document.getElementById('standard-menu-fields').style.display = 'block';
        var addonSec = document.getElementById('addon-foods-section');
        if(addonSec) addonSec.style.display = 'block';
        document.getElementById('bespoke-menu-fields').style.display = 'none';
    }
    us();
    if (typeof validateStep1 === 'function') validateStep1();
}

function toggleAnniversary() {
    let chk = document.getElementById('add_anniversary_service');
    let flds = document.getElementById('anniversary-fields');
    if (chk && flds) {
        flds.style.display = chk.checked ? 'block' : 'none';
        if (!chk.checked) {
            document.getElementById('event_type').value = '';
            document.getElementById('decor_package').value = '';
            document.querySelectorAll('input[name="has_cake"]').forEach(e=>e.checked=false);
            document.querySelectorAll('input[name="has_flower"]').forEach(e=>e.checked=false);
            let imgWrap1 = document.getElementById('event_img_wrap');
            if (imgWrap1) imgWrap1.style.display = 'none';
            let imgWrap2 = document.getElementById('decor_img_wrap');
            if (imgWrap2) imgWrap2.style.display = 'none';
        }
    }
}

function us(){
    try {
        // Check table availability remotely
        if (typeof checkTableAvailability === 'function') {
            checkTableAvailability();
        }

    var n=document.querySelector('[name="customer_name"]');
    document.getElementById('sn').textContent=n&&n.value?n.value:'—';

    var typeStr = "<?= $type ?>";
    var d=document.getElementById('bd');
    if(d&&d.value){
        var dt=new Date(d.value);
        var timeStr = dt.toLocaleDateString('vi-VN') + ' ' + dt.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
        if (typeStr === 'chef') {
            var arriveDt = new Date(dt.getTime() - 90 * 60000); // 1.5 hours before
            var arriveStr = arriveDt.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
            document.getElementById('sd').innerHTML = timeStr + '<br><span style="color:var(--accent-burgundy); font-size:11px; font-style:italic;">(Bếp trưởng có mặt lúc ' + arriveStr + ' để setup)</span>';
        } else {
            document.getElementById('sd').textContent = timeStr;
        }
    } else {
        document.getElementById('sd').textContent='—';
    }

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

    var isBespokeMenu = document.getElementById('is_bespoke_menu') ? document.getElementById('is_bespoke_menu').value : '0';
    var sid = document.getElementById('sid') ? parseInt(document.getElementById('sid').value) : 0;
    var food = 0;
    if (sid === -1 || isBespokeMenu === '1') {
        document.getElementById('sm').textContent = "Thiết kế riêng (Dựa theo NS)";
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
                itemDiv.style.cssText = "background: rgba(0,0,0,0.02); padding: 10px; border-left: 3px solid var(--accent-burgundy); font-size: 12px; margin-bottom: 5px;";
                
                var titleDiv = document.createElement('div');
                titleDiv.style.cssText = "display: flex; justify-content: space-between; font-weight: 600; color: #000;";
                titleDiv.innerHTML = '<span>' + name + ' x' + qty + '</span><span>' + totalPrice.toLocaleString('vi-VN') + ' đ</span>';
                itemDiv.appendChild(titleDiv);
                
                if (note && note.trim() !== '') {
                    var noteDiv = document.createElement('div');
                    noteDiv.style.cssText = "color: rgba(0,0,0,0.7); font-size: 11px; margin-top: 4px; font-style: italic; word-break: break-word;";
                    noteDiv.textContent = note;
                    itemDiv.appendChild(noteDiv);
                }
                
                var actionDiv = document.createElement('div');
                actionDiv.style.cssText = "display: flex; gap: 10px; margin-top: 8px; justify-content: flex-end;";
                
                var editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.style.cssText = "background: none; border: none; color: var(--accent-burgundy); font-size: 11px; cursor: pointer; padding: 0;";
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
        let eventSum = document.getElementById('m-event-sum');
        if (eventSum) {
            if (evType.options && evType.selectedIndex >= 0) {
                eventSum.textContent = evType.options[evType.selectedIndex].text;
            } else {
                eventSum.textContent = evType.value || '—';
            }
        }
        
        let decorEl = document.querySelector('[name="decor_package"]');
        let decorName = 'Mặc định';
        if (decorEl && decorEl.options && decorEl.selectedIndex >= 0 && decorEl.value !== '') {
            let opt = decorEl.options[decorEl.selectedIndex];
            decorPrice = parseFloat(opt.getAttribute('data-price')) || 0;
            decorName = opt.getAttribute('data-name') || 'Gói trang trí';
        }

        let cakeEl = document.querySelector('[name="has_cake"]');
        let flowerEl = document.querySelector('[name="has_flower"]');
        let cake = cakeEl ? cakeEl.checked : false;
        let flower = flowerEl ? flowerEl.checked : false;
        
        if (cake) decorPrice += 300000;
        if (flower) decorPrice += 200000;

        let addonTxt = decorName;
        if (cake) addonTxt += ' + Bánh';
        if (flower) addonTxt += ' + Hoa';
        let addonSum = document.getElementById('m-addon-sum');
        if (addonSum) addonSum.textContent = addonTxt;
    }

    var bespokePrice = 0;
    
    // Tính ngân sách thiết kế riêng (nếu có)
    var budgetSel = document.getElementById('chef_budget');
    var isBespokeMenu = document.getElementById('is_bespoke_menu') ? document.getElementById('is_bespoke_menu').value : '0';
    if (budgetSel && isBespokeMenu === '1' && budgetSel.options && budgetSel.selectedIndex >= 0) {
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
    
    var discountPercent = <?= $ms_discount_percent > 0 ? floatval($ms_discount_percent) : 0 ?>;
    var discountAmount = 0;
    if (discountPercent > 0) {
        discountAmount = total * (discountPercent / 100);
        total = total - discountAmount;
        var sMsDiscount = document.getElementById('s-ms-discount');
        if (sMsDiscount) {
            sMsDiscount.textContent = '-' + discountAmount.toLocaleString('vi-VN') + ' đ';
        }
    }
    
    // Giảm 10% nếu là Sinh Nhật (sau khi trừ Milestone)
    var isBirthday = <?= (isset($is_birthday) && $is_birthday) ? 'true' : 'false' ?>;
    if (isBirthday && total > 0) {
        var bdDiscountAmount = total * 0.10;
        total = total - bdDiscountAmount;
        var sBdDiscount = document.getElementById('s-bd-discount');
        if (sBdDiscount) {
            sBdDiscount.textContent = '-' + bdDiscountAmount.toLocaleString('vi-VN') + ' đ';
        }
    }
    
    // Cập nhật số tiền đặt cọc 30%
    var btnGo = document.getElementById('btn-go');
    if (sid === -1 && total === 0) {
        document.getElementById('sdep').innerHTML = '<span style="font-size:1.2rem; color:var(--accent-burgundy);">Liên hệ báo giá cọc</span>';
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

    // Cập nhật ảnh xem trước cho Sự Kiện
    var evtSel = document.getElementById('event_type');
    var evtImgWrap = document.getElementById('event_img_wrap');
    var evtImgPreview = document.getElementById('event_img_preview');
    if (evtSel && evtSel.options.length > 0 && evtSel.selectedIndex >= 0) {
        var evtImgSrc = evtSel.options[evtSel.selectedIndex].getAttribute('data-img');
        if (evtImgSrc && evtImgSrc.trim() !== '') {
            evtImgPreview.src = evtImgSrc;
            evtImgWrap.style.display = 'block';
        } else {
            evtImgWrap.style.display = 'none';
        }
    }

    // Cập nhật ảnh xem trước cho Gói Trang Trí
    var dcrSel = document.getElementById('decor_package');
    var dcrImgWrap = document.getElementById('decor_img_wrap');
    var dcrImgPreview = document.getElementById('decor_img_preview');
    if (dcrSel && dcrSel.options.length > 0 && dcrSel.selectedIndex >= 0) {
        var dcrImgSrc = dcrSel.options[dcrSel.selectedIndex].getAttribute('data-img');
        if (dcrImgSrc && dcrImgSrc.trim() !== '') {
            dcrImgPreview.src = dcrImgSrc;
            dcrImgWrap.style.display = 'block';
        } else {
            dcrImgWrap.style.display = 'none';
        }
    }
    } catch (e) {
        console.error("Lỗi trong hàm us():", e);
    }
}

/* KHỞI TẠO CÁC SỰ KIỆN VÀ AJAX CHUYỂN TAB */
function initBookingEvents() {
    var bkForm = document.getElementById('bk-form');
    if (bkForm) {
        bkForm.addEventListener('submit',function(){
            var b=document.getElementById('btn-go');
            if (b) {
                b.style.pointerEvents = 'none';
                b.style.opacity = '0.7';
            }
            var btnTxt = document.getElementById('btn-txt');
            if (btnTxt) btnTxt.style.display='none';
            var btnSpin = document.getElementById('btn-spin');
            if (btnSpin) {
                btnSpin.style.display='inline-block';
                btnSpin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý thanh toán...';
            }
        });
    }

    var inp=document.getElementById('bd');
    if(inp) {
        var now=new Date(); now.setHours(now.getHours()+2);
        inp.min=now.toISOString().slice(0,16);
    }
    
    // Xử lý chuyển tab mượt mà bằng AJAX (PJAX)
    document.querySelectorAll('.svc-card-inline').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var url = this.getAttribute('href').split('#')[0];
            var formArea = document.getElementById('booking-form-area');
            
            // Hiệu ứng mờ đi khi đang tải
            formArea.style.transition = 'opacity 0.3s ease';
            formArea.style.opacity = '0.5';
            formArea.style.pointerEvents = 'none';
            
            fetch(url)
            .then(response => response.text())
            .then(html => {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newFormArea = doc.getElementById('booking-form-area');
                
                if (newFormArea) {
                    formArea.innerHTML = newFormArea.innerHTML;
                    
                    // Reset biến toàn cục của Wizard
                    currentStep = 1;
                    
                    // Khởi tạo lại các sự kiện cho HTML mới
                    initBookingEvents();
                    if (typeof us === 'function') us();
                    
                    // Đổi URL trên thanh địa chỉ mà không reload
                    window.history.pushState({path: url}, '', url);
                }
                
                // Hiện rõ lại
                formArea.style.opacity = '1';
                formArea.style.pointerEvents = 'auto';
            })
            .catch(err => {
                // Nếu lỗi thì fallback về chuyển trang bình thường
                window.location.href = url;
            });
        });
    });
}

// Chạy lần đầu khi load trang
initBookingEvents();
if (typeof us === 'function') us();

// Xử lý nút Lùi/Tiến của trình duyệt (Browser Back/Forward)
window.addEventListener('popstate', function(e) {
    var url = window.location.href;
    var formArea = document.getElementById('booking-form-area');
    if (!formArea) {
        window.location.reload();
        return;
    }
    
    formArea.style.transition = 'opacity 0.3s ease';
    formArea.style.opacity = '0.5';
    formArea.style.pointerEvents = 'none';
    
    fetch(url)
    .then(response => response.text())
    .then(html => {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newFormArea = doc.getElementById('booking-form-area');
        
        if (newFormArea) {
            formArea.innerHTML = newFormArea.innerHTML;
            currentStep = 1;
            initBookingEvents();
            if (typeof us === 'function') us();
        } else {
            window.location.reload();
        }
        
        formArea.style.opacity = '1';
        formArea.style.pointerEvents = 'auto';
    })
    .catch(err => {
        window.location.reload();
    });
});
</script>

<?php include 'views/client/layouts/footer.php'; ?>

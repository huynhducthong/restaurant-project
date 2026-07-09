<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

$all_categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Active Themes
$active_themes = $db->query("SELECT * FROM themes WHERE is_active = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($active_themes as &$t) {
    $t_combos = $db->prepare("SELECT c.*, GROUP_CONCAT(f.name SEPARATOR '|') as list_foods FROM combos c LEFT JOIN combo_items ci ON c.id = ci.combo_id LEFT JOIN foods f ON ci.food_id = f.id WHERE c.theme_id = ? AND c.is_active = 1 GROUP BY c.id");
    $t_combos->execute([$t['id']]);
    $t['combos'] = $t_combos->fetchAll(PDO::FETCH_ASSOC);
    
    $t_foods = $db->prepare("SELECT f.*, c.name as cat_name, (SELECT GROUP_CONCAT(CONCAT(i.item_name, ',', IFNULL(i.category, '')) SEPARATOR ',') FROM food_recipes fr JOIN inventory i ON fr.ingredient_id = i.id WHERE fr.food_id = f.id) as recipe_ingredients, (SELECT GROUP_CONCAT(CONCAT(tp.name, ' (+', FORMAT(tp.price, 0), 'đ)') SEPARATOR ' | ') FROM food_toppings ft JOIN toppings tp ON ft.topping_id = tp.id WHERE ft.food_id = f.id AND tp.status=1) as list_toppings FROM foods f LEFT JOIN categories c ON f.category_id = c.id WHERE f.theme_id = ? AND f.is_active = 1");
    $t_foods->execute([$t['id']]);
    $t['foods'] = $t_foods->fetchAll(PDO::FETCH_ASSOC);
}
unset($t);

$all_foods      = $db->query("SELECT f.*, c.name as cat_name, (SELECT GROUP_CONCAT(CONCAT(i.item_name, ',', IFNULL(i.category, '')) SEPARATOR ',') FROM food_recipes fr JOIN inventory i ON fr.ingredient_id = i.id WHERE fr.food_id = f.id) as recipe_ingredients, (SELECT GROUP_CONCAT(CONCAT(tp.name, ' (+', FORMAT(tp.price, 0), 'đ)') SEPARATOR ' | ') FROM food_toppings ft JOIN toppings tp ON ft.topping_id = tp.id WHERE ft.food_id = f.id AND tp.status=1) as list_toppings FROM foods f LEFT JOIN categories c ON f.category_id = c.id WHERE f.is_active = 1 AND (f.theme_id IS NULL OR f.theme_id = 0) ORDER BY f.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$chef_foods     = $db->query("SELECT f.*, c.name as cat_name, (SELECT GROUP_CONCAT(CONCAT(i.item_name, ',', IFNULL(i.category, '')) SEPARATOR ',') FROM food_recipes fr JOIN inventory i ON fr.ingredient_id = i.id WHERE fr.food_id = f.id) as recipe_ingredients, (SELECT GROUP_CONCAT(CONCAT(tp.name, ' (+', FORMAT(tp.price, 0), 'đ)') SEPARATOR ' | ') FROM food_toppings ft JOIN toppings tp ON ft.topping_id = tp.id WHERE ft.food_id = f.id AND tp.status=1) as list_toppings FROM foods f LEFT JOIN categories c ON f.category_id = c.id WHERE f.is_active = 1 AND f.is_chef_recommended = 1 AND (f.theme_id IS NULL OR f.theme_id = 0) ORDER BY f.id DESC")->fetchAll(PDO::FETCH_ASSOC);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_allergies = [];
$user_flavor = [];
$user_fav = [];
$user_dislikes = [];
$user_history_counts = [];

if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT allergies, flavor_profile, fav_ingredients, disliked_ingredients FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        if ($u['allergies']) $user_allergies = array_map('trim', explode(',', mb_strtolower($u['allergies'], 'UTF-8')));
        if ($u['flavor_profile']) $user_flavor = array_map('trim', explode(',', mb_strtolower($u['flavor_profile'], 'UTF-8')));
        if ($u['fav_ingredients']) $user_fav = array_map('trim', explode(',', mb_strtolower($u['fav_ingredients'], 'UTF-8')));
        if ($u['disliked_ingredients']) $user_dislikes = array_map('trim', explode(',', mb_strtolower($u['disliked_ingredients'], 'UTF-8')));
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

function hasAllergen($food, $user_allergies) {
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

function hasDislike($food, $user_dislikes) {
    if (empty($user_dislikes)) return false;
    $all_food_ingredients = ($food['allergens'] ?? '') . ',' . ($food['recipe_ingredients'] ?? '') . ',' . ($food['cat_name'] ?? '');
    $food_ingredients = array_map('trim', explode(',', mb_strtolower($all_food_ingredients, 'UTF-8')));
    foreach($user_dislikes as $ud) {
        foreach($food_ingredients as $fi) {
            if (!empty($fi) && strpos($fi, $ud) !== false) return true;
        }
    }
    return false;
}

function removeVietnameseAccents($str) {
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

foreach ($all_foods as &$f) {
    $score = 0;
    $f_tags = removeVietnameseAccents($f['tags'] ?? '');
    $f_ingr = removeVietnameseAccents($f['ingredients'] ?? '');
    $f_name = removeVietnameseAccents($f['name'] ?? '');

    foreach ($user_flavor as $flav) {
        $flav = removeVietnameseAccents($flav);
        if (!empty($flav) && (strpos($f_tags, $flav) !== false || strpos($f_name, $flav) !== false || strpos($f_ingr, $flav) !== false)) {
            $score += 2;
        }
    }
    foreach ($user_fav as $fav) {
        $fav = removeVietnameseAccents($fav);
        if (!empty($fav) && (strpos($f_ingr, $fav) !== false || strpos($f_name, $fav) !== false || strpos($f_tags, $fav) !== false)) {
            $score += 3;
        }
    }

    // Cộng điểm dựa trên Lịch sử gọi món (Tần suất)
    if (isset($user_history_counts[$f['id']])) {
        $history_score = min(10, $user_history_counts[$f['id']] * 2);
        $score += $history_score;
    }

    $f['ai_score'] = $score;
}
unset($f);

usort($all_foods, function($a, $b) {
    if ($a['ai_score'] == $b['ai_score']) return $b['id'] <=> $a['id'];
    return $b['ai_score'] <=> $a['ai_score'];
});

include __DIR__ . '/views/client/layouts/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
/* === EDITORIAL FINE DINING VARIABLES === */
:root {
  --bg-color: #F9F9F9;       /* Cream */
  --text-main: #222222;      /* Dark Gray */
  --text-muted: #777777;     /* Light Gray for descriptions */
  --accent-burgundy: #A88746;          /* Olive Green */
  --accent-burgundy: #A88746;           /* Gold Accent */
  --font-serif: 'Cormorant Garamond', serif;
  --font-sans: 'Source Sans 3', sans-serif;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body { background-color: var(--bg-color); color: var(--text-main); font-family: var(--font-sans); line-height: 1.6; overflow-x: hidden; }
::selection { background: var(--accent-burgundy); color: #fff; }

/* === HERO SECTION === */
.editorial-hero {
  position: relative;
  height: 60vh;
  min-height: 450px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  border-bottom: 1px solid rgba(168, 135, 70,0.1);
}
.editorial-hero-bg {
  position: absolute;
  inset: 0;
  background-image: url('public/assets/img/hero/e1ed0ad828fff1d6a15d.jpg'); /* Default hero image */
  background-size: cover;
  background-position: center;
  filter: grayscale(0.2) opacity(0.8);
  z-index: 0;
}
.editorial-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 100%);
  z-index: 1;
}
.editorial-hero-content {
  position: relative;
  z-index: 2;
  padding: 0 20px;
  max-width: 1000px;
}
.eyebrow {
  display: block;
  font-family: var(--font-sans);
  font-size: 11px;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--accent-burgundy);
  margin-bottom: 20px;
}
.editorial-hero h1 {
  font-family: var(--font-serif);
  font-size: clamp(3rem, 6vw, 4.5rem);
  font-weight: 300;
  color: #ffffff;
  line-height: 1.1;
  margin-bottom: 20px;
}
.editorial-hero h1 em {
  font-style: italic;
  color: var(--accent-burgundy);
}
.editorial-hero p {
  font-family: var(--font-serif);
  font-size: 1.3rem;
  color: rgba(255,255,255,0.85);
  font-style: italic;
  line-height: 1.8;
  padding: 0 5%;
}

/* === MENU CONTAINER === */
.editorial-menu-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 100px 20px;
  background-color: var(--bg-color);
}

/* === SECTION HEADERS === */
.menu-section { margin-bottom: 120px; }
.menu-section-title {
  text-align: center;
  font-family: var(--font-serif);
  font-size: 2.8rem;
  font-weight: 400;
  color: var(--accent-burgundy);
  margin-bottom: 10px;
  text-transform: uppercase;
  letter-spacing: 2px;
}
.menu-section-subtitle {
  text-align: center;
  font-family: var(--font-sans);
  font-size: 11px;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--accent-burgundy);
  margin-bottom: 60px;
}

/* === TASTING MENU (COMBOS) === */
.tasting-grid {
  display: flex;
  flex-direction: column;
  gap: 60px;
  align-items: center;
}
.tasting-course {
  text-align: center;
  max-width: 500px;
}
.tasting-name {
  font-family: var(--font-serif);
  font-size: 1.8rem;
  font-weight: 400;
  color: var(--text-main);
  margin-bottom: 15px;
  letter-spacing: 1px;
}
.tasting-desc {
  font-family: var(--font-serif);
  font-size: 1.1rem;
  font-style: italic;
  color: var(--text-muted);
  line-height: 1.8;
  margin-bottom: 20px;
}
.tasting-price {
  font-family: var(--font-sans);
  font-size: 14px;
  font-weight: 500;
  color: var(--accent-burgundy);
  letter-spacing: 2px;
}

/* === DIVIDER === */
.menu-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 80px 0;
  opacity: 0.5;
}
.menu-divider::before, .menu-divider::after {
  content: '';
  height: 1px;
  width: 100px;
  background-color: var(--accent-burgundy);
}
.menu-divider .diamond {
  width: 8px;
  height: 8px;
  background-color: var(--accent-burgundy);
  transform: rotate(45deg);
  margin: 0 15px;
}

/* === MÓN LẺ (FOODS) === */
.menu-category {
  margin-bottom: 100px;
  display: flex;
  align-items: flex-start; /* Sửa từ center sang flex-start để sticky hoạt động */
  gap: 60px;
}
.menu-category.image-right {
  flex-direction: row-reverse;
}
.category-image-wrap {
  flex: 0 0 40%;
  height: 550px;
  position: sticky;
  top: 100px; /* Trượt theo cuộn chuột */
}
.category-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: grayscale(0.1) contrast(1.05);
}
.category-content-wrap {
  flex: 1;
}
.category-title {
  text-align: left;
  font-family: var(--font-serif);
  font-size: 2.2rem;
  font-weight: 600;
  font-style: italic;
  color: var(--accent-burgundy);
  margin-bottom: 30px;
  border-bottom: 1px solid rgba(168, 135, 70,0.1);
  padding-bottom: 15px;
}
.menu-list {
  display: flex;
  flex-direction: column;
  gap: 25px;
}
.menu-item {
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: opacity 0.3s ease;
  padding: 10px 0;
}
.menu-item:hover {
  opacity: 0.7;
}
.allergy-item {
  opacity: 0.5;
}
.menu-item-header {
  display: flex;
  align-items: baseline;
  margin-bottom: 5px;
}
.menu-item-name {
  font-family: var(--font-serif);
  font-size: 1.7rem;
  font-weight: 600;
  color: var(--text-main);
  background: var(--bg-color);
  padding-right: 15px;
  z-index: 2;
}
.menu-item-dots {
  flex-grow: 1;
  border-bottom: 1px dotted rgba(168, 135, 70,0.4);
  margin: 0 10px;
  position: relative;
  top: -6px;
  z-index: 1;
}
.menu-item-price {
  font-family: var(--font-sans);
  font-size: 14px;
  color: var(--accent-burgundy);
  background: var(--bg-color);
  padding-left: 15px;
  z-index: 2;
  font-weight: 500;
  letter-spacing: 1px;
}
.menu-item-desc {
  font-family: var(--font-serif);
  font-size: 1.25rem;
  font-weight: 500;
  color: var(--text-muted);
  font-style: italic;
  max-width: 85%;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* === BUTTON === */
.btn-reserve-solid {
  display: inline-block;
  padding: 16px 40px;
  background: var(--accent-burgundy);
  color: #fff;
  font-family: var(--font-sans);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 3px;
  text-transform: uppercase;
  text-decoration: none;
  border: 1px solid var(--accent-burgundy);
  transition: all 0.4s ease;
  border-radius: 0;
}
.btn-reserve-solid:hover {
  background: transparent;
  color: var(--accent-burgundy);
}

/* === MODAL MINIMALIST === */
.ed-modal {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(10, 10, 12, 0.85);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; pointer-events: none; transition: opacity 0.6s ease;
  backdrop-filter: blur(8px);
}
.ed-modal.open { opacity: 1; pointer-events: all; }

.ed-modal-box {
  background: #FFFFFF;
  width: 100%; max-width: 800px;
  display: flex;
  box-shadow: 0 30px 60px rgba(0,0,0,0.5);
  transform: translateY(30px); transition: transform 0.6s ease;
  z-index: 1;
  position: relative;
  overflow: hidden;
}

.ed-modal.open .ed-modal-box { transform: translateY(0); }

.ed-modal-img {
  flex: 0 0 50%;
  position: relative;
}


.ed-modal-img img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
.ed-modal-content {
  flex: 1; padding: 50px 40px; position: relative;
  display: flex; flex-direction: column; justify-content: center;
}
.ed-modal-close {
  position: absolute; top: 20px; right: 20px;
  background: none; border: none; font-size: 24px; color: var(--text-muted); cursor: pointer;
  transition: color 0.3s;
}
.ed-modal-close:hover { color: var(--accent-burgundy); }
.ed-m-cat { font-family: var(--font-sans); font-size: 10px; letter-spacing: 3px; text-transform: uppercase; color: var(--accent-burgundy); margin-bottom: 15px; }
.ed-m-name { font-family: var(--font-serif); font-size: 2.4rem; color: var(--text-main); font-weight: 600; line-height: 1.2; margin-bottom: 20px; }
.ed-m-desc { font-family: var(--font-serif); font-size: 1.1rem; color: var(--text-muted); font-style: italic; line-height: 1.8; margin-bottom: 30px; }
.ed-m-price { font-family: var(--font-sans); font-size: 18px; font-weight: 500; color: var(--accent-burgundy); margin-bottom: 30px; letter-spacing: 1px; }

@media (max-width: 768px) {
  .ed-modal-box { flex-direction: column; max-height: 90vh; overflow-y: auto; }
  .ed-modal-img { height: 300px; flex: none; }
  .ed-modal-content { padding: 30px 20px; }
}

/* Responsive */
@media (max-width: 900px) {
  .menu-category { flex-direction: column !important; gap: 30px; }
  .category-image-wrap { width: 100%; height: 350px; flex: auto; }
  .category-title { text-align: center; }
  .editorial-menu-container { padding: 60px 15px; }
  .menu-item-desc { max-width: 100%; }
}
</style>

<!-- Hero Section -->
<section class="editorial-hero">
   <div class="editorial-hero-bg"></div>
   <div class="editorial-hero-overlay"></div>
   <div class="editorial-hero-content">
       <span class="eyebrow">Gastronomy Collection</span>
       <h1>Thực đơn<br><em>Restaurantly</em></h1>
       <p>Một bản giao hưởng của hương vị tinh tế, kết hợp giữa nghệ thuật ẩm thực đương đại và những nguyên liệu thượng hạng&nbsp;nhất.</p>
   </div>
</section>

<!-- Menu Container -->
<div class="editorial-menu-container">

    <?php if (isset($_SESSION['user_id']) && (!empty($user_allergies) || !empty($user_flavor) || !empty($user_fav) || !empty($user_dislikes))): ?>
    <!-- AI Recommendation Section -->
    <div class="menu-section a-la-carte-section" style="margin-bottom: 80px;">
        <h2 class="menu-section-title">🌟 Dành Riêng Cho Bạn</h2>
        <div class="menu-section-subtitle">Gợi Ý Dựa Trên DNA Ẩm Thực Của Bạn</div>
        <div style="background: #fffcf5; border: 1px solid #c8933a; border-radius: 12px; padding: 30px; text-align: center; max-width: 900px; margin: 30px auto 0;">
            <div id="ai-rec-loading">
                <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;"></div>
                <p class="mt-3" style="font-family: var(--font-serif); font-size: 1.2rem; color: var(--accent-burgundy); font-style: italic;">
                    Bếp trưởng đang xem xét khẩu vị của bạn...
                </p>
            </div>
            <div id="ai-rec-content" style="display:none; font-family: var(--font-serif); font-size: 1.15rem; color: var(--text-main); text-align: left; line-height: 1.8;">
                <!-- AI Content Here -->
            </div>
        </div>
    </div>
    <?php endif; ?>


    <!-- Themed Collections -->
    <?php if(!empty($active_themes)): ?>
        <?php foreach($active_themes as $t): ?>
            <?php if(empty($t['combos']) && empty($t['foods'])) continue; ?>
            <div class="menu-section tasting-menu-section" style="margin-bottom: 80px;">
                <h2 class="menu-section-title"><?= htmlspecialchars($t['name']) ?></h2>
                <div class="menu-section-subtitle"><?= htmlspecialchars($t['description']) ?></div>
                
                <?php if(!empty($t['image'])): ?>
                <div style="width: 100%; max-width: 900px; height: 350px; margin: 0 auto 40px auto; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <img src="<?= htmlspecialchars($t['image']) ?>" style="width:100%; height:100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" alt="<?= htmlspecialchars($t['name']) ?>">
                </div>
                <?php endif; ?>

                <div class="menu-list">
                    <?php if(!empty($t['combos'])): ?>
                        <div class="menu-category mt-4" style="display: block;">
                            <div class="category-content-wrap" style="width: 100%; max-width: 100%; padding: 0;">
                                <h3 class="category-title" style="text-align:center; border:none; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">Set Menu (Tasting Menu)</h3>
                                <div class="menu-list" style="margin-top: 30px;">
                                    <?php foreach($t['combos'] as $row): ?>
                                    <div class="menu-item menu-hover-trigger" data-img="public/assets/img/combos/<?= htmlspecialchars($row['image'] ?: 'default-combo.jpg') ?>" onclick="window.location.href='combo_detail.php?id=<?= $row['id'] ?>'" style="cursor:pointer; transition: background 0.3s ease;">
                                        <div class="menu-item-header">
                                            <span class="menu-item-name"><?= htmlspecialchars($row['name']) ?></span>
                                            <span class="menu-item-dots"></span>
                                            <span class="menu-item-price"><?= number_format($row['price'],0,',','.') ?></span>
                                        </div>
                                        <p class="menu-item-desc">
                                            <?= htmlspecialchars($row['description']) ?>
                                            <br><i class="bi bi-star-fill me-1" style="color:#A88746; font-size:9px; margin-top:5px;"></i><span style="font-size:11px; color:#999;"><?= htmlspecialchars(str_replace(',', ' • ', $row['list_foods'])) ?></span>
                                        </p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($t['foods'])): ?>
                        <div class="menu-category mt-5" style="display: block;">
                            <div class="category-content-wrap" style="width: 100%; max-width: 100%; padding: 0;">
                                <h3 class="category-title" style="text-align:center; border:none; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">Món tự chọn</h3>
                                <div class="menu-list" style="margin-top: 30px;">
                                    <?php foreach($t['foods'] as $f): 
                                        $has_al = hasAllergen($f, $user_allergies);
                                        $has_dl = hasDislike($f, $user_dislikes);
                                        $modalData = htmlspecialchars(json_encode([
                                            'name' => $f['name'], 'desc' => $f['description'],
                                            'price' => number_format($f['price'],0,',','.'),
                                            'img' => 'public/assets/img/menu/' . ($f['image'] ?: 'default.jpg'),
                                            'cat' => "Món tự chọn",
                                            'chef_note' => $f['chef_note'] ?? '',
                                            'toppings' => $f['list_toppings'] ?? ''
                                        ]));
                                    ?>
                                    <div class="menu-item menu-hover-trigger <?= $has_al ? 'allergy-item' : '' ?>" 
                                         data-img="public/assets/img/menu/<?= htmlspecialchars($f['image'] ?: 'default-food.jpg') ?>"
                                         onclick="window.location.href='dish.php?id=<?= $f['id'] ?>'" style="cursor:pointer; transition: background 0.3s ease;">
                                        <div class="menu-item-header">
                                            <span class="menu-item-name"><?= htmlspecialchars($f['name']) ?></span>
                                            <span class="menu-item-dots"></span>
                                            <span class="menu-item-price"><?= number_format($f['price'],0,',','.') ?></span>
                                        </div>
                                        <p class="menu-item-desc">
                                            <?= htmlspecialchars($f['description']) ?>
                                        </p>
                                        <div style="margin-top:5px;">
                                            <?php if($has_al): ?>
                                            <span style="color:#d64545; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block;">* Chứa thành phần dị ứng với bạn</span>
                                            <?php elseif($has_dl): ?>
                                            <span style="color:#e67e22; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block;">* Có chứa nguyên liệu bạn không thích</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Divider between themes -->
            <div class="menu-divider"><div class="diamond"></div></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- All Set Menus (Standalone Sets) -->
    <?php 
    // Get sets that are either not in a theme, or in an inactive theme
    $standalone_sets = $db->query("
        SELECT c.*, GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') as list_foods
        FROM combos c
        LEFT JOIN combo_items ci ON c.id = ci.combo_id
        LEFT JOIN foods f ON ci.food_id = f.id
        WHERE c.is_active = 1 
        AND (c.theme_id IS NULL OR c.theme_id NOT IN (
            SELECT id FROM themes WHERE is_active = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW())
        ))
        GROUP BY c.id ORDER BY c.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php if(!empty($standalone_sets)): ?>
    <div class="menu-section a-la-carte-section">
        <h2 class="menu-section-title">Khám Phá Set Menu</h2>
        <div class="menu-section-subtitle">Tasting Menus</div>
        
        <div class="menu-category mt-4" style="display: block;">
            <div class="category-content-wrap" style="width: 100%; max-width: 100%; padding: 0;">
                <div class="menu-list">
                    <?php foreach($standalone_sets as $row): ?>
                    <div class="menu-item menu-hover-trigger" data-img="public/assets/img/combos/<?= htmlspecialchars($row['image'] ?: 'default-combo.jpg') ?>" onclick="window.location.href='combo_detail.php?id=<?= $row['id'] ?>'" style="cursor:pointer; transition: background 0.3s ease;">
                        <div class="menu-item-header">
                            <span class="menu-item-name"><?= htmlspecialchars($row['name']) ?></span>
                            <span class="menu-item-dots"></span>
                            <span class="menu-item-price"><?= number_format($row['price'],0,',','.') ?></span>
                        </div>
                        <p class="menu-item-desc">
                            <?= htmlspecialchars($row['description']) ?>
                            <br><i class="bi bi-star-fill me-1" style="color:#A88746; font-size:9px; margin-top:5px;"></i><span style="font-size:11px; color:#999;"><?= htmlspecialchars(str_replace(',', ' • ', $row['list_foods'])) ?></span>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="menu-divider"><div class="diamond"></div></div>
    <?php endif; ?>

    <!-- Chef's Recommendation -->
    <?php if(!empty($chef_foods)): ?>
    <div class="menu-section a-la-carte-section">
        <h2 class="menu-section-title">Gợi Ý Từ Bếp Trưởng</h2>
        <div class="menu-section-subtitle">Chef's Recommendation</div>
        
        <div class="menu-category">
            <div class="category-image-wrap">
                <img id="cat-img-chef" src="public/assets/img/menu/<?= htmlspecialchars($chef_foods[0]['image']) ?>" class="category-image" onerror="this.onerror=null; this.src='https://placehold.co/800x600/262629/A88746?text=No+Image'" style="transition: opacity 0.15s ease;">
            </div>
            
            <div class="category-content-wrap">
                <h3 class="category-title">Signature Dishes</h3>
                <div class="menu-list">
                    <?php foreach($chef_foods as $f): 
                        $has_al = hasAllergen($f, $user_allergies);
                        $has_dl = hasDislike($f, $user_dislikes);
                        $modalData = htmlspecialchars(json_encode([
                            'name' => $f['name'], 'desc' => $f['description'],
                            'price' => number_format($f['price'],0,',','.'),
                            'img' => 'public/assets/img/menu/' . ($f['image'] ?: 'default.jpg'),
                            'cat' => "Chef's Choice",
                            'chef_note' => $f['chef_note'] ?? '',
                            'toppings' => $f['list_toppings'] ?? ''
                        ]));
                    ?>
                    <div class="menu-item menu-hover-trigger <?= $has_al ? 'allergy-item' : '' ?>" 
                         data-img="public/assets/img/menu/<?= htmlspecialchars($f['image'] ?: 'default-food.jpg') ?>"
                         onmouseenter="changeFeaturedImage('cat-img-chef', 'public/assets/img/menu/<?= htmlspecialchars($f['image'] ?: 'default.jpg') ?>')"
                         onclick="window.location.href='dish.php?id=<?= $f['id'] ?>'" style="cursor:pointer; transition: background 0.3s ease;">
                        <div class="menu-item-header">
                            <span class="menu-item-name"><?= htmlspecialchars($f['name']) ?></span>
                            <span class="menu-item-dots"></span>
                            <span class="menu-item-price"><?= number_format($f['price'],0,',','.') ?></span>
                        </div>
                        <p class="menu-item-desc">
                            <?= htmlspecialchars($f['description']) ?>
                        </p>
                        <div style="margin-top:5px;">
                            <?php if($has_al): ?>
                            <span style="color:#d64545; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block;">* Chứa thành phần dị ứng với bạn</span>
                            <?php elseif($has_dl): ?>
                            <span style="color:#e67e22; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block;">* Có chứa nguyên liệu bạn không thích</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Divider -->
    <div class="menu-divider"><div class="diamond"></div></div>
    <?php endif; ?>

    <!-- Món lẻ -->
    <div class="menu-section a-la-carte-section">
        <h2 class="menu-section-title">MÓN ĂN</h2>
        <div class="menu-section-subtitle">Tuyển chọn nghệ thuật</div>
        
        <?php 
        $cat_index = 0;
        foreach($all_categories as $cat): 
            $cat_foods = array_filter($all_foods, function($f) use ($cat) {
                return $f['category_id'] == $cat['id'];
            });
            if(empty($cat_foods)) continue;
            
            // Lấy ảnh của món đầu tiên làm ảnh đại diện cho Category
            $first_food = array_values($cat_foods)[0];
            $cat_image = $first_food['image'] ? $first_food['image'] : 'default.jpg';
        ?>
        <div class="menu-category <?= $cat_index % 2 != 0 ? 'image-right' : '' ?>">
            <div class="category-image-wrap">
                <img id="cat-img-<?= $cat['id'] ?>" src="public/assets/img/menu/<?= htmlspecialchars($cat_image) ?>" class="category-image" onerror="this.onerror=null; this.src='https://placehold.co/800x600/262629/A88746?text=No+Image'" style="transition: opacity 0.15s ease;">
            </div>
            
            <div class="category-content-wrap">
                <h3 class="category-title"><?= htmlspecialchars($cat['name']) ?></h3>
                <div class="menu-list">
                    <?php foreach($cat_foods as $f): 
                        $has_al = hasAllergen($f, $user_allergies);
                        $has_dl = hasDislike($f, $user_dislikes);
                        
                        $modalData = htmlspecialchars(json_encode([
                            'name' => $f['name'],
                            'desc' => $f['description'],
                            'price' => number_format($f['price'],0,',','.'),
                            'img' => 'public/assets/img/menu/' . ($f['image'] ?: 'default.jpg'),
                            'cat' => $cat['name'],
                            'chef_note' => $f['chef_note'] ?? '',
                            'toppings' => $f['list_toppings'] ?? ''
                        ]));
                    ?>
                    <div class="menu-item <?= $has_al ? 'allergy-item' : '' ?>" 
                         onmouseenter="changeFeaturedImage('cat-img-<?= $cat['id'] ?>', 'public/assets/img/menu/<?= htmlspecialchars($f['image'] ?: 'default.jpg') ?>')"
                         onclick="window.location.href='dish.php?id=<?= $f['id'] ?>'">
                        <div class="menu-item-header">
                            <span class="menu-item-name"><?= htmlspecialchars($f['name']) ?></span>
                            <span class="menu-item-dots"></span>
                            <span class="menu-item-price"><?= number_format($f['price'],0,',','.') ?></span>
                        </div>
                        <p class="menu-item-desc">
                            <?= htmlspecialchars($f['description']) ?>
                        </p>
                        <div style="margin-top:5px;">
                            <?php if($has_al): ?>
                            <span style="color:#d64545; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block;">* Chứa thành phần dị ứng với bạn</span>
                            <?php elseif($has_dl): ?>
                            <span style="color:#e67e22; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block;">* Có chứa nguyên liệu bạn không thích</span>
                            <?php endif; ?>
                            <?php 
                                $is_hist = isset($user_history_counts[$f['id']]);
                                $flav_score = isset($f['ai_score']) ? $f['ai_score'] - ($is_hist ? min(10, $user_history_counts[$f['id']] * 2) : 0) : 0;
                            ?>
                            <?php if($is_hist): ?>
                            <span style="color:#17a2b8; font-size:13px; font-weight:500; font-family:var(--font-sans); font-style:normal; margin-left:10px;"><i class="fas fa-history"></i> Món quen</span>
                            <?php endif; ?>
                            <?php if($flav_score > 0): ?>
                            <span style="color:var(--accent-burgundy); font-size:13px; font-weight:500; font-family:var(--font-sans); font-style:normal; margin-left:10px;"><i class="fas fa-star"></i> Gợi ý</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php 
        $cat_index++;
        endforeach; 
        ?>
    </div>

    <!-- Call to Action -->
    <div class="text-center mt-5 mb-5" style="padding-top: 50px;">
        <p style="font-family:var(--font-serif); font-size:1.3rem; color:var(--text-main); margin-bottom:25px; font-style:italic;">Khám phá trọn vẹn hương vị tại không gian của chúng tôi.</p>
        <a href="booking_service.php" class="btn-reserve-solid">ĐẶT BÀN NGAY</a>
    </div>

</div>

<!-- EDITORIAL MODAL -->
<div class="ed-modal" id="edModal" onclick="closeEdModal(event)">
  <div class="ed-modal-box">
    <div class="ed-modal-img">
      <img id="ed-m-img" src="" alt="" onerror="this.onerror=null; this.src='https://placehold.co/800x600/262629/A88746?text=No+Image'">

    </div>
    <div class="ed-modal-content">
      <button class="ed-modal-close" onclick="closeEdModal(null)">✕</button>
      <div class="ed-m-cat" id="ed-m-cat"></div>
      <h2 class="ed-m-name" id="ed-m-name"></h2>
      <p class="ed-m-desc" id="ed-m-desc"></p>
      
      <!-- Chef Note Block - Premium Redesign -->
      <div id="ed-m-chef-note-wrap" style="display:none; margin: 25px 0 20px; position: relative; background: #fffcf8; border: 1px solid #efe3c8; padding: 25px 25px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
        <div style="position: absolute; top: -12px; left: 20px; background: #fffcf8; padding: 0 10px; color: var(--accent-gold, #c9a66b); font-size: 20px;">
            <i class="fas fa-quote-left"></i>
        </div>
        <div style="font-family:'Cormorant Garamond', serif; font-size:14px; font-weight:700; color:var(--accent-burgundy, #800020); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; text-align: center;">
            Câu chuyện của Bếp trưởng
        </div>
        <div id="ed-m-chef-note" style="font-family:'Cormorant Garamond', serif; font-size:16px; color:#555; line-height:1.7; font-style:italic; text-align: center; margin-bottom: 15px;"></div>
        <div style="text-align: center;">
            <hr style="width: 50px; margin: 0 auto 10px; border-color: var(--accent-gold, #c9a66b); opacity: 0.5;">
            <span style="font-family:'Cormorant Garamond', serif; font-size: 14px; color: var(--accent-gold, #c9a66b); font-weight: 600; font-style: italic;">— Executive Chef —</span>
        </div>
      </div>
      
      <div class="ed-m-price" id="ed-m-price"></div>
      <div class="ed-m-toppings" id="ed-m-toppings" style="font-size:12px; color:var(--text-muted); margin-bottom:15px; font-style:italic;"></div>
      <a href="booking_service.php" class="btn-reserve-solid" style="width: fit-content;">ĐẶT BÀN NGAY</a>
    </div>
  </div>
</div>
  <img id="hoverImageTooltip" class="menu-hover-tooltip" src="" alt="">
<style>
.menu-hover-tooltip {
    position: absolute;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    object-fit: cover;
    z-index: 9999;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s ease, transform 0.2s ease;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    border: 4px solid #fff;
    transform: translate(15px, -50%) scale(0.95);
}
.menu-item:hover {
    background: rgba(168, 135, 70, 0.03);
}
.menu-hover-trigger:hover .menu-item-name {
    color: var(--accent-burgundy);
    transition: color 0.3s ease;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tooltip = document.getElementById('hoverImageTooltip');
    if (tooltip) {
        tooltip.onerror = function() {
            this.src = 'https://placehold.co/800x600/262629/A88746?text=No+Image';
        };
    }
    const triggers = document.querySelectorAll('.menu-hover-trigger');
    
    triggers.forEach(trigger => {
        trigger.addEventListener('mousemove', function(e) {
            tooltip.src = this.getAttribute('data-img');
            tooltip.style.left = e.pageX + 'px';
            tooltip.style.top = e.pageY + 'px';
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'translate(15px, -50%) scale(1)';
        });
        trigger.addEventListener('mouseleave', function() {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translate(15px, -50%) scale(0.95)';
        });
    });
});
</script>

<script>
let imageTimeoutIds = {};
function changeFeaturedImage(imgId, newSrc) {
    var imgEl = document.getElementById(imgId);
    if (!imgEl) return;
    if (imgEl.src.indexOf(newSrc) !== -1) return; // Do nothing if same image
    
    if (imageTimeoutIds[imgId]) {
        clearTimeout(imageTimeoutIds[imgId]);
    }
    
    imgEl.style.opacity = 0.6;
    imageTimeoutIds[imgId] = setTimeout(function() {
        imgEl.onerror = function() {
            this.onerror = null;
            this.src = 'https://placehold.co/800x600/262629/A88746?text=No+Image';
        };
        imgEl.src = newSrc;
        imgEl.style.opacity = 1;
    }, 150); // Fast enough to not feel laggy
}

function openEdModal(data) {
    document.getElementById('ed-m-img').src = data.img;
    document.getElementById('ed-m-cat').textContent = data.cat;
    document.getElementById('ed-m-name').textContent = data.name;
    document.getElementById('ed-m-desc').textContent = data.desc;
    
    var chefNoteWrap = document.getElementById('ed-m-chef-note-wrap');
    if (data.chef_note && data.chef_note.trim() !== '') {
        chefNoteWrap.style.display = 'block';
        document.getElementById('ed-m-chef-note').textContent = data.chef_note;
    } else {
        chefNoteWrap.style.display = 'none';
    }

    document.getElementById('ed-m-price').textContent = data.price + ' VND';
    
    var topEl = document.getElementById('ed-m-toppings');
    topEl.style.display = 'none';
    topEl.innerHTML = '';
    
    document.getElementById('edModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    

}

function closeEdModal(e) {
    if(e && e.target !== document.getElementById('edModal')) return;
    document.getElementById('edModal').classList.remove('open');
    document.body.style.overflow = '';
    

}
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') closeEdModal(null);
});

document.addEventListener('DOMContentLoaded', function() {
    const loadingEl = document.getElementById('ai-rec-loading');
    const contentEl = document.getElementById('ai-rec-content');
    
    if (loadingEl && contentEl) {
        fetch('ajax/ajax_ai_menu.php')
            .then(response => response.json())
            .then(res => {
                loadingEl.style.display = 'none';
                if (res.status === 'success') {
                    let html = res.data;
                    html = html.replace(/\*\*(.*?)\*\*/g, '<strong style="color:var(--accent-burgundy);">$1</strong>');
                    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
                    html = html.replace(/\n/g, '<br>');
                    contentEl.innerHTML = html;
                    
                    // Fade in effect
                    contentEl.style.opacity = 0;
                    contentEl.style.display = 'block';
                    let opacity = 0;
                    let timer = setInterval(function() {
                        if (opacity >= 1) clearInterval(timer);
                        contentEl.style.opacity = opacity;
                        opacity += 0.1;
                    }, 20);
                } else {
                    contentEl.innerHTML = '<p style="color:red; text-align:center;">' + res.message + '</p>';
                    contentEl.style.display = 'block';
                }
            })
            .catch(error => {
                loadingEl.style.display = 'none';
                contentEl.innerHTML = '<p style="color:red; text-align:center;">Lỗi kết nối tới hệ thống AI.</p>';
                contentEl.style.display = 'block';
            });
    }
});
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
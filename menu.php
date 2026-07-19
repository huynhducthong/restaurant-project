<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

$all_categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

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
$user_nutrition_goals = [];
$user_history_counts = [];

if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT allergies, flavor_profile, fav_ingredients, disliked_ingredients, nutrition_goals FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        if ($u['allergies']) $user_allergies = array_map('trim', explode(',', mb_strtolower($u['allergies'], 'UTF-8')));
        if ($u['flavor_profile']) $user_flavor = array_map('trim', explode(',', mb_strtolower($u['flavor_profile'], 'UTF-8')));
        if ($u['fav_ingredients']) $user_fav = array_map('trim', explode(',', mb_strtolower($u['fav_ingredients'], 'UTF-8')));
        if ($u['disliked_ingredients']) $user_dislikes = array_map('trim', explode(',', mb_strtolower($u['disliked_ingredients'], 'UTF-8')));
        if (!empty($u['nutrition_goals'])) $user_nutrition_goals = array_map('trim', explode(',', $u['nutrition_goals']));
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
        
        // Expand aliases if the user's sentence contains an alias keyword
        foreach ($aliases as $key => $values) {
            if (mb_strpos($ua, $key, 0, 'UTF-8') !== false) {
                $check_terms = array_merge($check_terms, $values);
                $check_terms[] = $key;
            }
        }
        
        foreach($food_allergens as $fa) {
            if (empty($fa)) continue;
            foreach ($check_terms as $term) {
                if (mb_strpos($fa, $term, 0, 'UTF-8') !== false) {
                    return true;
                }
                
                if (mb_strlen($fa, 'UTF-8') > 1) {
                    $pattern = '/(?<=^|\s)' . preg_quote($fa, '/') . '(?=\s|$|[.,!?])/iu';
                    if (preg_match($pattern, $term)) {
                        return true;
                    }
                }
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

    // Khớp mục tiêu dinh dưỡng
    $matched_nutrition = null;
    $nf = json_decode($f['nutrition_facts'] ?? '{}', true);
    if (!empty($user_nutrition_goals) && is_array($nf)) {
        foreach ($user_nutrition_goals as $goal) {
            $goal_lower = mb_strtolower($goal, 'UTF-8');
            $cal = isset($nf['calories']) ? floatval($nf['calories']) : 0;
            $carbs = isset($nf['carbs']) ? floatval($nf['carbs']) : 0;
            $pro = isset($nf['protein']) ? floatval($nf['protein']) : 0;
            $fat = isset($nf['fat']) ? floatval($nf['fat']) : 0;
            
            if (strpos($goal_lower, 'eat clean') !== false && $cal > 0 && $cal < 500 && $fat < 15) {
                $matched_nutrition = 'Eat Clean'; $score += 3; break;
            }
            if (strpos($goal_lower, 'keto') !== false && $carbs > 0 && $carbs < 15) {
                $matched_nutrition = 'Keto (Low Carb)'; $score += 3; break;
            }
            if (strpos($goal_lower, 'ít calo') !== false && $cal > 0 && $cal < 350) {
                $matched_nutrition = 'Ít Calo'; $score += 3; break;
            }
            if (strpos($goal_lower, 'tăng cơ') !== false && $pro > 25) {
                $matched_nutrition = 'Tăng cơ'; $score += 3; break;
            }
            if (strpos($goal_lower, 'ít béo') !== false && $fat > 0 && $fat < 10) {
                $matched_nutrition = 'Ít béo'; $score += 3; break;
            }
            if (strpos($goal_lower, 'chay') !== false) {
                $f_ingr_str = strtolower($f_name . ' ' . $f_ingr . ' ' . $f_tags);
                if (strpos($f_ingr_str, 'bò') === false && strpos($f_ingr_str, 'thịt') === false && strpos($f_ingr_str, 'gà') === false && strpos($f_ingr_str, 'cá') === false && strpos($f_ingr_str, 'hải sản') === false) {
                    $matched_nutrition = 'Ăn chay'; $score += 3; break;
                }
            }
        }
    }
    
    $f['matched_nutrition'] = $matched_nutrition;
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
                                            'toppings' => $f['list_toppings'] ?? '',
                                            'allergens' => $f['allergens'] ?? ''
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
                                        <div style="margin-top:5px; display: flex; flex-wrap: wrap; align-items: center;">
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
                            'toppings' => $f['list_toppings'] ?? '',
                            'allergens' => $f['allergens'] ?? ''
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
                        <div style="margin-top:5px; display: flex; flex-wrap: wrap; align-items: center;">
                            <?php if($has_al): ?>
                            <span style="color:#d64545; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block; margin-right: 12px; margin-top: 4px;">* Chứa thành phần dị ứng với bạn</span>
                            <?php elseif($has_dl): ?>
                            <span style="color:#e67e22; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block; margin-right: 12px; margin-top: 4px;">* Có chứa nguyên liệu bạn không thích</span>
                            <?php endif; ?>
                            <?php if(!empty($f['matched_nutrition'])): ?>
                            <span style="color:#28a745; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block; margin-right: 12px; margin-top: 4px;"><i class="fas fa-leaf"></i> Phù hợp chế độ <?= htmlspecialchars($f['matched_nutrition']) ?></span>
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
                            'toppings' => $f['list_toppings'] ?? '',
                            'allergens' => $f['allergens'] ?? ''
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
                        <div style="margin-top:5px; display: flex; flex-wrap: wrap; align-items: center;">
                            <?php if($has_al): ?>
                            <span style="color:#d64545; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block; margin-right: 12px; margin-top: 4px;">* Chứa thành phần dị ứng với bạn</span>
                            <?php elseif($has_dl): ?>
                            <span style="color:#e67e22; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block; margin-right: 12px; margin-top: 4px;">* Có chứa nguyên liệu bạn không thích</span>
                            <?php endif; ?>
                            <?php if(!empty($f['matched_nutrition'])): ?>
                            <span style="color:#28a745; font-size:12px; font-weight:600; font-family:var(--font-sans); font-style:normal; display:inline-block; margin-right: 12px; margin-top: 4px;"><i class="fas fa-leaf"></i> Phù hợp chế độ <?= htmlspecialchars($f['matched_nutrition']) ?></span>
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
      <div id="ed-m-allergens" style="font-size:13px; color:var(--accent-burgundy, #800020); margin-bottom:5px; font-weight: 500; display:none;"></div>
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
let currentCurrency = 'VND';
const exchangeRateVndToUsd = 25400; // Tỷ giá quy đổi

document.addEventListener('DOMContentLoaded', () => {
    // Lưu lại giá trị VNĐ gốc cho mọi phần tử hiển thị giá
    document.querySelectorAll('.menu-item-price').forEach(el => {
        let text = el.innerText.trim();
        let raw = parseInt(text.replace(/\./g, '').replace(/,/g, ''));
        if (!isNaN(raw)) {
            el.setAttribute('data-raw-vnd', raw);
        }
    });

    // Tự động phát hiện khi Google Dịch (hoặc trình duyệt) dịch trang
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class' || mutation.attributeName === 'lang') {
                const isTranslated = document.documentElement.classList.contains('translated-ltr') 
                                  || document.documentElement.classList.contains('translated-rtl')
                                  || document.documentElement.lang === 'en';
                if (isTranslated) {
                    setCurrency('USD');
                } else {
                    setCurrency('VND');
                }
            }
        });
    });

    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'lang'] });
    
    // Kiểm tra ngay lúc load xem đã bị dịch chưa
    if (document.documentElement.classList.contains('translated-ltr') || document.documentElement.lang === 'en') {
        setCurrency('USD');
    }
});

function setCurrency(curr) {
    if (currentCurrency === curr) return;
    currentCurrency = curr;
    
    // Update prices on the page
    document.querySelectorAll('.menu-item-price').forEach(el => {
        let raw = el.getAttribute('data-raw-vnd');
        if (raw) {
            raw = parseInt(raw);
            if (curr === 'VND') {
                el.innerText = new Intl.NumberFormat('vi-VN').format(raw);
            } else {
                let usd = (raw / exchangeRateVndToUsd).toFixed(2);
                el.innerText = '$' + usd;
            }
        }
    });
}

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
    
    var algEl = document.getElementById('ed-m-allergens');
    if (data.allergens && data.allergens.trim() !== '') {
        algEl.style.display = 'block';
        algEl.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right:5px;"></i>Chứa thành phần: ' + data.allergens;
    } else {
        algEl.style.display = 'none';
        algEl.innerHTML = '';
    }
    
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
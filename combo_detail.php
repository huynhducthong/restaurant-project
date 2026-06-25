<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

$combo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin Set
$stmt = $db->prepare("SELECT * FROM combos WHERE id = ? AND is_active = 1");
$stmt->execute([$combo_id]);
$combo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$combo) {
    // Nếu không tồn tại set, đưa về trang chủ
    header("Location: index.php");
    exit;
}

// Lấy danh sách món ăn trong Set
// Sắp xếp theo Category để ra cấu trúc Khai vị -> Món chính -> Tráng miệng
$stmt_foods = $db->prepare("SELECT f.*, c.name as cat_name 
                            FROM foods f 
                            JOIN combo_items ci ON f.id = ci.food_id 
                            LEFT JOIN categories c ON f.category_id = c.id 
                            WHERE ci.combo_id = ? 
                            ORDER BY c.id ASC, f.name ASC");
$stmt_foods->execute([$combo_id]);
$foods = $stmt_foods->fetchAll(PDO::FETCH_ASSOC);

// Nhóm các món theo danh mục (Category)
$course_menus = [];
foreach ($foods as $food) {
    $cat = $food['cat_name'] ? $food['cat_name'] : 'Món Khác';
    $course_menus[$cat][] = $food;
}

// Nhúng Header
include __DIR__ . '/views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
:root {
  --cd-bg: #F9F9F9;
  --cd-text: #222222;
  --cd-muted: #666666;
  --cd-olive: #A88746;
  --cd-gold: #A88746;
  --cd-serif: 'Cormorant Garamond', serif;
  --cd-sans: 'Source Sans 3', sans-serif;
}

body { background-color: var(--cd-bg); }

/* HERO SECTION */
.cd-hero {
  position: relative;
  height: 65vh;
  min-height: 500px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  margin-top: 55px; /* Offset for header */
}
.cd-hero-bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  filter: brightness(0.7);
  z-index: 0;
}
.cd-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(26, 26, 29, 0) 40%, var(--cd-bg) 100%);
  z-index: 1;
}
.cd-hero-content {
  position: relative;
  z-index: 2;
  padding: 0 20px;
  max-width: 800px;
  margin-top: 100px;
}
.cd-eyebrow {
  display: block;
  font-family: var(--cd-sans);
  font-size: 12px;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--cd-gold);
  margin-bottom: 20px;
  font-weight: 500;
}
.cd-title {
  font-family: var(--cd-serif);
  font-size: clamp(3rem, 6vw, 5rem);
  font-weight: 400;
  color: #fff;
  line-height: 1.1;
  margin-bottom: 20px;
  text-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

/* DETAILS CONTAINER */
.cd-container {
  max-width: 900px;
  margin: -80px auto 100px;
  background: #FFFFFF;
  position: relative;
  z-index: 3;
  padding: 80px 60px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.05);
  border: 1px solid rgba(168, 135, 70, 0.2);
  text-align: center;
}
.cd-desc {
  font-family: var(--cd-sans);
  font-size: 15px;
  color: var(--cd-muted);
  line-height: 1.8;
  max-width: 700px;
  margin: 0 auto 40px;
  font-weight: 300;
}
.cd-price {
  font-family: var(--cd-serif);
  font-size: 2.5rem;
  color: var(--cd-olive);
  margin-bottom: 40px;
}

.cd-divider {
  width: 60px;
  height: 2px;
  background: var(--cd-gold);
  margin: 0 auto 60px;
}

/* COURSE MENU */
.cd-course-block {
  margin-bottom: 60px;
}
.cd-course-title {
  font-family: var(--cd-serif);
  font-size: 1.8rem;
  color: var(--cd-gold);
  margin-bottom: 30px;
  font-style: italic;
  position: relative;
  display: inline-block;
}
.cd-course-title::before, .cd-course-title::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 40px;
  height: 1px;
  background: rgba(168, 135, 70, 0.4);
}
.cd-course-title::before { right: 100%; margin-right: 20px; }
.cd-course-title::after { left: 100%; margin-left: 20px; }

.cd-food-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-align: left;
  margin-bottom: 30px;
  padding-bottom: 30px;
  border-bottom: 1px dashed rgba(168, 135, 70, 0.1);
}
.cd-food-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}
.cd-food-img {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  margin-right: 30px;
  border: 3px solid var(--cd-bg);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.cd-food-info {
  flex-grow: 1;
}
.cd-food-name {
  font-family: var(--cd-serif);
  font-size: 1.4rem;
  color: var(--cd-text);
  margin-bottom: 8px;
  font-weight: 600;
}
.cd-food-desc {
  font-family: var(--cd-sans);
  font-size: 13px;
  color: var(--cd-muted);
  line-height: 1.6;
}

.cd-btn-book {
  background: var(--cd-olive);
  color: #fff;
  border: none;
  padding: 18px 50px;
  font-family: var(--cd-sans);
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 2px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 20px;
}
.cd-btn-book:hover {
  background: var(--cd-text);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
  .cd-container { padding: 40px 20px; margin: -50px 15px 50px; }
  .cd-food-item { flex-direction: column; text-align: center; }
  .cd-food-img { margin: 0 0 20px 0; }
  .cd-course-title::before, .cd-course-title::after { display: none; }
}
</style>

<!-- HERO -->
<div class="cd-hero">
  <div class="cd-hero-bg" style="background-image: url('public/assets/img/combos/<?= htmlspecialchars($combo['image'] ?: 'default-combo.jpg') ?>');"></div>
  <div class="cd-hero-overlay"></div>
  <div class="cd-hero-content">
    <span class="cd-eyebrow">Tasting Menu</span>
    <h1 class="cd-title"><?= htmlspecialchars($combo['name']) ?></h1>
  </div>
</div>

<!-- DETAILS -->
<div class="cd-container">
  <p class="cd-desc"><?= nl2br(htmlspecialchars($combo['description'])) ?></p>
  <div class="cd-price"><?= number_format($combo['price'], 0, ',', '.') ?> VND</div>
  
  <button class="cd-btn-book" onclick="addToCart('combo', <?= $combo['id'] ?>)">ĐẶT BÀN VỚI SET NÀY</button>
  
  <div class="cd-divider" style="margin-top: 60px;"></div>
  
  <!-- MENU LIST -->
  <div class="cd-courses">
    <?php if (empty($course_menus)): ?>
      <p class="text-muted italic">Set này hiện chưa cấu hình danh sách món ăn.</p>
    <?php else: ?>
      <?php foreach ($course_menus as $cat_name => $items): ?>
        <div class="cd-course-block">
          <h3 class="cd-course-title"><?= htmlspecialchars($cat_name) ?></h3>
          
          <?php foreach ($items as $food): ?>
            <div class="cd-food-item">
              <img src="public/assets/img/menu/<?= htmlspecialchars($food['image'] ?: 'default-food.jpg') ?>" 
                   alt="<?= htmlspecialchars($food['name']) ?>" 
                   class="cd-food-img"
                   onerror="this.src='public/assets/img/menu/default-food.jpg'">
              
              <div class="cd-food-info">
                <h4 class="cd-food-name"><?= htmlspecialchars($food['name']) ?></h4>
                <?php if ($food['description']): ?>
                  <p class="cd-food-desc"><?= htmlspecialchars($food['description']) ?></p>
                <?php endif; ?>
                <?php if (!empty($food['ingredients'])): ?>
                  <p class="cd-food-desc mt-1" style="font-size: 11px; color: #999;"><i class="bi bi-info-circle me-1"></i>Thành phần: <?= htmlspecialchars($food['ingredients']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>

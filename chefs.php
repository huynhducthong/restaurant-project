<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Automated DB upgrades
try {
    $db->exec("ALTER TABLE chefs ADD COLUMN IF NOT EXISTS awards TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE chefs ADD COLUMN IF NOT EXISTS signature_dishes VARCHAR(255) DEFAULT NULL");
    
    // Create chef_reviews table if it does not exist
    $db->exec("CREATE TABLE IF NOT EXISTS chef_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chef_id INT NOT NULL,
        user_id INT NULL,
        author_name VARCHAR(100) DEFAULT 'Khách ẩn danh',
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $db->exec("ALTER TABLE chef_reviews ADD COLUMN IF NOT EXISTS author_name VARCHAR(100) DEFAULT 'Khách ẩn danh'");
} catch (Exception $e) {
    // Ignore database upgrade errors
}

$stmt = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 AND position IN ('Bếp trưởng', 'Bếp phó', 'Bếp chính') ORDER BY sort_order ASC, id DESC");
$stmt->execute();
$all_chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map signature dishes for each chef
foreach ($all_chefs as &$chef) {
    $chef['signature_dishes_list'] = [];
    if (!empty($chef['signature_dishes'])) {
        $ids = array_map('intval', explode(',', $chef['signature_dishes']));
        if (!empty($ids)) {
            $in_clause = implode(',', $ids);
            $food_stmt = $db->query("SELECT id, name, price, image, description, allergens, chef_note FROM foods WHERE id IN ($in_clause) AND status = 1");
            $chef['signature_dishes_list'] = $food_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
unset($chef);

// Pagination logic
$limit = 8;
$total_chefs = count($all_chefs);
$total_pages = ceil($total_chefs / $limit);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
$chefs = array_slice($all_chefs, $offset, $limit);

include __DIR__ . '/views/client/layouts/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source Sans 3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">


<div class="page-space"></div>

<!-- HERO -->
<section class="ch-hero">
  <div class="container">
    <div class="ch-hero-eyebrow">Restaurantly</div>
    <h1>Những <em>nghệ nhân</em><br>đứng sau mỗi món ăn</h1>
    <p class="ch-hero-sub">Đội ngũ đầu bếp của chúng tôi mang trong mình đam mê, kỹ năng và câu chuyện riêng — để biến từng bữa ăn thành một trải nghiệm đáng nhớ.</p>
  </div>
</section>

<!-- STATS STRIP -->
<?php if(!empty($all_chefs)): ?>
<div class="ch-strip">
  <div class="strip-item">
    <div class="strip-num"><?= count($all_chefs) ?>+</div>
    <div class="strip-lbl">Đầu bếp chuyên nghiệp</div>
  </div>
  <div class="strip-item">
    <div class="strip-num"><?php
      $exp = array_sum(array_filter(array_column($all_chefs,'experience')));
      echo ($exp ?: 50).'+';
    ?></div>
    <div class="strip-lbl">Năm kinh nghiệm</div>
  </div>
  <div class="strip-item">
    <div class="strip-num">10+</div>
    <div class="strip-lbl">Năm phục vụ</div>
  </div>
</div>
<?php endif; ?>

<!-- GRID -->
<div class="ch-wrap">
  <?php if(!empty($chefs)): ?>
  <div class="ch-grid">
    <?php foreach($chefs as $index => $chef):
      $img = !empty($chef['image'])
        ? 'public/assets/img/chefs/'.htmlspecialchars($chef['image'])
        : 'public/assets/img/chefs/default-chef.jpg';
      $hasSocial = !empty($chef['facebook']) || !empty($chef['instagram']) || !empty($chef['email']);
      $chef_json = htmlspecialchars(json_encode($chef, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="ch-card" style="cursor: pointer;" onclick="window.location.href='chef_detail.php?id=<?= $chef['id'] ?>'">
      <div class="ch-img">
        <img src="<?= $img ?>"
             alt="<?= htmlspecialchars($chef['name']) ?>"
             onerror="this.onerror=null; this.src='public/assets/img/chefs/default-chef.jpg'">
      </div>

      <div class="ch-info">
        <div class="ch-position"><?= htmlspecialchars($chef['position'] ?? 'Đầu Bếp') ?></div>
        <div class="ch-name"><?= htmlspecialchars($chef['name']) ?></div>

        <div class="ch-exp-spec" style="margin-bottom: 0;">
            <?php if(!empty($chef['experience'])): ?>
            <div><i class="bi bi-clock-history"></i> <?= (int)$chef['experience'] ?> năm kinh nghiệm</div>
            <?php endif; ?>
        </div>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($total_pages > 1): ?>
  <div class="ch-pagination-wrap">
    <ul class="ch-pagination">
      <li class="<?= $page <= 1 ? 'disabled' : '' ?>">
        <a href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
      </li>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="<?= $i == $page ? 'active' : '' ?>">
          <a href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <li class="<?= $page >= $total_pages ? 'disabled' : '' ?>">
        <a href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
      </li>
    </ul>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="ch-empty">
    <i class="bi bi-people"></i>
    <p style="font-family:'Cormorant Garamond', serif;font-style:italic;font-size:1.2rem;">
      Thông tin đội ngũ đang được cập nhật...
    </p>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/views/client/layouts/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<<style>
:root {
  --bg-color: #F9F9F9; /* Cream */
  --card-bg: #ffffff; /* White */
  --text-main: #222222;
  --text-muted: #777777;
  --accent-burgundy: #A88746;
  --accent-burgundy: #A88746;
  --border-light: rgba(168, 135, 70, 0.15);
  --ease: cubic-bezier(.4,0,.2,1);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg-color); color: var(--text-main); font-family: 'Open Sans', sans-serif; }

/* HERO */
.ch-hero {
  position: relative; overflow: hidden;
  padding: 160px 0 80px; text-align: center;
  background: var(--bg-color);
}
.ch-hero-eyebrow {
  display: inline-flex; align-items: center; gap: 12px;
  font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--accent-burgundy); font-weight: 600;
  margin-bottom: 20px;
}
.ch-hero-eyebrow::before, .ch-hero-eyebrow::after {
  content: ''; display: block; width: 30px; height: 1px; background: var(--accent-burgundy); opacity: .5;
}
.ch-hero h1 {
  font-family: 'Cormorant Garamond', serif; font-weight: 700;
  font-size: clamp(2.6rem, 6vw, 4.5rem); color: var(--accent-burgundy); line-height: 1.2; margin-bottom: 20px;
}
.ch-hero h1 em { font-style: italic; color: var(--accent-burgundy); font-weight: 600; }
.ch-hero-sub {
  font-size: 15px; color: var(--text-muted); font-weight: 400;
  max-width: 550px; margin: 0 auto; line-height: 1.8;
}

/* STATS STRIP */
.ch-strip {
  background: var(--card-bg);
  border-top: 1px solid var(--border-light);
  border-bottom: 1px solid var(--border-light);
  padding: 40px 32px;
  display: flex; align-items: center; justify-content: center;
  gap: 80px; flex-wrap: wrap;
}
.strip-item { text-align: center; }
.strip-num { font-family: 'Cormorant Garamond', serif; font-weight: 700; font-size: 2.5rem; color: var(--accent-burgundy); line-height: 1; margin-bottom: 10px; }
.strip-lbl { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }

/* WRAP + GRID */
.ch-wrap { max-width: 1200px; margin: 0 auto; padding: 100px 20px; }
.ch-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 40px;
}
@media(max-width: 800px){ .ch-grid { grid-template-columns: 1fr; gap: 60px; } }

/* CARD */
.ch-card {
  background: var(--card-bg);
  border: 1px solid var(--border-light);
  border-top: 3px solid var(--accent-burgundy);
  display: flex; flex-direction: column;
  transition: transform 0.3s var(--ease), box-shadow 0.3s var(--ease);
}
.ch-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(0,0,0,0.03);
}

/* Ảnh */
.ch-img { width: 100%; aspect-ratio: 3/4; overflow: hidden; position: relative; border-bottom: 1px solid var(--border-light); }
.ch-img img {
  width: 100%; height: 100%; object-fit: cover;
  filter: grayscale(0.2) contrast(1.05);
  transition: transform 0.65s var(--ease), filter 0.5s var(--ease);
}
.ch-card:hover .ch-img img {
  transform: scale(1.05);
  filter: grayscale(0) contrast(1.1);
}

/* THÔNG TIN PUBLIC BÊN DƯỚI */
.ch-info {
  padding: 35px 30px;
  display: flex; flex-direction: column; flex: 1;
}
.ch-position {
  font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
  color: var(--accent-burgundy); margin-bottom: 10px; font-weight: 600;
}
.ch-name {
  font-family: 'Cormorant Garamond', serif; font-weight: 700;
  font-size: 2rem; color: var(--text-main); line-height: 1.1; margin-bottom: 20px;
}
.ch-exp-spec {
  display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;
  font-size: 12px; color: var(--text-muted);
}
.ch-exp-spec i { color: var(--accent-burgundy); margin-right: 6px; font-size: 14px; }

.ch-desc {
  font-size: 13px; color: var(--text-muted); line-height: 1.8;
  margin-bottom: 25px; flex: 1;
}

.ch-quote {
  font-family: 'Cormorant Garamond', serif; font-style: italic;
  font-size: 1.1rem; color: var(--accent-burgundy);
  border-left: 2px solid var(--accent-burgundy);
  padding-left: 15px; margin-bottom: 25px;
  line-height: 1.6;
}

.ch-social { display: flex; gap: 12px; border-top: 1px solid var(--border-light); padding-top: 20px; }
.ch-social a {
  width: 36px; height: 36px; border: 1px solid var(--border-light);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); font-size: 14px; text-decoration: none;
  transition: 0.3s;
}
.ch-social a:hover { background: var(--accent-burgundy); color: #fff; border-color: var(--accent-burgundy); }

/* EMPTY */
.ch-empty { text-align: center; padding: 100px 20px; color: var(--text-muted); }
.ch-empty i { font-size: 52px; opacity: 0.2; display: block; margin-bottom: 16px; }
</style>

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
<?php if(!empty($chefs)): ?>
<div class="ch-strip">
  <div class="strip-item">
    <div class="strip-num"><?= count($chefs) ?>+</div>
    <div class="strip-lbl">Đầu bếp chuyên nghiệp</div>
  </div>
  <div class="strip-item">
    <div class="strip-num"><?php
      $exp = array_sum(array_filter(array_column($chefs,'experience')));
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
    <?php foreach($chefs as $chef):
      $img = !empty($chef['image'])
        ? 'public/assets/img/chefs/'.htmlspecialchars($chef['image'])
        : 'https://placehold.co/400x530/262629/A88746?text=Chef';
      $hasSocial = !empty($chef['facebook']) || !empty($chef['instagram']) || !empty($chef['email']);
    ?>
    <div class="ch-card">
      <div class="ch-img">
        <img src="<?= $img ?>"
             alt="<?= htmlspecialchars($chef['name']) ?>"
             onerror="this.onerror=null; this.src='https://placehold.co/400x530/262629/A88746?text=Chef'">
      </div>

      <div class="ch-info">
        <div class="ch-position"><?= htmlspecialchars($chef['position'] ?? 'Đầu Bếp') ?></div>
        <div class="ch-name"><?= htmlspecialchars($chef['name']) ?></div>

        <div class="ch-exp-spec">
            <?php if(!empty($chef['experience'])): ?>
            <div><i class="bi bi-clock-history"></i> <?= (int)$chef['experience'] ?> năm kinh nghiệm</div>
            <?php endif; ?>
            <?php if(!empty($chef['specialty'])): ?>
            <div><i class="bi bi-stars"></i> <?= htmlspecialchars($chef['specialty']) ?></div>
            <?php endif; ?>
        </div>

        <?php if(!empty($chef['quote'])): ?>
        <div class="ch-quote">"<?= htmlspecialchars($chef['quote']) ?>"</div>
        <?php endif; ?>

        <?php if(!empty($chef['description'])): ?>
        <p class="ch-desc"><?= htmlspecialchars($chef['description']) ?></p>
        <?php endif; ?>

        <?php if($hasSocial): ?>
        <div class="ch-social">
          <?php if(!empty($chef['facebook'])): ?>
          <a href="<?= htmlspecialchars($chef['facebook']) ?>" target="_blank" rel="noopener">
            <i class="bi bi-facebook"></i>
          </a>
          <?php endif; ?>
          <?php if(!empty($chef['instagram'])): ?>
          <a href="<?= htmlspecialchars($chef['instagram']) ?>" target="_blank" rel="noopener">
            <i class="bi bi-instagram"></i>
          </a>
          <?php endif; ?>
          <?php if(!empty($chef['email'])): ?>
          <a href="mailto:<?= htmlspecialchars($chef['email']) ?>">
            <i class="bi bi-envelope"></i>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

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
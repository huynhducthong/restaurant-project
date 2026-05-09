<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Lấy danh sách đầu bếp đang hiển thị
try {
    $stmt_chefs = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, id ASC");
    $stmt_chefs->execute();
    $chefs_list = $stmt_chefs->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $chefs_list = [];
}

include_once 'layouts/header.php';
?>

<style>
  /* Offset cho fixed header */
  .chefs-page { padding-top: 140px; padding-bottom: 80px; background: #0c0b09; min-height: 100vh; }

  /* Hero tiêu đề */
  .chefs-page-hero {
    text-align: center;
    padding: 60px 0 50px;
  }
  .chefs-page-hero .eyebrow {
    font-size: 13px; letter-spacing: 3px; text-transform: uppercase;
    color: #cda45e; font-family: 'Poppins', sans-serif; font-weight: 500;
    margin-bottom: 12px;
  }
  .chefs-page-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 5vw, 3.2rem);
    color: #fff; font-weight: 700; margin-bottom: 16px;
  }
  .chefs-page-hero h1 span { color: #cda45e; font-style: italic; }
  .chefs-page-hero p { color: #aaa; max-width: 500px; margin: 0 auto; line-height: 1.8; font-family: 'Poppins', sans-serif; font-size: 15px; }
  .divider-gold { width: 55px; height: 3px; background: #cda45e; margin: 20px auto; }

  /* Cards */
  .chef-card {
    background: #1a1814;
    border: 1px solid #37332a;
    border-radius: 10px;
    overflow: hidden;
    transition: transform .35s ease, box-shadow .35s ease, border-color .35s ease;
    height: 100%;
    position: relative;
  }
  .chef-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(205,164,94,.2);
    border-color: #cda45e;
  }
  .chef-card.featured { border-color: #cda45e; }

  .featured-badge {
    position: absolute; top: 12px; right: 12px;
    background: #cda45e; color: #fff;
    font-size: .65rem; font-weight: 700; letter-spacing: 1px;
    padding: 4px 10px; border-radius: 20px; text-transform: uppercase;
    z-index: 2;
  }

  /* Ảnh */
  .chef-img-wrap { position: relative; overflow: hidden; height: 260px; }
  .chef-img-wrap img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform .5s ease;
  }
  .chef-card:hover .chef-img-wrap img { transform: scale(1.06); }
  .chef-no-photo {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #2c3e50, #1a252f);
    display: flex; align-items: center; justify-content: center;
    color: #cda45e; font-size: 4rem; opacity: .5;
  }

  /* Social overlay */
  .chef-social-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(10,10,10,.85) 0%, transparent 55%);
    display: flex; align-items: flex-end; justify-content: center;
    gap: 12px; padding-bottom: 16px;
    opacity: 0; transition: opacity .35s ease;
  }
  .chef-card:hover .chef-social-overlay { opacity: 1; }
  .chef-social-overlay a {
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,.1); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    color: #fff; text-decoration: none; font-size: .85rem;
    border: 1px solid rgba(255,255,255,.2);
    transition: background .25s, border-color .25s;
  }
  .chef-social-overlay a:hover { background: #cda45e; border-color: #cda45e; }

  /* Body */
  .chef-body { padding: 20px; }
  .chef-position {
    color: #cda45e; font-size: .72rem; text-transform: uppercase;
    letter-spacing: 2px; font-weight: 600; margin-bottom: 5px;
    font-family: 'Poppins', sans-serif;
  }
  .chef-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem; color: #fff; margin-bottom: 10px;
  }
  .chef-stats { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
  .chef-stat { font-size: .78rem; color: #888; font-family: 'Poppins', sans-serif; }
  .chef-stat i { color: #cda45e; margin-right: 4px; }
  .chef-divider { height: 1px; background: #37332a; margin-bottom: 12px; }
  .chef-desc {
    font-size: .83rem; color: #aaa; line-height: 1.7;
    font-family: 'Poppins', sans-serif;
    display: -webkit-box; -webkit-line-clamp: 3;
    -webkit-box-orient: vertical; overflow: hidden;
  }
  .chef-quote {
    margin-top: 12px; padding: 10px 12px;
    background: rgba(205,164,94,.07);
    border-left: 3px solid #cda45e;
    border-radius: 0 6px 6px 0;
    font-style: italic; font-size: .8rem; color: #bbb;
    font-family: 'Poppins', sans-serif;
  }
  .chef-quote i { color: #cda45e; margin-right: 4px; font-size: .7rem; }

  /* Empty */
  .empty-state { text-align: center; padding: 80px 20px; color: #555; }
  .empty-state i { font-size: 3.5rem; display: block; margin-bottom: 16px; color: #333; }
</style>

<!-- Topbar + Header đã được include từ header.php -->
<div class="chefs-page">
  <div class="container">

    <!-- Tiêu đề trang -->
    <div class="chefs-page-hero">
      <p class="eyebrow"><i class="bi bi-person-badge me-2"></i>Đội ngũ chúng tôi</p>
      <h1>Những <span>Nghệ nhân</span> Đứng sau Mỗi Món ăn</h1>
      <div class="divider-gold"></div>
      <p>Đội ngũ đầu bếp tài năng với niềm đam mê ẩm thực, mang đến những trải nghiệm hương vị độc đáo và đáng nhớ cho từng vị khách.</p>
    </div>

    <!-- Danh sách đầu bếp -->
    <?php if (empty($chefs_list)): ?>
      <div class="empty-state">
        <i class="bi bi-person-x"></i>
        <p class="text-muted">Chưa có thông tin đầu bếp. Vui lòng quay lại sau.</p>
      </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($chefs_list as $chef): ?>
      <div class="col-lg-4 col-md-6">
        <div class="chef-card <?= $chef['is_featured'] ? 'featured' : '' ?>">

          <?php if ($chef['is_featured']): ?>
            <div class="featured-badge"><i class="bi bi-star-fill me-1"></i>Nổi bật</div>
          <?php endif; ?>

          <!-- Ảnh + Social Overlay -->
          <div class="chef-img-wrap">
            <?php if (!empty($chef['image'])): ?>
              <img src="/restaurant-project/public/assets/img/chefs/<?= htmlspecialchars($chef['image']) ?>"
                   alt="<?= htmlspecialchars($chef['name']) ?>">
            <?php else: ?>
              <div class="chef-no-photo"><i class="bi bi-person"></i></div>
            <?php endif; ?>

            <?php $has_social = !empty($chef['facebook']) || !empty($chef['instagram']) || !empty($chef['email']); ?>
            <?php if ($has_social): ?>
            <div class="chef-social-overlay">
              <?php if (!empty($chef['facebook'])): ?>
                <a href="<?= htmlspecialchars($chef['facebook']) ?>" target="_blank" title="Facebook">
                  <i class="bi bi-facebook"></i>
                </a>
              <?php endif; ?>
              <?php if (!empty($chef['instagram'])): ?>
                <a href="<?= htmlspecialchars($chef['instagram']) ?>" target="_blank" title="Instagram">
                  <i class="bi bi-instagram"></i>
                </a>
              <?php endif; ?>
              <?php if (!empty($chef['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($chef['email']) ?>" title="Email">
                  <i class="bi bi-envelope"></i>
                </a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Thông tin -->
          <div class="chef-body">
            <div class="chef-position"><?= htmlspecialchars($chef['position']) ?></div>
            <div class="chef-name"><?= htmlspecialchars($chef['name']) ?></div>

            <div class="chef-stats">
              <?php if ($chef['experience'] > 0): ?>
                <span class="chef-stat"><i class="bi bi-clock"></i><?= $chef['experience'] ?> năm kinh nghiệm</span>
              <?php endif; ?>
              <?php if (!empty($chef['specialty'])): ?>
                <span class="chef-stat"><i class="bi bi-star"></i><?= htmlspecialchars($chef['specialty']) ?></span>
              <?php endif; ?>
            </div>

            <?php if (!empty($chef['description'])): ?>
              <div class="chef-divider"></div>
              <div class="chef-desc"><?= nl2br(htmlspecialchars($chef['description'])) ?></div>
            <?php endif; ?>

            <?php if (!empty($chef['quote'])): ?>
              <div class="chef-quote">
                <i class="bi bi-quote"></i><?= htmlspecialchars($chef['quote']) ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include_once 'layouts/footer.php'; ?>
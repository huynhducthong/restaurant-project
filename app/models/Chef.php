<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Lấy danh sách đầu bếp đang hiển thị, ghim nổi bật lên đầu
$query = "SELECT * FROM chefs 
          WHERE is_active = 1 
          ORDER BY is_featured DESC, sort_order ASC, id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin nhà hàng (nếu có bảng settings)
$restaurant_name = 'Restaurantly';
try {
    $s = $db->query("SELECT key_value FROM settings WHERE key_name='restaurant_name'")->fetchColumn();
    if ($s) $restaurant_name = $s;
} catch (Exception $e) {}

include __DIR__ . '/layouts/header.php';
?>

<style>
/* ===== CHEF PAGE — DARK LUXURY STYLE ===== */
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap');

:root {
    --accent-burgundy:       #A88746;
    --accent-burgundy-light: #e2b97a;
    --dark:       #1a1a1a;
    --dark2:      #242424;
    --dark3:      #2e2e2e;
    --bg-smoke:      #f8f3ec;
    --text-light: #c8b89a;
    --text-muted: #888;
}

.chefs-page {
    background: var(--dark);
    min-height: 100vh;
    font-family: 'Open Sans', sans-serif;
}

/* ---- HERO BANNER ---- */
.chefs-hero {
    position: relative;
    padding: 100px 0 80px;
    text-align: center;
    overflow: hidden;
    background: var(--dark);
}

.chefs-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 60% at 50% 0%, rgba(205,164,94,.18) 0%, transparent 70%);
    pointer-events: none;
}

.chefs-hero .ornament {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-bottom: 20px;
}

.chefs-hero .ornament span {
    display: block;
    height: 1px;
    width: 60px;
    background: linear-gradient(to right, transparent, var(--accent-burgundy));
}

.chefs-hero .ornament span:last-child {
    background: linear-gradient(to left, transparent, var(--accent-burgundy));
}

.chefs-hero .ornament i {
    color: var(--accent-burgundy);
    font-size: 1.1rem;
}

.chefs-hero .subtitle {
    font-family: 'Open Sans', sans-serif;
    font-size: .8rem;
    font-weight: 600;
    letter-spacing: .35em;
    text-transform: uppercase;
    color: var(--accent-burgundy);
    margin-bottom: 16px;
}

.chefs-hero h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.8rem, 6vw, 5rem);
    font-weight: 600;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 24px;
}

.chefs-hero h1 em {
    font-style: italic;
    color: var(--accent-burgundy-light);
}

.chefs-hero .lead-text {
    font-size: .95rem;
    color: var(--text-muted);
    max-width: 500px;
    margin: 0 auto;
    line-height: 1.8;
    font-weight: 300;
}

/* ---- DIVIDER ---- */
.section-divider {
    display: flex;
    align-items: center;
    gap: 0;
    margin: 0;
    padding: 0 5%;
}

.section-divider::before,
.section-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(to right, transparent, rgba(205,164,94,.4), transparent);
}

/* ---- GRID ---- */
.chefs-section {
    padding: 70px 5% 100px;
    background: var(--dark);
}

.chefs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 32px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ---- CHEF CARD ---- */
.chef-card {
    position: relative;
    background: var(--dark2);
    border: 1px solid rgba(205,164,94,.15);
    border-radius: 4px;
    overflow: hidden;
    transition: transform .4s cubic-bezier(.25,.46,.45,.94),
                border-color .4s,
                box-shadow .4s;
}

.chef-card:hover {
    transform: translateY(-8px);
    border-color: rgba(205,164,94,.5);
    box-shadow: 0 24px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(205,164,94,.1);
}

/* Featured badge */
.chef-card.featured::after {
    content: '★ Nổi bật';
    position: absolute;
    top: 16px;
    right: -30px;
    background: var(--accent-burgundy);
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    padding: 4px 36px;
    transform: rotate(45deg);
    transform-origin: center;
}

/* Image wrapper */
.chef-img-wrap {
    position: relative;
    height: 320px;
    overflow: hidden;
    background: var(--dark3);
}

.chef-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    transition: transform .6s cubic-bezier(.25,.46,.45,.94);
    filter: brightness(.95) saturate(.9);
}

.chef-card:hover .chef-img-wrap img {
    transform: scale(1.06);
    filter: brightness(1) saturate(1);
}

/* Image overlay on hover */
.chef-img-wrap .overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top,
        rgba(26,26,26,.92) 0%,
        rgba(26,26,26,.3) 50%,
        transparent 100%);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 20px;
    opacity: 0;
    transition: opacity .4s;
}

.chef-card:hover .overlay {
    opacity: 1;
}

/* Social links inside overlay */
.chef-socials {
    display: flex;
    gap: 10px;
}

.chef-socials a {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .85rem;
    text-decoration: none;
    backdrop-filter: blur(8px);
    background: rgba(255,255,255,.08);
    transition: background .25s, border-color .25s, transform .25s;
}

.chef-socials a:hover {
    background: var(--accent-burgundy);
    border-color: var(--accent-burgundy);
    transform: translateY(-2px);
}

/* No image placeholder */
.chef-img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e1e1e, #2a2a2a);
}

.chef-img-placeholder i {
    font-size: 4rem;
    color: var(--accent-burgundy);
    opacity: .5;
}

/* Card body */
.chef-body {
    padding: 24px 22px 22px;
}

.chef-position-badge {
    display: inline-block;
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--accent-burgundy);
    margin-bottom: 8px;
    border: 1px solid rgba(205,164,94,.4);
    padding: 3px 10px;
    border-radius: 2px;
}

.chef-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.65rem;
    font-weight: 600;
    color: #fff;
    line-height: 1.2;
    margin-bottom: 4px;
}

/* Meta row: experience + specialty */
.chef-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 10px 0 14px;
    flex-wrap: wrap;
}

.chef-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: .78rem;
    color: var(--text-muted);
    font-weight: 400;
}

.chef-meta .meta-item i {
    color: var(--accent-burgundy);
    font-size: .75rem;
}

.chef-meta .dot {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: rgba(205,164,94,.4);
}

/* Description */
.chef-description {
    font-size: .84rem;
    color: var(--text-muted);
    line-height: 1.75;
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Quote */
.chef-quote {
    border-left: 2px solid var(--accent-burgundy);
    padding: 8px 14px;
    background: rgba(205,164,94,.06);
    border-radius: 0 3px 3px 0;
}

.chef-quote p {
    font-family: 'Cormorant Garamond', serif;
    font-style: italic;
    font-size: 1rem;
    color: var(--text-light);
    line-height: 1.6;
    margin: 0;
}

.chef-quote p::before { content: '\201C'; }
.chef-quote p::after  { content: '\201D'; }

/* ---- EMPTY STATE ---- */
.chefs-empty {
    text-align: center;
    padding: 80px 20px;
    color: var(--text-muted);
}

.chefs-empty i {
    font-size: 3.5rem;
    color: rgba(205,164,94,.3);
    margin-bottom: 20px;
    display: block;
}

.chefs-empty p {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.4rem;
    font-style: italic;
}

/* ---- RESPONSIVE ---- */
@media (max-width: 768px) {
    .chefs-hero {
        padding: 70px 20px 50px;
    }

    .chefs-section {
        padding: 50px 5% 70px;
    }

    .chefs-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .chef-img-wrap {
        height: 280px;
    }
}
</style>

<div class="chefs-page">

    <!-- HERO SECTION -->
    <div class="chefs-hero">
        <div class="ornament">
            <span></span>
            <i class="fas fa-utensils"></i>
            <span></span>
        </div>
        <p class="subtitle">Nghệ nhân ẩm thực</p>
        <h1>Đội Ngũ <em>Đầu Bếp</em></h1>
        <p class="lead-text">
            Những người thầm lặng đứng sau mỗi tuyệt tác trên bàn ăn — 
            mang cả đam mê, tâm huyết và kỹ thuật vào từng món ăn.
        </p>
    </div>

    <!-- CHEF CARDS GRID -->
    <div class="chefs-section">
        <?php if (!empty($chefs)): ?>
            <div class="chefs-grid">
                <?php foreach ($chefs as $chef): ?>
                    <article class="chef-card <?= !empty($chef['is_featured']) ? 'featured' : '' ?>">

                        <!-- IMAGE + SOCIAL OVERLAY -->
                        <div class="chef-img-wrap">
                            <?php if (!empty($chef['image'])): ?>
                                <img
                                    src="<?= htmlspecialchars('public/assets/img/chefs/' . $chef['image']) ?>"
                                    alt="<?= htmlspecialchars($chef['name']) ?>"
                                    loading="lazy">
                            <?php else: ?>
                                <div class="chef-img-placeholder">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Social links appear on hover -->
                            <?php
                            $has_social = !empty($chef['facebook']) || !empty($chef['instagram']) || !empty($chef['email']);
                            ?>
                            <?php if ($has_social): ?>
                                <div class="overlay">
                                    <div class="chef-socials">
                                        <?php if (!empty($chef['facebook'])): ?>
                                            <a href="<?= htmlspecialchars($chef['facebook']) ?>"
                                               target="_blank" rel="noopener" title="Facebook">
                                                <i class="fab fa-facebook-f"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($chef['instagram'])): ?>
                                            <a href="<?= htmlspecialchars($chef['instagram']) ?>"
                                               target="_blank" rel="noopener" title="Instagram">
                                                <i class="fab fa-instagram"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($chef['email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($chef['email']) ?>" title="Email">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CARD BODY -->
                        <div class="chef-body">
                            <div class="chef-position-badge">
                                <?= htmlspecialchars($chef['position']) ?>
                            </div>

                            <h2 class="chef-name">
                                <?= htmlspecialchars($chef['name']) ?>
                            </h2>

                            <!-- Experience + Specialty meta -->
                            <?php if (!empty($chef['experience']) || !empty($chef['specialty'])): ?>
                                <div class="chef-meta">
                                    <?php if (!empty($chef['experience']) && $chef['experience'] > 0): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-award"></i>
                                            <?= (int)$chef['experience'] ?> năm kinh nghiệm
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($chef['experience']) && $chef['experience'] > 0 && !empty($chef['specialty'])): ?>
                                        <div class="dot"></div>
                                    <?php endif; ?>

                                    <?php if (!empty($chef['specialty'])): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-fire-alt"></i>
                                            <?= htmlspecialchars($chef['specialty']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Description -->
                            <?php if (!empty($chef['description'])): ?>
                                <p class="chef-description">
                                    <?= nl2br(htmlspecialchars($chef['description'])) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Quote -->
                            <?php if (!empty($chef['quote'])): ?>
                                <div class="chef-quote">
                                    <p><?= htmlspecialchars($chef['quote']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="chefs-empty">
                <i class="fas fa-hat-chef"></i>
                <p>Đội ngũ đầu bếp đang được cập nhật...</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- FontAwesome (nếu chưa load từ layout) -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      crossorigin="anonymous" referrerpolicy="no-referrer">

<?php include __DIR__ . '/layouts/footer.php'; ?>
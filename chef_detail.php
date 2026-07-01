<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM chefs WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$chef = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chef) {
    header("Location: chefs.php");
    exit;
}

$img = !empty($chef['image']) ? 'public/assets/img/chefs/' . htmlspecialchars($chef['image']) : 'public/assets/img/chefs/default-chef.jpg';

// Fetch Signature Dishes
$sig_dishes = [];
if (!empty($chef['signature_dishes'])) {
    $dish_ids = explode(',', $chef['signature_dishes']);
    $in = str_repeat('?,', count($dish_ids) - 1) . '?';
    $stmt_d = $db->prepare("SELECT * FROM foods WHERE id IN ($in) AND status = 1 LIMIT 4");
    $stmt_d->execute($dish_ids);
    $sig_dishes = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
}

// Parse Awards
$awards = [];
if (!empty($chef['awards'])) {
    $lines = explode("\n", trim($chef['awards']));
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 2) {
            $name = $parts[0];
            $year = '';
            if (preg_match('/\b(19|20)\d{2}\b/', $name, $matches)) {
                $year = $matches[0];
                $name = trim(str_replace($year, '', $name));
            }
            $awards[] = [
                'name' => $name,
                'desc' => $parts[1],
                'icon' => trim($parts[2] ?? 'award'),
                'year' => $year,
                'link' => trim($parts[3] ?? '')
            ];
        }
    }
}

$page_title = htmlspecialchars($chef['name']) . " - Bếp Trưởng";
include __DIR__ . '/views/client/layouts/header.php';
?>
<style>
/* HERO SECTION */
.cd-hero {
    position: relative;
    height: 100vh;
    min-height: 600px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    text-align: center;
    color: #fff;
    background: #111;
    padding-bottom: 80px;
}
.cd-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: url('<?= $img ?>') center 20%/cover no-repeat;
    opacity: 0.6;
    z-index: 0;
}
.cd-hero::after {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    z-index: 1;
}
.cd-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    padding: 0 20px;
    animation: fadeInUp 1.5s ease;
}
.cd-position {
    font-size: 14px;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #cda45e;
    margin-bottom: 20px;
    display: block;
}
.cd-name {
    font-family: 'Playfair Display', serif;
    font-size: 5rem;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 30px;
    text-shadow: 2px 2px 10px rgba(0,0,0,0.8);
}
.cd-quote {
    font-family: 'Cormorant Garamond', serif;
    font-style: italic;
    font-size: 1.8rem;
    color: #ddd;
    line-height: 1.5;
}
.cd-quote::before, .cd-quote::after {
    content: '"';
    color: #cda45e;
    font-size: 2rem;
}

/* BIOGRAPHY */
.cd-section {
    padding: 100px 0;
    background: #ffffff;
    color: #444;
}
.cd-bio {
    max-width: 900px;
    margin: 0 auto;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.4rem;
    line-height: 1.9;
    color: #333;
    text-align: justify;
    text-justify: inter-word;
}
.cd-bio::first-letter {
    font-family: 'Playfair Display', serif;
    font-size: 6rem;
    float: left;
    line-height: 0.7;
    padding-right: 20px;
    padding-top: 12px;
    color: #cda45e;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
}

/* AWARDS */
.cd-awards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
    text-align: center;
}
.cd-award-item {
    padding: 40px 20px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(205, 164, 94, 0.2);
    border-radius: 10px;
    transition: 0.3s;
}
.cd-award-item:hover {
    background: rgba(205, 164, 94, 0.05);
    transform: translateY(-5px);
}
.cd-award-icon {
    font-size: 3rem;
    color: #cda45e;
    margin-bottom: 20px;
}
.cd-award-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    color: #111;
    margin-bottom: 10px;
}
.cd-award-desc {
    color: #aaa;
    font-size: 0.95rem;
}

/* SIGNATURE TECHNIQUE REDESIGN */
.cd-technique {
    background: linear-gradient(135deg, #fdfbf7 0%, #f4eee3 100%);
    border-top: 1px solid rgba(205, 164, 94, 0.2);
    border-bottom: 1px solid rgba(205, 164, 94, 0.2);
    position: relative;
    overflow: hidden;
    padding: 80px 0;
}
.cd-technique::before {
    content: '"';
    position: absolute;
    top: -40px;
    left: 50%;
    transform: translateX(-50%);
    font-family: 'Playfair Display', serif;
    font-size: 350px;
    color: rgba(205, 164, 94, 0.04);
    line-height: 1;
    z-index: 0;
}
.cd-tech-content {
    max-width: 1100px;
    margin: 0 auto;
    text-align: center;
    position: relative;
    z-index: 1;
    padding: 50px 40px;
    background: rgba(255,255,255,0.85);
    border: 1px solid rgba(205, 164, 94, 0.3);
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.05);
    backdrop-filter: blur(5px);
}
.cd-tech-title {
    font-family: 'Playfair Display', serif;
    color: #cda45e;
    font-size: 2.5rem;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 2px;
}
.cd-tech-desc {
    font-size: 1.15rem;
    line-height: 1.9;
    color: #333;
    font-style: italic;
}

/* SIGNATURE DISHES ZIG-ZAG */
.cd-dishes-list {
    max-width: 1000px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 40px;
}
.cd-dish-row {
    display: flex;
    flex-direction: column;
    gap: 30px;
    align-items: center;
    background: #ffffff;
    border: 1px solid rgba(205,164,94,0.2);
    border-radius: 10px;
    overflow: hidden;
    transition: 0.3s;
}
.cd-dish-row:hover {
    background: #fdfbf7;
    border-color: #cda45e;
}
@media (min-width: 768px) {
    .cd-dish-row {
        flex-direction: row;
    }
    .cd-dish-row.reverse {
        flex-direction: row-reverse;
    }
}
.cd-dish-img-wrap {
    flex: 1;
    width: 100%;
}
.cd-dish-img-wrap img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
}
.cd-dish-info-wrap {
    flex: 1;
    padding: 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.cd-dish-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    color: #cda45e;
    margin-bottom: 15px;
}
.cd-dish-desc {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}
.cd-dish-btn {
    align-self: flex-start;
    padding: 10px 20px;
    border: 1px solid #cda45e;
    color: #cda45e;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 1px;
    transition: 0.3s;
    text-decoration: none;
}
.cd-dish-btn:hover {
    background: #cda45e;
    color: #111;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* HALL OF HONORS */
.cd-hall-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 40px;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px 0;
}
.cd-hall-plaque {
    position: relative;
    background: #fdfbf7;
    border: 12px solid #1a1814;
    outline: 2px solid #cda45e;
    outline-offset: -18px;
    padding: 50px 30px;
    text-align: center;
    overflow: hidden;
    box-shadow: 10px 15px 30px rgba(0,0,0,0.15);
    transition: transform 0.4s ease, box-shadow 0.4s ease;
}
.cd-hall-plaque:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 15px 25px 40px rgba(0,0,0,0.25);
}
.plaque-bg-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 180px;
    color: rgba(205, 164, 94, 0.05);
    z-index: 0;
    pointer-events: none;
}
.plaque-content {
    position: relative;
    z-index: 1;
}
.plaque-year {
    display: inline-block;
    background: #1a1814;
    color: #cda45e;
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    padding: 5px 20px;
    border: 1px solid #cda45e;
    margin-bottom: 25px;
    letter-spacing: 2px;
}
.plaque-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    color: #111;
    font-weight: 700;
    margin-bottom: 15px;
    text-transform: uppercase;
}
.plaque-divider {
    width: 60px;
    height: 2px;
    background: #cda45e;
    margin: 0 auto 20px auto;
}
.plaque-desc {
    color: #555;
    line-height: 1.7;
    font-size: 0.95rem;
    margin-bottom: 30px;
}
.plaque-btn {
    display: inline-block;
    background: #cda45e;
    color: #111;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 1px;
    padding: 10px 25px;
    text-decoration: none;
    border-radius: 0px;
    border: 1px solid #1a1814;
    box-shadow: 0 4px 10px rgba(205, 164, 94, 0.3);
    transition: 0.3s;
}
.plaque-btn:hover {
    background: #1a1814;
    color: #cda45e;
    border-color: #cda45e;
}

.cd-tech-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 40px;
}
.cd-tech-part {
    background: #fdfbf7;
    border: 1px solid rgba(205, 164, 94, 0.2);
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.02);
}
.cd-tech-part:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(205, 164, 94, 0.1);
    border-color: #cda45e;
}
.tech-icon {
    font-size: 2rem;
    color: #cda45e;
    margin-bottom: 15px;
}
.tech-part-title {
    color: #111;
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    margin-bottom: 15px;
    position: relative;
    padding-bottom: 10px;
}
.tech-part-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 2px;
    background: #cda45e;
}
.cd-tech-part .cd-tech-desc {
    font-size: 0.95rem !important;
    line-height: 1.8 !important;
    color: #555 !important;
    font-style: normal !important;
}
</style>

<!-- HERO SECTION -->
<div class="cd-hero">
    <div class="cd-hero-content">
        <span class="cd-position"><?= htmlspecialchars($chef['position'] ?? 'Đầu Bếp') ?></span>
        <h1 class="cd-name"><?= htmlspecialchars($chef['name']) ?></h1>
        <?php if (!empty($chef['quote'])): ?>
            <p class="cd-quote"><?= htmlspecialchars($chef['quote']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- BIOGRAPHY -->
<?php if (!empty($chef['description'])): ?>
<div class="cd-section">
    <div class="container">
        <div class="cd-bio">
            <?= nl2br(htmlspecialchars($chef['description'])) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SIGNATURE TECHNIQUE -->
<?php if (!empty($chef['signature_technique']) || !empty($chef['signature_technique_process'])): ?>
<div class="cd-section st-story-section">
    <div class="container">
        
        <!-- Title & Quote -->
        <div class="st-header text-center mb-5">
            <h2 class="st-title">TUYỆT KỸ CHẾ BIẾN</h2>
            <div class="st-divider"></div>
        </div>

        <div class="st-story-body">




            <!-- Timeline -->
            <?php if (!empty($chef['signature_technique_process'])): ?>
            <div class="st-timeline-wrap mb-5">
                <h4 class="st-timeline-title text-center mb-4">Quy trình thực hiện</h4>
                <div class="st-timeline">
                    <?php
                    // Auto parse numbered lists into steps
                    $process_text = $chef['signature_technique_process'];
                    // Split by numbers like 1., 2., 3. or 1), 2)
                    $steps = preg_split('/^\s*\d+[\.\)]\s*/m', $process_text, -1, PREG_SPLIT_NO_EMPTY);
                    if (empty($steps) || count($steps) == 1) {
                        // If parsing fails (no numbers), just split by newlines
                        $steps = explode("\n", $process_text);
                    }
                    
                    foreach ($steps as $index => $step) {
                        $step = trim($step);
                        if ($step) {
                            $side = ($index % 2 == 0) ? 'left' : 'right';
                            echo '
                            <div class="st-timeline-item '.$side.'">
                                <div class="st-timeline-dot"></div>
                                <div class="st-timeline-content">
                                    <span class="st-step-num">BƯỚC '.($index + 1).'</span>
                                    <p>' . nl2br(htmlspecialchars($step)) . '</p>
                                </div>
                            </div>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Final Result -->
            <?php if (!empty($chef['signature_technique_final_result'])): ?>
            <div class="st-final-result text-center">
                <div class="st-final-icon mb-3"><i class="bi bi-gem"></i></div>
                <h4 class="st-final-title">Thành Quả</h4>
                <p class="st-final-text"><?= nl2br(htmlspecialchars($chef['signature_technique_final_result'])) ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php endif; ?>

<!-- AWARDS -->
<?php if (!empty($awards)): ?>
<div class="cd-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="font-family: 'Playfair Display', serif; color: #111; font-size: 2.5rem;">BẢNG VÀNG DANH DỰ</h2>
            <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        </div>
        <div class="cd-hall-grid">
            <?php foreach ($awards as $award): ?>
            <div class="cd-hall-plaque">
                <div class="plaque-bg-icon">
                    <i class="bi bi-<?= htmlspecialchars($award['icon']) ?>"></i>
                </div>
                <div class="plaque-content">
                    <?php if (!empty($award['year'])): ?>
                        <div class="plaque-year"><?= htmlspecialchars($award['year']) ?></div>
                    <?php endif; ?>
                    <h3 class="plaque-title"><?= htmlspecialchars($award['name']) ?></h3>
                    <div class="plaque-divider"></div>
                    <p class="plaque-desc"><?= htmlspecialchars($award['desc']) ?></p>
                    <?php if (!empty($award['link'])): ?>
                        <a href="<?= htmlspecialchars($award['link']) ?>" target="_blank" class="plaque-btn">
                            <i class="bi bi-award"></i> Xem Chứng Nhận
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SIGNATURE DISHES -->
<?php if (!empty($sig_dishes)): ?>
<div class="cd-section" style="background: #fdfbf7;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="font-family: 'Playfair Display', serif; color: #111; font-size: 2.5rem;">Kiệt Tác Ẩm Thực</h2>
            <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        </div>
        <div class="cd-dishes-list">
            <?php foreach ($sig_dishes as $index => $dish): ?>
            <div class="cd-dish-row <?= $index % 2 != 0 ? 'reverse' : '' ?>">
                <div class="cd-dish-img-wrap">
                    <img src="public/assets/img/menu/<?= htmlspecialchars($dish['image']) ?>" alt="<?= htmlspecialchars($dish['name']) ?>">
                </div>
                <div class="cd-dish-info-wrap">
                    <h4 class="cd-dish-name"><?= htmlspecialchars($dish['name']) ?></h4>
                    <p class="cd-dish-desc"><?= mb_strimwidth(strip_tags($dish['description'] ?? ''), 0, 150, '...') ?></p>
                    <a href="dish.php?id=<?= $dish['id'] ?>" class="cd-dish-btn">Khám phá hương vị</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- CHEF GALLERY -->
<?php 
$gallery_images = [];
if (!empty($chef['gallery_images'])) {
    $gallery_images = json_decode($chef['gallery_images'], true) ?: [];
}
if (!empty($gallery_images)): 
?>
<div class="cd-section st-gallery-section">
    <div class="container-fluid px-0">
        <div class="st-gallery-header text-center mb-5">
            <h2 style="font-family: 'Playfair Display', serif; color: #111; font-size: 2.5rem; letter-spacing: 2px;">Thư Viện Ảnh</h2>
            <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        </div>
        
        <div class="st-gallery-grid">
            <?php foreach ($gallery_images as $g_img): ?>
                <div class="st-gallery-item">
                    <img src="/restaurant-project/public/assets/img/chefs/gallery/<?= htmlspecialchars($g_img) ?>" alt="Gallery">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.st-gallery-section {
    padding: 40px 0;
    background-color: #fff;
    overflow: hidden;
}
.st-gallery-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 4px;
    max-width: 100%;
    margin: 0;
    padding: 0;
}
@media (min-width: 768px) {
    .st-gallery-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (min-width: 1024px) {
    .st-gallery-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
.st-gallery-item {
    overflow: hidden;
    position: relative;
    background: #000;
}
.st-gallery-item img {
    width: 100%;
    aspect-ratio: 16 / 9;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    opacity: 0.95;
    display: block;
}
.st-gallery-item:hover img {
    transform: scale(1.05);
    opacity: 1;
}
</style>


<?php endif; ?>

<div class="cd-section" style="background: #fcfaf5;">
    <div class="container" style="max-width: 1000px; margin: 0 auto;">
        <div class="text-center mb-5">
            <h2 style="font-family: 'Playfair Display', serif; color: #111; font-size: 2.5rem;">Trải Nghiệm Cùng Bếp Trưởng</h2>
            <p style="color: #666; font-size: 1rem; margin-top: 10px;">Những chia sẻ chân thực từ các thực khách đã trực tiếp trải nghiệm ẩm thực do Bếp trưởng thực hiện.</p>
            <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        </div>
        
        <!-- Prestige Stats -->
        <div class="prestige-stats">
            <div class="prestige-main">
                <div class="rating-big" id="modalAvgRating">0.0<span style="font-size:1.2rem;">/5</span></div>
                <div class="stars-wrap mb-2" id="modalAvgStars"></div>
                <div class="reviews-count" id="modalReviewsCount">Được đánh giá từ 0 trải nghiệm</div>
                <div class="recommend-text">98% khách hàng sẵn sàng giới thiệu Bếp trưởng cho bạn bè.</div>
            </div>
            <div class="prestige-cards">
                <div class="p-card"><i class="bi bi-award-fill"></i><span><?= htmlspecialchars($chef['experience'] ?? '15') ?> năm kinh nghiệm</span></div>
                <div class="p-card"><i class="bi bi-people-fill"></i><span>850+ thực khách đã phục vụ</span></div>
                <div class="p-card"><i class="bi bi-arrow-repeat"></i><span>96% khách quay lại</span></div>
                <div class="p-card"><i class="bi bi-journal-check"></i><span>35 thực đơn Bespoke</span></div>
            </div>
        </div>

        <div class="chef-modal-section" style="background: transparent; border: none; padding: 0;">
            <!-- Reviews list -->
            <div id="modalReviewsList" class="luxury-reviews-list">
              <!-- Rendered dynamically -->
            </div>
            
            <div id="reviewsShowMoreContainer" class="text-center mt-4" style="display: none;">
                <button type="button" id="btnShowMoreReviews" class="btn-show-more-luxury">Xem thêm trải nghiệm</button>
            </div>

            <!-- Submit Review Form -->
            <div class="write-review-luxury mt-5">
              <h5 style="font-family:'Playfair Display',serif; font-size: 1.5rem; color: #111; margin-bottom: 20px; text-align: center;">Chia Sẻ Trải Nghiệm Của Bạn</h5>
              <form id="submitReviewForm" onsubmit="submitChefReview(event)">
                <input type="hidden" name="chef_id" id="reviewChefId" value="<?= $chef['id'] ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="font-size: 0.9rem; color: #555; font-weight: 600;">Loại trải nghiệm</label>
                        <select name="experience_type" class="form-select luxury-input" required>
                            <option value="Fine Dining">Fine Dining</option>
                            <option value="Chef's Table">Chef's Table</option>
                            <option value="Private Dining">Private Dining</option>
                            <option value="Bespoke Menu">Bespoke Menu</option>
                            <option value="Anniversary Dinner">Anniversary Dinner</option>
                            <option value="Corporate Event">Corporate Event</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="font-size: 0.9rem; color: #555; font-weight: 600;">Đánh giá sao</label>
                        <div class="star-rating-picker">
                          <div class="rating-stars-picker">
                            <i class="bi bi-star-fill star-pick active" data-val="1"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="2"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="3"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="4"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="5"></i>
                          </div>
                          <input type="hidden" name="rating" id="selectedRatingVal" value="5">
                        </div>
                    </div>
                </div>

                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="mb-3">
                  <input type="text" name="author_name" id="reviewAuthorName" class="luxury-input" placeholder="Tên của bạn (Tùy chọn)">
                </div>
                <?php endif; ?>

                <div class="mb-4">
                  <textarea name="comment" id="reviewComment" class="luxury-input" rows="4" placeholder="Kể về trải nghiệm ẩm thực đáng nhớ của bạn..." required></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn-submit-luxury">Gửi Cảm Nhận</button>
                </div>
              </form>
            </div>
        </div>
    </div>
</div>
</div>
<script>
// Fetch Reviews & Ratings
let allReviews = [];
let currentlyDisplayedReviews = 0;

function renderReviewsChunk(reviewsToRender, append = false) {
    let reviewsList = document.getElementById('modalReviewsList');
    if (!append) reviewsList.innerHTML = '';
    
    reviewsToRender.forEach(rev => {
        let div = document.createElement('div');
        div.className = 'review-card';
        
        let avatarSrc = rev.user_avatar ? 'public/assets/img/avatars/' + rev.user_avatar : 'https://placehold.co/50x50/F6F2E9/4F5B3A?text=' + rev.author_name.charAt(0);
        
        let starsHtml = '';
        for (let j = 1; j <= 5; j++) {
            starsHtml += j <= rev.rating ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>';
        }
        
        div.innerHTML = `
            <div class="review-header">
                <img class="review-avatar" src="${avatarSrc}" onerror="this.onerror=null; this.src='https://placehold.co/50x50/262629/A88746?text=${rev.author_name.charAt(0)}'" />
                <div class="review-meta">
                    <span class="review-author">${escapeHtml(rev.author_name)}</span>
                    <span class="review-date">${rev.created_at}</span>
                </div>
                <div class="review-stars">${starsHtml}</div>
            </div>
            <div class="review-comment">${escapeHtml(rev.comment)}</div>
        `;
        reviewsList.appendChild(div);
    });
}

function fetchChefReviews(chefId) {
    fetch('ajax/get_chef_reviews.php?chef_id=' + chefId)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('modalAvgRating').textContent = data.avg_rating > 0 ? data.avg_rating.toFixed(1) : '0.0';
                document.getElementById('modalReviewsCount').textContent = data.review_count + ' đánh giá';
                
                let avgStarsContainer = document.getElementById('modalAvgStars');
                avgStarsContainer.innerHTML = '';
                let ratingVal = Math.round(data.avg_rating);
                for (let i = 1; i <= 5; i++) {
                    let star = document.createElement('i');
                    star.className = i <= ratingVal ? 'bi bi-star-fill text-warning' : 'bi bi-star';
                    avgStarsContainer.appendChild(star);
                }
                
                if (data.reviews && data.reviews.length > 0) {
                    allReviews = data.reviews;
                    currentlyDisplayedReviews = Math.min(2, allReviews.length);
                    renderReviewsChunk(allReviews.slice(0, currentlyDisplayedReviews));
                    
                    if (allReviews.length > 2) {
                        document.getElementById('reviewsShowMoreContainer').style.display = 'block';
                    } else {
                        document.getElementById('reviewsShowMoreContainer').style.display = 'none';
                    }
                } else {
                    document.getElementById('modalReviewsList').innerHTML = '<p class="text-center text-muted py-3">Chưa có đánh giá nào cho đầu bếp này. Hãy là người đầu tiên chia sẻ cảm nhận!</p>';
                    document.getElementById('reviewsShowMoreContainer').style.display = 'none';
                }
            }
        });
}

document.addEventListener("DOMContentLoaded", function() {
    let btnShowMore = document.getElementById('btnShowMoreReviews');
    if(btnShowMore) {
        btnShowMore.addEventListener('click', function() {
            let nextBatch = allReviews.slice(currentlyDisplayedReviews, currentlyDisplayedReviews + 5);
            renderReviewsChunk(nextBatch, true);
            currentlyDisplayedReviews += nextBatch.length;
            
            if (currentlyDisplayedReviews >= allReviews.length) {
                document.getElementById('reviewsShowMoreContainer').style.display = 'none';
            }
        });
    }
});

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function submitChefReview(event) {
    event.preventDefault();
    let form = event.target;
    let formData = new FormData(form);
    
    fetch('ajax/submit_chef_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            form.reset();
            resetStarPicker();
            let chefId = document.getElementById('reviewChefId').value;
            fetchChefReviews(chefId);
        } else {
            alert(data.message || 'Có lỗi xảy ra.');
        }
    })
    .catch(error => {
        console.error('Error submitting review:', error);
        alert('Có lỗi xảy ra khi gửi đánh giá.');
    });
}

// Star rating picker interactivity
document.addEventListener('DOMContentLoaded', function() {
    let starPicks = document.querySelectorAll('.star-pick');
    starPicks.forEach(star => {
        star.addEventListener('click', function() {
            let val = parseInt(this.getAttribute('data-val'));
            document.getElementById('selectedRatingVal').value = val;
            updateStarPicker(val);
        });
        star.addEventListener('mouseover', function() {
            let val = parseInt(this.getAttribute('data-val'));
            updateStarPicker(val);
        });
    });
    
    let starsContainer = document.querySelector('.rating-stars-picker');
    if (starsContainer) {
        starsContainer.addEventListener('mouseleave', function() {
            let val = parseInt(document.getElementById('selectedRatingVal').value);
            updateStarPicker(val);
        });
    }
});

function updateStarPicker(val) {
    let starPicks = document.querySelectorAll('.star-pick');
    starPicks.forEach(star => {
        let starVal = parseInt(star.getAttribute('data-val'));
        if (starVal <= val) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function resetStarPicker() {
    document.getElementById('selectedRatingVal').value = '5';
    updateStarPicker(5);
}


document.addEventListener("DOMContentLoaded", function() {
    let chefId = <?= $id ?>;
    document.getElementById("reviewChefId").value = chefId;
    fetchChefReviews(chefId);
});
</script>
<style>
/* Star Rating Picker */
.star-rating-picker {
  display: flex;
  align-items: center;
  gap: 12px;
}
.rating-picker-label {
  font-size: 13px;
  color: var(--text-muted);
}
.rating-stars-picker {
  display: flex;
  gap: 6px;
  cursor: pointer;
}
.rating-stars-picker .star-pick {
  font-size: 20px;
  color: #4A4A4F;
  transition: color 0.2s;
}
.rating-stars-picker .star-pick.active,
.rating-stars-picker .star-pick:hover {
  color: #FFC107 !important;
}

/* Reviews Summary */
.reviews-summary {
  display: flex;
  align-items: center;
  gap: 20px;
  background: var(--bg-color);
  padding: 15px 20px;
  border: 1px solid var(--border-light);
  margin-bottom: 25px;
}
.rating-big {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--gold);
  line-height: 1;
}
.rating-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.stars-wrap {
  display: flex;
  gap: 3px;
}
.reviews-count {
  font-size: 12px;
  color: var(--text-muted);
}

/* Reviews List */
.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
  max-height: 250px;
  overflow-y: auto;
  padding-right: 8px;
  margin-bottom: 25px;
}
.reviews-list::-webkit-scrollbar {
  width: 4px;
}
.reviews-list::-webkit-scrollbar-thumb {
  background: var(--gold);
  border-radius: 2px;
}
.review-card {
  background: var(--bg-color);
  border: 1px solid var(--border-light);
  padding: 15px;
}
.review-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}
.review-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid var(--border-light);
}
.review-meta {
  display: flex;
  flex-direction: column;
  flex: 1;
}
.review-author {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-main);
}
.review-date {
  font-size: 10px;
  color: var(--text-muted);
}
.review-stars {
  display: flex;
  gap: 2px;
  font-size: 11px;
}
.review-comment {
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.6;
  white-space: pre-line;
}

/* Review form inputs */
.review-input {
  width: 100%;
  background: var(--bg-color);
  border: 1px solid var(--border-light);
  color: var(--text-main);
  padding: 10px 14px;
  font-size: 13px;
  outline: none;
  font-family: inherit;
  transition: border-color 0.2s;
}
.review-input:focus {
  border-color: var(--gold);
}
.btn-submit-review {
  background: var(--gold);
  border: 1px solid var(--gold);
  color: #111;
  padding: 8px 20px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
}
.btn-submit-review:hover {
  background: transparent;
  color: var(--gold);
}


/* PREMIUM LIGHT THEME REVIEWS */
.reviews-summary, .review-card { 
    background: #ffffff !important; 
    color: #333 !important; 
    border: 1px solid rgba(205,164,94,0.2) !important; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.04) !important; 
    border-radius: 16px !important; 
}
.review-input {
    background: #fdfbf7 !important; 
    color: #333 !important; 
    border: 1px solid rgba(205,164,94,0.3) !important; 
    border-radius: 12px !important;
    padding: 15px !important;
}
.review-author { color: #111 !important; font-size: 1.1rem !important; font-family: 'Playfair Display', serif !important; }
.review-date { color: #888 !important; }
.review-comment { color: #555 !important; font-size: 0.95rem !important; margin-top: 10px !important; }
.review-avatar { width: 45px !important; height: 45px !important; border: 2px solid #cda45e !important; }
.rating-picker-label { color: #111 !important; font-weight: bold !important; font-size: 1.1rem !important; margin-bottom: 10px !important; display: block; }
.btn-submit-review { border-radius: 25px !important; padding: 10px 25px !important; font-size: 0.9rem !important; box-shadow: 0 5px 15px rgba(205, 164, 94, 0.3) !important; }

/* HALL OF HONORS */
.cd-hall-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 40px;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px 0;
}
.cd-hall-plaque {
    position: relative;
    background: #fdfbf7;
    border: 12px solid #1a1814;
    outline: 2px solid #cda45e;
    outline-offset: -18px;
    padding: 50px 30px;
    text-align: center;
    overflow: hidden;
    box-shadow: 10px 15px 30px rgba(0,0,0,0.15);
    transition: transform 0.4s ease, box-shadow 0.4s ease;
}
.cd-hall-plaque:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 15px 25px 40px rgba(0,0,0,0.25);
}
.plaque-bg-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 180px;
    color: rgba(205, 164, 94, 0.05);
    z-index: 0;
    pointer-events: none;
}
.plaque-content {
    position: relative;
    z-index: 1;
}
.plaque-year {
    display: inline-block;
    background: #1a1814;
    color: #cda45e;
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    padding: 5px 20px;
    border: 1px solid #cda45e;
    margin-bottom: 25px;
    letter-spacing: 2px;
}
.plaque-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    color: #111;
    font-weight: 700;
    margin-bottom: 15px;
    text-transform: uppercase;
}
.plaque-divider {
    width: 60px;
    height: 2px;
    background: #cda45e;
    margin: 0 auto 20px auto;
}
.plaque-desc {
    color: #555;
    line-height: 1.7;
    font-size: 0.95rem;
    margin-bottom: 30px;
}
.plaque-btn {
    display: inline-block;
    background: #cda45e;
    color: #111;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 1px;
    padding: 10px 25px;
    text-decoration: none;
    border-radius: 0px;
    border: 1px solid #1a1814;
    box-shadow: 0 4px 10px rgba(205, 164, 94, 0.3);
    transition: 0.3s;
}
.plaque-btn:hover {
    background: #1a1814;
    color: #cda45e;
    border-color: #cda45e;
}

.cd-tech-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 40px;
}
.cd-tech-part {
    background: #fdfbf7;
    border: 1px solid rgba(205, 164, 94, 0.2);
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.02);
}
.cd-tech-part:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(205, 164, 94, 0.1);
    border-color: #cda45e;
}
.tech-icon {
    font-size: 2rem;
    color: #cda45e;
    margin-bottom: 15px;
}
.tech-part-title {
    color: #111;
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    margin-bottom: 15px;
    position: relative;
    padding-bottom: 10px;
}
.tech-part-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 2px;
    background: #cda45e;
}
.cd-tech-part .cd-tech-desc {
    font-size: 0.95rem !important;
    line-height: 1.8 !important;
    color: #555 !important;
    font-style: normal !important;
}
</style>


<style>
/* PRESTIGE STATS */
.prestige-stats {
    background: #fff;
    border: 1px solid rgba(205, 164, 94, 0.2);
    padding: 40px;
    margin-bottom: 50px;
    display: flex;
    align-items: center;
    gap: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
}
.prestige-main {
    text-align: center;
    min-width: 250px;
    border-right: 1px dashed rgba(205, 164, 94, 0.3);
    padding-right: 40px;
}
.prestige-main .rating-big {
    font-size: 4rem;
    font-family: 'Playfair Display', serif;
    color: #cda45e;
    line-height: 1;
}
.prestige-main .stars-wrap {
    text-align: center;
    margin-bottom: 0.5rem;
}
.prestige-main .stars-wrap i {
    font-size: 1.2rem;
    color: #cda45e;
}
.prestige-main .reviews-count {
    font-size: 0.9rem;
    color: #888;
    margin-top: 10px;
}
.prestige-main .recommend-text {
    font-size: 0.85rem;
    color: #111;
    font-weight: 600;
    margin-top: 15px;
}
.prestige-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    flex-grow: 1;
}
.p-card {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #fdfbf7;
    padding: 15px 20px;
    border-radius: 4px;
    border-left: 3px solid #cda45e;
}
.p-card i {
    font-size: 1.5rem;
    color: #cda45e;
}
.p-card span {
    font-size: 0.95rem;
    color: #333;
    font-weight: 500;
}

/* LUXURY REVIEW CARD */
.luxury-reviews-list {
    display: flex;
    flex-direction: column;
    gap: 30px;
}
.luxury-review-card {
    background: #fff;
    border: 1px solid rgba(205, 164, 94, 0.2);
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.02);
    transition: all 0.4s ease;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeUp 0.6s forwards;
}
@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}
.luxury-review-card:hover {
    box-shadow: 0 15px 35px rgba(205, 164, 94, 0.1);
    border-color: #cda45e;
    transform: translateY(-3px);
}
.luxury-review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}
.l-author-info {
    display: flex;
    align-items: center;
    gap: 15px;
}
.l-avatar {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fdfbf7;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.l-meta h4 {
    margin: 0 0 5px 0;
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    color: #111;
}
.l-date {
    font-size: 0.8rem;
    color: #888;
}
.l-right {
    text-align: right;
}
.l-stars {
    margin-bottom: 5px;
}
.l-stars i {
    color: #cda45e;
    font-size: 1rem;
    transition: transform 0.3s ease;
    display: inline-block;
}
.luxury-review-card:hover .l-stars i {
    animation: starPop 0.5s ease;
}
@keyframes starPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}
.l-exp-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #1a1814;
    color: #cda45e;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 20px;
}
.luxury-review-body {
    font-family: 'Open Sans', sans-serif;
    color: #444;
    font-size: 1rem;
    line-height: 1.8;
    font-style: italic;
    position: relative;
    padding-left: 20px;
}
.luxury-review-body::before {
    content: '"';
    position: absolute;
    top: -10px;
    left: -10px;
    font-family: 'Playfair Display', serif;
    font-size: 4rem;
    color: rgba(205, 164, 94, 0.15);
    line-height: 1;
}

/* CHEF RESPONSE */
.chef-response-box {
    margin-top: 25px;
    background: #fdfbf7;
    border-left: 3px solid #cda45e;
    padding: 20px;
    display: flex;
    gap: 15px;
}
.chef-response-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}
.chef-response-content h5 {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    color: #111;
    margin: 0 0 5px 0;
}
.chef-response-content p {
    font-family: 'Open Sans', sans-serif;
    font-size: 0.9rem;
    color: #555;
    margin: 0;
    line-height: 1.6;
}

/* FORM LUXURY */
.write-review-luxury {
    background: #fff;
    padding: 40px;
    border: 1px solid rgba(205, 164, 94, 0.2);
    box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}
.luxury-input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    background: #fdfbf7;
    border-radius: 0;
    font-family: 'Open Sans', sans-serif;
    transition: 0.3s;
}
.luxury-input:focus {
    border-color: #cda45e;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(205, 164, 94, 0.25);
}
.btn-submit-luxury {
    background: #cda45e;
    color: #111;
    border: none;
    padding: 12px 35px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-family: 'Playfair Display', serif;
    transition: 0.3s;
}
.btn-submit-luxury:hover {
    background: #1a1814;
    color: #cda45e;
}
.btn-show-more-luxury {
    background: transparent;
    border: 1px solid #cda45e;
    color: #cda45e;
    padding: 10px 30px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.85rem;
    transition: 0.3s;
}
.btn-show-more-luxury:hover {
    background: #cda45e;
    color: #111;
}

@media(max-width: 768px){
    .prestige-stats { flex-direction: column; gap: 30px; padding: 25px; }
    .prestige-main { border-right: none; border-bottom: 1px dashed rgba(205,164,94,0.3); padding-right: 0; padding-bottom: 30px; }
    .prestige-cards { grid-template-columns: 1fr; }
    .luxury-review-header { flex-direction: column; gap: 15px; }
    .l-right { text-align: left; }
}
</style>
<script>
// OVERRIDE OLD JS FUNCTIONS
function renderReviewsChunk(reviewsToRender, append = false) {
    let reviewsList = document.getElementById('modalReviewsList');
    if (!append) reviewsList.innerHTML = '';
    
    reviewsToRender.forEach((rev, index) => {
        let div = document.createElement('div');
        div.className = 'luxury-review-card';
        div.style.animationDelay = (index * 0.1) + 's';
        
        let avatarSrc = rev.user_avatar ? 'public/assets/img/avatars/' + rev.user_avatar : 'https://placehold.co/100x100/F6F2E9/A88746?text=' + rev.author_name.charAt(0);
        let chefAvatar = '<?= $img ?>';
        
        let starsHtml = '';
        for (let j = 1; j <= 5; j++) {
            starsHtml += j <= rev.rating ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
        }
        
        let expType = rev.experience_type || 'Fine Dining';
        
        let chefResponseHtml = '';
        if (rev.chef_response) {
            chefResponseHtml = `
            <div class="chef-response-box">
                <img src="${chefAvatar}" class="chef-response-avatar" alt="Chef">
                <div class="chef-response-content">
                    <h5>Executive Chef Response</h5>
                    <p>${escapeHtml(rev.chef_response)}</p>
                </div>
            </div>`;
        }
        
        div.innerHTML = `
            <div class="luxury-review-header">
                <div class="l-author-info">
                    <img class="l-avatar" src="${avatarSrc}" onerror="this.src='https://placehold.co/100x100/1a1814/A88746?text=${rev.author_name.charAt(0)}'" />
                    <div class="l-meta">
                        <h4>${escapeHtml(rev.author_name)}</h4>
                        <div class="l-date">${rev.created_at}</div>
                    </div>
                </div>
                <div class="l-right">
                    <div class="l-stars">${starsHtml}</div>
                    <div class="l-exp-badge">${escapeHtml(expType)}</div>
                </div>
            </div>
            <div class="luxury-review-body">
                ${escapeHtml(rev.comment)}
            </div>
            ${chefResponseHtml}
        `;
        reviewsList.appendChild(div);
    });
}
// Automatically trigger the fetch for the initial load since the HTML ID is the same 
// and the old script is still present further down (we just override the render function)
setTimeout(() => {
    let chefId = document.getElementById('reviewChefId').value;
    if(chefId) fetchChefReviews(chefId);
}, 500);
</script>

<style>

/* STORYTELLING SIGNATURE TECHNIQUE CSS */
.st-story-section {
    background-color: #fdfbf7;
    padding: 80px 0;
    border-top: 1px solid rgba(205, 164, 94, 0.2);
    border-bottom: 1px solid rgba(205, 164, 94, 0.2);
}
.st-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    color: #1a1814;
    letter-spacing: 2px;
    margin-bottom: 15px;
}
.st-divider {
    width: 60px;
    height: 2px;
    background: #cda45e;
    margin: 0 auto 30px auto;
}
.st-quote-wrap {
    max-width: 800px;
    margin: 0 auto;
    padding: 10px 20px;
}
.st-quote-icon-inline {
    font-size: 1.8rem;
    color: #cda45e;
    opacity: 0.5;
    vertical-align: middle;
}
.st-quote-text {
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    font-style: italic;
    color: #333;
    line-height: 1.8;
    margin: 0;
    display: inline;
}
.st-story-body {
    max-width: 800px;
    margin: 0 auto;
}
.st-intro-box p {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #555;
    text-align: center;
}
.st-specs-box {
    background: #fff;
    padding: 30px;
    border: 1px solid rgba(205, 164, 94, 0.3);
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    position: relative;
}
.st-difficulty-label {
    font-size: 0.85rem;
    color: #888;
    letter-spacing: 1px;
}
.st-difficulty-badge {
    display: inline-block;
    padding: 8px 20px;
    background: #1a1814;
    color: #cda45e;
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    border-radius: 30px;
}
.text-gold { color: #cda45e; }
.st-specs-list li {
    font-size: 1.05rem;
    color: #333;
    margin-bottom: 10px;
}

/* TIMELINE */
.st-timeline-title {
    font-family: 'Playfair Display', serif;
    color: #1a1814;
    font-size: 1.8rem;
}
.st-timeline {
    position: relative;
    max-width: 100%;
    margin: 0 auto;
}
.st-timeline::after {
    content: '';
    position: absolute;
    width: 2px;
    background-color: rgba(205, 164, 94, 0.3);
    top: 0;
    bottom: 0;
    left: 50%;
    margin-left: -1px;
}
.st-timeline-item {
    padding: 15px 15px;
    position: relative;
    background-color: inherit;
    width: 50%;
}
.st-timeline-item.left { left: 0; }
.st-timeline-item.right { left: 50%; }
.st-timeline-dot {
    position: absolute;
    width: 16px;
    height: 16px;
    right: -8px;
    background-color: #cda45e;
    border: 3px solid #fff;
    top: 15px;
    border-radius: 50%;
    z-index: 1;
    box-shadow: 0 0 0 4px rgba(205, 164, 94, 0.2);
}
.st-timeline-item.right .st-timeline-dot { left: -8px; }
.st-timeline-content {
    padding: 20px 30px;
    background-color: white;
    position: relative;
    border: 1px solid rgba(205, 164, 94, 0.15);
    border-radius: 4px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.02);
}
.st-step-num {
    display: block;
    font-size: 0.8rem;
    color: #cda45e;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 8px;
}
.st-timeline-content p { margin: 0; color: #555; font-size: 0.95rem; line-height: 1.6; }

/* FINAL RESULT */
.st-final-result {
    padding: 40px;
    background: linear-gradient(135deg, #1a1814 0%, #111 100%);
    color: #fff;
    border: 1px solid #cda45e;
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
}
.st-final-icon { font-size: 2.5rem; color: #cda45e; line-height: 1; }
.st-final-title { font-family: 'Playfair Display', serif; color: #cda45e; font-size: 1.8rem; margin-bottom: 15px; }
.st-final-text { font-size: 1.1rem; color: #ddd; font-style: italic; line-height: 1.8; margin: 0; }

@media screen and (max-width: 768px) {
    .st-timeline::after { left: 31px; }
    .st-timeline-item { width: 100%; padding-left: 70px; padding-right: 25px; }
    .st-timeline-item.left, .st-timeline-item.right { left: 0; }
    .st-timeline-item.left .st-timeline-dot, .st-timeline-item.right .st-timeline-dot { left: 23px; right: auto; }
}

</style>
<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>

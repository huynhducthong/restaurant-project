<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// 1. Lấy dữ liệu Câu chuyện
$stmt = $db->prepare("SELECT * FROM about_content WHERE category_id = (SELECT id FROM about_categories WHERE slug='cau-chuyen' LIMIT 1) AND status = 1 ORDER BY is_pinned DESC, display_order ASC LIMIT 1");
$stmt->execute();
$story = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Lấy dữ liệu Đội ngũ
$stmt2 = $db->prepare("SELECT * FROM about_content WHERE category_id = (SELECT id FROM about_categories WHERE slug='doi-ngu' LIMIT 1) AND status = 1 ORDER BY display_order ASC");
$stmt2->execute();
$team = $stmt2->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/client/layouts/header.php'; 
?>

<style>
    /* Reset & Base Styles */
    .about-section { padding: 80px 0; color: #fff; }
    .ck-content-view { line-height: 1.8; color: #ced4da; font-size: 1.05rem; }
    .ck-content-view p { margin-bottom: 1rem; }
    
    .section-title { margin-bottom: 50px; }
    .section-title h2 {
        font-family: 'Playfair Display', serif;
        color: #cda45e;
        font-size: 2.5rem;
        position: relative;
        padding-bottom: 15px;
    }
    .section-title h2::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 80px;
        height: 2px;
        background: #cda45e;
    }

    /* Hình ảnh */
    .img-hover-effect {
        transition: transform 0.4s ease;
        border: 1px solid rgba(205, 164, 94, 0.2);
    }
    .img-hover-effect:hover {
        transform: scale(1.02);
        border-color: #cda45e;
    }

    .chef-img-container {
        width: 280px;
        height: 280px;
        margin: 0 auto;
    }
    .chef-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: 5px solid #37332a;
        transition: 0.3s;
    }
    .chef-img-container img:hover { border-color: #cda45e; }

    hr.light-sep { border-top: 1px solid rgba(255,255,255,0.05); margin: 60px 0; }
</style>

<section id="about-hero" class="d-flex align-items-center" style="background: url('public/assets/img/about-bg.jpg') center/cover; height: 400px; position: relative;">
    <div class="container text-center" style="z-index: 2;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 4rem; color: #cda45e; text-transform: uppercase;">Về Chúng Tôi</h1>
        <div style="width: 100px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        <p class="fst-italic text-white">Nơi tinh hoa ẩm thực hội tụ và tỏa sáng</p>
    </div>
    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.75);"></div>
</section>

<section class="about-section" style="background: #0c0b09;">
    <div class="container">
        <?php if ($story): ?>
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <img src="public/assets/img/about/<?= htmlspecialchars($story['thumbnail']) ?>" 
                     class="img-fluid rounded shadow-lg img-hover-effect" 
                     alt="Our Story">
            </div>
            <div class="col-lg-6 ps-lg-5">
                <div class="section-title">
                    <h2><?= htmlspecialchars($story['title']) ?></h2>
                </div>
                <div class="ck-content-view">
                    <?= htmlspecialchars_decode($story['content']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="about-section" style="background: #1a1814;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 3rem;">Đội Ngũ Tài Năng</h2>
            <p class="fst-italic text-muted">Những tâm hồn nghệ sĩ đứng sau mỗi món ăn</p>
        </div>

        <div class="row">
            <?php if (!empty($team)): ?>
                <?php foreach($team as $index => $member): 
                    // Logic để đảo bên: Người chẵn ảnh trái, người lẻ ảnh phải
                    $isEven = ($index % 2 == 0);
                ?>
                <div class="col-12 mb-5">
                    <div class="row align-items-center <?= $isEven ? '' : 'flex-row-reverse' ?>">
                        
                        <div class="col-md-5 text-center">
                            <div class="chef-img-container mb-4 mb-md-0">
                                <img src="public/assets/img/about/<?= htmlspecialchars($member['thumbnail']) ?>" 
                                     class="rounded-circle shadow">
                            </div>
                        </div>

                        <div class="col-md-7 <?= $isEven ? 'ps-md-5' : 'pe-md-5' ?> text-start">
                            <h3 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 2rem;">
                                <?= htmlspecialchars($member['title']) ?>
                            </h3>
                            <div style="width: 50px; height: 1px; background: #cda45e; margin-bottom: 20px;"></div>
                            <div class="ck-content-view">
                                <?= htmlspecialchars_decode($member['content']) ?>
                            </div>
                        </div>

                    </div>
                    <?php if ($index < count($team) - 1): ?>
                        <hr class="light-sep">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">Dữ liệu đang được cập nhật...</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
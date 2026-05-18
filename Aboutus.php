<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// ============================================================
// BỔ SUNG: Hàm làm sạch HTML cơ bản để chống XSS
// ============================================================
function safe_html_render($html)
{
    if (empty($html))
        return '';

    $decoded = htmlspecialchars_decode($html);

    // Chỉ cho phép các thẻ định dạng văn bản cơ bản (cấm <script>, <iframe>, <object>...)
    $allowed_tags = '<h1><h2><h3><h4><h5><h6><p><br><b><i><strong><em><u><ul><ol><li><a><img><blockquote><span><div><table><thead><tbody><tr><th><td>';
    $cleaned = strip_tags($decoded, $allowed_tags);

    // Xóa các thuộc tính sự kiện javascript (vd: onclick="...", onmouseover="...")
    $cleaned = preg_replace('/on[a-zA-Z]+\s*=\s*["\'][^"\']*["\']/i', '', $cleaned);

    // Xóa link chứa mã javascript trực tiếp (vd: href="javascript:alert(1)")
    $cleaned = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $cleaned);

    return $cleaned;
}

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
    .about-section {
        padding: 80px 0;
        color: #fff;
        background: #0c0b09;
    }

    .ck-content-view {
        line-height: 1.8;
        color: #ced4da;
        font-size: 1.05rem;
    }

    .ck-content-view p {
        margin-bottom: 1rem;
    }

    .section-title {
        margin-bottom: 50px;
    }

    .section-title h2 {
        font-size: 14px;
        font-weight: 500;
        padding: 0;
        line-height: 1px;
        margin: 0 0 5px 0;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #aaaaaa;
        font-family: "Poppins", sans-serif;
    }

    .section-title h2::after {
        content: "";
        width: 120px;
        height: 1px;
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        margin: 4px 10px;
    }

    .section-title p {
        margin: 0;
        font-size: 36px;
        font-weight: 700;
        font-family: "Playfair Display", serif;
        color: #cda45e;
    }

    .chef-img-container img {
        width: 100%;
        max-width: 300px;
        height: 300px;
        object-fit: cover;
        border: 4px solid #2c2924;
    }

    .light-sep {
        border-color: #37332a;
        margin: 40px 0;
    }
</style>

<main id="main">
    <section class="about-section">
        <div class="container" data-aos="fade-up">
            <div class="section-title text-center">
                <h2>Câu Chuyện</h2>
                <p>Hành trình của chúng tôi</p>
            </div>

            <?php if ($story): ?>
                <div class="row align-items-center">
                    <?php if (!empty($story['thumbnail'])): ?>
                        <div class="col-lg-5 mb-4 mb-lg-0 text-center">
                            <img src="public/assets/img/about/<?= htmlspecialchars($story['thumbnail']) ?>"
                                class="img-fluid rounded shadow" alt="Story" style="border: 4px solid #cda45e;">
                        </div>
                        <div class="col-lg-7 px-4">
                        <?php else: ?>
                            <div class="col-lg-12 px-4">
                            <?php endif; ?>
                            <h3
                                style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 20px;">
                                <?= htmlspecialchars($story['title']) ?>
                            </h3>
                            <div class="ck-content-view">
                                <?= safe_html_render($story['content']) ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Đang cập nhật câu chuyện của nhà hàng...</p>
                <?php endif; ?>
            </div>
    </section>

    <section class="about-section" style="background: #1a1814;">
        <div class="container" data-aos="fade-up">
            <div class="section-title text-center">
                <h2>Đội Ngũ</h2>
                <p>Những người tạo nên hương vị</p>
            </div>

            <div class="row mt-5">
                <?php if (!empty($team)): ?>
                    <?php foreach ($team as $index => $member):
                        $isEven = ($index % 2 === 0);
                        ?>
                        <div class="col-12 mb-5">
                            <div class="row align-items-center <?= $isEven ? '' : 'flex-row-reverse' ?>">

                                <div class="col-md-5 text-center">
                                    <div class="chef-img-container mb-4 mb-md-0">
                                        <img src="public/assets/img/about/<?= htmlspecialchars($member['thumbnail']) ?>"
                                            class="rounded-circle shadow" alt="Avatar">
                                    </div>
                                </div>

                                <div class="col-md-7 <?= $isEven ? 'ps-md-5' : 'pe-md-5' ?> text-start">
                                    <h3 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 2rem;">
                                        <?= htmlspecialchars($member['title']) ?>
                                    </h3>
                                    <div style="width: 50px; height: 1px; background: #cda45e; margin-bottom: 20px;"></div>
                                    <div class="ck-content-view">
                                        <?= safe_html_render($member['content']) ?>
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
</main>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
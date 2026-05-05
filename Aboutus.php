<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

/*
|--------------------------------------------------------------------------
| LẤY DỮ LIỆU CÂU CHUYỆN (Sử dụng làm bài viết nổi bật hoặc nội dung chính)
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("
    SELECT * FROM about_content
    WHERE category_id = (
        SELECT id FROM about_categories
        WHERE slug='cau-chuyen'
        LIMIT 1
    )
    AND status = 1
    ORDER BY is_pinned DESC, display_order ASC
    LIMIT 1
");
$stmt->execute();
$story = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| LẤY DỮ LIỆU ĐỘI NGŨ (Sử dụng làm danh sách tin tức dạng lưới)
|--------------------------------------------------------------------------
*/
$stmt2 = $db->prepare("
    SELECT * FROM about_content
    WHERE category_id = (
        SELECT id FROM about_categories
        WHERE slug='doi-ngu'
        LIMIT 1
    )
    AND status = 1
    ORDER BY display_order ASC
");
$stmt2->execute();
$team = $stmt2->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/client/layouts/header.php';
?>

<style>
    :root {
        --main-bg: #0b1311; /* Màu xanh đen đậm theo style ảnh mẫu */
        --sidebar-bg: #1a2924; 
        --card-bg: #050505;
        --gold: #d4a762;
        --text-light: #f2f2f2;
        --muted: #a9a9a9;
    }

    body {
        background: var(--main-bg);
        color: var(--text-light);
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    /* ================= HERO SECTION ================= */
    #about-hero {
        position: relative;
        height: 350px;
        background: linear-gradient(rgba(0,0,0,.7), rgba(0,0,0,.7)),
                    url('public/assets/img/about-bg.jpg') center center/cover no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        margin-bottom: 50px;
    }

    #about-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 56px;
        color: var(--gold);
        text-transform: uppercase;
        font-weight: 700;
    }

    /* ================= MAIN LAYOUT ================= */
    .news-container {
        padding-bottom: 80px;
    }

    /* ================= SIDEBAR STYLE ================= */
    .sidebar-block {
        background: var(--sidebar-bg);
        border: 1px solid rgba(212, 167, 98, 0.2);
        border-radius: 4px;
        margin-bottom: 30px;
        overflow: hidden;
    }

    .sidebar-title {
        background: var(--gold);
        color: #fff;
        padding: 10px 15px;
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
    }

    .side-item {
        display: flex;
        padding: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: 0.3s;
    }

    .side-item:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .side-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        margin-right: 12px;
        border-radius: 2px;
    }

    .side-item-info a {
        color: var(--text-light);
        text-decoration: none;
        font-size: 13px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .side-item-info a:hover {
        color: var(--gold);
    }

    /* ================= GRID NEWS CARDS ================= */
    .news-card {
        background: var(--card-bg);
        border-radius: 4px;
        overflow: hidden;
        height: 100%;
        transition: transform 0.3s ease;
    }

    .news-card:hover {
        transform: translateY(-5px);
    }

    .news-thumb {
        position: relative;
        height: 220px;
        overflow: hidden;
    }

    .news-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Badge ngày tháng giống ảnh mẫu */
    .date-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #fff;
        color: #333;
        padding: 5px 10px;
        text-align: center;
        border-radius: 2px;
        line-height: 1.1;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    }

    .date-badge .day {
        display: block;
        font-size: 18px;
    }

    .date-badge .month {
        font-size: 11px;
        text-transform: uppercase;
    }

    .news-body {
        padding: 20px;
    }

    .news-title {
        font-size: 18px;
        margin-bottom: 12px;
        line-height: 1.4;
        font-weight: 600;
    }

    .news-title a {
        color: #fff;
        text-decoration: none;
        transition: 0.3s;
    }

    .news-title a:hover {
        color: var(--gold);
    }

    .news-excerpt {
        color: var(--muted);
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-divider {
        width: 100%;
        height: 1px;
        background: rgba(255,255,255,0.1);
    }

    @media (max-width: 992px) {
        #about-hero h1 { font-size: 40px; }
    }
</style>

<section id="about-hero">
    <div class="container">
        <h1>Về Chúng Tôi</h1>
    </div>
</section>

<!-- Header & CSS giữ nguyên từ yêu cầu trước -->
<section class="news-container">
    <div class="container">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-lg-3">
                <aside class="sidebar">
                    <div class="sidebar-block">
                        <div class="sidebar-title">Bài Viết Mới</div>
                        <div class="sidebar-content">
                            <?php foreach(array_slice($team, 0, 4) as $item): ?>
                            <div class="side-item">
                                <img src="public/assets/img/about/<?= htmlspecialchars($item['thumbnail'] ?: 'default.jpg') ?>" alt="">
                                <div class="side-item-info">
                                    <!-- Gọi Modal dựa trên ID -->
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modal-<?= $item['id'] ?>">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- MAIN GRID -->
            <div class="col-lg-9">
                <div class="row">
                    <?php foreach($team as $member): 
                        // Xử lý ngày tháng linh hoạt
                        $d = date('d', strtotime($member['publish_date']));
                        $m = date('m', strtotime($member['publish_date']));
                    ?>
                    <div class="col-md-6 col-xl-4 mb-4">
                        <article class="news-card">
                            <div class="news-thumb">
                                <img src="public/assets/img/about/<?= htmlspecialchars($member['thumbnail'] ?: 'default.jpg') ?>" alt="">
                                <div class="date-badge">
                                    <span class="day"><?= $d ?></span>
                                    <span class="month">Th<?= (int)$m ?></span>
                                </div>
                            </div>
                            <div class="news-body">
                                <h3 class="news-title">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modal-<?= $member['id'] ?>">
                                        <?= htmlspecialchars($member['title']) ?>
                                    </a>
                                </h3>
                                <div class="news-excerpt">
                                    <?= mb_substr(strip_tags(htmlspecialchars_decode($member['content'])), 0, 100, 'UTF-8') ?>...
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- TRANG PHỤ TẠO NHANH (MODAL) -->
                    <div class="modal fade" id="modal-<?= $member['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0" style="background:#14110f; color:#fff;">
                                <div class="modal-header border-bottom border-secondary">
                                    <h5 class="modal-title fw-bold text-warning"><?= htmlspecialchars($member['title']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <img src="public/assets/img/about/<?= $member['thumbnail'] ?>" class="w-100 rounded mb-4" style="max-height:400px; object-fit:cover;">
                                    <div class="content-detail text-light">
                                        <?= htmlspecialchars_decode($member['content']) ?>
                                    </div>
                                </div>
                                <div class="modal-footer border-top border-secondary">
                                    <small class="text-muted">Đăng ngày: <?= date('d/m/Y', strtotime($member['publish_date'])) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
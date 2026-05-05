<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// 1. Lấy tất cả danh mục món ăn
$all_categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy danh sách Combo nổi bật
$all_combos = $db->query("SELECT * FROM combos WHERE status = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/client/layouts/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    :root {
        --gold-primary: #cda45e;
        --dark-bg: #0c0b09;
        --dark-card: #1a1814;
        --text-muted: rgba(255, 255, 255, 0.5);
    }

    body { background-color: var(--dark-bg); color: #fff; }

    /* Header Section */
    .menu-header {
        position: relative;
        padding: 150px 0 100px;
        background: linear-gradient(to bottom, rgba(0,0,0,0.7), var(--dark-bg)), url('public/assets/img/menu-bg.jpg') center center fixed;
        background-size: cover;
    }

    .section-title h2 {
        font-family: "Playfair Display", serif;
        font-size: 14px; font-weight: 500; padding: 0; line-height: 1px;
        margin: 0 0 20px 0; letter-spacing: 2px; text-transform: uppercase;
        color: #aaaaaa; display: flex; align-items: center;
    }
    .section-title h2::after { content: ""; width: 120px; height: 1px; background: var(--gold-primary); margin-left: 15px; }
    .section-title p { margin: 0; font-size: 36px; font-weight: 700; font-family: "Playfair Display", serif; color: var(--gold-primary); }

    /* Combo Đặc Biệt - Hiển thị riêng biệt */
    .combo-section { padding-bottom: 60px; }
    .combo-card {
        background: linear-gradient(145deg, #1e1b16, #0c0b09);
        border: 1px solid rgba(205, 164, 94, 0.2);
        border-radius: 15px; padding: 30px; margin-bottom: 30px;
        transition: 0.4s; position: relative; overflow: hidden;
    }
    .combo-card:hover { border-color: var(--gold-primary); transform: translateY(-10px); box-shadow: 0px 10px 30px rgba(205, 164, 94, 0.1); }
    .combo-card::before {
        content: "SPECIAL OFFER"; position: absolute; top: 15px; right: -35px;
        background: var(--gold-primary); color: #000; font-size: 10px; font-weight: 700;
        padding: 5px 40px; transform: rotate(45deg);
    }
    .combo-img { width: 120px; height: 120px; border-radius: 15px; object-fit: cover; border: 2px solid var(--gold-primary); }

    /* Menu Tabs */
    .nav-tabs-menu { border: none; justify-content: center; margin-bottom: 50px; }
    .nav-tabs-menu .nav-link {
        color: #fff; background: none; border: none; font-weight: 400; font-size: 16px;
        padding: 12px 25px; margin: 0 10px; position: relative; transition: 0.3s;
    }
    .nav-tabs-menu .nav-link.active { color: var(--gold-primary); }
    .nav-tabs-menu .nav-link.active::after {
        content: ""; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
        width: 30px; height: 2px; background: var(--gold-primary);
    }

    /* Menu Items - Nghệ thuật tương phản */
    .menu-item { margin-bottom: 40px; }
    .menu-content { position: relative; padding-left: 100px; }
    .menu-img-circle {
        position: absolute; left: 0; top: 0;
        width: 80px; height: 80px; border-radius: 50%;
        border: 2px solid var(--gold-primary); padding: 5px;
        transition: 0.5s;
    }
    .menu-item:hover .menu-img-circle { transform: scale(1.1) rotate(10deg); }
    
    .menu-link {
        display: flex; justify-content: space-between; align-items: baseline;
        font-family: "Poppins", sans-serif; font-weight: 600; font-size: 18px;
        color: #fff; text-decoration: none; position: relative;
    }
    .menu-link::after {
        content: "....................................................................................................";
        position: absolute; left: 0; right: 0; bottom: 4px; z-index: -1;
        color: rgba(255,255,255,0.1); overflow: hidden;
    }
    .menu-link span:first-child { background: var(--dark-bg); padding-right: 10px; }
    .menu-price { color: var(--gold-primary); background: var(--dark-bg); padding-left: 10px; }
    .menu-ingredients { font-style: italic; color: var(--text-muted); font-size: 14px; margin-top: 8px; }

    /* Glassmorphism Effect cho Tabs content */
    .tab-pane {
        background: rgba(26, 24, 20, 0.4);
        padding: 40px; border-radius: 20px;
        backdrop-filter: blur(10px);
    }
</style>

<section class="menu-header animate__animated animate__fadeIn">
    <div class="container text-center">
        <div class="section-title">
            <h2>Our Menu</h2>
            <p>Khám Phá Hương Vị Nghệ Thuật</p>
        </div>
    </div>
</section>

<section id="menu" class="menu-section">
    <div class="container">

        <?php if (!empty($all_combos)): ?>
        <div class="combo-section animate__animated animate__fadeInUp">
            <div class="section-title mb-4">
                <p style="font-size: 24px; text-align: center;">🔥 Combo Ưu Đãi Đặc Biệt</p>
            </div>
            <div class="row">
                <?php foreach ($all_combos as $combo): ?>
                <div class="col-lg-6">
                    <div class="combo-card d-flex align-items-center">
                        <img src="public/assets/img/combos/<?= $combo['image'] ?>" class="combo-img me-4" alt="<?= $combo['name'] ?>">
                        <div class="combo-info">
                            <h4 style="color: var(--gold-primary); font-family: 'Playfair Display';"><?= $combo['name'] ?></h4>
                            <div class="menu-ingredients mb-2"><?= $combo['description'] ?></div>
                            <span class="fs-4 fw-bold" style="color: #fff;"><?= number_format($combo['price'], 0, ',', '.') ?>đ</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <ul class="nav nav-tabs nav-tabs-menu animate__animated animate__fadeIn" id="menuTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#tab-all">Tất Cả</button>
            </li>
            <?php foreach ($all_categories as $cat): ?>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cat-<?= $cat['id'] ?>"><?= $cat['name'] ?></button>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content animate__animated animate__fadeInUp">
            <div class="tab-pane fade show active" id="tab-all">
                <div class="row">
                    <?php 
                    $all_foods = $db->query("SELECT * FROM foods ORDER BY category_id ASC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($all_foods as $f): 
                    ?>
                    <div class="col-lg-6 menu-item">
                        <div class="menu-content">
                            <img src="public/assets/img/menu/<?= $f['image'] ?>" class="menu-img-circle" alt="<?= $f['name'] ?>">
                            <div class="menu-info">
                                <div class="menu-link">
                                    <span><?= $f['name'] ?></span>
                                    <span class="menu-price"><?= number_format($f['price'], 0, ',', '.') ?>đ</span>
                                </div>
                                <div class="menu-ingredients"><?= $f['description'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($all_categories as $cat): ?>
            <div class="tab-pane fade" id="cat-<?= $cat['id'] ?>">
                <div class="row">
                    <?php 
                    $foods_by_cat = $db->prepare("SELECT * FROM foods WHERE category_id = ?");
                    $foods_by_cat->execute([$cat['id']]);
                    $foods = $foods_by_cat->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($foods)): echo "<p class='text-center text-muted'>Đang cập nhật món ăn cho danh mục này...</p>";
                    else:
                        foreach ($foods as $f): 
                    ?>
                    <div class="col-lg-6 menu-item">
                        <div class="menu-content">
                            <img src="public/assets/img/menu/<?= $f['image'] ?>" class="menu-img-circle" alt="<?= $f['name'] ?>">
                            <div class="menu-info">
                                <div class="menu-link">
                                    <span><?= $f['name'] ?></span>
                                    <span class="menu-price"><?= number_format($f['price'], 0, ',', '.') ?>đ</span>
                                </div>
                                <div class="menu-ingredients"><?= $f['description'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
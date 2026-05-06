<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// 1. Lấy tất cả danh mục món ăn
$all_categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy danh sách Combo nổi bật
$all_combos = $db->query("SELECT * FROM combos WHERE status = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
// 2. Lấy danh sách Combo nổi bật (Đang active)
$all_combos = $db->query("SELECT * FROM combos WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

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
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    :root {
        --bg-main: #143b36;       /* Màu nền chính (Xanh cổ vịt đậm) */
        --card-bg: #1b4f49;       /* Màu nền thẻ (Sáng hơn nền chính 1 chút để nổi bật) */
        --accent-gold: #cda45e;   /* Màu vàng đồng tạo điểm nhấn sang trọng */
        --text-light: #ffffff;    /* Màu chữ chính */
        --text-muted: #aabebc;    /* Màu chữ phụ (mô tả) */
    }

    body { 
        background-color: var(--bg-main); 
        color: var(--text-light); 
        font-family: 'Poppins', sans-serif; 
    }

    /* Header Section */
    .menu-header {
        padding: 120px 0 40px;
        background: var(--bg-main);
        text-align: center;
    }

    .section-title h2 {
        font-family: 'Poppins', sans-serif;
        font-size: 14px; font-weight: 600; 
        margin: 0 0 10px 0; letter-spacing: 3px; text-transform: uppercase;
        color: var(--accent-gold); 
    }
    .section-title p { 
        margin: 0; font-size: 42px; font-weight: 700; 
        font-family: "Playfair Display", serif; 
        color: var(--text-light); 
    }

    /* Tiêu đề phân cách Combo / Món lẻ */
    .category-divider {
        text-align: center;
        margin: 50px 0 60px;
        position: relative;
    }
    .category-divider::before {
        content: ""; position: absolute; top: 50%; left: 0; right: 0;
        height: 1px; background: rgba(255, 255, 255, 0.1); z-index: 1;
    }
    .category-divider span {
        position: relative; z-index: 2; background: var(--bg-main);
        padding: 0 25px; font-family: 'Playfair Display', serif;
        font-size: 26px; font-weight: 700; color: var(--accent-gold);
        font-style: italic; letter-spacing: 1px;
    }

    /* Menu Tabs */
    /* Menu Tabs (Đã đổi sang font Serif giống ảnh) */
    .nav-tabs-menu { border: none; justify-content: center; margin-bottom: 70px; gap: 30px;}
    .nav-tabs-menu .nav-link {
        color: var(--text-light); 
        background: none; 
        border: none; 
        font-family: 'Playfair Display', serif; /* Font có chân giống ảnh */
        font-weight: 700; 
        font-size: 16px; 
        text-transform: uppercase; 
        letter-spacing: 3px; /* Khoảng cách giữa các chữ cái rộng ra */
        padding: 10px 5px; 
        transition: 0.3s;
        opacity: 0.5; /* Hơi mờ khi chưa được chọn */
    }
    .nav-tabs-menu .nav-link:hover { 
        color: var(--accent-gold); 
        background: none; 
        opacity: 1;
    }
    .nav-tabs-menu .nav-link.active { 
        color: var(--accent-gold); 
        background: none; 
        opacity: 1;
        /* Thêm gạch chân nhỏ bên dưới để biết đang ở tab nào */
        border-bottom: 2px solid var(--accent-gold); 
    }
    /* =========================================
       CARD STYLE (Nền tối sang trọng)
       ========================================= */
    .food-card-wrapper {
        margin-top: 60px; /* Nhường chỗ cho ảnh trồi lên */
        margin-bottom: 40px;
        padding: 0 15px;
    }

    .food-card {
        background: var(--card-bg);
        border: 1px solid rgba(205, 164, 94, 0.2);
        border-radius: 15px;
        padding: 90px 20px 40px; /* Padding top lớn để chừa chỗ cho ảnh */
        text-align: center;
        position: relative;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .food-card:hover {
        border-color: var(--accent-gold);
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
    }

    .food-img-container {
        position: absolute;
        top: -60px; /* Trồi lên 50% */
        left: 50%;
        transform: translateX(-50%);
        width: 130px;
        height: 130px;
        border-radius: 50%;
        background: var(--card-bg);
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        border: 3px solid var(--accent-gold); /* Viền vàng bọc ảnh */
        overflow: hidden;
        z-index: 2;
    }

    .food-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.5s ease;
    }
    .food-card:hover .food-img-container img { transform: scale(1.1); }

    .food-title {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 22px;
        color: var(--text-light);
        margin-bottom: 12px;
    }

    .food-desc {
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 25px;
        flex-grow: 1; /* Đẩy giá tiền xuống đáy */
    }

    .food-price {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 22px;
        color: var(--accent-gold);
    }
</style>

<section class="menu-header animate__animated animate__fadeIn">
    <div class="container text-center">
        <div class="section-title">
            <h2>Our Menu</h2>
            <p>Khám Phá Hương Vị Nghệ Thuật</p>
    <div class="container">
        <div class="section-title">
            <h2>Khám Phá</h2>
            <p>Thực đơn ngon miệng của chúng tôi</p>
        </div>
    </div>
</section>

<section id="menu" class="menu-section pb-5" style="background-color: var(--bg-main);">
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
        <!-- ==============================================
             KHU VỰC COMBO NẰM RIÊNG BIỆT NGAY DƯỚI TIÊU ĐỀ
             ============================================== -->
        <?php if (!empty($all_combos)): ?>
        <div class="combo-wrapper mb-5 animate__animated animate__fadeInUp">
            <div class="category-divider" style="margin-top: 20px;"><span>🔥 Combo Ưu Đãi Đặc Biệt 🔥</span></div>
            <div class="row justify-content-center">
                <?php foreach ($all_combos as $combo): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 food-card-wrapper">
                    <div class="food-card" style="border: 2px solid var(--accent-gold); background: linear-gradient(145deg, #1b4f49, #123430);">
                        <div class="food-img-container">
                            <img src="public/assets/img/combos/<?= $combo['image'] ?>" onerror="this.src='public/assets/img/default.jpg'" alt="<?= $combo['name'] ?>">
                        </div>
                        <h3 class="food-title"><?= htmlspecialchars($combo['name']) ?></h3>
                        <div class="food-desc"><?= htmlspecialchars($combo['description']) ?></div>
                        <div class="food-price"><?= number_format($combo['price'], 0, ',', '.') ?> đ</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==============================================
             KHU VỰC MÓN LẺ (CÓ TABS LỌC THEO DANH MỤC)
             ============================================== -->
        <div class="category-divider"><span>A La Carte (Món Lẻ)</span></div>
             
        <!-- Tabs điều hướng -->
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

        <!-- Nội dung Tabs -->
        <div class="tab-content animate__animated animate__fadeInUp">
            
            <!-- TAB TẤT CẢ (Chỉ hiển thị món lẻ) -->
            <div class="tab-pane fade show active" id="tab-all">
                <div class="row justify-content-center">
                    <?php 
                    $all_foods = $db->query("SELECT * FROM foods WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($all_foods)): 
                        echo "<div class='col-12'><p class='text-center text-light mt-4'>Chưa có món ăn nào.</p></div>";
                    else:
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
                    <div class="col-lg-3 col-md-4 col-sm-6 food-card-wrapper">
                        <div class="food-card">
                            <div class="food-img-container">
                                <img src="public/assets/img/menu/<?= $f['image'] ?>" onerror="this.src='public/assets/img/default.jpg'" alt="<?= $f['name'] ?>">
                            </div>
                            <h3 class="food-title"><?= htmlspecialchars($f['name']) ?></h3>
                            <div class="food-desc"><?= mb_strimwidth(htmlspecialchars($f['description']), 0, 60, "...") ?></div>
                            <div class="food-price"><?= number_format($f['price'], 0, ',', '.') ?> đ</div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- TABS TỪNG DANH MỤC MÓN ĂN -->
            <?php foreach ($all_categories as $cat): ?>
            <div class="tab-pane fade" id="cat-<?= $cat['id'] ?>">
                <div class="row justify-content-center">
                    <?php 
                    $foods_by_cat = $db->prepare("SELECT * FROM foods WHERE category_id = ? AND is_active = 1");
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

                    if (empty($foods)): 
                        echo "<div class='col-12'><p class='text-center text-light mt-4'>Chưa có món ăn nào trong danh mục này.</p></div>";
                    else:
                        foreach ($foods as $f): 
                    ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 food-card-wrapper">
                        <div class="food-card">
                            <div class="food-img-container">
                                <img src="public/assets/img/menu/<?= $f['image'] ?>" onerror="this.src='public/assets/img/default.jpg'" alt="<?= $f['name'] ?>">
                            </div>
                            <h3 class="food-title"><?= htmlspecialchars($f['name']) ?></h3>
                            <div class="food-desc"><?= mb_strimwidth(htmlspecialchars($f['description']), 0, 60, "...") ?></div>
                            <div class="food-price"><?= number_format($f['price'], 0, ',', '.') ?> đ</div>
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
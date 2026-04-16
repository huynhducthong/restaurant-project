<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// 1. Lấy tất cả danh mục món ăn (Khai vị, Món chính,...)
$all_categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy danh sách Combo (Dữ liệu này trang cũ chưa có)
$all_combos = $db->query("SELECT * FROM combos WHERE status = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/client/layouts/header.php'; 
?>

<style>
    /* CSS tùy chỉnh cho giao diện Menu mới */
    .menu-section { padding: 60px 0; background: #0c0b09; }
    .section-title h2 { color: #cda45e; font-family: "Playfair Display", serif; font-size: 36px; font-weight: 700; text-transform: uppercase; margin-bottom: 40px; text-align: center;}
    
    .nav-tabs-menu { border: none; justify-content: center; margin-bottom: 40px; }
    .nav-tabs-menu .nav-link { 
        color: #fff; background: none; border: none; font-weight: 600; font-size: 16px; 
        padding: 10px 25px; transition: 0.3s; text-transform: uppercase;
    }
    .nav-tabs-menu .nav-link.active { color: #cda45e; border-bottom: 2px solid #cda45e; }
    .nav-tabs-menu .nav-link:hover { color: #cda45e; }

    .menu-item { margin-bottom: 25px; transition: 0.3s; }
    .menu-content { display: flex; align-items: center; }
    .menu-img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid rgba(255, 255, 255, 0.1); margin-right: 20px; object-fit: cover; }
    .menu-info { flex-grow: 1; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); padding-bottom: 5px; }
    .menu-info a { color: #fff; font-weight: 700; font-size: 18px; text-decoration: none; transition: 0.3s; }
    .menu-info a:hover { color: #cda45e; }
    .menu-info span { color: #cda45e; font-weight: 700; float: right; }
    .menu-ingredients { font-style: italic; font-size: 14px; color: rgba(255, 255, 255, 0.5); margin-top: 5px; }
    
    .badge-combo { background: #cda45e; color: #000; font-size: 10px; padding: 3px 8px; border-radius: 4px; vertical-align: middle; margin-left: 5px; }
</style>

<section id="menu-header" style="background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('public/assets/img/menu-bg.jpg') center center; padding: 100px 0 50px 0; text-align: center;">
    <div class="container">
        <h2 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 48px;">THỰC ĐƠN NHÀ HÀNG</h2>
        <p style="color: #eee; font-style: italic;">Sự kết hợp hoàn hảo giữa hương vị và nghệ thuật</p>
    </div>
</section>

<section id="menu" class="menu-section">
    <div class="container">

        <ul class="nav nav-tabs nav-tabs-menu" id="menuTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#tab-all">Tất Cả</button>
            </li>
            <?php foreach ($all_categories as $cat): ?>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cat-<?= $cat['id'] ?>"><?= $cat['name'] ?></button>
            </li>
            <?php endforeach; ?>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-combo">🔥 Combo Ưu Đãi</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-all">
                <div class="row">
                    <?php 
                    $all_foods = $db->query("SELECT * FROM foods ORDER BY category_id ASC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($all_foods as $f): 
                    ?>
                    <div class="col-lg-6 menu-item">
                        <div class="menu-content">
                            <img src="public/assets/img/menu/<?= $f['image'] ?>" class="menu-img" alt="<?= $f['name'] ?>">
                            <div class="menu-info">
                                <a href="#"><?= $f['name'] ?></a>
                                <span><?= number_format($f['price'], 0, ',', '.') ?>đ</span>
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
                    
                    if (empty($foods)): echo "<p class='text-center text-muted'>Hiện chưa có món nào trong danh mục này.</p>";
                    else:
                        foreach ($foods as $f): 
                    ?>
                    <div class="col-lg-6 menu-item">
                        <div class="menu-content">
                            <img src="public/assets/img/menu/<?= $f['image'] ?>" class="menu-img" alt="<?= $f['name'] ?>">
                            <div class="menu-info">
                                <a href="#"><?= $f['name'] ?></a>
                                <span><?= number_format($f['price'], 0, ',', '.') ?>đ</span>
                                <div class="menu-ingredients"><?= $f['description'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="tab-pane fade" id="tab-combo">
                <div class="row">
                    <?php if (empty($all_combos)): ?>
                        <p class='text-center text-muted'>Hiện chưa có chương trình combo ưu đãi nào.</p>
                    <?php else: ?>
                        <?php foreach ($all_combos as $combo): ?>
                        <div class="col-lg-6 menu-item">
                            <div class="menu-content">
                                <img src="public/assets/img/combos/<?= $combo['image'] ?>" class="menu-img" style="border-color: #cda45e;" alt="<?= $combo['name'] ?>">
                                <div class="menu-info">
                                    <a href="#" style="color: #cda45e;"><?= $combo['name'] ?> <span class="badge-combo">COMBO</span></a>
                                    <span><?= number_format($combo['price'], 0, ',', '.') ?>đ</span>
                                    <div class="menu-ingredients">
                                        <strong>Bao gồm:</strong> <?= $combo['description'] ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
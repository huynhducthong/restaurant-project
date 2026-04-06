<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Lấy tất cả danh mục theo thứ tự ID
$all_categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/client/layouts/header.php'; 
?>

<section id="menu-header" style="background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('public/assets/img/menu-bg.jpg') center center; padding: 120px 0 60px 0; text-align: center;">
    <div class="container">
        <h2 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 48px;">THỰC ĐƠN CHI TIẾT</h2>
        <p style="color: #eee; font-style: italic;">Khám phá hương vị ẩm thực độc đáo của chúng tôi</p>
    </div>
</section>

<section id="menu-page" class="menu" style="background: #0c0b09; padding: 60px 0;">
    <div class="container">
        
        <div class="row align-items-stretch">
            <?php 
            for ($i = 0; $i < 3; $i++): 
                if (isset($all_categories[$i])):
                    $cat = $all_categories[$i];
                    $foods = $db->query("SELECT * FROM foods WHERE category_id = {$cat['id']}")->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="category-column-box" style="background: rgba(26, 24, 20, 0.5); border: 1px solid #37332a; padding: 30px 20px; border-radius: 10px; height: 100%;">
                        <h3 class="category-title text-center" style="color: #cda45e; font-family: 'Playfair Display', serif; margin-bottom: 30px; position: relative; padding-bottom: 15px;">
                            <?php echo $cat['name']; ?>
                            <span style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 50px; height: 2px; background: #cda45e;"></span>
                        </h3>
                        
                        <?php foreach ($foods as $f): ?>
                            <div class="menu-item-horizontal d-flex align-items-center mb-4" style="transition: 0.3s;">
                                <div class="item-img" style="width: 70px; height: 70px; min-width: 70px; margin-right: 15px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(205, 164, 94, 0.2);">
                                    <img src="public/assets/img/menu/<?php echo $f['image']; ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="item-details flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-baseline">
                                        <h5 class="food-name" style="color: #fff; font-size: 16px; font-weight: 600; margin: 0;"><?php echo $f['name']; ?></h5>
                                        <span class="food-price" style="color: #cda45e; font-weight: 700; font-size: 15px; margin-left: 10px;"><?php echo number_format($f['price'], 0, ',', '.'); ?>đ</span>
                                    </div>
                                    <p class="food-desc" style="color: rgba(255, 255, 255, 0.5); font-size: 13px; font-style: italic; margin: 5px 0 0 0;"><?php echo $f['description']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; endfor; ?>
        </div>

        <div style="height: 40px;"></div>

        <div class="row justify-content-center align-items-stretch">
            <?php 
            for ($i = 3; $i < count($all_categories); $i++): 
                $cat = $all_categories[$i];
                $foods = $db->query("SELECT * FROM foods WHERE category_id = {$cat['id']}")->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="category-column-box" style="background: rgba(26, 24, 20, 0.5); border: 1px solid #37332a; padding: 30px 20px; border-radius: 10px; height: 100%;">
                        <h3 class="category-title text-center" style="color: #cda45e; font-family: 'Playfair Display', serif; margin-bottom: 30px; position: relative; padding-bottom: 15px;">
                            <?php echo $cat['name']; ?>
                            <span style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 50px; height: 2px; background: #cda45e;"></span>
                        </h3>
                        
                        <?php foreach ($foods as $f): ?>
                            <div class="menu-item-horizontal d-flex align-items-center mb-4">
                                <div class="item-img" style="width: 70px; height: 70px; min-width: 70px; margin-right: 15px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(205, 164, 94, 0.2);">
                                    <img src="public/assets/img/menu/<?php echo $f['image']; ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="item-details flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-baseline">
                                        <h5 class="food-name" style="color: #fff; font-size: 16px; font-weight: 600; margin: 0;"><?php echo $f['name']; ?></h5>
                                        <span class="food-price" style="color: #cda45e; font-weight: 700; font-size: 15px; margin-left: 10px;"><?php echo number_format($f['price'], 0, ',', '.'); ?>đ</span>
                                    </div>
                                    <p class="food-desc" style="color: rgba(255, 255, 255, 0.5); font-size: 13px; font-style: italic; margin: 5px 0 0 0;"><?php echo $f['description']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

    </div>
</section>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy danh sách đầu bếp
$query = "SELECT * FROM chefs 
          WHERE is_active = 1 
          ORDER BY sort_order ASC, id DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bao gồm header
include __DIR__ . '/layouts/header.php';
?>

<style>
    /* Chỉnh màu nền và theme cho đồng nhất với website */
    .chefs-page-bg {
        background: #0c0b09;
        color: #fff;
        padding: 60px 0;
        min-height: 80vh;
    }
    
    .chefs-page-bg .section-title h2 {
        color: #cda45e;
        font-family: 'Playfair Display', serif;
        font-weight: 700;
    }
    
    .chefs-page-bg .section-title p {
        color: #aaaaaa;
        font-style: italic;
    }

    .chef-card-custom {
        background: #1a1814;
        border: 1px solid #37332a;
        border-radius: 8px;
        overflow: hidden;
        transition: 0.4s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .chef-card-custom:hover {
        transform: translateY(-10px);
        border-color: #cda45e;
        box-shadow: 0 5px 20px rgba(205, 164, 94, 0.2);
    }

    .chef-img-wrapper {
        position: relative;
        overflow: hidden;
        height: 350px;
    }

    .chef-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.5s;
    }

    .chef-card-custom:hover .chef-img-wrapper img {
        transform: scale(1.08);
    }

    .chef-social {
        position: absolute;
        bottom: -50px;
        left: 0;
        right: 0;
        background: rgba(12, 11, 9, 0.8);
        padding: 10px 0;
        display: flex;
        justify-content: center;
        gap: 15px;
        transition: 0.4s;
    }

    .chef-card-custom:hover .chef-social {
        bottom: 0;
    }

    .chef-social a {
        color: #fff;
        font-size: 18px;
        transition: 0.3s;
    }

    .chef-social a:hover {
        color: #cda45e;
    }

    .chef-info {
        padding: 25px;
        text-align: center;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .chef-info h4 {
        color: #fff;
        font-weight: 700;
        font-size: 22px;
        margin-bottom: 5px;
        font-family: 'Playfair Display', serif;
    }

    .chef-info span.position {
        display: block;
        font-size: 14px;
        font-style: italic;
        color: #cda45e;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .chef-info p.specialty {
        color: #aaaaaa;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .chef-info p.desc {
        color: #ced4da;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .chef-info .quote {
        margin-top: auto;
        font-style: italic;
        color: #cda45e;
        font-size: 13px;
    }
</style>

<div class="page-space"></div>

<section class="chefs-page-bg">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Đội Ngũ Đầu Bếp</h2>
            <p>Những nghệ nhân ẩm thực tạo nên hương vị tuyệt vời</p>
        </div>

        <?php if(count($chefs) > 0): ?>
            <div class="row g-4 justify-content-center">
                <?php foreach($chefs as $chef): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="chef-card-custom">
                            <div class="chef-img-wrapper">
                                <?php
                                $image = !empty($chef['image'])
                                    ? '/restaurant-project/public/assets/img/chefs/' . $chef['image']
                                    : 'https://via.placeholder.com/400x400?text=No+Image';
                                ?>
                                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($chef['name']) ?>" onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
                                
                                <div class="chef-social">
                                    <?php if(!empty($chef['facebook'])): ?>
                                        <a href="<?= htmlspecialchars($chef['facebook']) ?>" target="_blank"><i class="bi bi-facebook"></i></a>
                                    <?php endif; ?>
                                    <?php if(!empty($chef['instagram'])): ?>
                                        <a href="<?= htmlspecialchars($chef['instagram']) ?>" target="_blank"><i class="bi bi-instagram"></i></a>
                                    <?php endif; ?>
                                    <?php if(!empty($chef['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($chef['email']) ?>"><i class="bi bi-envelope"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="chef-info">
                                <h4><?= htmlspecialchars($chef['name']) ?></h4>
                                <span class="position"><?= htmlspecialchars($chef['position']) ?> <?= !empty($chef['experience']) ? "({$chef['experience']} năm kinh nghiệm)" : "" ?></span>
                                
                                <?php if(!empty($chef['specialty'])): ?>
                                    <p class="specialty"><strong>Chuyên môn:</strong> <?= htmlspecialchars($chef['specialty']) ?></p>
                                <?php endif; ?>
                                
                                <?php if(!empty($chef['description'])): ?>
                                    <p class="desc"><?= htmlspecialchars($chef['description']) ?></p>
                                <?php endif; ?>
                                
                                <?php if(!empty($chef['quote'])): ?>
                                    <div class="quote">"<?= htmlspecialchars($chef['quote']) ?>"</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 100px 0;">
                <i class="bi bi-people" style="font-size: 4rem; color: #cda45e; opacity: 0.5; margin-bottom: 20px; display: block;"></i>
                <h4 style="color: #aaaaaa;">Hiện tại chưa có thông tin đầu bếp nào.</h4>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/layouts/footer.php'; ?>
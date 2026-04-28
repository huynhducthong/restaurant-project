<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Lấy thông tin động từ cấu hình Footer để hiển thị trên trang Liên hệ
$stmt = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ft[$row['setting_key']] = $row['setting_value'];
}

include 'views/client/layouts/header.php'; 
?>

<main id="main">
    <section class="breadcrumbs" style="padding: 140px 0 60px; background: #0c0b09; border-bottom: 1px solid rgba(205, 164, 94, 0.2);">
        <div class="container text-center">
            <h2 style="font-family: 'Playfair Display', serif; color: #cda45e; font-size: 50px; font-weight: 700;">Liên Hệ</h2>
            <p style="color: #eee;">Kết nối với chúng tôi để nhận được sự phục vụ tốt nhất</p>
        </div>
    </section>

    <section id="contact" class="contact" style="padding: 80px 0; background: #1a1814;">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="contact-info" style="color: #fff;">
                        <div class="info-item mb-5">
                            <i class="bi bi-geo-alt" style="font-size: 24px; color: #cda45e; float: left; margin-right: 20px;"></i>
                            <h4 style="font-size: 20px; font-weight: 600; font-family: 'Playfair Display', serif;">Địa chỉ:</h4>
                            <p style="color: #aaa;"><?= htmlspecialchars($ft['address'] ?? 'Đang cập nhật...') ?></p>
                        </div>

                        <div class="info-item mb-5">
                            <i class="bi bi-envelope" style="font-size: 24px; color: #cda45e; float: left; margin-right: 20px;"></i>
                            <h4 style="font-size: 20px; font-weight: 600; font-family: 'Playfair Display', serif;">Email:</h4>
                            <p style="color: #aaa;"><?= htmlspecialchars($ft['email'] ?? 'Đang cập nhật...') ?></p>
                        </div>

                        <div class="info-item mb-5">
                            <i class="bi bi-phone" style="font-size: 24px; color: #cda45e; float: left; margin-right: 20px;"></i>
                            <h4 style="font-size: 20px; font-weight: 600; font-family: 'Playfair Display', serif;">Điện thoại:</h4>
                            <p style="color: #aaa;"><?= htmlspecialchars($ft['phone'] ?? 'Đang cập nhật...') ?></p>
                        </div>

                        <div class="info-item mb-5">
                            <i class="bi bi-clock" style="font-size: 24px; color: #cda45e; float: left; margin-right: 20px;"></i>
                            <h4 style="font-size: 20px; font-weight: 600; font-family: 'Playfair Display', serif;">Giờ mở cửa:</h4>
                            <p style="color: #aaa;"><?= htmlspecialchars($ft['opening_hours'] ?? 'Đang cập nhật...') ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <form action="#" method="post" class="p-4 rounded shadow" style="background: #0c0b09; border: 1px solid rgba(255,255,255,0.05);">
                        <div class="row g-3">
                            <div class="col-md-6 form-group">
                                <input type="text" name="name" class="form-control" placeholder="Tên của bạn" required style="background: #1a1814; border: 1px solid #37332a; color: #fff; padding: 12px;">
                            </div>
                            <div class="col-md-6 form-group">
                                <input type="email" class="form-control" name="email" placeholder="Email của bạn" required style="background: #1a1814; border: 1px solid #37332a; color: #fff; padding: 12px;">
                            </div>
                            <div class="col-md-12 form-group">
                                <input type="text" class="form-control" name="subject" placeholder="Tiêu đề" required style="background: #1a1814; border: 1px solid #37332a; color: #fff; padding: 12px;">
                            </div>
                            <div class="col-md-12 form-group">
                                <textarea class="form-control" name="message" rows="7" placeholder="Nội dung lời nhắn" required style="background: #1a1814; border: 1px solid #37332a; color: #fff; padding: 12px;"></textarea>
                            </div>
                            <div class="col-md-12 text-center">
                                <button type="submit" style="background: #cda45e; border: 0; padding: 12px 40px; color: #fff; border-radius: 50px; font-weight: 600; transition: 0.3s;">Gửi Lời Nhắn</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

           <?php if (($ft['show_map'] ?? '0') == '1' && !empty($ft['google_map_iframe'])): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="map-wrapper shadow-lg" style="border-radius: 12px; overflow: hidden; border: 2px solid #37332a; height: 450px; background: #1a1814;">
                        
                        <style>
                            .map-wrapper iframe {
                                width: 100% !important;
                                height: 100% !important;
                                border: none !important;
                            }
                        </style>
                        
                        <?= $ft['google_map_iframe'] ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
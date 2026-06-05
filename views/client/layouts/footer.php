<?php
require_once __DIR__ . '/../../../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $ft[$row['setting_key']] = $row['setting_value']; }

$links      = $db->query("SELECT * FROM footer_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Đảm bảo $path_prefix và safe_url tồn tại
$path_prefix = $path_prefix ?? '';
if (!function_exists('safe_url')) {
    function safe_url($url, $prefix) {
        if (empty($url)) return '';
        // Nếu URL là tuyệt đối (bắt đầu bằng http, https, // hoặc /)
        if (preg_match('/^(https?:|\/\/|\/)/i', $url)) {
            return $url;
        }
        return $prefix . $url;
    }
}

$bgImg      = !empty($ft['footer_bg_image']) ? safe_url('public/assets/img/' . $ft['footer_bg_image'], $path_prefix) : '';
$logo       = !empty($ft['footer_logo']) ? safe_url('public/assets/img/' . $ft['footer_logo'], $path_prefix) : '';
$showSocial = ($ft['show_social'] ?? '0') == '1';
$showMap    = ($ft['show_map'] ?? '0') == '1';
$showNews   = ($ft['show_newsletter'] ?? '0') == '1';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap');

.footer {
    font-family: 'Playfair Display', serif;
    position: relative;
    padding: 80px 0 40px;
    background-color: <?= !empty($ft['footer_bg_color']) && $ft['footer_bg_color'] !== '#0c0b09' ? $ft['footer_bg_color'] : '#113f36' ?>;
    color: <?= $ft['footer_text_color'] ?? '#ffffff' ?>;
    <?php if ($bgImg): ?>
    background: url('<?= $bgImg ?>') center/cover no-repeat fixed;
    <?php endif; ?>
}
<?php if ($bgImg): ?>
.footer::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(17, 63, 54, 0.9);
    z-index: 1;
}
<?php endif; ?>
.footer .container { position: relative; z-index: 2; }
.footer h4 { 
    font-family: 'Inter', sans-serif; 
    font-size: 11px; 
    letter-spacing: 2px; 
    text-transform: uppercase; 
    color: rgba(255,255,255,0.6); 
    margin-bottom: 25px; 
    font-weight: 500;
}
.footer p, .footer a, .footer li { 
    font-family: 'Playfair Display', serif; 
    font-size: 15px; 
    color: #ffffff; 
    line-height: 1.8; 
    text-decoration: none; 
    transition: opacity 0.3s;
    margin-bottom: 5px;
}
.footer a:hover { opacity: 0.7; color: #fff; }
.footer-logo { max-height: 80px; margin-bottom: 25px; }

.social-icons { margin-top: 25px; }
.social-icons a {
    display: inline-block;
    margin-right: 15px;
    font-size: 18px;
}

.explore-links a {
    display: block; 
    margin-bottom: 12px;
}

.contact-info { margin-bottom: 25px; }

/* Map Overlay */
.map-wrapper {
    position: relative; width: 100%; height: 150px;
    border-radius: 4px; overflow: hidden;
    margin-top: 10px;
}
.map-wrapper iframe { width: 100%; height: 100%; border: none; }
.map-overlay {
    position: absolute; inset: 0; background: rgba(17, 63, 54, 0.7);
    backdrop-filter: blur(2px); z-index: 2; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: opacity 0.3s ease;
}
.map-overlay-message {
    background: #fff; color: #113f36; padding: 10px 20px;
    border-radius: 4px; font-size: 12px; font-family: 'Inter', sans-serif;
    font-weight: 600; text-transform: uppercase; letter-spacing: 1px;
    pointer-events: none;
}
.map-wrapper.active .map-overlay { opacity: 0; pointer-events: none; }

.newsletter-form { margin-top: 10px; }
.newsletter-form input {
    width: 100%; padding: 12px 0; border: none; border-bottom: 1px solid rgba(255,255,255,0.3);
    background: transparent; color: #fff; outline: none; margin-bottom: 15px;
    font-family: 'Playfair Display', serif; font-size: 15px;
}
.newsletter-form input::placeholder { color: rgba(255,255,255,0.5); }
.newsletter-form button {
    background: #ffffff; color: #113f36; width: 100%;
    border: none; padding: 14px 20px; border-radius: 4px; 
    font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
    text-transform: uppercase; letter-spacing: 1.5px; font-size: 12px;
    transition: background 0.3s;
}
.newsletter-form button:hover { background: #e0e0e0; }

.bottom-bar {
    border-top: 1px solid rgba(255,255,255,0.2);
    margin-top: 60px;
    padding-top: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Playfair Display', serif;
    font-size: 14px;
    color: rgba(255,255,255,0.8);
}
@media (max-width: 768px) {
    .bottom-bar { flex-direction: column; gap: 15px; text-align: center; }
}
</style>

<footer class="footer">
    <div class="container">
        <div class="row gy-5">
            <!-- Cột 1: Thông tin liên hệ -->
            <div class="col-lg-3 col-md-6">
                <?php if ($logo): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" class="footer-logo">
                <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                        <h4 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 24px; text-transform: none; letter-spacing: normal; color: #fff; line-height: 1.2;">
                            <?= htmlspecialchars($ft['restaurant_name'] ?? 'Restaurantly') ?>
                        </h4>
                    </div>
                <?php endif; ?>
                
                <div class="contact-info" style="margin-bottom: 25px;">
                    <?php if (!empty($ft['address'])): ?><p style="font-size: 13px;"><?= htmlspecialchars($ft['address']) ?></p><?php endif; ?>
                    <?php if (!empty($ft['phone'])): ?><p style="font-size: 13px; margin-top: 15px;"><?= htmlspecialchars($ft['phone']) ?></p><?php endif; ?>
                    <?php if (!empty($ft['email'])): ?><p style="font-size: 13px;"><a href="mailto:<?= htmlspecialchars($ft['email']) ?>"><?= htmlspecialchars($ft['email']) ?></a></p><?php endif; ?>
                </div>
                
                <?php if ($showSocial): ?>
                <div class="social-icons" style="margin-top:0">
                    <?php if (!empty($ft['facebook_url'])): ?><a href="<?= htmlspecialchars($ft['facebook_url']) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                    <?php if (!empty($ft['instagram_url'])): ?><a href="<?= htmlspecialchars($ft['instagram_url']) ?>" target="_blank"><i class="fab fa-instagram"></i></a><?php endif; ?>
                    <?php if (!empty($ft['tiktok_url'])): ?><a href="<?= htmlspecialchars($ft['tiktok_url']) ?>" target="_blank"><i class="fab fa-tiktok"></i></a><?php endif; ?>
                    <?php if (!empty($ft['zalo_url'])): ?><a href="<?= htmlspecialchars($ft['zalo_url']) ?>" target="_blank"><i class="fab fa-zalo"></i></a><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cột 2: Quick Links & Thực Đơn -->
            <div class="col-lg-3 col-md-6">
                <h4>LIÊN KẾT NHANH</h4>
                <div class="explore-links" style="margin-bottom: 30px;">
                    <?php foreach ($links as $l): ?>
                        <a href="<?= htmlspecialchars(safe_url($l['url'], $path_prefix)) ?>" style="font-size: 13px;"><?= htmlspecialchars($l['title']) ?></a>
                    <?php endforeach; ?>
                    <?php if (empty($links)): ?>
                        <a href="<?= safe_url('index.php', $path_prefix) ?>" style="font-size: 13px;">Trang chủ</a>
                        <a href="<?= safe_url('about.php', $path_prefix) ?>" style="font-size: 13px;">Về chúng tôi</a>
                        <a href="<?= safe_url('contact.php', $path_prefix) ?>" style="font-size: 13px;">Liên hệ</a>
                    <?php endif; ?>
                </div>
                
                <h4>THỰC ĐƠN</h4>
                <div class="explore-links">
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#starters" style="font-size: 13px;">Món khai vị (Appetizers)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#main" style="font-size: 13px;">Món chính (Main Courses)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#seafood" style="font-size: 13px;">Hải sản tươi sống (Seafood)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#bbq" style="font-size: 13px;">Đồ nướng (BBQ & Grill)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#vegetarian" style="font-size: 13px;">Món chay (Vegetarian)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#desserts" style="font-size: 13px;">Tráng miệng (Desserts)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#drinks" style="font-size: 13px;">Đồ uống & Rượu vang (Drinks)</a>
                </div>
            </div>

            <!-- Cột 3: Dịch vụ & Mô tả -->
            <div class="col-lg-3 col-md-6">
                <h4>DỊCH VỤ</h4>
                <div class="explore-links" style="margin-bottom: 30px;">
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#private" style="font-size: 13px;">Đặt tiệc cá nhân (Private Dining)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#events" style="font-size: 13px;">Tiệc cưới & Sự kiện (Events)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#catering" style="font-size: 13px;">Phục vụ tận nơi (Catering)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#delivery" style="font-size: 13px;">Giao hàng (Delivery)</a>
                </div>
                
                <h4>MÔ TẢ</h4>
                <p style="font-size: 13px; line-height: 1.8; color: #fff;"><?= nl2br(htmlspecialchars($ft['footer_description'] ?? 'Trải nghiệm ẩm thực tuyệt vời với không gian sang trọng và các món ăn được chế biến từ nguyên liệu tươi ngon nhất.')) ?></p>
            </div>

            <!-- Cột 4: Giờ mở cửa & Đặt bàn -->
            <div class="col-lg-3 col-md-6">
                <h4>GIỜ MỞ CỬA</h4>
                <div class="contact-info" style="font-size: 13px; line-height: 2;">
                    <?php if (!empty($ft['opening_hours'])): ?>
                        <div style="white-space: pre-line;"><?= htmlspecialchars($ft['opening_hours']) ?></div>
                    <?php else: ?>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 2</span> <span>Nghỉ định kỳ</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 3 - Thứ 6</span> <span>10:00 AM - 10:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 7 - CN</span> <span>09:00 AM - 11:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Ngày Lễ</span> <span>Theo lịch đặt trước</span></div>
                    <?php endif; ?>
                </div>
                
                <a href="<?= safe_url('booking_service.php?type=table', $path_prefix) ?>" style="display: block; text-align: center; background: #fff; color: #113f36; padding: 12px; border-radius: 6px; font-family: 'Inter', sans-serif; font-weight: 600; margin-top: 30px; text-decoration: none; transition: background 0.3s;" onmouseover="this.style.background='#e0e0e0'" onmouseout="this.style.background='#fff'">
                    Đặt Bàn Ngay (Book a Table)
                </a>

                <?php if ($showMap && !empty($ft['google_map_iframe'])): ?>
                    <div class="map-wrapper mt-4" id="footerMapWrapper">
                        <div class="map-overlay" id="footerMapOverlay" onclick="activateFooterMap()">
                            <span class="map-overlay-message">🗺️ Xem bản đồ</span>
                        </div>
                        <?= $ft['google_map_iframe'] ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bottom-bar">
            <div><?= htmlspecialchars($ft['copyright_text'] ?? '© 2026 Restaurantly. All Rights Reserved.') ?></div>
            <div>
                <a href="#">Điều khoản sử dụng</a>
                <span style="margin: 0 10px;">|</span>
                <a href="#">Chính sách bảo mật</a>
            </div>
        </div>
    </div>
</footer>

<script>
function activateFooterMap() {
    document.getElementById('footerMapWrapper').classList.add('active');
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
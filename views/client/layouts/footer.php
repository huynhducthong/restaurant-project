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
.footer {
    font-family: 'Poppins', sans-serif;
    position: relative;
    padding: 70px 0 0;
    background-color: <?= $ft['footer_bg_color'] ?? '#0c0b09' ?>;
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
    background: rgba(0,0,0,0.85);
    z-index: 1;
}
<?php endif; ?>
.footer .container { position: relative; z-index: 2; }
.footer h4 { font-family: 'Playfair Display', serif; color: #cda45e; margin-bottom: 20px; }
.footer a { color: inherit; text-decoration: none; transition: 0.3s; }
.footer a:hover { color: #cda45e; }
.footer-logo { max-height: 60px; margin-bottom: 15px; }

.social-icons a {
    display: inline-flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; background: rgba(255,255,255,0.1);
    border-radius: 50%; margin: 0 5px 10px 0; font-size: 14px;
}
.social-icons a:hover { background: #cda45e; color: #000; }

.explore-links a {
    display: inline-block; margin: 0 6px 10px 0;
    padding: 6px 18px; border: 1px solid rgba(205,164,94,0.3);
    border-radius: 30px; font-size: 13px;
}
.explore-links a:hover { background: rgba(205,164,94,0.15); border-color: #cda45e; }

.contact-info { list-style: none; padding: 0; }
.contact-info li { margin-bottom: 12px; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }
.contact-info i { width: 18px; color: #cda45e; margin-top: 2px; }

/* Map Overlay cho LiveView */
.map-wrapper {
    position: relative;
    width: 100%;
    height: 200px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(205,164,94,0.2);
    margin-top: 10px;
}
.map-wrapper iframe {
    width: 100%;
    height: 100%;
    border: none;
}
.map-overlay {
    position: absolute;
    inset: 0;
    background: rgba(12, 11, 9, 0.6);
    backdrop-filter: blur(2px);
    z-index: 2;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.3s ease;
}
.map-overlay-message {
    background: rgba(0,0,0,0.7);
    color: #cda45e;
    padding: 10px 22px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid rgba(205,164,94,0.5);
    pointer-events: none;
}
.map-wrapper.active .map-overlay {
    opacity: 0;
    pointer-events: none;
}

.newsletter-form { position: relative; margin-top: 15px; }
.newsletter-form input {
    width: 100%; padding: 10px 50px 10px 15px; border: 1px solid rgba(205,164,94,0.3);
    border-radius: 30px; background: transparent; color: #fff; outline: none;
}
.newsletter-form button {
    position: absolute; right: 3px; top: 3px; background: #cda45e; color: #000;
    border: none; padding: 8px 20px; border-radius: 30px; font-weight: 600; cursor: pointer;
}
.copyright {
    border-top: 1px solid rgba(205,164,94,0.15);
    font-size: 13px; color: #aaa; padding: 20px 0; margin-top: 40px;
    text-align: center; position: relative; z-index: 2;
}
</style>

<footer class="footer">
    <div class="container">
        <div class="row gy-4">
            <!-- Cột 1: Thương hiệu + Mạng xã hội -->
            <div class="col-lg-3 col-md-6">
                <?php if ($logo): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" class="footer-logo">
                <?php else: ?>
                    <h4><?= htmlspecialchars($ft['restaurant_name'] ?? 'Restaurantly') ?></h4>
                <?php endif; ?>
                <p style="font-size:14px;line-height:1.7"><?= nl2br(htmlspecialchars($ft['footer_description'] ?? '')) ?></p>
                <?php if ($showSocial): ?>
                <div class="social-icons mt-3">
                    <?php if (!empty($ft['facebook_url'])): ?><a href="<?= htmlspecialchars($ft['facebook_url']) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                    <?php if (!empty($ft['instagram_url'])): ?><a href="<?= htmlspecialchars($ft['instagram_url']) ?>" target="_blank"><i class="fab fa-instagram"></i></a><?php endif; ?>
                    <?php if (!empty($ft['tiktok_url'])): ?><a href="<?= htmlspecialchars($ft['tiktok_url']) ?>" target="_blank"><i class="fab fa-tiktok"></i></a><?php endif; ?>
                    <?php if (!empty($ft['zalo_url'])): ?><a href="<?= htmlspecialchars($ft['zalo_url']) ?>" target="_blank"><i class="fab fa-zalo"></i></a><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cột 2: Khám phá -->
            <div class="col-lg-3 col-md-6">
                <h4>Khám phá</h4>
                <div class="explore-links">
                    <?php foreach ($links as $l): ?>
                        <a href="<?= htmlspecialchars(safe_url($l['url'], $path_prefix)) ?>"><?= htmlspecialchars($l['title']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cột 3: Liên hệ -->
            <div class="col-lg-3 col-md-6">
                <h4>Liên hệ</h4>
                <ul class="contact-info">
                    <?php if (!empty($ft['address'])): ?><li><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ft['address']) ?></li><?php endif; ?>
                    <?php if (!empty($ft['phone'])): ?><li><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($ft['phone']) ?></li><?php endif; ?>
                    <?php if (!empty($ft['email'])): ?><li><i class="fas fa-envelope"></i> <?= htmlspecialchars($ft['email']) ?></li><?php endif; ?>
                    <?php if (!empty($ft['opening_hours'])): ?><li><i class="fas fa-clock"></i> <?= htmlspecialchars($ft['opening_hours']) ?></li><?php endif; ?>
                </ul>
            </div>

            <!-- Cột 4: Bản đồ (LiveView) & Newsletter -->
            <div class="col-lg-3 col-md-6">
                <?php if ($showMap && !empty($ft['google_map_iframe'])): ?>
                    <h4>Bản đồ</h4>
                    <div class="map-wrapper" id="footerMapWrapper">
                        <div class="map-overlay" id="footerMapOverlay" onclick="activateFooterMap()">
                            <span class="map-overlay-message">🗺️ Nhấn để xem bản đồ</span>
                        </div>
                        <?= $ft['google_map_iframe'] ?>
                    </div>
                <?php endif; ?>
                <?php if ($showNews): ?>
                    <h4 class="mt-3">Đăng ký nhận tin</h4>
                    <form action="<?= safe_url('newsletter_subscribe.php', $path_prefix) ?>" method="POST" class="newsletter-form">
                        <?= csrf_field() ?>
                        <input type="email" name="email" placeholder="Nhập email của bạn" required>
                        <button type="submit">Gửi</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="copyright"><?= htmlspecialchars($ft['copyright_text'] ?? '© 2026 Restaurantly. All Rights Reserved.') ?></div>
    </div>
</footer>

<script>
function activateFooterMap() {
    document.getElementById('footerMapWrapper').classList.add('active');
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
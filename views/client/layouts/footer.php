<?php
require_once __DIR__ . '/../../../config/database.php';
$db = (new Database())->getConnection();

// Lấy cấu hình Footer từ database
$stmt = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
    $ft[$row['setting_key']] = $row['setting_value']; 
}

$links = $db->query("SELECT * FROM footer_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);
$bg_img = !empty($ft['footer_bg_image']) ? "public/assets/img/" . $ft['footer_bg_image'] : "";
?>


<footer id="footer" style="position:relative; background:<?= $bg_img ? "url('$bg_img') center center / cover no-repeat fixed" : $ft['footer_bg_color'] ?>; color:<?= $ft['footer_text_color'] ?>; padding:80px 0 30px;">
    <?php if($bg_img): ?>
        <div style="position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:1;"></div>
    <?php endif; ?>
    
    <div class="container" style="position:relative; z-index:2;">
        <div class="row">
            <div class="col-lg-4">
                <h3 style="color:#cda45e; font-family:'Playfair Display',serif;"><?= htmlspecialchars($ft['restaurant_name'] ?? 'Restaurantly') ?></h3>
                <p><?= nl2br(htmlspecialchars($ft['footer_description'] ?? '')) ?></p>
            </div>

            <div class="col-lg-4">
                <h4 style="color:#cda45e;">Khám phá</h4>
                <ul class="list-unstyled">
                    <?php foreach($links as $l): ?>
                        <li class="mb-2">
                            <a href="<?= htmlspecialchars($l['url']) ?>" style="color:inherit; text-decoration:none;">
                                <i class="fas fa-chevron-right me-2" style="font-size: 10px; color: #cda45e;"></i>
                                <?= htmlspecialchars($l['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="col-lg-4">
                <?php if(($ft['show_map'] ?? '0') == '1'): ?>
                    <div style="border-radius:10px; overflow:hidden; border: 1px solid rgba(255,255,255,0.1);">
                        <?= $ft['google_map_iframe'] ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/assets/client/js/main.js"></script>

</body>
</html>
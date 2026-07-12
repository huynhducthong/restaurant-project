<?php
session_start();
$current_page = 'dish.php';
require_once 'config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: menu.php");
    exit;
}

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT * FROM foods WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$dish = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dish) {
    header("Location: menu.php");
    exit;
}

// Parse JSON food journey
$fj = json_decode($dish['food_journey'] ?? '{}', true);
if (!is_array($fj)) {
    // Nếu dữ liệu cũ không phải JSON, gán tạm vào 1 field để fallback
    $fj = ['cooking_art' => $dish['food_journey'] ?? ''];
}

include 'views/client/layouts/header.php';
?>



<!-- Hero Section -->
<section class="dish-hero">
    <div class="hero-bg-blur" style="--dish-bg-img: url('public/assets/img/menu/<?= htmlspecialchars($dish['image'] ?: 'default.jpg') ?>');"></div>
    <img src="public/assets/img/menu/<?= htmlspecialchars($dish['image'] ?: 'default.jpg') ?>" alt="Hero Background" class="hero-bg-img">
    <div class="container">
        <div class="hero-content">
            <div class="hero-subtitle">Bespoke Dining Experience</div>
            <h1 class="hero-title"><?= htmlspecialchars($dish['name']) ?></h1>
            <div class="hero-price"><?= number_format($dish['price'], 0, ',', '.') ?> VND</div>
        </div>
    </div>
    <div class="scroll-indicator">
        Khám phá
        <div class="scroll-line"></div>
    </div>
</section>

<div class="editorial-wrapper">
    <!-- Introduction & Chef Note -->
    <div class="container">
        <div class="row align-items-center mb-5 pb-5">
            <div class="col-lg-6 mb-5 mb-lg-0 pr-lg-5 reveal">
                <h3 style="font-family:'Cormorant Garamond', serif; font-size:2rem; color:var(--c-burgundy); margin-bottom: 30px;">
                    Tinh Hoa Ẩm Thực
                </h3>
                <div class="editorial-text drop-cap">
                    <?= nl2br(htmlspecialchars($dish['description'])) ?>
                </div>
            </div>
            
            <?php if (!empty(trim($dish['chef_note']))): ?>
            <div class="col-lg-6 reveal" style="transition-delay: 0.2s;">
                <div class="chef-quote-box">
                    <div class="chef-quote-text">
                        <?= nl2br(htmlspecialchars($dish['chef_note'])) ?>
                    </div>
                    <div class="chef-sign">— Executive Chef</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Vertical Timeline -->
    <?php if (!empty(array_filter($fj))): ?>
    <section class="timeline-section">
        <div class="container">
            <h2 class="timeline-title reveal">Hành Trình Kiến Tạo</h2>
            <div class="timeline">
                
                                <?php if (!empty(trim($fj['origin'] ?? ''))): ?>
                <div class="timeline-item reveal">
                    <div class="timeline-icon"><i class="fas fa-globe-asia"></i></div>
                    <div class="timeline-content">
                        <?php if (!empty($fj['origin_img'])): ?>
                            <div style="overflow: hidden; border-radius: 8px;">
                                <img src="public/assets/img/journey/<?= htmlspecialchars($fj['origin_img']) ?>" alt="Nguồn Gốc" class="timeline-image">
                            </div>
                        <?php endif; ?>
                        <h3 class="timeline-step-title"><span>01</span> Nguồn Gốc</h3>
                        <div class="editorial-text">
                            <?= nl2br(htmlspecialchars($fj['origin'])) ?>
                        </div>
                        
                        <?php if (!empty($fj['certificate_img'])): ?>
                        <div class="mt-4">
                            <button onclick="openCertModal('<?= htmlspecialchars($fj['certificate_img']) ?>')" class="btn btn-outline-warning btn-sm w-100 text-uppercase rounded-0" style="letter-spacing: 2px; font-family: 'Cormorant Garamond', serif; border-color: var(--c-gold); color: var(--c-gold); transition: 0.3s; padding: 10px;">[ Xem Chứng Nhận ]</button>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                <?php endif; ?>

                                <?php if (!empty(trim($fj['selection'] ?? ''))): ?>
                <div class="timeline-item reveal">
                    <div class="timeline-icon"><i class="fas fa-gem"></i></div>
                    <div class="timeline-content">
                        <?php if (!empty($fj['selection_img'])): ?>
                            <div style="overflow: hidden; border-radius: 8px;">
                                <img src="public/assets/img/journey/<?= htmlspecialchars($fj['selection_img']) ?>" alt="Tuyển Chọn" class="timeline-image">
                            </div>
                        <?php endif; ?>
                        <h3 class="timeline-step-title"><span>02</span> Tuyển Chọn</h3>
                        <div class="editorial-text">
                            <?= nl2br(htmlspecialchars($fj['selection'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                                <?php if (!empty(trim($fj['storage'] ?? ''))): ?>
                <div class="timeline-item reveal">
                    <div class="timeline-icon"><i class="fas fa-snowflake"></i></div>
                    <div class="timeline-content">
                        <?php if (!empty($fj['storage_img'])): ?>
                            <div style="overflow: hidden; border-radius: 8px;">
                                <img src="public/assets/img/journey/<?= htmlspecialchars($fj['storage_img']) ?>" alt="Bảo Quản" class="timeline-image">
                            </div>
                        <?php endif; ?>
                        <h3 class="timeline-step-title"><span>03</span> Bảo Quản</h3>
                        <div class="editorial-text">
                            <?= nl2br(htmlspecialchars($fj['storage'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                                <?php if (!empty(trim($fj['prep'] ?? ''))): ?>
                <div class="timeline-item reveal">
                    <div class="timeline-icon"><i class="fas fa-hands"></i></div>
                    <div class="timeline-content">
                        <?php if (!empty($fj['prep_img'])): ?>
                            <div style="overflow: hidden; border-radius: 8px;">
                                <img src="public/assets/img/journey/<?= htmlspecialchars($fj['prep_img']) ?>" alt="Sơ Chế" class="timeline-image">
                            </div>
                        <?php endif; ?>
                        <h3 class="timeline-step-title"><span>04</span> Sơ Chế</h3>
                        <div class="editorial-text">
                            <?= nl2br(htmlspecialchars($fj['prep'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                                <?php if (!empty(trim($fj['cooking_art'] ?? ''))): ?>
                <div class="timeline-item reveal">
                    <div class="timeline-icon"><i class="fas fa-fire"></i></div>
                    <div class="timeline-content">
                        <?php if (!empty($fj['cooking_art_img'])): ?>
                            <div style="overflow: hidden; border-radius: 8px;">
                                <img src="public/assets/img/journey/<?= htmlspecialchars($fj['cooking_art_img']) ?>" alt="Nghệ Thuật Chế Biến" class="timeline-image">
                            </div>
                        <?php endif; ?>
                        <h3 class="timeline-step-title"><span>05</span> Nghệ Thuật Chế Biến</h3>
                        <div class="editorial-text">
                            <?= nl2br(htmlspecialchars($fj['cooking_art'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                                <?php if (!empty(trim($fj['presentation'] ?? ''))): ?>
                <div class="timeline-item reveal">
                    <div class="timeline-icon"><i class="fas fa-utensils"></i></div>
                    <div class="timeline-content">
                        <?php if (!empty($fj['presentation_img'])): ?>
                            <div style="overflow: hidden; border-radius: 8px;">
                                <img src="public/assets/img/journey/<?= htmlspecialchars($fj['presentation_img']) ?>" alt="Trình Bày" class="timeline-image">
                            </div>
                        <?php endif; ?>
                        <h3 class="timeline-step-title"><span>06</span> Trình Bày</h3>
                        <div class="editorial-text">
                            <?= nl2br(htmlspecialchars($fj['presentation'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Final CTA -->
    <section class="reveal" style="padding: 100px 0; background: var(--c-light);">
        <div class="container text-center">
            <h2 style="font-family:'Cormorant Garamond', serif; font-size: 2.5rem; color: var(--c-burgundy); margin-bottom: 20px;">Trải Nghiệm Hương Vị</h2>
            <p class="editorial-text mb-5" style="max-width: 500px; margin: 0 auto; color: #444;">Vị giác của bạn xứng đáng được thăng hoa. Hãy đặt bàn ngay để trực tiếp thưởng thức tuyệt tác này.</p>
            <a href="booking_service.php" class="btn-reserve-solid" style="padding: 15px 45px; font-size: 1.1rem; background: var(--c-gold); color:#fff; border-color:var(--c-gold); text-decoration: none;">
                ĐẶT BÀN NGAY
            </a>
        </div>
    </section>
</div>

<!-- Certificate Modal -->
<div id="certModal" class="cert-modal" onclick="closeCertModal(event)">
    <div class="cert-modal-content" onclick="event.stopPropagation()">
        <button class="cert-modal-close" onclick="closeCertModal()">&times;</button>
        <div class="cert-modal-body text-center">
            <img id="certModalImg" src="" alt="Certificate" class="img-fluid border shadow-lg" style="border-color: var(--c-gold) !important; padding: 5px; background: #fff; max-height: 85vh; object-fit: contain;">
        </div>
    </div>
</div>



<script>
    // Certificate Modal Logic
    function openCertModal(imgFile) {
        document.getElementById('certModalImg').src = 'public/assets/img/journey/' + imgFile;
        
        const modal = document.getElementById('certModal');
        modal.style.display = 'flex';
        // Trigger reflow
        void modal.offsetWidth;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeCertModal(e) {
        if(e && e.target !== e.currentTarget && !e.target.classList.contains('cert-modal-close')) return;
        const modal = document.getElementById('certModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 400);
    }

    // Simple Scroll Reveal Animation
    document.addEventListener("DOMContentLoaded", function() {
        const reveals = document.querySelectorAll(".reveal");
        
        function revealOnScroll() {
            const windowHeight = window.innerHeight;
            const elementVisible = 100;
            
            reveals.forEach(reveal => {
                const elementTop = reveal.getBoundingClientRect().top;
                if (elementTop < windowHeight - elementVisible) {
                    reveal.classList.add("active");
                }
            });
        }
        
        window.addEventListener("scroll", revealOnScroll);
        revealOnScroll(); // Trigger once on load
    });
</script>

<?php include 'views/client/layouts/footer.php'; ?>

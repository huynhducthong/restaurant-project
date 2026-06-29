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

<style>
    :root {
        --c-gold: #D4AF37;
        --c-dark: #121212;
        --c-light: #F9F8F6;
        --c-burgundy: #601A24;
    }
    
    body {
        background-color: var(--c-light);
    }

    /* Hero Section */
    .dish-hero {
        position: relative;
        height: 100vh;
        min-height: 700px;
        display: flex;
        align-items: flex-end;
        padding-bottom: 100px;
        overflow: hidden;
    }
    .hero-bg-blur {
        position: absolute;
        inset: -50px;
        background-image: url('public/assets/img/menu/<?= htmlspecialchars($dish['image'] ?: 'default.jpg') ?>');
        background-size: cover;
        background-position: center;
        filter: blur(40px) brightness(0.5);
        z-index: 0;
    }
    .hero-bg-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        z-index: 1;
    }
    .dish-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(18,18,18,1) 0%, rgba(18,18,18,0.5) 50%, rgba(18,18,18,0) 100%);
        z-index: 2;
        pointer-events: none;
    }
    .hero-content {
        position: relative;
        z-index: 2;
        width: 100%;
        animation: fadeUp 1.5s ease forwards;
    }
    .hero-subtitle {
        font-family: 'Inter', sans-serif;
        color: var(--c-gold);
        letter-spacing: 4px;
        text-transform: uppercase;
        font-size: 0.9rem;
        margin-bottom: 15px;
        font-weight: 600;
    }
    .hero-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 5.5rem;
        font-weight: 400;
        color: #fff;
        line-height: 1.1;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .hero-price {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        color: #fff;
        font-style: italic;
    }
    
    /* Scroll Down Indicator */
    .scroll-indicator {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        color: #fff;
        font-size: 12px;
        letter-spacing: 2px;
        text-transform: uppercase;
        display: flex;
        flex-direction: column;
        align-items: center;
        opacity: 0.7;
        z-index: 2;
    }
    .scroll-line {
        width: 1px;
        height: 40px;
        background: rgba(255,255,255,0.5);
        margin-top: 10px;
        position: relative;
        overflow: hidden;
    }
    .scroll-line::after {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: var(--c-gold);
        animation: scrollLine 2s infinite cubic-bezier(0.65, 0, 0.35, 1);
    }

    /* Magazine Editorial Layout */
    .editorial-wrapper {
        position: relative;
        z-index: 10;
        background: var(--c-light);
        margin-top: -50px;
        padding: 100px 0;
        border-top-left-radius: 40px;
        border-top-right-radius: 40px;
        box-shadow: 0 -20px 50px rgba(0,0,0,0.2);
    }

    .drop-cap::first-letter {
        font-family: 'Cormorant Garamond', serif;
        font-size: 5rem;
        float: left;
        line-height: 0.8;
        padding-right: 15px;
        padding-top: 5px;
        color: var(--c-gold);
    }
    
    .editorial-text {
        font-family: 'Inter', sans-serif;
        font-size: 1.1rem;
        line-height: 1.9;
        color: #555;
        font-weight: 300;
    }

    .chef-quote-box {
        position: relative;
        padding: 50px 40px;
        background: #fff;
        border: 1px solid #EAE0C8;
        box-shadow: 0 15px 40px rgba(0,0,0,0.03);
    }
    .chef-quote-box::before {
        content: "“";
        position: absolute;
        top: -30px;
        left: 30px;
        font-family: 'Cormorant Garamond', serif;
        font-size: 100px;
        color: var(--c-gold);
        opacity: 0.3;
        line-height: 1;
    }
    .chef-quote-text {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.6rem;
        font-style: italic;
        color: var(--c-burgundy);
        line-height: 1.6;
        position: relative;
        z-index: 2;
    }
    .chef-sign {
        margin-top: 30px;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.2rem;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--c-gold);
    }

    /* Vertical Timeline Section */
    .timeline-section {
        background-color: var(--c-dark);
        color: #fff;
        padding: 120px 0;
        position: relative;
    }
    .timeline-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        text-align: center;
        color: var(--c-gold);
        margin-bottom: 80px;
    }
    .timeline {
        position: relative;
        max-width: 1000px;
        margin: 0 auto;
    }
    .timeline::after {
        content: '';
        position: absolute;
        width: 2px;
        background-color: rgba(212, 175, 55, 0.2);
        top: 0;
        bottom: 0;
        left: 50%;
        margin-left: -1px;
    }
    
    .timeline-item {
        padding: 20px 40px;
        position: relative;
        background: inherit;
        width: 50%;
    }
    .timeline-item:nth-child(odd) {
        left: 0;
        text-align: right;
    }
    .timeline-item:nth-child(even) {
        left: 50%;
        text-align: left;
    }
    
    .timeline-icon {
        position: absolute;
        width: 60px;
        height: 60px;
        right: -30px;
        background-color: var(--c-dark);
        border: 2px solid var(--c-gold);
        top: 15px;
        border-radius: 50%;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: var(--c-gold);
    }
    .timeline-item:nth-child(even) .timeline-icon {
        left: -30px;
    }
    
    .timeline-content {
        padding: 20px 30px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 10px;
        position: relative;
        border: 1px solid rgba(255,255,255,0.05);
    }
    
    .timeline-step-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        color: #fff;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    .timeline-item:nth-child(odd) .timeline-step-title {
        justify-content: flex-end;
    }
    .timeline-step-title span {
        color: var(--c-gold);
        font-size: 1.2rem;
        margin: 0 10px;
        font-family: 'Inter', sans-serif;
        letter-spacing: 3px;
    }
    
    .timeline-item .editorial-text {
        color: #ddd;
    }
    .timeline-image {
        width: 100%;
        height: 250px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 20px;
        transition: transform 0.5s ease;
    }
    .timeline-content:hover .timeline-image {
        transform: scale(1.02);
    }

    /* Animations */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes scrollLine {
        0% { transform: translateY(-100%); }
        100% { transform: translateY(100%); }
    }
    
    /* Reveal on Scroll Class */
    .reveal {
        opacity: 0;
        transform: translateY(40px);
        transition: all 1s cubic-bezier(0.5, 0, 0, 1);
    }
    .reveal.active {
        opacity: 1;
        transform: translateY(0);
    }

    @media (max-width: 768px) {
        .hero-title { font-size: 3.5rem; }
        .dish-hero { min-height: 500px; padding-bottom: 80px; }
        .editorial-wrapper { padding: 60px 0; border-top-left-radius: 20px; border-top-right-radius: 20px; }
        .timeline::after { left: 30px; }
        .timeline-item { width: 100%; padding-left: 80px; padding-right: 20px; text-align: left !important; }
        .timeline-item:nth-child(even) { left: 0; }
        .timeline-icon { left: 0 !important; }
        .timeline-item:nth-child(odd) .timeline-step-title { justify-content: flex-start; }
    }
</style>

<!-- Hero Section -->
<section class="dish-hero">
    <div class="hero-bg-blur"></div>
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
                            <button onclick="openCertModal('<?= htmlspecialchars($fj['certificate_img']) ?>')" class="btn btn-outline-warning btn-sm w-100 text-uppercase rounded-0" style="letter-spacing: 2px; font-family: 'Playfair Display', serif; border-color: var(--c-gold); color: var(--c-gold); transition: 0.3s; padding: 10px;">[ Xem Chứng Nhận ]</button>
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

<style>
.cert-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(18, 18, 18, 0.85);
    backdrop-filter: blur(8px);
    opacity: 0;
    transition: opacity 0.4s ease;
    align-items: center;
    justify-content: center;
}
.cert-modal.show {
    opacity: 1;
}
.cert-modal-content {
    background: var(--c-dark);
    border: 1px solid var(--c-gold);
    padding: 40px;
    max-width: 800px;
    width: 90%;
    position: relative;
    transform: scale(0.95);
    transition: transform 0.4s ease;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}
.cert-modal.show .cert-modal-content {
    transform: scale(1);
}
.cert-modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    background: none;
    border: none;
    color: var(--c-gold);
    font-size: 2rem;
    cursor: pointer;
    line-height: 1;
    transition: 0.3s;
}
.cert-modal-close:hover {
    color: #fff;
    transform: scale(1.1);
}
</style>

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

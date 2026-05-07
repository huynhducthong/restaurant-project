<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
$db = (new Database())->getConnection();

include 'views/client/layouts/header.php'; 
?>

<main id="main">
    <section class="breadcrumbs" style="padding: 140px 0 60px; background: #0c0b09; border-bottom: 1px solid rgba(205, 164, 94, 0.2);">
        <div class="container text-center">
            <h2 style="font-family: 'Playfair Display', serif; color: #cda45e; font-size: 50px; font-weight: 700;">Câu Chuyện Restaurantly</h2>
            <p style="color: #eee; font-style: italic;">Nơi tinh hoa ẩm thực hội tụ</p>
        </div>
    </section>

    <section id="about" class="about" style="padding: 100px 0; background: #1a1814;">
        <div class="container">
            <div class="row gy-5 align-items-center">
                <div class="col-lg-6">
                    <h3 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 36px; margin-bottom: 25px;">Hành trình 15 năm kiến tạo hương vị</h3>
                    <p style="color: #fff; line-height: 1.8; font-size: 16px;">
                        Bắt đầu từ một cửa hàng nhỏ ven phố, Restaurantly đã vươn mình trở thành biểu tượng của sự tinh tế trong làng ẩm thực. Chúng tôi không chỉ bán món ăn, chúng tôi mang đến một trải nghiệm cảm xúc.
                    </p>
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <h5 style="color: #cda45e;"><i class="bi bi-star-fill me-2"></i> Chất lượng</h5>
                            <p style="color: #aaa; font-size: 14px;">Nguyên liệu tươi sạch, nhập khẩu trực tiếp và kiểm định khắt khe.</p>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h5 style="color: #cda45e;"><i class="bi bi-people-fill me-2"></i> Đội ngũ</h5>
                            <p style="color: #aaa; font-size: 14px;">Những đầu bếp hàng đầu với tâm huyết đặt vào từng chi tiết nhỏ.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-img position-relative">
                        <img src="public/assets/img/about.jpg" class="img-fluid rounded-4 shadow-lg" alt="About Image" style="border: 2px solid #cda45e;">
                        <div class="experience-badge" style="position: absolute; bottom: -20px; right: -20px; background: #cda45e; padding: 20px; border-radius: 10px; color: #fff; text-align: center;">
                            <span style="font-size: 30px; font-weight: 700; display: block;">15+</span>
                            <small>Năm kinh nghiệm</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features" style="padding: 80px 0; background: #0c0b09;">
        <div class="container text-center">
            <h4 style="color: #cda45e; margin-bottom: 40px; font-family: 'Playfair Display', serif;">Giá trị cốt lõi của chúng tôi</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="p-4" style="background: #1a1814; border: 1px solid #37332a; border-radius: 10px;">
                        <i class="bi bi-heart-fill" style="font-size: 40px; color: #cda45e;"></i>
                        <h5 class="text-white mt-3">Tận Tâm</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4" style="background: #1a1814; border: 1px solid #37332a; border-radius: 10px;">
                        <i class="bi bi-shield-check" style="font-size: 40px; color: #cda45e;"></i>
                        <h5 class="text-white mt-3">Uy Tín</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4" style="background: #1a1814; border: 1px solid #37332a; border-radius: 10px;">
                        <i class="bi bi-brightness-high" style="font-size: 40px; color: #cda45e;"></i>
                        <h5 class="text-white mt-3">Sáng Tạo</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'views/client/layouts/footer.php'; ?>
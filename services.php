<?php 
session_start();

// Nếu chưa có session user_id, đá về trang login
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('Vui lòng đăng nhập để sử dụng dịch vụ này!');
        window.location.href = 'public/login.php';
    </script>";
    exit();
}

include 'views/client/layouts/header.php'; 
?>

<style>
/* Nền tổng thể trang */
.services-page {
    background: linear-gradient(rgba(12, 11, 9, 0.9), rgba(12, 11, 9, 0.9)), 
                url('public/assets/img/hero-bg.jpg') center center no-repeat;
    background-size: cover;
    padding: 100px 0;
}

/* Tiêu đề trang */
.section-title h2 {
    color: #cda45e;
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 20px;
    text-transform: uppercase;
}

/* Các ô dịch vụ (S-Box) */
.s-box {
    background: rgba(255, 255, 255, 0.03); /* Hiệu ứng kính mờ */
    border: 1px solid rgba(205, 164, 94, 0.2); /* Viền vàng nhạt */
    padding: 40px;
    border-radius: 15px;
    transition: all 0.4s ease;
    height: 100%;
    backdrop-filter: blur(5px); /* Làm mờ nền phía sau */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Hiệu ứng khi di chuột vào ô */
.s-box:hover {
    background: rgba(205, 164, 94, 0.1);
    border-color: #cda45e;
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(205, 164, 94, 0.15);
}

/* Icon */
.s-box i {
    font-size: 45px;
    color: #cda45e;
    margin-bottom: 25px;
}

/* Tiêu đề dịch vụ */
.s-box h4 {
    color: #fff;
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    margin-bottom: 15px;
}

/* Nội dung mô tả */
.s-box p {
    color: #adb5bd;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 25px;
}

/* Nút bấm */
.btn-outline-warning {
    border: 2px solid #cda45e;
    color: #cda45e;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 50px;
    text-transform: uppercase;
    font-size: 13px;
    transition: 0.3s;
}

.btn-outline-warning:hover {
    background: #cda45e;
    color: #000;
}
</style>

<main id="main" style="margin-top: 100px;">
  <section class="services-page" style="background: #0c0b09; padding: 60px 0; color: #fff;">
    <div class="container">
      <div class="section-title text-center mb-5">
        <h2 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 36px;">Dịch Vụ Của Chúng Tôi</h2>
        <p style="font-style: italic; color: #eee;">Mang đến trải nghiệm ẩm thực đẳng cấp và chuyên nghiệp</p>
      </div>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="s-box" style="background: #1a1814; padding: 40px; border: 1px solid #37332a; display: flex; gap: 20px;">
            <i class="fas fa-utensils" style="color: #cda45e; font-size: 40px;"></i>
            <div>
              <h4 style="color: #cda45e;">Đặt Bàn Định Kỳ</h4>
              <p>Thưởng thức không gian nhà hàng sang trọng với những vị trí view đẹp nhất.</p>
              <a href="booking_service.php?type=table" class="btn btn-sm btn-outline-warning">Đặt chỗ ngay</a>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="s-box" style="background: #1a1814; padding: 40px; border: 1px solid #37332a; display: flex; gap: 20px;">
            <i class="fas fa-birthday-cake" style="color: #cda45e; font-size: 40px;"></i>
            <div>
              <h4 style="color: #cda45e;">Tiệc Sinh Nhật / Kỷ Niệm</h4>
              <p>Trang trí theo chủ đề và tạo nên những khoảnh khắc đáng nhớ.</p>
              <a href="booking_service.php?type=birthday" class="btn btn-sm btn-outline-warning">Đặt tiệc</a>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="s-box" style="background: #1a1814; padding: 40px; border: 1px solid #37332a; display: flex; gap: 20px;">
            <i class="fas fa-user-tie" style="color: #cda45e; font-size: 40px;"></i>
            <div>
              <h4 style="color: #cda45e;">Đầu Bếp Tại Gia</h4>
              <p>Đưa đầu bếp chuẩn 5 sao đến tận gian bếp nhà bạn để phục vụ.</p>
              <a href="booking_service.php?type=chef" class="btn btn-sm btn-outline-warning">Thuê đầu bếp</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php // include 'footer.php'; ?>
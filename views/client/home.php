<?php include_once 'layouts/header.php'; ?>

<section id="hero">
    <div class="container">
        <h1>Chào mừng đến với <span>Restaurantly</span></h1>
        <p>Cung cấp những món ăn tuyệt vời trong hơn 18 năm!</p>
        <div class="btns">
            <a href="#menu" class="btn-menu">Thực đơn của chúng tôi</a>
            <a href="#book-a-table" class="btn-book">Đặt bàn</a>
        </div>
    </div>
</section>

<section id="book-a-table" class="book-a-table" style="padding: 60px 0; background: #0c0b09; color: #fff;">
  <div class="container" data-aos="fade-up">
    <div class="section-title text-center mb-5">
      <h2 style="font-size: 14px; color: #cda45e; letter-spacing: 2px; text-transform: uppercase;">Đặt bàn</h2>
      <p style="font-family: 'Playfair Display', serif; font-size: 36px; font-weight: 700;">Giữ chỗ cho bữa tối của bạn</p>
    </div>

    <form action="index.php?action=book" method="post" class="php-email-form">
      <div class="row">
        <div class="col-lg-4 col-md-6 form-group">
          <input type="text" name="name" class="form-control" placeholder="Tên của bạn" required style="background: #0c0b09; border: 1px solid #625b4b; color: #fff; padding: 10px;">
        </div>
        <div class="col-lg-4 col-md-6 form-group mt-3 mt-md-0">
          <input type="email" class="form-control" name="email" placeholder="Email" required style="background: #0c0b09; border: 1px solid #625b4b; color: #fff; padding: 10px;">
        </div>
        <div class="col-lg-4 col-md-6 form-group mt-3 mt-md-0">
          <input type="text" class="form-control" name="phone" placeholder="Số điện thoại" required style="background: #0c0b09; border: 1px solid #625b4b; color: #fff; padding: 10px;">
        </div>
        <div class="col-lg-4 col-md-6 form-group mt-3">
          <input type="date" name="date" class="form-control" required style="background: #0c0b09; border: 1px solid #625b4b; color: #fff; padding: 10px;">
        </div>
        <div class="col-lg-4 col-md-6 form-group mt-3">
          <input type="time" name="time" class="form-control" required style="background: #0c0b09; border: 1px solid #625b4b; color: #fff; padding: 10px;">
        </div>
        <div class="col-lg-4 col-md-6 form-group mt-3">
          <input type="number" class="form-control" name="people" placeholder="Số người" required style="background: #0c0b09; border: 1px solid #625b4b; color: #fff; padding: 10px;">
        </div>
      </div>
      <div class="text-center mt-4">
        <button type="submit" style="background: #cda45e; border: 0; padding: 10px 35px; color: #fff; transition: 0.4s; border-radius: 50px;">Gửi yêu cầu đặt bàn</button>
      </div>
    </form>
  </div>
</section>

<?php include_once 'layouts/footer.php'; ?>
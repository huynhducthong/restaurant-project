<?php
// Gọi file cấu hình ở đầu trang
require_once '../config/google_setup.php';
// Tạo đường dẫn động để chuyển hướng sang Google
$login_url = $client->createAuthUrl(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Đăng nhập - Restaurantly</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  
  <style>
    /* Tổng thể trang */
    body { 
        background: #0c0b09; 
        color: #fff; 
        font-family: 'Poppins', sans-serif; 
        margin: 0; 
        overflow-x: hidden; 
    }

    /* Nền hình ảnh */
    .auth-bg {
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%;
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                    url('assets/img/hero-bg.jpg') center center no-repeat;
        background-size: cover; 
        z-index: -1;
    }

    /* Khung chứa Form */
    .auth-container {
        background: rgba(26, 24, 20, 0.9);
        padding: 40px; 
        border: 1px solid #37332a; 
        border-radius: 10px;
        width: 100%; 
        max-width: 400px; 
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }

    /* Tiêu đề */
    h2 { 
        color: #cda45e; 
        font-family: 'Playfair Display', serif; 
        text-align: center; 
        margin-bottom: 30px; 
    }

    /* Ô nhập liệu (Input) */
    .form-control {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid #37332a !important;
        color: #ffffff !important; /* Chữ gõ vào màu trắng */
        padding: 12px;
        margin-bottom: 20px;
        transition: 0.3s;
    }

    .form-control:focus { 
        border-color: #cda45e !important; 
        box-shadow: none !important; 
        background: rgba(255, 255, 255, 0.1) !important;
    }

    /* Làm sáng chữ gợi ý (Placeholder) vừa đủ - Màu xám sáng #bbbbbb */
    .form-control::placeholder {
        color: #bbbbbb !important; 
        opacity: 1 !important; 
        font-weight: 300;
    }
    .form-control:-ms-input-placeholder { color: #bbbbbb !important; }
    .form-control::-moz-placeholder { color: #bbbbbb !important; opacity: 1; }

    /* Nút Đăng nhập */
    .btn-auth {
        background: #cda45e; 
        border: none; 
        color: #fff; 
        width: 100%;
        padding: 12px; 
        border-radius: 50px; 
        font-weight: 600; 
        transition: 0.3s;
    }

    .btn-auth:hover { 
        background: #d3af71; 
        transform: translateY(-2px); /* Hiệu ứng nổi nhẹ khi di chuột */
    }

    /* Các dòng chữ liên kết phía dưới */
    .auth-link { 
        text-align: center; 
        margin-top: 20px; 
    }

    .auth-link p {
        color: #dddddd !important; /* Màu xám trắng dịu mắt */
        font-size: 14px;
        margin-bottom: 10px;
    }

    .auth-link a { 
        color: #cda45e; 
        text-decoration: none; 
        font-weight: 500;
        transition: 0.3s;
    }

    .auth-link a:hover { 
        color: #fff; 
        text-decoration: underline; 
    }
</style>
</head>
<body>

  <div class="auth-bg"></div>

  <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="auth-container">
      <h2>Đăng Nhập</h2>
    <form action="../config/login_action.php" method="POST">
    <div class="mb-3">
        <input type="text" name="email" class="form-control" placeholder="Email của bạn" required>
    </div>
    <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
    </div>
    <button type="submit" class="btn-auth">Đăng nhập</button>
</form>
<div class="text-center mt-3">
    <p>Hoặc đăng nhập bằng</p>
    <a href="<?= htmlspecialchars($login_url) ?>" class="btn btn-outline-danger w-100 fw-bold">
        <i class="bi bi-google me-2"></i> Đăng nhập bằng Google
    </a>
</div>
      <div class="auth-link">
        <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        <p><a href="../index.php">← Quay lại trang chủ</a></p>
      </div>
    </div>
  </div>

</body>
</html>
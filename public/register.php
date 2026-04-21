<?php 
session_start(); 
// Gọi cấu hình Google để lấy link đăng ký nhanh
require_once '../config/google_setup.php';
$login_url = $client->createAuthUrl(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Đăng ký - Restaurantly</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  
  <style>
    body { 
        background: #0c0b09; 
        color: #fff; 
        font-family: 'Poppins', sans-serif; 
        margin: 0; 
        overflow-x: hidden;
    }

    .auth-bg {
        position: fixed; 
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                    url('assets/img/hero-bg.jpg') center center no-repeat;
        background-size: cover; 
        z-index: -1;
    }

    .auth-container {
        background: rgba(26, 24, 20, 0.9);
        padding: 40px; 
        border: 1px solid #37332a; 
        border-radius: 10px;
        width: 100%; 
        max-width: 450px; 
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }

    h2 { 
        color: #cda45e; 
        font-family: 'Playfair Display', serif; 
        text-align: center; 
        margin-bottom: 25px; 
        font-size: 32px;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid #37332a !important;
        color: #ffffff !important;
        padding: 12px;
        margin-bottom: 15px;
        transition: 0.3s;
    }

    .form-control:focus {
        border-color: #cda45e !important;
        box-shadow: none !important;
        background: rgba(255, 255, 255, 0.1) !important;
    }

    .form-control::placeholder {
        color: #bbbbbb !important;
        font-weight: 300;
    }

    .btn-auth {
        background: #cda45e; 
        border: none; 
        color: #fff; 
        width: 100%;
        padding: 12px; 
        border-radius: 50px; 
        font-weight: 600; 
        transition: 0.3s;
        margin-top: 10px;
    }

    .btn-auth:hover { 
        background: #d3af71; 
        transform: translateY(-2px);
    }

    .auth-link { 
        text-align: center; 
        margin-top: 20px; 
    }

    .auth-link p {
        color: #dddddd;
        font-size: 14px;
        margin-bottom: 8px;
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
      <h2>Đăng Ký</h2>

      <?php if(isset($_SESSION['error'])): ?>
          <div class="alert alert-danger p-2 text-center" style="background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ffcccc; font-size: 14px;">
              <i class="bi bi-exclamation-circle me-1"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
          </div>
      <?php endif; ?>

      <form action="../config/register_action.php" method="POST">
            <input type="text" name="fullname" class="form-control" placeholder="Họ và tên" required>
            <input type="email" name="email" class="form-control" placeholder="Email" required>
            <input type="password" name="password" class="form-control" placeholder="Mật khẩu (tối thiểu 6 ký tự)" required minlength="6">
            <input type="password" name="re-password" class="form-control" placeholder="Xác nhận mật khẩu" required minlength="6">
            <button type="submit" class="btn-auth shadow">TẠO TÀI KHOẢN</button>
      </form>

      <div class="text-center mt-3">
          <p class="mb-2" style="color: #bbbbbb; font-size: 14px;">Hoặc đăng ký nhanh bằng</p>
          <a href="<?= htmlspecialchars($login_url) ?>" class="btn btn-outline-danger w-100 fw-bold shadow-sm" style="border-radius: 50px; padding: 10px;">
              <i class="bi bi-google me-2"></i> Google
          </a>
      </div>
      
      <div class="auth-link border-top pt-3 mt-4" style="border-color: #37332a !important;">
        <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
        <p><a href="../index.php">← Quay lại trang chủ</a></p>
      </div>
    </div>
  </div>

</body>
</html>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Đăng ký - Restaurantly</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

    /* Khung chứa Form - Rộng hơn một chút cho trang đăng ký */
    .auth-container {
        background: rgba(26, 24, 20, 0.9);
        padding: 40px; 
        border: 1px solid #37332a; 
        border-radius: 10px;
        width: 100%; 
        max-width: 450px; 
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }

    /* Tiêu đề Đăng Ký */
    h2 { 
        color: #cda45e; 
        font-family: 'Playfair Display', serif; 
        text-align: center; 
        margin-bottom: 30px; 
        font-size: 32px;
    }

    /* Ô nhập liệu (Input) */
    .form-control {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid #37332a !important;
        color: #ffffff !important; /* Chữ gõ vào là trắng */
        padding: 12px;
        margin-bottom: 15px; /* Khoảng cách hẹp hơn một chút để vừa form dài */
        transition: 0.3s;
    }

    .form-control:focus {
        border-color: #cda45e !important;
        box-shadow: none !important;
        background: rgba(255, 255, 255, 0.1) !important;
    }

    /* Chữ gợi ý (Họ tên, Email, Mật khẩu...) sáng vừa đủ */
    .form-control::placeholder {
        color: #bbbbbb !important; /* Màu xám sáng trung tính */
        opacity: 1 !important;     /* Hiện rõ trên nền tối */
        font-weight: 300;
    }

    /* Nút Đăng Ký */
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

    /* Các dòng chữ liên kết phía dưới */
    .auth-link { 
        text-align: center; 
        margin-top: 20px; 
    }

    .auth-link p {
        color: #dddddd !important; /* Xám trắng dịu mắt, dễ đọc */
        font-size: 14px;
        margin-bottom: 8px;
    }

    .auth-link a {
        color: #cda45e !important; /* Vàng đặc trưng cho link */
        text-decoration: none;
        font-weight: 500;
        transition: 0.3s;
    }

    .auth-link a:hover {
        color: #fff !important;
        text-decoration: underline;
    }
</style>
</head>
<body>

  <div class="auth-bg"></div>

  <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="auth-container">
      <h2>Đăng Ký</h2>
      <form action="../config/register_action.php" method="POST">
            <input type="text" name="fullname" class="form-control" placeholder="Họ và tên" required>
            <input type="email" name="email" class="form-control" placeholder="Email" required>
            <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
            <input type="password" name="re-password" class="form-control" placeholder="Xác nhận mật khẩu" required>
            <button type="submit" class="btn-auth">TẠO TÀI KHOẢN</button>
        </form>
      <div class="auth-link">
        <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        <p><a href="../index.php">← Quay lại trang chủ</a></p>
      </div>
    </div>
  </div>

</body>
</html>
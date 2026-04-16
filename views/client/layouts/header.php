<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Restaurantly</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  
  <style>
    :root { --primary-color: #cda45e; }

    /* QUAN TRỌNG: Loại bỏ mọi khoảng trống để Banner tràn lên đỉnh */
    body { padding: 0 !important; margin: 0; }

    /* Topbar trong suốt ban đầu */
    #topbar {
      height: 40px;
      position: fixed; /* Cố định trôi nổi */
      top: 0;
      width: 100%;
      z-index: 998;
      display: flex;
      align-items: center;
      transition: all 0.5s;

      color: #ffffff;
    }

    /* Header TRONG SUỐT - Đây là chìa khóa để Banner hiện "khổng lồ" */
    #header {
      height: 90px;
      position: fixed; /* Cố định trôi nổi, không đẩy nội dung xuống */
      top: 40px;
      left: 0;
      right: 0;
      z-index: 997;
      background: rgba(0, 0, 0, 0.2);
      transition: all 0.5s;
      display: flex;
      align-items: center;
    }

    /* Khi cuộn xuống: Hiện dải đen mờ sang trọng (Ảnh 2) */
    #header.header-scrolled {
      top: 0 !important;
      height: 75px;
      background: rgba(21, 20, 15, 0.9);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      box-shadow: 0px 2px 15px rgba(0, 0, 0, 0.5);
    }

    #topbar.topbar-scrolled { top: -40px; }

    /* --- Nút bấm và Menu --- */
    .logo a { font-family: "Playfair Display", serif; font-size: 32px; font-weight: 700; color: #fff; text-decoration: none; }
    .navbar ul { display: flex; list-style: none; align-items: center; gap: 20px; margin: 0; }
    .navbar a {
      color: #fff;
      font-family: "Poppins", sans-serif;
      font-size: 13px;
      font-weight: 600;
      text-transform: uppercase;
      text-decoration: none;
      transition: 0.3s;
    }
    .navbar a:hover, .navbar a.active { color: var(--primary-color); }

    /* Cụm Chào User Bo Tròn */
    .user-dropdown-custom {
      display: none; 
      position: absolute;
      right: 15px;
      top: 100%;
      background: rgba(0, 0, 0, 0.9);
      border: 1px solid var(--primary-color);
      border-radius: 50px;
      padding: 8px 25px;
      gap: 15px;
      align-items: center;
      z-index: 1000;
    }
    .user-dropdown-custom.active { display: flex; }
    .user-dropdown-custom li { list-style: none; }
    .user-dropdown-custom li a { color: #fff; font-size: 13px; text-decoration: none; white-space: nowrap; }

    /* Nút 3 gạch & Đặt bàn */
    .mobile-nav-toggle { color: #fff; font-size: 28px; cursor: pointer; margin-left: 15px; }
    .book-a-table-btn {
      background: transparent; color: #fff; padding: 10px 25px; border-radius: 50px;
      border: 2px solid var(--primary-color); text-decoration: none; font-size: 13px;
      font-weight: 600; text-transform: uppercase; transition: 0.3s;
    }
    .book-a-table-btn:hover { background: var(--primary-color); color: #000; }
  </style>
</head>

<body>
  <div id="topbar">
    <div class="container d-flex justify-content-center justify-content-md-between">
      <div class="contact-info d-flex align-items-center">
        <i class="bi bi-phone me-1" style="color: var(--primary-color);"></i><span>0123 456 789</span>
        <i class="bi bi-clock ms-4 me-1" style="color: var(--primary-color);"></i><span> 11:00 AM - 23:00 PM</span>
      </div>
    </div>
  </div>

  <header id="header">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo"><a href="index.php">Restaurantly</a></h1>

      <nav id="navbar" class="navbar order-last order-lg-0">
        <ul>
          <li><a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">Trang chủ</a></li>
          <li><a class="nav-link" href="#about">Về chúng tôi</a></li>
          <li><a class="nav-link <?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>" href="menu.php">Thực đơn</a></li>
          <li><a class="nav-link" href="#contact">Liên hệ</a></li>
        </ul>
      </nav>

      <div class="header-right d-flex align-items-center">
        <i class="bi bi-list mobile-nav-toggle" id="user-menu-btn"></i>

        <ul class="user-dropdown-custom shadow" id="user-content">
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="#" style="font-weight: 700; color: var(--primary-color);">Chào, <?= htmlspecialchars($_SESSION['user_name']) ?></a></li>
                <li style="color: rgba(255,255,255,0.2);">|</li>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 1): ?>
                  <li><a href="admin/admin_dashboard.php">Quản lý</a></li>
                  <li style="color: rgba(255,255,255,0.2);">|</li>
                <?php endif; ?>
                <li><a href="public/logout.php" style="color: #ff4d4d;">Đăng xuất</a></li>
            <?php else: ?>
                <li><a href="public/login.php">Đăng nhập</a></li>
                <li style="color: rgba(255,255,255,0.2);">|</li>
                <li><a href="public/register.php">Đăng ký</a></li>
            <?php endif; ?>
        </ul>
        
        <a href="booking_service.php?type=table" class="book-a-table-btn ms-3">ĐẶT BÀN</a>
      </div>
    </div>
  </header>

  <script>
    // Xử lý cuộn trang
    window.addEventListener('scroll', function() {
      const header = document.querySelector('#header');
      const topbar = document.querySelector('#topbar');
      if (window.scrollY > 50) {
        header.classList.add('header-scrolled');
        topbar.classList.add('topbar-scrolled');
      } else {
        header.classList.remove('header-scrolled');
        topbar.classList.remove('topbar-scrolled');
      }
    });

    // Xử lý nút 3 gạch
    document.getElementById('user-menu-btn').onclick = function(e) {
      e.stopPropagation();
      document.getElementById('user-content').classList.toggle('active');
    };
    window.onclick = function() {
      document.getElementById('user-content').classList.remove('active');
    };
  </script>
</body>
</html>
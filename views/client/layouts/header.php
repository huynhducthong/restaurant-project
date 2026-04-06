<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
  <link rel="stylesheet" href="public/assets/client/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">

      <h1 class="logo">
        <a href="index.php">Restaurantly</a>
      </h1>

      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="index.php">TRANG CHỦ</a></li>
          <li><a class="nav-link scrollto" href="index.php#about">GIỚI THIỆU</a></li>
          <li><a class="nav-link" href="menu.php">THỰC ĐƠN</a></li>
          <li><a class="nav-link" href="services.php">DỊCH VỤ</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>

      <div class="header-right d-flex align-items-center">
        <div class="auth-box d-flex align-items-center me-3">
          <div class="dropdown auth-menu">
            <a href="javascript:void(0)" class="menu-toggle-btn me-3">
              <i class="fas fa-bars" style="color: #cda45e; font-size: 20px;"></i>
            </a>
            <ul class="dropdown-horizontal shadow">
              <?php if(isset($_SESSION['user_id'])): ?>
                  <li><a href="javascript:void(0)">Chào, <?= $_SESSION['user_name'] ?></a></li>
                  
                  <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 1): ?>
                    <li><a href="admin/admin_dashboard.php">Quản lý</a></li>
                  <?php endif; ?>
                  
                  <li><a href="public/logout.php" style="color: #ff4d4d;">Đăng xuất</a></li>
              <?php else: ?>
                <li><a href="public/login.php">Đăng nhập</a></li>
                <li class="separator">|</li>
                <li><a href="public/register.php">Đăng ký</a></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        
        <a href="booking_service.php?type=table" class="book-a-table-btn scrollto">ĐẶT BÀN</a>
      </div>

    </div>
  </header>

  <style>
    .dropdown-horizontal {
      display: flex;
      list-style: none;
      padding: 10px 20px;
      margin: 0;
      background: rgba(0, 0, 0, 0.9);
      border: 1px solid #37332a;
      align-items: center;
      gap: 15px;
    }
    .dropdown-horizontal li a {
      color: #fff;
      font-size: 14px;
      text-decoration: none;
      transition: 0.3s;
    }
    .dropdown-horizontal li a:hover {
      color: #cda45e;
    }
    .separator {
      color: #37332a;
    }
  </style>
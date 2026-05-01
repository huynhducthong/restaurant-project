<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kết nối Database
require_once __DIR__ . '/../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

/** 1. LẤY CẤU HÌNH DÙNG CHUNG **/
$settings = [];
try {
    $stmt = $db->prepare("SELECT key_name, key_value FROM settings");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (Exception $e) {}

// Thiết lập mặc định nếu Database trống
$settings['restaurant_name'] = $settings['restaurant_name'] ?? 'Restaurantly'; 
$settings['hotline']         = $settings['hotline'] ?? '0123 456 789';
$settings['open_days']       = $settings['open_days'] ?? 'Thứ 2 - Chủ Nhật';
$settings['open_time']       = $settings['open_time'] ?? '11:00 AM - 23:00 PM';
$settings['logo_position']   = $settings['name_position'] ?? 'left'; // Đọc đúng key name_position từ settings

$current_page = basename($_SERVER['PHP_SELF']); 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= htmlspecialchars($settings['restaurant_name']) ?></title>
  
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  
  <style>
    :root { --primary-color: #cda45e; }
    body { padding: 0 !important; margin: 0; }

    /* Topbar & Header Scrolled Logic */
    #topbar { height: 40px; position: fixed; top: 0; width: 100%; z-index: 998; display: flex; align-items: center; transition: all 0.5s; color: #fff; }
    #topbar.topbar-scrolled { top: -40px; }
    #header { height: 90px; position: fixed; top: 40px; left: 0; right: 0; z-index: 997; background: rgba(0, 0, 0, 0.2); transition: all 0.5s; display: flex; align-items: center; }
    #header.header-scrolled { top: 0 !important; height: 75px; background: rgba(21, 20, 15, 0.9); backdrop-filter: blur(10px); }

    /* --- Cụm Logo Đảo Vị Trí --- */
    .logo a { text-decoration: none; display: flex; align-items: center; gap: 10px; }
    /* Nếu vị trí là 'right', ta dùng flex-direction: row-reverse */
    .logo-container-right { flex-direction: row-reverse !important; }
    
    .logo img { max-height: 40px; }
    .logo span { font-family: "Playfair Display", serif; font-size: 28px; font-weight: 700; color: #fff; white-space: nowrap; }

    /* Menu Cố Định */
    .navbar ul { display: flex; list-style: none; align-items: center; gap: 20px; margin: 0; }
    .navbar a { color: #fff; font-family: "Poppins", sans-serif; font-size: 13px; font-weight: 600; text-transform: uppercase; text-decoration: none; transition: 0.3s; }
    .navbar a:hover, .navbar a.active { color: var(--primary-color); }

    /* Tài khoản & Nút bấm */
    .user-dropdown-custom { display: none; position: absolute; right: 15px; top: 100%; background: rgba(0, 0, 0, 0.9); border: 1px solid var(--primary-color); border-radius: 50px; padding: 8px 25px; gap: 15px; align-items: center; z-index: 1000; }
    .user-dropdown-custom.active { display: flex; }
    .book-a-table-btn { background: transparent; color: #fff; padding: 10px 25px; border-radius: 50px; border: 2px solid var(--primary-color); text-decoration: none; font-size: 13px; font-weight: 600; text-transform: uppercase; }
    .book-a-table-btn:hover { background: var(--primary-color); color: #000; }
  </style>
</head>

<body>
  <div id="topbar">
    <div class="container d-flex justify-content-center justify-content-md-between">
      <div class="contact-info d-flex align-items-center flex-wrap">
        <i class="bi bi-phone me-1" style="color: var(--primary-color);"></i>
        <span><?= htmlspecialchars($settings['hotline']) ?></span>
        <i class="bi bi-clock ms-4 me-1" style="color: var(--primary-color);"></i>
        <span><?= htmlspecialchars($settings['open_days']) ?>: <strong><?= htmlspecialchars($settings['open_time']) ?></strong></span>
        <?php if (!empty($settings['address'])): ?>
        <i class="bi bi-geo-alt ms-4 me-1" style="color: var(--primary-color);"></i>
        <span><?= htmlspecialchars($settings['address']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <header id="header">
    <div class="container d-flex align-items-center justify-content-between">
      
      <h1 class="logo">
        <a href="index.php" class="<?= ($settings['logo_position'] == 'right') ? 'logo-container-right' : '' ?>">
          <?php if (!empty($settings['logo_url'])): ?>
            <img src="<?= htmlspecialchars($settings['logo_url']) ?>?v=<?= htmlspecialchars($settings['logo_ver'] ?? '1') ?>" alt="Logo">
          <?php endif; ?>
          <span><?= htmlspecialchars($settings['restaurant_name']) ?></span>
        </a>
      </h1>

      <nav id="navbar" class="navbar order-last order-lg-0">
        <ul>
          <li><a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="index.php">Trang chủ</a></li>
          <li><a class="nav-link" href="#about">Về chúng tôi</a></li>
          <li><a class="nav-link <?= ($current_page == 'menu.php') ? 'active' : '' ?>" href="menu.php">Thực đơn</a></li>
          <li><a class="nav-link" href="#contact">Liên hệ</a></li>
        </ul>
      </nav>

      <div class="header-right d-flex align-items-center position-relative">
        <i class="bi bi-list mobile-nav-toggle" id="user-menu-btn" style="color:#fff; cursor:pointer; font-size:24px;"></i>
        <ul class="user-dropdown-custom shadow" id="user-content">
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><span style="font-weight:700; color:var(--primary-color); font-size:12px;">Chào, <?= htmlspecialchars($_SESSION['user_name']) ?></span></li>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 1): ?>
                  <li><a href="admin/admin_dashboard.php">Quản lý</a></li>
                <?php endif; ?>
                <li><a href="public/logout.php" style="color:#ff4d4d;">Thoát</a></li>
            <?php else: ?>
                <li><a href="public/login.php">Đăng nhập</a></li>
                <li><a href="public/register.php">Đăng ký</a></li>
            <?php endif; ?>
        </ul>
        <a href="booking_service.php?type=table" class="book-a-table-btn ms-3">ĐẶT BÀN</a>
      </div>
    </div>
  </header>

  <script>
    window.addEventListener('scroll', function() {
      const header = document.querySelector('#header');
      const topbar = document.querySelector('#topbar');
      if (window.scrollY > 50) { header.classList.add('header-scrolled'); topbar.classList.add('topbar-scrolled'); } 
      else { header.classList.remove('header-scrolled'); topbar.classList.remove('topbar-scrolled'); }
    });

    document.getElementById('user-menu-btn').onclick = function(e) {
      e.stopPropagation();
      document.getElementById('user-content').classList.toggle('active');
    };
    window.onclick = function() { document.getElementById('user-content').classList.remove('active'); };
  </script>
</body>
</html>
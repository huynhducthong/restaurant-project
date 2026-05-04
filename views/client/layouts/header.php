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

// 2. Kiểm tra quyền để quyết định xem có hiển thị nút "Vào trang Quản trị" không
$user_role = $_SESSION['role'] ?? '';
$is_backend_access = in_array($user_role, ['admin', 'staff', 1, 2]);
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
    .header-actions { display: flex; align-items: center; gap: 15px; }
    .auth-btn {
      font-family: "Open Sans", sans-serif;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      padding: 8px 18px;
      border-radius: 50px;
      transition: 0.3s;
      text-decoration: none;
    }
    .login-btn {
      color: #fff;
      background: transparent;
    }
    .login-btn:hover {
      color: #cda45e;
    }
    .register-btn {
      color: #fff;
      border: 2px solid #cda45e;
      background: rgba(205, 164, 94, 0.1);
    }
    .register-btn:hover {
      background: #cda45e;
      color: #1a1814;
    }
    .book-a-table-btn { background: transparent; color: #fff; padding: 10px 25px; border-radius: 50px; border: 2px solid var(--primary-color); text-decoration: none; font-size: 13px; font-weight: 600; text-transform: uppercase; }
    .book-a-table-btn:hover { background: var(--primary-color); color: #000; }
    
    /* Canh chỉnh icon trong dropdown */
    .dropdown-item i { width: 22px; text-align: center; }
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
          <li><a class="nav-link scrollto active" href="index.php">Trang chủ</a></li>
          <li><a class="nav-link scrollto" href="about.php">Về chúng tôi</a></li>
          <li><a class="nav-link scrollto" href="menu.php">Thực đơn</a></li>
          <li><a class="nav-link scrollto" href="contact.php">Liên hệ</a></li>
        </ul>
      </nav>

      <div class="d-flex align-items-center">
        <i class="bi bi-list mobile-nav-toggle" style="color:#fff; cursor:pointer; font-size:24px;"></i>
        <div class="header-actions ms-3">
          <a href="booking_service.php?type=table" class="book-a-table-btn">ĐẶT BÀN</a>
          
          <?php if(isset($_SESSION['user_id'])): ?>
            <div class="dropdown">
              <a href="#" class="auth-btn register-btn dropdown-toggle text-white d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" style="gap: 8px;">
                <i class="bi bi-person-circle fs-5"></i>
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-dark shadow" style="background: #1a1814; border: 1px solid #cda45e; border-radius: 8px;">
                
                <?php if($is_backend_access): ?>
                    <li><a class="dropdown-item fw-bold" style="color: #cda45e;" href="admin/admin_dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Vào trang Quản trị
                    </a></li>
                    <li><hr class="dropdown-divider" style="border-color: rgba(205,164,94,0.3);"></li>
                <?php endif; ?>

                <li><a class="dropdown-item text-white hover-gold" href="profile.php"><i class="bi bi-person me-2" style="color: #cda45e;"></i>Thông tin cá nhân</a></li>
                <li><a class="dropdown-item text-white hover-gold" href="my_orders.php"><i class="bi bi-receipt me-2" style="color: #cda45e;"></i>Lịch sử đặt bàn</a></li>
                <li><hr class="dropdown-divider" style="border-color: rgba(205,164,94,0.3);"></li>
                <li><a class="dropdown-item text-danger fw-bold" href="public/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
              </ul>
            </div>
          <?php else: ?>
            <div class="d-flex align-items-center gap-2">
              <a href="public/login.php" class="auth-btn login-btn">Đăng nhập</a>
              <a href="public/register.php" class="auth-btn register-btn">Đăng ký</a>
            </div>
          <?php endif; ?>
        </div>
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
  </script>
</body>
</html>
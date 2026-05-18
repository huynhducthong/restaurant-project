<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kết nối Database
require_once __DIR__ . '/../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

/** LẤY CẤU HÌNH DÙNG CHUNG **/
$settings = [];
try {
    $stmt = $db->prepare("SELECT key_name, key_value FROM settings");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (Exception $e) {
}

// Thiết lập mặc định nếu Database trống
$settings['restaurant_name'] = $settings['restaurant_name'] ?? 'Restaurantly';
$settings['hotline']         = $settings['hotline']         ?? '0123 456 789';
$settings['open_days']       = $settings['open_days']       ?? 'Thứ 2 - Chủ Nhật';
$settings['open_time']       = $settings['open_time']       ?? '11:00 AM - 23:00 PM';
$settings['logo_position']   = $settings['name_position']   ?? 'left';
$settings['logo_ver']        = $settings['logo_ver']        ?? '1';

$current_page = basename($_SERVER['PHP_SELF']);

// 2. Kiểm tra quyền hiển thị nút "Vào trang Quản trị"
$user_role        = $_SESSION['role'] ?? '';
$is_backend_access = in_array($user_role, ['admin', 'staff', 1, 2]);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['restaurant_name']) ?></title>

    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-color: #cda45e;
        }

        body {
            margin: 0;
            padding: 0 !important;
            font-family: "Poppins", sans-serif;
        }

        /* =======================
           TOPBAR
        ======================= */

        #topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 40px;
            z-index: 999;
            display: flex;
            align-items: center;
            color: #fff;
            background: transparent;
            transition: all 0.5s;
        }

        #topbar.topbar-scrolled {
            top: -40px;
        }

        /* =======================
           HEADER
        ======================= */

        #header {
            position: fixed;
            top: 40px;
            left: 0;
            right: 0;
            height: 90px;
            z-index: 998;
            background: rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            transition: all 0.5s;
        }

        #header.header-scrolled {
            top: 0;
            height: 75px;
            background: rgba(21, 20, 15, 0.92);
            backdrop-filter: blur(10px);
        }

        /* =======================
           LOGO
        ======================= */

        .logo a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-container-right {
            flex-direction: row-reverse;
        }

        .logo img {
            max-height: 42px;
        }

        .logo span {
            font-family: "Playfair Display", serif;
            font-size: 30px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
        }

        /* =======================
           NAVBAR
        ======================= */

        .navbar ul {
            display: flex;
            align-items: center;
            gap: 22px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .navbar a {
            text-decoration: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            transition: 0.3s;
        }

        .navbar a:hover,
        .navbar a.active {
            color: var(--primary-color);
        }

        @media (max-width: 991px) {
            .navbar {
                display: none;
            }
        }

        /* =======================
           HEADER ACTIONS
        ======================= */

        .header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .auth-btn {
            padding: 8px 18px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            transition: 0.3s;
        }

        .login-btn {
            color: #fff;
        }

        .login-btn:hover {
            color: var(--primary-color);
        }

        .register-btn {
            border: 2px solid var(--primary-color);
            color: #fff;
            background: rgba(205, 164, 94, 0.12);
        }

        .register-btn:hover {
            background: var(--primary-color);
            color: #000;
        }

        .book-a-table-btn {
            padding: 10px 24px;
            border-radius: 50px;
            border: 2px solid var(--primary-color);
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            transition: 0.3s;
        }

        .book-a-table-btn:hover {
            background: var(--primary-color);
            color: #000;
        }

        /* =======================
           DROPDOWN (USER MENU)
        ======================= */

        .dropdown-item i {
            width: 22px;
            text-align: center;
        }

        /* =======================
           ORIENTAL MEGA MENU
           (hiển thị trên mobile)
        ======================= */

        .oriental-nav-wrapper {
            position: relative;
            display: none; /* ẩn trên desktop */
        }

        @media (max-width: 991px) {
            .oriental-nav-wrapper {
                display: block;
            }
        }

        .oriental-trigger {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s;
        }

        .oriental-trigger:hover {
            background: var(--primary-color);
            color: #1a1814;
        }

        .oriental-trigger i {
            font-size: 22px;
        }

        .oriental-panel {
            position: absolute;
            top: 48px;
            right: 0;
            width: 240px;
            background: rgba(21, 20, 15, 0.97);
            border: 1px solid rgba(205, 164, 94, 0.3);
            border-radius: 8px;
            padding: 10px 0;
            z-index: 10001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .oriental-nav-wrapper:hover .oriental-panel,
        .oriental-panel.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .oriental-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            text-decoration: none;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(205, 164, 94, 0.1);
            transition: all 0.3s;
        }

        .oriental-item:last-child {
            border-bottom: none;
        }

        .oriental-item:hover {
            color: var(--primary-color);
            background: rgba(205, 164, 94, 0.08);
            padding-left: 26px;
        }

        .oriental-item i {
            color: var(--primary-color);
            font-size: 16px;
            width: 18px;
            text-align: center;
        }

        .oriental-item.text-danger-custom {
            color: #ff5e5e !important;
        }

        .oriental-item.text-danger-custom i {
            color: #ff5e5e !important;
        }

        .oriental-item.text-danger-custom:hover {
            background: rgba(255, 94, 94, 0.1);
        }
    </style>
</head>

<body>

    <!-- TOPBAR -->
    <div id="topbar">
        <div class="container d-flex justify-content-center justify-content-md-between">
            <div class="contact-info d-flex align-items-center flex-wrap">
                <i class="bi bi-phone me-1" style="color: var(--primary-color);"></i>
                <span><?= htmlspecialchars($settings['hotline']) ?></span>

                <i class="bi bi-clock ms-4 me-1" style="color: var(--primary-color);"></i>
                <span>
                    <?= htmlspecialchars($settings['open_days']) ?>:
                    <strong><?= htmlspecialchars($settings['open_time']) ?></strong>
                </span>

                <?php if (!empty($settings['address'])): ?>
                    <i class="bi bi-geo-alt ms-4 me-1" style="color: var(--primary-color);"></i>
                    <span><?= htmlspecialchars($settings['address']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- HEADER -->
    <header id="header">
        <div class="container d-flex align-items-center justify-content-between">

            <!-- LOGO -->
            <h1 class="logo">
                <a href="index.php" class="<?= ($settings['logo_position'] === 'right') ? 'logo-container-right' : '' ?>">
                    <?php if (!empty($settings['logo_url'])): ?>
                        <img src="<?= htmlspecialchars($settings['logo_url']) ?>?v=<?= htmlspecialchars($settings['logo_ver']) ?>" alt="Logo">
                    <?php endif; ?>
                    <span><?= htmlspecialchars($settings['restaurant_name']) ?></span>
                </a>
            </h1>

            <!-- NAVBAR (desktop) -->
            <nav id="navbar" class="navbar order-last order-lg-0">
                <ul>
                    <li><a class="<?= ($current_page === 'index.php')   ? 'active' : '' ?>" href="index.php">Trang chủ</a></li>
                    <li><a class="<?= ($current_page === 'Aboutus.php') ? 'active' : '' ?>" href="Aboutus.php">Về chúng tôi</a></li>
                    <li><a class="<?= ($current_page === 'menu.php')    ? 'active' : '' ?>" href="menu.php">Thực đơn</a></li>
                    <li><a class="<?= ($current_page === 'chefs.php')   ? 'active' : '' ?>" href="views/client/chefs.php">Đội bếp</a></li>
                    <li><a class="<?= ($current_page === 'contact.php') ? 'active' : '' ?>" href="contact.php">Liên hệ</a></li>
                </ul>
            </nav>

            <!-- ACTIONS -->
            <div class="d-flex align-items-center">
                <div class="header-actions ms-3">

                    <!-- Nút đặt bàn (desktop) -->
                    <a href="booking_service.php?type=table" class="book-a-table-btn d-none d-lg-inline-block">ĐẶT BÀN</a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Dropdown tài khoản (desktop) -->
                        <div class="dropdown d-none d-lg-block">
                            <a href="#" class="auth-btn register-btn dropdown-toggle text-white d-flex align-items-center"
                               data-bs-toggle="dropdown" aria-expanded="false" style="gap: 8px;">
                                <i class="bi bi-person-circle fs-5"></i>
                                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark shadow"
                                style="background: #1a1814; border: 1px solid #cda45e; border-radius: 8px;">

                                <?php if ($is_backend_access): ?>
                                    <li>
                                        <a class="dropdown-item fw-bold" style="color: #cda45e;" href="admin/admin_dashboard.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Vào trang Quản trị
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider" style="border-color: rgba(205,164,94,0.3);"></li>
                                <?php endif; ?>

                                <li>
                                    <a class="dropdown-item text-white" href="profile.php">
                                        <i class="bi bi-person me-2" style="color: #cda45e;"></i>Thông tin cá nhân
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-white" href="my_orders.php">
                                        <i class="bi bi-receipt me-2" style="color: #cda45e;"></i>Lịch sử đặt bàn
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider" style="border-color: rgba(205,164,94,0.3);"></li>
                                <li>
                                    <a class="dropdown-item text-danger fw-bold" href="public/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </div>

                    <?php else: ?>
                        <!-- Đăng nhập / Đăng ký (desktop) -->
                        <div class="d-none d-lg-flex align-items-center gap-2">
                            <a href="public/login.php"    class="auth-btn login-btn">Đăng nhập</a>
                            <a href="public/register.php" class="auth-btn register-btn">Đăng ký</a>
                        </div>
                    <?php endif; ?>

                    <!-- ORIENTAL MEGA MENU (mobile) -->
                    <div class="oriental-nav-wrapper">
                        <button class="oriental-trigger" id="orientalTrigger" aria-label="Mở menu">
                            <i class="bi bi-list"></i>
                        </button>

                        <div class="oriental-panel" id="orientalPanel">
                            <a href="index.php"                        class="oriental-item <?= ($current_page === 'index.php')   ? 'active' : '' ?>"><i class="bi bi-house-door"></i> Trang chủ</a>
                            <a href="Aboutus.php"                      class="oriental-item <?= ($current_page === 'Aboutus.php') ? 'active' : '' ?>"><i class="bi bi-info-circle"></i> Về chúng tôi</a>
                            <a href="menu.php"                         class="oriental-item <?= ($current_page === 'menu.php')    ? 'active' : '' ?>"><i class="bi bi-card-list"></i> Thực đơn</a>
                            <a href="views/client/chefs.php"           class="oriental-item <?= ($current_page === 'chefs.php')   ? 'active' : '' ?>"><i class="bi bi-people"></i> Đội bếp</a>
                            <a href="contact.php"                      class="oriental-item <?= ($current_page === 'contact.php') ? 'active' : '' ?>"><i class="bi bi-envelope"></i> Liên hệ</a>
                            <a href="booking_service.php?type=table"   class="oriental-item"><i class="bi bi-calendar-check"></i> Đặt bàn</a>

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- TRANG QUẢN TRỊ / BẢNG CÔNG -->
                                <?php if ($user_role === 'admin' || $user_role == 1): ?>
                                    <a href="admin/admin_dashboard.php" class="oriental-item" style="color: var(--primary-color);">
                                        <i class="bi bi-speedometer2"></i> Trang quản trị
                                    </a>
                                <?php elseif ($user_role !== 'admin' && $user_role !== 'customer' && !empty($user_role)): ?>
                                    <a href="views/client/employee_dashboard.php" class="oriental-item" style="color: var(--primary-color);">
                                        <i class="bi bi-clock-history"></i> Bảng công & Chấm công
                                    </a>
                                <?php endif; ?>
                                <a href="profile.php"       class="oriental-item"><i class="bi bi-person"></i> Thông tin cá nhân</a>
                                <a href="my_orders.php"     class="oriental-item"><i class="bi bi-receipt"></i> Lịch sử đặt bàn</a>
                                <a href="public/logout.php" class="oriental-item text-danger-custom fw-bold"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
                            <?php else: ?>
                                <a href="public/register.php" class="oriental-item"><i class="bi bi-person-plus"></i> Đăng ký</a>
                                <a href="public/login.php"    class="oriental-item"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </header>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Scroll effect: thu topbar & đổi màu header
        window.addEventListener('scroll', function () {
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

        // Oriental menu toggle (mobile tap)
        const trigger = document.getElementById('orientalTrigger');
        const panel   = document.getElementById('orientalPanel');
        if (trigger && panel) {
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                panel.classList.toggle('open');
            });
            document.addEventListener('click', function () {
                panel.classList.remove('open');
            });
        }
    </script>

</body>
</html>
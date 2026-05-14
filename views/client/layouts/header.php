<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

/* =========================================================
   LẤY CẤU HÌNH WEBSITE
========================================================= */

$settings = [];

try {

    $stmt = $db->prepare("
        SELECT key_name, key_value
        FROM settings
    ");

    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key_name']] = $row['key_value'];
    }

} catch (Exception $e) {
}

/* =========================================================
   DEFAULT SETTINGS
========================================================= */

$settings['restaurant_name'] = $settings['restaurant_name'] ?? 'Restaurantly';
$settings['hotline']         = $settings['hotline'] ?? '0123 456 789';
$settings['open_days']       = $settings['open_days'] ?? 'Thứ 2 - Chủ Nhật';
$settings['open_time']       = $settings['open_time'] ?? '11:00 - 23:00';
$settings['address']         = $settings['address'] ?? '';
$settings['logo_url']        = $settings['logo_url'] ?? '';
$settings['logo_ver']        = $settings['logo_ver'] ?? '1';
$settings['logo_position']   = $settings['name_position'] ?? 'left';

/* =========================================================
   PAGE
========================================================= */

$current_page = basename($_SERVER['PHP_SELF']);

/* =========================================================
   ROLE
========================================================= */

$user_role = $_SESSION['role'] ?? '';

$is_backend_access = in_array(
    $user_role,
    ['admin', 'staff', 1, 2]
);

/* =========================================================
   FIX ĐƯỜNG DẪN LOGO
========================================================= */

$is_admin_area = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);

$logo_path = $settings['logo_url'] ?? '';
$final_logo_src = '';

if (!empty($logo_path)) {

    if ($is_admin_area) {

        $final_logo_src = '../../public/' . ltrim($logo_path, '/');

    } else {

        $final_logo_src = 'public/' . ltrim($logo_path, '/');
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($settings['restaurant_name']) ?>
    </title>

    <!-- GOOGLE FONT -->
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- BOOTSTRAP -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- BOOTSTRAP ICON -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

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
            font-size: 30px;
            font-weight: 700;

            color: #fff;

            font-family: "Playfair Display", serif;

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

        /* =======================
           BUTTON
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

        /* Khách: đăng ký / đăng nhập xếp dọc, nút vuông */

        .guest-auth-stack {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .guest-auth-stack .auth-btn {
            border-radius: 4px;
            text-align: center;
            border: none;
            padding: 8px 16px;
        }

        .guest-register-btn {
            background: #e6b422;
            color: #fff;
        }

        .guest-register-btn:hover {
            background: #d4a41a;
            color: #fff;
        }

        .guest-login-btn {
            background: #1a1814;
            color: #fff;
        }

        .guest-login-btn:hover {
            background: #2a2620;
            color: #fff;
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
           DROPDOWN
        ======================= */

        /* =======================
           ORIENTAL MEGA MENU
        ======================= */
        .oriental-nav-wrapper {
            position: relative;
            margin-left: 15px;
        }

        .oriental-trigger {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
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
            top: 55px;
            right: 0;
            width: 320px;
            min-height: 420px;
            background-image: url('public/assets/img/oriental-frame.png');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            padding: 60px 40px;
            z-index: 10001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            display: flex;
            flex-direction: column;
            gap: 5px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }

        .oriental-nav-wrapper:hover .oriental-panel,
        .oriental-nav-wrapper:active .oriental-panel {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .oriental-item {
            text-decoration: none;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            padding: 12px 15px;
            border-bottom: 1px solid rgba(205, 164, 94, 0.15);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .oriental-item:last-child {
            border-bottom: none;
        }

        .oriental-item:hover {
            color: var(--primary-color);
            padding-left: 20px;
            background: rgba(205, 164, 94, 0.08);
        }

        .oriental-item i {
            color: var(--primary-color);
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .oriental-item.text-danger-custom {
            color: #ff5e5e !important;
        }
        
        .oriental-item.text-danger-custom:hover {
            background: rgba(255, 94, 94, 0.1);
        }

        @media (max-width: 991px) {
            .navbar {
                display: none;
            }

            .mobile-nav-toggle {
                display: block;
            }
        }

    </style>

</head>

<body>

    <!-- TOPBAR -->

    <div id="topbar">

        <div class="container d-flex justify-content-between">

            <div class="contact-info d-flex align-items-center flex-wrap">

                <i class="bi bi-phone me-1"
                    style="color: var(--primary-color);"></i>

                <span>
                    <?= htmlspecialchars($settings['hotline']) ?>
                </span>

                <i class="bi bi-clock ms-4 me-1"
                    style="color: var(--primary-color);"></i>

                <span>
                    <?= htmlspecialchars($settings['open_days']) ?>
                    :
                    <strong>
                        <?= htmlspecialchars($settings['open_time']) ?>
                    </strong>
                </span>

                <?php if (!empty($settings['address'])): ?>

                    <i class="bi bi-geo-alt ms-4 me-1"
                        style="color: var(--primary-color);"></i>

                    <span>
                        <?= htmlspecialchars($settings['address']) ?>
                    </span>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- HEADER -->

    <header id="header">

        <div class="container d-flex align-items-center justify-content-between">

            <!-- LOGO -->

            <h1 class="logo">

                <a href="index.php"
                    class="<?= ($settings['logo_position'] == 'right') ? 'logo-container-right' : '' ?>">

                    <?php if (!empty($final_logo_src)): ?>

                        <img
                            src="<?= htmlspecialchars($final_logo_src) ?>?v=<?= htmlspecialchars($settings['logo_ver']) ?>"
                            alt="Logo">

                    <?php endif; ?>

                    <span>
                        <?= htmlspecialchars($settings['restaurant_name']) ?>
                    </span>

                </a>

            </h1>

            <!-- NAVBAR -->

            <nav id="navbar"
                class="navbar order-last order-lg-0">

                <ul>

                    <li>
                        <a class="<?= ($current_page == 'index.php') ? 'active' : '' ?>"
                            href="index.php">
                            Trang chủ
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'Aboutus.php') ? 'active' : '' ?>"
                            href="Aboutus.php">
                            Về chúng tôi
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'menu.php') ? 'active' : '' ?>"
                            href="menu.php">
                            Thực đơn
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'contact.php') ? 'active' : '' ?>"
                            href="contact.php">
                            Liên hệ
                        </a>
                    </li>

                </ul>

            </nav>

            <!-- ACTION -->

            <div class="d-flex align-items-center">


                <div class="header-actions ms-3">

                    <!-- ORIENTAL MEGA MENU -->
                    <div class="oriental-nav-wrapper">
                        <div class="oriental-trigger">
                            <i class="bi bi-list"></i>
                        </div>
                        
                        <div class="oriental-panel">
                            <!-- ĐẶT BÀN (LUÔN CÓ) -->
                            <a href="booking_service.php?type=table" class="oriental-item">
                                <i class="bi bi-calendar-check"></i> Đặt bàn
                            </a>

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

                                <!-- THÔNG TIN CÁ NHÂN -->
                                <a href="profile.php" class="oriental-item">
                                    <i class="bi bi-person"></i> Thông tin cá nhân
                                </a>

                                <!-- ĐĂNG XUẤT -->
                                <a href="public/logout.php" class="oriental-item text-danger-custom fw-bold">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a>
                            <?php else: ?>
                                <!-- ĐĂNG KÝ -->
                                <a href="public/register.php" class="oriental-item">
                                    <i class="bi bi-person-plus"></i> Đăng ký
                                </a>

                                <!-- ĐĂNG NHẬP -->
                                <a href="public/login.php" class="oriental-item">
                                    <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </header>

    <!-- BOOTSTRAP JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SCROLL EFFECT -->

    <script>

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

    </script>

</body>

</html>
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

            background: rgba(0, 0, 0, 0.4);

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

        .dropdown-menu-dark {

            background: #1a1814;

            border: 1px solid #cda45e;

            border-radius: 8px;
        }

        .dropdown-menu-dark .dropdown-item {
            color: #fff;
        }

        .dropdown-menu-dark .dropdown-item:hover {

            background: rgba(205, 164, 94, 0.15);

            color: #cda45e;
        }

        .dropdown-item i {
            width: 22px;
            text-align: center;
        }

        .mobile-nav-toggle {

            color: #fff;

            cursor: pointer;

            font-size: 24px;

            display: none;
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
                        <a class="<?= ($current_page == 'about.php') ? 'active' : '' ?>"
                            href="about.php">
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
                        <a class="<?= ($current_page == 'chefs.php') ? 'active' : '' ?>"
                            href="views/client/chefs.php">
                            Đội bếp
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

                <i class="bi bi-list mobile-nav-toggle"></i>

                <div class="header-actions ms-3">

                    <a href="booking_service.php?type=table"
                        class="book-a-table-btn">
                        ĐẶT BÀN
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <div class="dropdown">

                            <a href="#"
                                class="auth-btn register-btn dropdown-toggle text-white d-flex align-items-center"
                                data-bs-toggle="dropdown"
                                style="gap:8px;">

                                <i class="bi bi-person-circle fs-5"></i>

                                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?>

                            </a>

                            <ul class="dropdown-menu dropdown-menu-dark shadow">

                                <?php if ($is_backend_access): ?>

                                    <li>
                                        <a class="dropdown-item fw-bold"
                                            style="color:#cda45e;"
                                            href="admin/admin_dashboard.php">

                                            <i class="bi bi-speedometer2 me-2"></i>

                                            Vào trang Quản trị

                                        </a>
                                    </li>

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>

                                <?php endif; ?>

                                <li>
                                    <a class="dropdown-item"
                                        href="profile.php">

                                        <i class="bi bi-person me-2"
                                            style="color:#cda45e;"></i>

                                        Thông tin cá nhân

                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item"
                                        href="my_orders.php">

                                        <i class="bi bi-receipt me-2"
                                            style="color:#cda45e;"></i>

                                        Lịch sử đặt bàn

                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <a class="dropdown-item text-danger fw-bold"
                                        href="public/logout.php">

                                        <i class="bi bi-box-arrow-right me-2"></i>

                                        Đăng xuất

                                    </a>
                                </li>

                            </ul>

                        </div>

                    <?php else: ?>

                        <div class="d-flex align-items-center gap-2">

                            <a href="public/login.php"
                                class="auth-btn login-btn">
                                Đăng nhập
                            </a>

                            <a href="public/register.php"
                                class="auth-btn register-btn">
                                Đăng ký
                            </a>

                        </div>

                    <?php endif; ?>

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
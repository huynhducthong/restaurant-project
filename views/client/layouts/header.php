<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

/* =========================================================
   LẤY CẤU HÌNH WEBSITE & NGÀY SINH KHÁCH HÀNG
========================================================= */

$user_birthday = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_bd = $db->prepare("SELECT birthday FROM users WHERE id = ?");
        $stmt_bd->execute([$_SESSION['user_id']]);
        $user_birthday = $stmt_bd->fetchColumn();
    } catch(Exception $e) {}
}

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

if (!function_exists('safe_url')) {
    function safe_url($url, $prefix = '') {
        if (empty($url)) return '';
        if (preg_match('/^(https?:|\/\/|\/)/i', $url)) {
            return $url;
        }
        return $prefix . $url;
    }
}

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
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap"
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

            height: 50px;
            padding-top: 10px; /* Push text down further */

            z-index: 999;

            display: flex;
            align-items: center;

            color: #fff;

            background: transparent;

            transition: all 0.5s;
        }

        #topbar.topbar-scrolled {
            top: -50px;
        }

        /* =======================
           HEADER
        ======================= */

        #header {
            position: fixed;

            top: 50px;
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

            font-family: "Montserrat", sans-serif;

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

            font-family: "Montserrat", sans-serif;
            font-size: 14px;
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

            font-family: "Montserrat", sans-serif;
            font-size: 14px;
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

            font-family: "Montserrat", sans-serif;
            font-size: 14px;
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

        .btn-book-outside {
            background-color: #F6F2E9; /* Cream */
            color: #4F5B3A; /* Olive */
            padding: 8px 16px;
            font-family: "Montserrat", sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 0; /* Sharp corners */
            border: 1px solid #4F5B3A;
            transition: all 0.3s ease;
        }
        
        .btn-book-outside:hover {
            background-color: #4F5B3A;
            color: #F6F2E9;
        }

        .oriental-panel {
            position: absolute;
            top: 55px;
            right: 0;
            width: 320px;
            min-height: auto;
            background-color: #F6F2E9; /* Cream */
            border: 1px solid #4F5B3A; /* Olive border */
            padding: 30px 20px;
            z-index: 10001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(15px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15); /* Lighter shadow for bright bg */
            border-radius: 0; /* Sharp corners */
        }

        .oriental-nav-wrapper:hover .oriental-panel,
        .oriental-nav-wrapper:active .oriental-panel {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .oriental-item {
            text-decoration: none;
            color: #4F5B3A; /* Olive */
            font-family: "Montserrat", sans-serif;
            font-size: 14px;
            font-weight: 600;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(79, 91, 58, 0.15); /* Faint olive line */
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .oriental-item:last-child {
            border-bottom: none;
        }

        .oriental-item:hover {
            color: #C9A66B; /* Gold */
            padding-left: 25px;
            background-color: rgba(201, 166, 107, 0.05); /* Slight gold hover bg */
        }

        .oriental-item i {
            color: #4F5B3A; /* Olive */
            font-size: 18px;
            width: 20px;
            text-align: center;
            transition: color 0.3s ease;
        }
        
        .oriental-item:hover i {
            color: #C9A66B; /* Gold icon on hover */
        }

        .oriental-item.text-danger-custom {
            color: #d63031 !important; /* Standard red for light bg */
        }
        
        .oriental-item.text-danger-custom:hover {
            background-color: rgba(214, 48, 49, 0.05);
            color: #d63031 !important;
        }

        @media (max-width: 991px) {
            .navbar {
                display: none;
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
                            Tin Tức
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'menu.php') ? 'active' : '' ?>"
                            href="menu.php">
                            Thực đơn
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'chefs.php' || ($current_page == 'Aboutus.php' && strpos($_SERVER['REQUEST_URI'], 'chef') !== false)) ? 'active' : '' ?>"
                            href="<?= safe_url('chefs.php', $path_prefix ?? '') ?>">
                            Đội Bếp
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

                    <!-- ORIENTAL MEGA MENU & OUTSIDE BOOKING BUTTON -->
                    <div class="oriental-nav-wrapper" style="display:flex; align-items:center; gap:15px;">
                        <a href="booking_service.php?type=bespoke" class="btn-book-outside" style="background-color: #1a1814; color: #C9A66B; border-color: #C9A66B; margin-right: 10px;">BESPOKE DINING</a>
                        <a href="booking_service.php?type=table" class="btn-book-outside">ĐẶT BÀN</a>

                        <div class="oriental-trigger">
                            <i class="bi bi-list"></i>
                        </div>
                        
                        <div class="oriental-panel" id="orientalPanel">

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- TRANG QUẢN TRỊ / BẢNG CÔNG -->
                                <?php if (in_array($user_role, ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2])): ?>
                                    <a href="admin/admin_dashboard.php" class="oriental-item" style="color: var(--primary-color);">
                                        <i class="bi bi-speedometer2"></i> Trang quản trị
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
                                <a href="<?= safe_url('public/register.php', $path_prefix ?? '') ?>" class="oriental-item">
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

    <?php
    $is_birthday = false;
    if ($user_birthday) {
        $bd_parts = explode('-', (string)$user_birthday);
        if (count($bd_parts) == 3) {
            $b_month = $bd_parts[1];
            $b_day = $bd_parts[2];
            // Compare month and day
            if (date('m') == $b_month && date('d') == $b_day) {
                $is_birthday = true;
            }
        }
    }
    if ($is_birthday):
    ?>
    <!-- BIRTHDAY SURPRISE EFFECT -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if(!sessionStorage.getItem('birthday_greeted')) {
                var duration = 5000;
                var end = Date.now() + duration;
                (function frame() {
                    confetti({
                        particleCount: 5,
                        angle: 60,
                        spread: 55,
                        origin: { x: 0 },
                        colors: ['#cda45e', '#ffffff']
                    });
                    confetti({
                        particleCount: 5,
                        angle: 120,
                        spread: 55,
                        origin: { x: 1 },
                        colors: ['#cda45e', '#ffffff']
                    });
                    if (Date.now() < end) {
                        requestAnimationFrame(frame);
                    }
                }());
                // Create a custom Toast notification
                var toastHTML = '<div id="bdToast" style="position:fixed;top:120px;right:20px;z-index:99999;background:rgba(9,30,27,0.98);border:1px solid #cda45e;padding:25px;border-radius:12px;color:#fff;box-shadow:0 10px 40px rgba(0,0,0,0.6);transform:translateX(150%);transition:0.6s cubic-bezier(0.34, 1.56, 0.64, 1);max-width:320px;text-align:center;">'+
                '<i class="fas fa-gift fa-3x mb-3" style="color:#cda45e"></i>'+
                '<h4 style="font-family:\'Cormorant Garamond\',serif;color:#cda45e;margin-bottom:12px;font-size:1.5rem">Chúc Mừng Sinh Nhật!</h4>'+
                '<p style="font-size:13px;margin:0;line-height:1.6;color:rgba(255,255,255,0.85)">Hệ thống ghi nhận hôm nay là sinh nhật của Quý khách. Nền tảng đã chuẩn bị sẵn đặc quyền ưu đãi dành riêng cho Đơn đặt bàn ngày hôm nay!</p>'+
                '</div>';
                document.body.insertAdjacentHTML('beforeend', toastHTML);
                setTimeout(function(){ document.getElementById('bdToast').style.transform = 'translateX(0)'; }, 500);
                setTimeout(function(){ document.getElementById('bdToast').style.transform = 'translateX(150%)'; }, 8000);
                sessionStorage.setItem('birthday_greeted', '1');
            }
        });
    </script>
    <?php endif; ?>

    <?php
    // HOLIDAY LOGIC
    $is_holiday = false;
    $holiday_name = "";
    $holiday_msg = "";
    $h_month = date('m');
    $h_day = date('d');

    if ($h_month == '02' && $h_day == '14') {
        $is_holiday = true;
        $holiday_name = "Lễ Tình Nhân (Valentine's Day)";
        $holiday_msg = "Chúc Quý khách một mùa Valentine ấm áp. Đừng quên thiết kế một bàn tiệc lãng mạn dành tặng người thương nhé!";
        $holiday_icon = "fa-heart text-danger";
    } elseif ($h_month == '03' && $h_day == '08') {
        $is_holiday = true;
        $holiday_name = "Quốc Tế Phụ Nữ 8/3";
        $holiday_msg = "Tôn vinh phái đẹp! Hãy để chúng tôi mang đến trải nghiệm Fine Dining hoàn hảo nhất cho những người phụ nữ tuyệt vời của bạn.";
        $holiday_icon = "fa-female text-danger";
    } elseif ($h_month == '12' && $h_day == '24' || $h_day == '25') {
        $is_holiday = true;
        $holiday_name = "Giáng Sinh An Lành";
        $holiday_msg = "Merry Christmas! Hệ thống đã mở các Gói thiết kế Không gian Mùa đông độc quyền. Chúc Quý khách một mùa lễ hội an lành!";
        $holiday_icon = "fa-snowflake text-info";
    }

    if ($is_holiday && !$is_birthday): // Avoid showing 2 toasts if born on a holiday
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if(!sessionStorage.getItem('holiday_greeted_' + '<?= $h_month.$h_day ?>')) {
                var toastHTML = '<div id="holToast" style="position:fixed;top:120px;right:20px;z-index:99999;background:rgba(9,30,27,0.98);border:1px solid #cda45e;padding:25px;border-radius:12px;color:#fff;box-shadow:0 10px 40px rgba(0,0,0,0.6);transform:translateX(150%);transition:0.6s cubic-bezier(0.34, 1.56, 0.64, 1);max-width:320px;text-align:center;">'+
                '<i class="fas <?= $holiday_icon ?> fa-3x mb-3"></i>'+
                '<h4 style="font-family:\'Cormorant Garamond\',serif;color:#cda45e;margin-bottom:12px;font-size:1.5rem"><?= $holiday_name ?></h4>'+
                '<p style="font-size:13px;margin:0;line-height:1.6;color:rgba(255,255,255,0.85)"><?= $holiday_msg ?></p>'+
                '</div>';
                document.body.insertAdjacentHTML('beforeend', toastHTML);
                setTimeout(function(){ document.getElementById('holToast').style.transform = 'translateX(0)'; }, 500);
                setTimeout(function(){ document.getElementById('holToast').style.transform = 'translateX(150%)'; }, 8000);
                sessionStorage.setItem('holiday_greeted_' + '<?= $h_month.$h_day ?>', '1');
            }
        });
    </script>
    <?php endif; ?>

</body>

</html>
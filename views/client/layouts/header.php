<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

/* =========================================================
   Láº¤Y Cáº¤U HÃŒNH WEBSITE & NGÃ€Y SINH KHÃCH HÃ€NG
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
$settings['open_days']       = $settings['open_days'] ?? 'Thá»© 2 - Chá»§ Nháº­t';
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
   FIX ÄÆ¯á»œNG DáºªN LOGO
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
    <meta name="google" content="notranslate">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($settings['restaurant_name']) ?>
    </title>

    <!-- GOOGLE FONT -->
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
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
            --primary-color: #A88746;
        }

        body {
            margin: 0;
            padding: 0 !important;
            font-family: 'Source Sans 3', sans-serif;
            background-color: #050505; /* Deep Black for high contrast */
            color: #FFFFFF; /* Pure White text for better contrast */
            }
        @keyframes fadeInPage {
            0% { opacity: 0; }
            100% { opacity: 1; }
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

            font-family: 'Cormorant Garamond', serif;

            white-space: nowrap;
        }

        /* =======================
           NAVBAR
        ======================= */

        @media (min-width: 992px) {
            .navbar {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }
        }

        .navbar ul {
            display: flex;
            align-items: center;
            gap: 36px;

            margin: 0;
            padding: 0;

            list-style: none;
        }

        .navbar a {

            text-decoration: none;

            color: #fff;

            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
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

            font-family: 'Cormorant Garamond', serif;
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

        /* KhÃ¡ch: Ä‘Äƒng kÃ½ / Ä‘Äƒng nháº­p xáº¿p dá»c, nÃºt vuÃ´ng */

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

            font-family: 'Cormorant Garamond', serif;
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
            background: transparent;
            color: var(--primary-color);
        }

        .oriental-trigger i {
            font-size: 38px; /* Much bigger icon */
            transition: transform 0.4s ease, color 0.3s ease;
            transform: rotate(0deg);
            display: inline-block;
        }

        .oriental-trigger.open i {
            transform: rotate(180deg);
        }

        .btn-book-outside {
            background-color: #E65C00;
            color: #ffffff;
            padding: 8px 16px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 0;
            border: 1px solid #E65C00;
            transition: all 0.3s ease;
        }
        
        .btn-book-outside:hover {
            background-color: #FF7A00;
            border-color: #FF7A00;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(255, 122, 0, 0.6);
        }

        .oriental-panel {
            position: absolute;
            top: 60px;
            right: 0;
            width: 300px;
            background-color: #ffffff;
            padding: 30px 20px;
            z-index: 10001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(15px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .oriental-panel.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .oriental-item {
            text-decoration: none;
            color: #000000;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 600;
            padding: 10px 20px;
            border-bottom: none;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            text-align: center;
            text-transform: capitalize;
            letter-spacing: 1px;
        }

        .oriental-item:hover {
            color: #A88746 !important;
            background-color: transparent;
        }

        .oriental-item i {
            display: none; /* Hide icons in the new clean menu */
        }
        @media (max-width: 991px) {
            .navbar {
                display: none;
            }
        }

    
        
            100% { opacity: 0; visibility: hidden; }
        }

    
        /* =======================
           PAGE TRANSITION OVERLAY
        ======================= */
        .page-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #050505;
            z-index: 999999;
            pointer-events: none;
            animation: fadeOutOverlay 0.8s cubic-bezier(0.8, 0, 0.2, 1) forwards;
        }
        @keyframes fadeOutOverlay {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }

    </style>

</head>

<body>
    <!-- PAGE TRANSITION OVERLAY -->
    <div class="page-transition-overlay"></div>
    

    <!-- TOPBAR -->

    <div id="topbar">

        <div class="container-fluid d-flex justify-content-between" style="padding-left: 8vw; padding-right: 8vw;">

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

            <div class="lang-switcher d-flex align-items-center">
                <a href="#" onclick="setLanguage('en'); return false;" id="btn-lang-en" style="color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 600; font-size: 14px; letter-spacing: 1px;">EN</a>
                <span class="mx-2" style="color: rgba(255,255,255,0.5); font-size: 14px;">/</span>
                <a href="#" onclick="setLanguage('vi'); return false;" id="btn-lang-vn" style="color: #C9A66B; text-decoration: none; font-weight: 600; font-size: 14px; letter-spacing: 1px;">VN</a>
            </div>

            <!-- Hidden Google Translate Logic -->
            <div id="google_translate_element" style="display:none;"></div>
            <style>
                /* Aggressively hide the Google Translate top banner (All versions) */
                .goog-te-banner-frame,
                .goog-te-banner-frame.skiptranslate,
                iframe.goog-te-banner-frame,
                iframe.skiptranslate,
                .skiptranslate > iframe.skiptranslate,
                .VIpgJd-ZVi9od-aZ2wEe-wOHMyf,
                .VIpgJd-ZVi9od-aZ2wEe-wOHMyf-ti6hGc,
                iframe[src*="translate.googleapis.com"] { 
                    display: none !important; 
                    visibility: hidden !important;
                    height: 0 !important;
                }
                body { 
                    position: static !important;
                    top: 0px !important; 
                    margin-top: 0px !important;
                }
                #goog-gt-tt, .goog-te-balloon-frame { display: none !important; } /* Hide tooltips */
            
        
            100% { opacity: 0; visibility: hidden; }
        }

    
        /* =======================
           PAGE TRANSITION OVERLAY
        ======================= */
        .page-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #050505;
            z-index: 999999;
            pointer-events: none;
            animation: fadeOutOverlay 0.8s cubic-bezier(0.8, 0, 0.2, 1) forwards;
        }
        @keyframes fadeOutOverlay {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }

    </style>
            <script type="text/javascript">
                function googleTranslateElementInit() {
                    new google.translate.TranslateElement({pageLanguage: 'vi', includedLanguages: 'en,vi', autoDisplay: false}, 'google_translate_element');
                }
                
                document.addEventListener("DOMContentLoaded", function() {
                    let match = document.cookie.match(/(?:^|;)\s*googtrans=([^;]*)/);
                    let currentLang = match ? decodeURIComponent(match[1]) : '/vi/vi';
                    if (currentLang === '/vi/en' || currentLang === '/en/en') {
                        document.getElementById('btn-lang-en').style.color = '#C9A66B';
                        document.getElementById('btn-lang-vn').style.color = 'rgba(255,255,255,0.7)';
                    } else {
                        document.getElementById('btn-lang-en').style.color = 'rgba(255,255,255,0.7)';
                        document.getElementById('btn-lang-vn').style.color = '#C9A66B';
                    }
                });

                function setLanguage(lang) {
                    var domain = window.location.hostname;
                    if (lang === 'vi') {
                        // Clear cookie to restore original Vietnamese (both domain and non-domain to be safe)
                        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                        document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + domain;
                    } else {
                        // Set cookie for English (both domain and non-domain to guarantee Google reads it on first load)
                        document.cookie = "googtrans=/vi/" + lang + "; path=/;";
                        document.cookie = "googtrans=/vi/" + lang + "; path=/; domain=" + domain;
                    }
                    // Add a tiny delay to ensure browser finishes writing cookies before reload
                    setTimeout(function() {
                        window.location.reload();
                    }, 100);
                }
            </script>
            <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

        </div>

    </div>

    <!-- HEADER -->

    <header id="header">

        <div class="container-fluid d-flex align-items-center justify-content-between" style="padding-left: 8vw; padding-right: 8vw;">

            <!-- LOGO -->

            <h1 class="logo">

                <a href="<?= safe_url('index.php', $path_prefix ?? '') ?>"
                    class="<?= ($settings['logo_position'] == 'right') ? 'logo-container-right' : '' ?>">

                    <?php if (!empty($final_logo_src)): ?>

                        <img
                            src="<?= ($path_prefix ?? '') ?><?= htmlspecialchars($final_logo_src) ?>?v=<?= htmlspecialchars($settings['logo_ver']) ?>"
                            alt="Logo">

                    <?php endif; ?>

                    <span class="notranslate">
                        <?= htmlspecialchars($settings['restaurant_name']) ?>
                    </span>

                </a>

            </h1>

            <!-- NAVBAR -->

            <nav id="navbar"
                class="navbar order-last order-lg-0">

                <ul>
                      <li>
                          <a class="<?= ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/about') === false) ? 'active' : '' ?>" href="<?= safe_url('index.php', $path_prefix ?? '') ?>">
                              <?= 'Trang chủ' ?>
                          </a>
                      </li>

                    <li>
                        <a class="<?= ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/about') !== false || $current_page == 'about.php') ? 'active' : '' ?>"
                            href="<?= safe_url('about.php', $path_prefix ?? '') ?>">
                            <?= 'Tin tức' ?>
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'menu.php') ? 'active' : '' ?>" href="<?= safe_url('menu.php', $path_prefix ?? '') ?>">
                            <?= 'Thực đơn' ?>
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'chefs.php' || ($current_page == 'about.php' && isset($_GET['cat_id']) && $_GET['cat_id'] == 3)) ? 'active' : '' ?>"
                            href="<?= safe_url('chefs.php', $path_prefix ?? '') ?>">
                            <?= 'Bếp trưởng' ?>
                        </a>
                    </li>

                    <li>
                        <a class="<?= ($current_page == 'contact.php') ? 'active' : '' ?>" href="<?= safe_url('contact.php', $path_prefix ?? '') ?>">
                            <?= 'Liên hệ' ?>
                        </a>
                    </li>
                </ul>

            </nav>

            <!-- ACTION -->

            <div class="d-flex align-items-center">


                <div class="header-actions ms-3">

                    <!-- ORIENTAL MEGA MENU & OUTSIDE BOOKING BUTTON -->
                    <div class="oriental-nav-wrapper" style="display:flex; align-items:center; gap:15px;">
                        <a href="<?= safe_url('booking_service.php?type=table', $path_prefix ?? '') ?>" class="btn-book-outside"><?= 'Đặt bàn' ?></a>

                        <!-- Divider line -->
                        <div style="width: 1px; height: 35px; background-color: rgba(168, 135, 70, 0.4); margin: 0 10px;"></div>

                        <div class="oriental-trigger" onclick="toggleOrientalMenu(event)">
                            <i class="bi bi-list" id="orientalIcon"></i>
                        </div>
                        
                        <div class="oriental-panel" id="orientalPanel">

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- TRANG QUẢN TRỊ -->
                                <?php if (in_array($user_role, ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2])): ?>
                                    <a href="<?= safe_url('admin/admin_dashboard.php', $path_prefix ?? '') ?>" class="oriental-item" style="color: var(--primary-color);">
                                        Trang quản trị
                                    </a>
                                <?php endif; ?>

                                <!-- THÔNG TIN CÁ NHÂN -->
                                <a href="<?= safe_url('profile.php', $path_prefix ?? '') ?>" class="oriental-item">
                                        Thông tin cá nhân
                                </a>

                                <!-- ĐĂNG XUẤT -->
                                <a href="<?= safe_url('public/logout.php', $path_prefix ?? '') ?>" class="oriental-item text-danger-custom fw-bold">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <?= 'Đăng xuất' ?>
                                </a>
                            <?php else: ?>
                                <!-- ĐĂNG KÝ -->
                                <a href="<?= safe_url('public/register.php', $path_prefix ?? '') ?>" class="oriental-item">
                                        Đăng ký
                                </a>

                                <!-- ĐĂNG NHẬP -->
                                <a href="<?= safe_url('public/login.php', $path_prefix ?? '') ?>" class="oriental-item">
                                        Đăng nhập
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
                        colors: ['#A88746', '#ffffff']
                    });
                    confetti({
                        particleCount: 5,
                        angle: 120,
                        spread: 55,
                        origin: { x: 1 },
                        colors: ['#A88746', '#ffffff']
                    });
                    if (Date.now() < end) {
                        requestAnimationFrame(frame);
                    }
                }());
                // Create a custom Toast notification
                var toastHTML = '<div id="bdToast" style="position:fixed;top:120px;right:20px;z-index:99999;background:rgba(9,30,27,0.98);border:1px solid #A88746;padding:25px;border-radius:12px;color:#fff;box-shadow:0 10px 40px rgba(0,0,0,0.6);transform:translateX(150%);transition:0.6s cubic-bezier(0.34, 1.56, 0.64, 1);max-width:320px;text-align:center;">'+
                '<i class="fas fa-gift fa-3x mb-3" style="color:#A88746"></i>'+
                '<h4 style="font-family:\'Source Sans 3\',serif;color:#A88746;margin-bottom:12px;font-size:1.5rem">ChÃºc Má»«ng Sinh Nháº­t!</h4>'+
                '<p style="font-size:13px;margin:0;line-height:1.6;color:rgba(255,255,255,0.85)">Há»‡ thá»‘ng ghi nháº­n hÃ´m nay lÃ  sinh nháº­t cá»§a QuÃ½ khÃ¡ch. Ná»n táº£ng Ä‘Ã£ chuáº©n bá»‹ sáºµn Ä‘áº·c quyá»n Æ°u Ä‘Ã£i dÃ nh riÃªng cho ÄÆ¡n Ä‘áº·t bÃ n ngÃ y hÃ´m nay!</p>'+
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
        $holiday_name = "Lá»… TÃ¬nh NhÃ¢n (Valentine's Day)";
        $holiday_msg = "ChÃºc QuÃ½ khÃ¡ch má»™t mÃ¹a Valentine áº¥m Ã¡p. Äá»«ng quÃªn thiáº¿t káº¿ má»™t bÃ n tiá»‡c lÃ£ng máº¡n dÃ nh táº·ng ngÆ°á»i thÆ°Æ¡ng nhÃ©!";
        $holiday_icon = "fa-heart text-danger";
    } elseif ($h_month == '03' && $h_day == '08') {
        $is_holiday = true;
        $holiday_name = "Quá»‘c Táº¿ Phá»¥ Ná»¯ 8/3";
        $holiday_msg = "TÃ´n vinh phÃ¡i Ä‘áº¹p! HÃ£y Ä‘á»ƒ chÃºng tÃ´i mang Ä‘áº¿n tráº£i nghiá»‡m Fine Dining hoÃ n háº£o nháº¥t cho nhá»¯ng ngÆ°á»i phá»¥ ná»¯ tuyá»‡t vá»i cá»§a báº¡n.";
        $holiday_icon = "fa-female text-danger";
    } elseif ($h_month == '12' && $h_day == '24' || $h_day == '25') {
        $is_holiday = true;
        $holiday_name = "GiÃ¡ng Sinh An LÃ nh";
        $holiday_msg = "Merry Christmas! Há»‡ thá»‘ng Ä‘Ã£ má»Ÿ cÃ¡c GÃ³i thiáº¿t káº¿ KhÃ´ng gian MÃ¹a Ä‘Ã´ng Ä‘á»™c quyá»n. ChÃºc QuÃ½ khÃ¡ch má»™t mÃ¹a lá»… há»™i an lÃ nh!";
        $holiday_icon = "fa-snowflake text-info";
    }

    if ($is_holiday && !$is_birthday): // Avoid showing 2 toasts if born on a holiday
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if(!sessionStorage.getItem('holiday_greeted_' + '<?= $h_month.$h_day ?>')) {
                var toastHTML = '<div id="holToast" style="position:fixed;top:120px;right:20px;z-index:99999;background:rgba(9,30,27,0.98);border:1px solid #A88746;padding:25px;border-radius:12px;color:#fff;box-shadow:0 10px 40px rgba(0,0,0,0.6);transform:translateX(150%);transition:0.6s cubic-bezier(0.34, 1.56, 0.64, 1);max-width:320px;text-align:center;">'+
                '<i class="fas <?= $holiday_icon ?> fa-3x mb-3"></i>'+
                '<h4 style="font-family:\'Source Sans 3\',serif;color:#A88746;margin-bottom:12px;font-size:1.5rem"><?= $holiday_name ?></h4>'+
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

    <script>
        function toggleOrientalMenu(e) {
            e.stopPropagation();
            const panel = document.getElementById('orientalPanel');
            const icon = document.getElementById('orientalIcon');
            panel.classList.toggle('show');
            if (panel.classList.contains('show')) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-x');
                icon.parentElement.classList.add('open');
            } else {
                icon.classList.remove('bi-x');
                icon.classList.add('bi-list');
                icon.parentElement.classList.remove('open');
            }
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            const panel = document.getElementById('orientalPanel');
            const trigger = document.querySelector('.oriental-trigger');
            if (panel && panel.classList.contains('show') && !panel.contains(e.target) && !trigger.contains(e.target)) {
                panel.classList.remove('show');
                const icon = document.getElementById('orientalIcon');
                if (icon) {
                    icon.classList.remove('bi-x');
                    icon.classList.add('bi-list');
                    icon.parentElement.classList.remove('open');
                }
            }
        });
    </script>
</body>

</html>



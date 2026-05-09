<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   KIỂM TRA ĐĂNG NHẬP & PHÂN QUYỀN
========================================================= */

$user_id   = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';

$allowed_roles = ['admin', 'staff', 1, 2];

if (!$user_id || !in_array($user_role, $allowed_roles)) {
    header("Location: /restaurant-project/login.php?error=access_denied");
    exit();
}

$is_admin = ($user_role === 'admin' || $user_role == 1);

/* =========================================================
   PAGE
========================================================= */

$current_page = basename($_SERVER['PHP_SELF']);

if (!function_exists('isActive')) {
    function isActive($path)
    {
        return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
    }
}

$page_titles = [
    'admin_dashboard.php'      => 'Bảng Điều Khiển Tổng Quan',
    'FoodController.php'       => 'Quản Lý Món Ăn',
    'ComboController.php'      => 'Quản Lý Combo',
    'list_combos.php'          => 'Quản Lý Combo',
    'manage_services.php'      => 'Quản Lý Dịch Vụ',
    'InventoryController.php'  => 'Quản Lý Kho Nguyên Liệu',
    'POController.php'         => 'Đặt Hàng & Nhập Kho',
    'ReportController.php'     => 'Báo Cáo & Thống Kê',
    'manage_banners.php'       => 'Quản Lý Banner',
    'manage_videos.php'        => 'Quản Lý Video',
    'manage_about.php'         => 'Quản Lý Tin Tức',
    'manage_chefs.php'         => 'Quản Lý Đầu Bếp',
    'settings.php'             => 'Cài Đặt Hệ Thống',
    'footer_settings.php'      => 'Cấu Hình Footer',
    'UserController.php'       => 'Quản Lý Người Dùng',
    'manage_users.php'         => 'Quản Lý Nhân Sự',
    'manage_contacts.php'      => 'Quản Lý Liên Hệ',
];

$page_title = $page_titles[$current_page] ?? 'Khu Vực Quản Trị';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $page_title ?> - Restaurantly</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #ffffff;
            --sidebar-border: #ececec;
            --accent: #b8862e;
            --accent-light: #fff7e9;
            --text-dark: #222;
            --text-muted: #777;
            --main-bg: #f5f5f5;

        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--main-bg);
        }

        /* =======================
           SIDEBAR
        ======================= */

        .admin-sidebar {
            width: 270px;
            height: 100vh;
            background: #fff;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid var(--sidebar-border);
            overflow-y: auto;
            z-index: 999;
        }

        .sidebar-brand {
            padding: 25px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #b8862e, #dca94e);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 18px;
        }

        .brand-text h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }

        .brand-text span {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* =======================
           MENU
        ======================= */

        .menu-header {
            padding: 18px 25px 8px;
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            font-weight: 700;
        }

        .menu-list {
            list-style: none;
            margin: 0;
            padding: 0 12px 20px;
        }

        .menu-list li {
            margin-bottom: 4px;
        }

        .menu-list li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            text-decoration: none;
            border-radius: 10px;
            color: #666;
            font-size: 14px;
            transition: 0.2s;
        }

        .menu-list li a:hover {
            background: var(--accent-light);
            color: var(--accent);
        }

        .menu-list li.active a {
            background: var(--accent-light);
            color: var(--accent);
            font-weight: 600;
        }

        .view-home-btn {
            margin: 15px 12px;
            border: 1px dashed var(--accent);
        }

        /* =======================
           LOGOUT
        ======================= */

        .logout-area {
            padding: 15px;
            border-top: 1px solid var(--sidebar-border);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 6px;
            color: #d33 !important;
            text-decoration: none;
            font-size: 13.5px;
            background: #fff0f0;
            transition: 0.2s;
        }

        .logout-btn:hover {
            background: #fef2f2;
        }

        /* =======================
           MAIN
        ======================= */


        .main-wrapper {
            margin-left: 270px;
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: white;
            border-bottom: 1px solid #eee;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #b8862e, #dca94e);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
        }

        .badge-admin,
        .badge-staff {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-admin {
            background: #b8862e;
            color: white;
        }

        .badge-staff {
            background: #e7f7ea;
            color: #238a3b;
        }

        .content-area {
            padding: 30px;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="brand-text">
                <h2>RESTAURANTLY</h2>
                <span>Admin Panel</span>
            </div>
        </div>

        <ul class="menu-list">

            <li>
                <a href="/restaurant-project/index.php" target="_blank" class="view-home-btn">
                    <i class="fas fa-home"></i>
                    Xem Trang Chủ
                </a>
            </li>

            <div class="menu-header">Vận hành Nhà hàng</div>

            <li class="<?= isActive('admin_dashboard.php') ?>">
                <a href="/restaurant-project/admin/admin_dashboard.php">
                    <i class="fas fa-chart-pie"></i>
                    Tổng Quan
                </a>
            </li>

            <li class="<?= isActive('FoodController.php') ?>">
                <a href="/restaurant-project/admin/controllers/FoodController.php">
                    <i class="fas fa-utensils"></i>
                    Quản lý Món ăn
                </a>
            </li>

            <li class="<?= isActive('ComboController.php') ?>">
                <a href="/restaurant-project/admin/controllers/ComboController.php">
                    <i class="fas fa-layer-group"></i>
                    Quản lý Combo
                </a>
            </li>

            <li class="<?= isActive('manage_services.php') ?>">
                <a href="/restaurant-project/admin/controllers/manage_services.php">
                    <i class="fas fa-concierge-bell"></i>
                    Quản lý Dịch vụ
                </a>
            </li>

            <li class="<?= isActive('InventoryController.php') ?>">
                <a href="/restaurant-project/admin/controllers/InventoryController.php">
                    <i class="fas fa-warehouse"></i>
                    Quản lý Kho
                </a>
            </li>

            <li class="<?= isActive('ReportController.php') ?>">
                <a href="/restaurant-project/admin/controllers/ReportController.php">
                    <i class="fas fa-chart-line"></i>
                    Báo cáo &amp; Thống kê
                </a>
            </li>

            <li class="<?= isActive('manage_contacts.php') ?>">
                <a href="/restaurant-project/admin/manage_contacts.php">
                    <i class="fas fa-envelope"></i>
                    Quản lý Liên hệ
                </a>
            </li>



            <?php if ($is_admin): ?>

                <div class="menu-header">Quản trị Hệ thống</div>

                <li class="<?= isActive('manage_banners.php') ?>">
                    <a href="/restaurant-project/admin/controllers/manage_banners.php">
                        <i class="fas fa-image"></i>
                        Quản lý Banner
                    </a>
                </li>

                <li class="<?= isActive('manage_videos.php') ?>">
                    <a href="/restaurant-project/admin/controllers/manage_videos.php">
                        <i class="fas fa-video"></i>
                        Quản lý Video
                    </a>
                </li>

                <li class="<?= isActive('manage_about.php') ?>">
                    <a href="/restaurant-project/admin/manage_about.php">
                        <i class="fas fa-newspaper"></i>
                        Quản lý tin tức
                    </a>
                </li>

                <li class="<?= isActive('settings.php') ?>">
                    <a href="/restaurant-project/admin/controllers/settings.php">
                        <i class="fas fa-cog"></i>
                        Cài Đặt Chung
                    </a>
                </li>

                <li class="<?= isActive('footer_settings.php') ?>">
                    <a href="/restaurant-project/admin/footer_settings.php">
                        <i class="fas fa-palette"></i>
                        Cấu hình Footer
                    </a>
                </li>

                <li class="<?= isActive('manage_users.php') ?>">
                    <a href="/restaurant-project/admin/manage_users.php">
                        <i class="fas fa-id-card"></i>
                        Quản lý Nhân sự
                    </a>
                </li>

                <li class="<?= isActive('UserController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/UserController.php">
                        <i class="fas fa-users-cog"></i>
                        Quản lý Người dùng
                    </a>
                </li>

            <?php endif; ?>

        </ul>

        <!-- Logout -->

        <div class="logout-area">
            <a href="/restaurant-project/admin/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Đăng xuất
            </a>
        </div>

    </aside>

    <!-- MAIN -->
    <div class="main-wrapper">

        <header class="topbar">

            <h4 class="topbar-title">
                <?= $page_title ?>
            </h4>

            <div class="user-profile">
                <div class="user-info">
                    <strong>
                        <?= htmlspecialchars($_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'Tài khoản')) ?>
                    </strong>
                    <?php if ($is_admin): ?>
                        <div class="badge-admin">Quản trị viên</div>
                    <?php else: ?>
                        <div class="badge-staff">Nhân viên</div>
                    <?php endif; ?>
                </div>
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>

        </header>

        <div class="content-area">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. KIỂM TRA PHÂN QUYỀN TRUY CẬP (Cho phép Admin và Nhân viên)
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';

// Các vai trò được phép vào Backend (Admin = 1/'admin', Staff = 2/'staff')
$allowed_roles = ['admin', 'staff', 1, 2];

if (!$user_id || !in_array($user_role, $allowed_roles)) {
    header("Location: /restaurant-project/login.php?error=access_denied");
    exit();
}

// 2. Biến kiểm tra Admin để ẩn/hiện menu Cấu hình
$is_admin = ($user_role === 'admin' || $user_role == 1);

// Hàm hỗ trợ active menu hiện tại
function isActive($path)
{
    return basename($_SERVER['PHP_SELF']) === $path ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị hệ thống - Restaurantly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #121212;
            /* Deep elegant black */
            --sidebar-border: rgba(255, 255, 255, 0.05);
            --sidebar-text: #ffffff;
            --sidebar-text-muted: #9ca3af;

            --accent-gradient: linear-gradient(135deg, #f5d061 0%, #e6a30b 100%);
            --accent-color: #e6a30b;
            --accent-light: rgba(230, 163, 11, 0.1);

            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --bg-main: #f3f4f6;
            /* Modern cool grey background */
            --topbar-bg: rgba(255, 255, 255, 0.85);
            /* Glassmorphism topbar */

            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.02);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.05);
            --shadow-gold: 0 8px 20px rgba(230, 163, 11, 0.25);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            overflow-x: hidden;
            color: var(--text-dark);
        }

        /* ── SIDEBAR ── */
        .admin-sidebar {
            width: 268px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-md);
        }

        /* Brand */
        .sidebar-brand {
            padding: 28px 24px 22px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #b8862e, #e0b060);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(184, 134, 46, 0.35);
        }

        .brand-text h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--sidebar-text);
            margin: 0;
            letter-spacing: 1.2px;
        }

        .brand-text span {
            font-size: 11px;
            color: var(--sidebar-text-muted);
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        /* Nav */
        .menu-scroll {
            flex-grow: 1;
            overflow-y: auto;
            padding: 16px 0 8px;
        }

        .menu-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .menu-scroll::-webkit-scrollbar-thumb {
            background: var(--accent-mid);
            border-radius: 4px;
        }

        .menu-list {
            list-style: none;
            padding: 0 14px;
            margin: 0;
        }

        .menu-list li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--sidebar-text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 6px;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .menu-list li a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .menu-list li a:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--sidebar-text);
            transform: translateX(4px);
        }

        .menu-list li.active a {
            background: var(--accent-light);
            color: var(--accent-color);
            font-weight: 600;
        }

        .menu-list li.active a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--accent-gradient);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 10px var(--accent-color);
        }

        .menu-list li.active a i {
            color: var(--accent-color);
        }

        /* View Homepage pill */
        .view-home-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 14px 12px;
            padding: 10px 14px;
            background: rgba(212, 175, 55, 0.08);
            border: 1px dashed rgba(212, 175, 55, 0.5);
            border-radius: 10px;
            color: var(--accent-color) !important;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: 0.3px;
            transition: all 0.2s;
        }

        .view-home-btn:hover {
            background: rgba(212, 175, 55, 0.15);
            border-color: var(--accent-color);
        }

        /* Menu section header */
        .menu-header {
            padding: 16px 28px 8px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #9d8765;
            letter-spacing: 1.4px;
        }

        /* Logout */
        .logout-area {
            padding: 16px;
            border-top: 1px solid var(--sidebar-border);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444 !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .logout-btn:hover {
            background: #ef4444;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* ── MAIN WRAPPER ── */
        .main-wrapper {
            margin-left: 268px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Topbar */
        .topbar {
            height: 76px;
            background: var(--topbar-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.3px;
        }

        .topbar-title::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 24px;
            background: var(--accent-gradient);
            border-radius: 6px;
            box-shadow: var(--shadow-gold);
        }

        /* User info */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: 0.3s;
            padding: 6px 12px;
            border-radius: 12px;
        }

        .user-profile:hover {
            background: rgba(0, 0, 0, 0.03);
        }

        .user-info {
            text-align: right;
        }

        .user-info strong {
            display: block;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .badge-admin {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            background: var(--accent-gradient);
            color: #fff;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-gold);
        }

        .badge-staff {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            background: #e0f2fe;
            color: #0284c7;
            letter-spacing: 0.5px;
        }

        .user-avatar {
            width: 46px;
            height: 46px;
            background: var(--accent-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            box-shadow: var(--shadow-gold);
        }

        /* Content */
        .content-area {
            padding: 28px 32px;
            flex-grow: 1;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="fas fa-utensils"></i></div>
            <div class="brand-text">
                <h2>RESTAURANTLY</h2>
                <span>Admin Control Panel</span>
            </div>
        </div>

        <div class="menu-scroll">
            <ul class="menu-list">

                <!-- View Homepage -->
                <li style="list-style:none;">
                    <a href="/restaurant-project/index.php" target="_blank" class="view-home-btn">
                        <i class="fas fa-external-link-alt"></i> Xem Trang Chủ
                    </a>
                </li>

                <!-- Restaurant Ops -->
                <div class="menu-header">Vận hành Nhà hàng</div>

                <li class="<?= isActive('admin_dashboard.php') ?>">
                    <a href="/restaurant-project/admin/admin_dashboard.php"><i class="fas fa-chart-pie"></i> Tổng
                        Quan</a>
                </li>

                <li class="<?= isActive('FoodController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/FoodController.php"><i class="fas fa-utensils"></i>
                        Quản lý Món ăn</a>
                </li>

                <li class="<?= isActive('ComboController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/ComboController.php"><i
                            class="fas fa-layer-group"></i> Quản lý Combo</a>
                </li>

                <li class="<?= isActive('manage_services.php') ?>">
                    <a href="/restaurant-project/admin/controllers/manage_services.php"><i
                            class="fas fa-concierge-bell"></i> Quản lý Dịch vụ</a>
                </li>

                <li class="<?= isActive('InventoryController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/InventoryController.php"><i
                            class="fas fa-warehouse"></i> Quản lý Kho</a>
                </li>

                <li class="<?= isActive('ReportController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/ReportController.php"><i
                            class="fas fa-chart-line"></i> Báo cáo & Thống kê</a>
                </li>

                <!-- Admin Only -->
                <?php if ($is_admin): ?>
                    <div class="menu-header">Quản trị Hệ thống</div>

                    <li class="<?= isActive('manage_banners.php') ?>">
                        <a href="/restaurant-project/admin/controllers/manage_banners.php"><i class="fas fa-image"></i> Quản
                            lý Banner</a>
                    </li>

                    <li class="<?= isActive('manage_videos.php') ?>">
                        <a href="/restaurant-project/admin/controllers/manage_videos.php"><i class="fas fa-video"></i> Quản
                            lý Video</a>
                    </li>

                    <li class="<?= isActive('settings.php') ?>">
                        <a href="/restaurant-project/admin/controllers/settings.php"><i class="fas fa-cog"></i> Cài Đặt
                            Chung</a>
                    </li>

                    <li class="<?= isActive('footer_settings.php') ?>">
                        <a href="/restaurant-project/admin/footer_settings.php"><i class="fas fa-palette"></i> Cấu hình
                            Footer</a>
                    </li>

                    <li class="<?= isActive('manage_users.php') ?>">
                        <a href="/restaurant-project/admin/manage_users.php"><i class="fas fa-users-cog"></i> Quản lý Nhân
                            sự</a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>

        <!-- Logout -->
        <div class="logout-area">
            <a href="/restaurant-project/admin/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <header class="topbar">
            <h4 class="topbar-title">
                <?php
                $page_titles = [
                    'admin_dashboard.php' => 'Bảng Điều Khiển Tổng Quan',
                    'FoodController.php' => 'Quản Lý Thực Đơn',
                    'ComboController.php' => 'Quản Lý Combo',
                    'manage_services.php' => 'Quản Lý Dịch Vụ',
                    'InventoryController.php' => 'Quản Lý Kho Nguyên Liệu',
                    'ReportController.php' => 'Báo Cáo & Thống Kê Kho',
                    'manage_banners.php' => 'Quản Lý Banner',
                    'manage_videos.php' => 'Quản Lý Video',
                    'settings.php' => 'Cài Đặt Hệ Thống Chung',
                    'footer_settings.php' => 'Cấu Hình Giao Diện Footer',
                    'manage_users.php' => 'Quản Lý Nhân Sự'
                ];
                $current_page = basename($_SERVER['PHP_SELF']);
                echo $page_titles[$current_page] ?? 'Khu Vực Quản Trị';
                ?>
            </h4>

            <div class="user-profile">
                <div class="user-info">
                    <strong><?= htmlspecialchars($_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'Tài khoản')) ?></strong>
                    <?php if ($is_admin): ?>
                        <span class="badge-admin">Quản trị viên</span>
                    <?php else: ?>
                        <span class="badge-staff">Nhân viên</span>
                    <?php endif; ?>
                </div>
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="content-area">
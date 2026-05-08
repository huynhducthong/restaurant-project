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
if (!function_exists('isActive')) {
    function isActive($path)
    {
        return basename($_SERVER['PHP_SELF']) === $path ? 'active' : '';
    }
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
            --sidebar-bg: #ffffff;
            --sidebar-border: #e5e7eb;
            --sidebar-text: #111827;
            --sidebar-text-muted: #6b7280;
            --accent-color: #0f172a;
            --accent-bg: #f8fafc;
            --text-dark: #111827;
            --bg-main: #f9fafb;
            --topbar-bg: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            overflow-x: hidden;
            color: var(--text-dark);
            margin: 0;
        }

        /* ── SIDEBAR ── */
        .admin-sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: var(--accent-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
        }

        .brand-text h2 {
            font-size: 15px;
            font-weight: 600;
            color: var(--sidebar-text);
            margin: 0;
        }

        .brand-text span {
            font-size: 11px;
            color: var(--sidebar-text-muted);
        }

        /* Nav */
        .menu-scroll {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px 0;
        }

        .menu-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .menu-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .menu-list {
            list-style: none;
            padding: 0 15px;
            margin: 0;
        }

        .menu-list li a {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: var(--sidebar-text-muted);
            text-decoration: none;
            font-size: 13.5px;
            border-radius: 6px;
            margin-bottom: 2px;
            gap: 12px;
            transition: 0.2s;
        }

        .menu-list li a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }

        .menu-list li a:hover {
            background-color: var(--accent-bg);
            color: var(--sidebar-text);
        }

        .menu-list li.active a {
            background: var(--accent-bg);
            color: var(--accent-color);
            font-weight: 500;
        }

        .view-home-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 15px 15px;
            padding: 8px 12px;
            background: #fff;
            border: 1px solid var(--sidebar-border);
            border-radius: 6px;
            color: var(--sidebar-text) !important;
            font-size: 12.5px;
            text-decoration: none;
            transition: 0.2s;
            justify-content: center;
        }

        .view-home-btn:hover {
            background: var(--accent-bg);
        }

        .menu-header {
            padding: 15px 15px 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--sidebar-text-muted);
            letter-spacing: 0.5px;
        }

        /* Logout */
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
            color: #ef4444 !important;
            text-decoration: none;
            font-size: 13.5px;
            transition: 0.2s;
        }

        .logout-btn:hover {
            background: #fef2f2;
        }

        /* ── MAIN WRAPPER ── */
        .main-wrapper {
            margin-left: 250px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 60px;
            background: var(--topbar-bg);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--sidebar-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-dark);
            margin: 0;
        }

        /* User info */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            text-align: right;
        }

        .user-info strong {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .badge-admin,
        .badge-staff {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            background: #f3f4f6;
            color: #4b5563;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--accent-bg);
            border: 1px solid var(--sidebar-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--sidebar-text-muted);
            font-size: 14px;
        }

        .content-area {
            padding: 24px;
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


                <li class="<?= isActive('manage_contacts.php') ?>">
                    <a href="/restaurant-project/admin/manage_contacts.php"><i
                            class="fas fa-envelope"></i> Quản lý Liên hệ</a>
                </li>

                <li class="<?= isActive('BookController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/BookController.php"><i
                            class="fas fa-book"></i> Quản lý Bán sách</a>
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

                    <li class="<?= isActive('UserController.php') ?>">
                        <a href="/restaurant-project/admin/controllers/UserController.php"><i class="fas fa-users-cog"></i> Quản lý Người dùng</a>
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
                    'manage_users.php' => 'Quản Lý Nhân Sự',
                    'manage_contacts.php' => 'Quản Lý Liên Hệ',
                    'BookController.php'       => 'Quản Lý Bán Sách',
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
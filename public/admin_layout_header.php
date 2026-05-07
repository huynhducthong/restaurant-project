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
    header("Location: ../public/login.php?error=access_denied");
    exit();
}

// 2. Biến kiểm tra Admin để ẩn/hiện menu Cấu hình
$is_admin = ($user_role === 'admin' || $user_role == 1);

$current_page = basename($_SERVER['PHP_SELF']);
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
            --sidebar-border: #e2e8f0;
            --accent-color: #0f172a;
            --accent-light: #f1f5f9;
            --accent-mid: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-main: #f8fafc;
            --topbar-bg: #ffffff;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: none;
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
            box-shadow: none;
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
            width: 38px;
            height: 38px;
            background: var(--accent-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: none;
        }

        .brand-text h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            letter-spacing: 1.2px;
        }

        .brand-text span {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
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
            padding: 11px 14px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s ease;
            margin-bottom: 2px;
            gap: 11px;
        }

        .menu-list li a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .menu-list li a:hover {
            background-color: var(--accent-light);
            color: var(--accent-color);
        }

        .menu-list li.active a {
            background: var(--accent-light);
            color: var(--accent-color);
            font-weight: 600;
            box-shadow: none;
        }

        .menu-list li.active a i {
            color: var(--accent-color);
        }

        /* View Homepage pill */
        .view-home-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 14px 8px;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid var(--sidebar-border);
            border-radius: 8px;
            color: var(--text-dark) !important;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .view-home-btn:hover {
            background: var(--bg-main);
        }

        /* Menu section header */
        .menu-header {
            padding: 14px 28px 5px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 1px;
        }

        /* Logout */
        .logout-area {
            padding: 14px 14px 20px;
            border-top: 1px solid var(--sidebar-border);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 8px;
            background: #ffffff;
            color: #ef4444 !important;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border: 1px solid #fca5a5;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #fef2f2;
            border-color: #f87171;
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
            height: 68px;
            background: var(--topbar-bg);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #ede8e0;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 18px;
            background: #0f172a;
            border-radius: 4px;
        }

        /* User info */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-info {
            text-align: right;
        }

        .user-info strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .badge-admin {
            font-size: 11px;
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 4px;
            background: #0f172a;
            color: #fff;
        }

        .badge-staff {
            font-size: 11px;
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #475569;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-size: 16px;
            box-shadow: none;
        }

        /* Content */
        .content-area {
            padding: 28px 32px;
            flex-grow: 1;
        }
    </style>
</head>

<body>

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

                <li class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/admin_dashboard.php"><i class="fas fa-chart-pie"></i> Tổng
                        Quan</a>
                </li>

                <li class="<?= ($current_page == 'FoodController.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/controllers/FoodController.php"><i class="fas fa-utensils"></i>
                        Quản lý Món ăn</a>
                </li>

                <li class="<?= ($current_page == 'ComboController.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/controllers/ComboController.php"><i
                            class="fas fa-layer-group"></i> Quản lý Combo</a>
                </li>

                <li class="<?= ($current_page == 'manage_services.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/controllers/manage_services.php"><i
                            class="fas fa-concierge-bell"></i> Quản lý Dịch vụ</a>
                </li>

                <li class="<?= ($current_page == 'InventoryController.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/controllers/InventoryController.php"><i
                            class="fas fa-warehouse"></i> Quản lý Kho</a>
                </li>

                <li class="<?= ($current_page == 'ReportController.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/controllers/ReportController.php"><i
                            class="fas fa-chart-line"></i> Báo cáo & Thống kê</a>
                </li>

                <li class="<?= ($current_page == 'manage_contacts.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/manage_contacts.php"><i class="fas fa-envelope"></i> Quản lý Liên
                        hệ</a>
                </li>

                <!-- Admin Only -->
                <?php if ($is_admin): ?>
                    <div class="menu-header">Quản trị Hệ thống</div>

                    <li class="<?= ($current_page == 'manage_banners.php') ? 'active' : '' ?>">
                        <a href="/restaurant-project/admin/controllers/manage_banners.php"><i class="fas fa-image"></i> Quản
                            lý Banner</a>
                    </li>

                    <li class="<?= ($current_page == 'manage_videos.php') ? 'active' : '' ?>">
                        <a href="/restaurant-project/admin/controllers/manage_videos.php"><i class="fas fa-video"></i> Quản
                            lý Video</a>
                    </li>

                    <li class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">
                        <a href="/restaurant-project/admin/controllers/settings.php"><i class="fas fa-cog"></i> Cài Đặt
                            Chung</a>
                    </li>

                    <li class="<?= ($current_page == 'footer_settings.php') ? 'active' : '' ?>">
                        <a href="/restaurant-project/admin/footer_settings.php"><i class="fas fa-palette"></i> Cấu hình
                            Footer</a>
                    </li>

                    <li class="<?= ($current_page == 'manage_users.php') ? 'active' : '' ?>">
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
                    'manage_users.php' => 'Quản Lý Nhân Sự',
                    'manage_contacts.php' => 'Quản Lý Liên Hệ'
                ];
                echo $page_titles[$current_page] ?? 'Khu Vực Quản Trị';
                ?>
            </h4>

            <div class="user-profile">
                <div class="user-info">
                    <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?></strong>
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
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #ffffff;
            --sidebar-border: #f0ebe3;
            --accent-color: #b8862e;
            --accent-light: #fdf6e9;
            --accent-mid: #f5e8c8;
            --text-dark: #1a1612;
            --text-muted: #8a7f72;
            --bg-main: #f7f5f2;
            --topbar-bg: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.07);
        }

        * { box-sizing: border-box; }

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
            top: 0; left: 0;
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
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #b8862e, #e0b060);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(184,134,46,0.35);
        }
        .brand-text h2 {
            font-size: 15px; font-weight: 700; color: var(--text-dark); margin: 0; letter-spacing: 1.2px;
        }
        .brand-text span {
            font-size: 11px; color: var(--text-muted); font-weight: 500; letter-spacing: 0.3px;
        }

        /* Nav */
        .menu-scroll { flex-grow: 1; overflow-y: auto; padding: 16px 0 8px; }
        .menu-scroll::-webkit-scrollbar { width: 4px; }
        .menu-scroll::-webkit-scrollbar-thumb { background: var(--accent-mid); border-radius: 4px; }

        .menu-list { list-style: none; padding: 0 14px; margin: 0; }
        .menu-list li a {
            display: flex; align-items: center;
            padding: 11px 14px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s ease;
            margin-bottom: 2px;
            gap: 11px;
        }
        .menu-list li a i {
            width: 18px; text-align: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .menu-list li a:hover {
            background-color: var(--accent-light);
            color: var(--accent-color);
        }
        .menu-list li.active a {
            background: linear-gradient(135deg, var(--accent-light), #fcefd4);
            color: var(--accent-color);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(184,134,46,0.12);
        }
        .menu-list li.active a i { color: var(--accent-color); }

        /* View Homepage pill */
        .view-home-btn {
            display: flex; align-items: center; gap: 10px;
            margin: 0 14px 8px;
            padding: 10px 14px;
            background: var(--accent-light);
            border: 1.5px dashed #d4aa5a;
            border-radius: 10px;
            color: var(--accent-color) !important;
            font-size: 12.5px; font-weight: 600;
            text-decoration: none;
            letter-spacing: 0.3px;
            transition: all 0.2s;
        }
        .view-home-btn:hover { background: var(--accent-mid); }

        /* Menu section header */
        .menu-header {
            padding: 14px 28px 5px;
            font-size: 10.5px; font-weight: 700;
            text-transform: uppercase;
            color: #c4b89e;
            letter-spacing: 1.4px;
        }

        /* Logout */
        .logout-area { padding: 14px 14px 20px; border-top: 1px solid var(--sidebar-border); }
        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px;
            border-radius: 10px;
            background: #fff5f5;
            color: #d64545 !important;
            text-decoration: none;
            font-size: 13.5px; font-weight: 600;
            border: 1px solid #fdd;
            transition: all 0.2s;
        }
        .logout-btn:hover { background: #ffe8e8; border-color: #f5b8b8; }

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
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #ede8e0;
            box-shadow: var(--shadow-sm);
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-title {
            font-size: 17px; font-weight: 700; color: var(--text-dark);
            margin: 0; display: flex; align-items: center; gap: 10px;
        }
        .topbar-title::before {
            content: '';
            display: inline-block;
            width: 4px; height: 20px;
            background: linear-gradient(180deg, #b8862e, #e0b060);
            border-radius: 4px;
        }

        /* User info */
        .user-profile { display: flex; align-items: center; gap: 14px; }
        .user-info { text-align: right; }
        .user-info strong { display: block; font-size: 14px; font-weight: 600; color: var(--text-dark); }
        .badge-admin {
            font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
            background: linear-gradient(135deg, #b8862e, #e0b060);
            color: #fff; letter-spacing: 0.3px;
        }
        .badge-staff {
            font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
            background: #e6f4ea; color: #2d7a3a; letter-spacing: 0.3px;
        }
        .user-avatar {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #b8862e, #e0b060);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 16px;
            box-shadow: 0 3px 10px rgba(184,134,46,0.3);
        }

        /* Content */
        .content-area { padding: 28px 32px; flex-grow: 1; }
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
                    <a href="../index.php" target="_blank" class="view-home-btn">
                        <i class="fas fa-external-link-alt"></i> Xem Trang Chủ
                    </a>
                </li>

                <!-- Restaurant Ops -->
                <div class="menu-header">Vận hành Nhà hàng</div>

                <li class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : '' ?>">
                    <a href="../admin/admin_dashboard.php"><i class="fas fa-chart-pie"></i> Tổng Quan</a>
                </li>
                <li class="<?= ($current_page == 'manage_foods.php' || $current_page == 'add_food.php') ? 'active' : '' ?>">
                    <a href="../admin/manage_foods.php"><i class="fas fa-utensils"></i> Quản lý Món ăn</a>
                </li>
                <li class="<?= ($current_page == 'list_combos.php') ? 'active' : '' ?>">
                    <a href="../admin/list_combos.php"><i class="fas fa-layer-group"></i> Quản lý Combo</a>
                </li>
                <li class="<?= ($current_page == 'manage_videos.php') ? 'active' : '' ?>">
                    <a href="../admin/manage_videos.php"><i class="fas fa-video"></i> Quản lý Video</a>
                </li>
                <li class="<?= ($current_page == 'InventoryController.php') ? 'active' : '' ?>">
                    <a href="../admin/InventoryController.php"><i class="fas fa-warehouse"></i> Quản lý Kho</a>
                </li>
                <li class="<?= ($current_page == 'manage_services.php') ? 'active' : '' ?>">
                    <a href="../admin/manage_services.php"><i class="fas fa-concierge-bell"></i> Quản lý Dịch vụ</a>
                </li>

                <!-- Admin Only -->
                <?php if ($is_admin): ?>
                    <div class="menu-header">Quản trị Hệ thống</div>
                    <li class="<?= ($current_page == 'footer_settings.php') ? 'active' : '' ?>">
                        <a href="../admin/footer_settings.php"><i class="fas fa-palette"></i> Cấu hình Footer</a>
                    </li>
                    <li class="<?= ($current_page == 'manage_users.php') ? 'active' : '' ?>">
                        <a href="../admin/manage_users.php"><i class="fas fa-users-cog"></i> Quản lý Nhân sự</a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>

        <!-- Logout -->
        <div class="logout-area">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-wrapper">
        <header class="topbar">
            <h4 class="topbar-title">
                <?php 
                    $page_titles = [
                        'admin_dashboard.php'   => 'Bảng Điều Khiển Tổng Quan',
                        'manage_foods.php'      => 'Quản Lý Thực Đơn',
                        'list_combos.php'       => 'Quản Lý Combo',
                        'manage_videos.php'     => 'Quản Lý Video',
                        'manage_services.php'   => 'Quản Lý Dịch Vụ',
                        'footer_settings.php'   => 'Cấu Hình Giao Diện Website',
                        'InventoryController.php' => 'Quản Lý Kho Nguyên Liệu',
                        'manage_users.php'      => 'Quản Lý Tài Khoản'
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
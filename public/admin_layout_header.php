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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #11100e;
            --sidebar-hover: #1a1814;
            --accent-color: #cda45e;
            --text-main: #fdfcf9;
            --bg-main: #f4f7f6;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-main);
            overflow-x: hidden;
        }
        .admin-sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background-color: var(--sidebar-bg);
            color: var(--text-main);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-brand h2 {
            font-size: 24px; font-weight: 700; color: var(--accent-color); margin: 0;
        }
        .menu-list { list-style: none; padding: 20px 0; margin: 0; flex-grow: 1; overflow-y: auto; }
        .menu-list li a {
            display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7);
            text-decoration: none; font-size: 15px; transition: 0.3s;
        }
        .menu-list li.active a, .menu-list li a:hover {
            background-color: var(--sidebar-hover); color: var(--accent-color); border-left: 3px solid var(--accent-color);
        }
        .menu-list li a i { width: 25px; margin-right: 10px; }
        
        /* Thêm CSS cho tiêu đề nhóm Menu */
        .menu-header { padding: 15px 25px 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #555; letter-spacing: 1px; }

        .main-wrapper { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { height: 70px; background: #fff; padding: 0 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .content-area { padding: 30px; flex-grow: 1; }
        .logout-btn { margin: 20px; padding: 12px; border-radius: 8px; background: rgba(220, 53, 69, 0.1); color: #dc3545 !important; text-align: center; text-decoration: none; border: 1px solid rgba(220, 53, 69, 0.2); }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <h2>RESTAURANTLY</h2>
            <span>Admin Control Panel</span>
        </div>
        
        <ul class="menu-list">
            <li class="mb-3">
                <a href="../index.php" target="_blank" style="background: rgba(205, 164, 94, 0.1); border: 1px dashed var(--accent-color); border-radius: 8px; margin: 0 15px;">
                    <i class="fas fa-external-link-alt"></i> XEM TRANG CHỦ
                </a>
            </li>

            <div class="menu-header">Vận hành Nhà hàng</div>
            <li class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : '' ?>">
                <a href="../admin/admin_dashboard.php"><i class="fas fa-home"></i> Tổng Quan</a>
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

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <h4 class="topbar-title">
                <?php 
                    $page_titles = [
                        'admin_dashboard.php' => 'Bảng Điều Khiển Tổng Quan',
                        'manage_foods.php' => 'Quản Lý Thực Đơn',
                        'list_combos.php' => 'Quản Lý Combo',
                        'manage_videos.php' => 'Quản Lý Video',
                        'manage_services.php' => 'Quản Lý Dịch Vụ',
                        'footer_settings.php' => 'Cấu Hình Giao Diện Website',
                        'InventoryController.php' => 'Quản Lý Kho Nguyên Liệu',
                        'manage_users.php' => 'Quản Lý Tài Khoản'
                    ];
                    echo $page_titles[$current_page] ?? 'Khu Vực Quản Trị';
                ?>
            </h4>
            
            <div class="user-profile d-flex align-items-center gap-3">
                <div class="text-end">
                    <strong class="d-block"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?></strong>
                    <span class="badge <?= $is_admin ? 'bg-danger' : 'bg-success' ?>">
                        <?= $is_admin ? 'Quản trị viên' : 'Nhân viên' ?>
                    </span>
                </div>
                <div class="user-avatar" style="width:40px; height:40px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff;">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="content-area">
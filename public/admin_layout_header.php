<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Khai báo $base_url rỗng vì DocumentRoot đã trỏ trực tiếp vào thư mục dự án
$base_url = '';

// 1. KIỂM TRA PHÂN QUYỀN TRUY CẬP (Cho phép Admin và Nhân viên)
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';

// Các vai trò được phép vào Backend (Admin = 1/'admin', Staff = 2/'staff', etc.)
$allowed_roles = ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2];

if (!$user_id || !in_array($user_role, $allowed_roles)) {
    header("Location: " . BASE_URL . "/public/login.php?error=access_denied");
    exit();
}

// 2. Biến kiểm tra Admin để ẩn/hiện menu Cấu hình
$is_admin = ($user_role === 'admin' || $user_role == 1);

// 3. Khai báo $current_page sớm để dùng trong sidebar
$current_page = basename($_SERVER['PHP_SELF']);

// Hàm hỗ trợ active menu hiện tại
if (!function_exists('isActive')) {
    function isActive($path)
    {
        // Tránh lỗi match theo chuỗi con (vd: footer_settings.php chứa "settings.php")
        // - Nếu $path là tên file (không có "/") thì so khớp CHÍNH XÁC theo basename của URL
        // - Nếu $path là đoạn path có "/" thì cho phép match theo contains như cũ
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        if (strpos($path, '/') === false) {
            return (basename($uriPath) === $path) ? 'active' : '';
        }
        return (strpos($uriPath, $path) !== false) ? 'active' : '';
    }
}

// Hàm kiểm tra quyền hiển thị menu
if (!function_exists('checkMenuAccess')) {
    function checkMenuAccess($user_role, $allowed_roles) {
        if ($user_role === 'admin' || $user_role == 1) return true;
        return in_array($user_role, $allowed_roles);
    }
}

// Fetch pending counts and alerts
$pending_transfers_count = 0;
$pending_services_count = 0;
try {
    if (!isset($db)) {
        require_once __DIR__ . '/../config/database.php';
        $db = (new Database())->getConnection();
    }
    if (isset($db)) {
        $stmt_pt = $db->query("SELECT COUNT(*) FROM inventory_transfers WHERE status = 'pending'");
        $pending_transfers_count = (int)$stmt_pt->fetchColumn();

        $stmt_ps = $db->query("SELECT COUNT(*) FROM service_bookings WHERE status = 'Pending'");
        $pending_services_count = (int)$stmt_ps->fetchColumn();

        // Cảnh báo tồn kho thấp
        $stmt_low = $db->query("SELECT COUNT(*) FROM inventory i WHERE i.is_active = 1 AND i.min_stock > 0 AND IFNULL((SELECT SUM(s.quantity) FROM inventory_stocks s WHERE s.ingredient_id = i.id AND s.warehouse_id NOT IN (6, 7)), 0) <= i.min_stock");
        $low_stock_count = (int)$stmt_low->fetchColumn();

        // Cảnh báo hết hạn (7 ngày tới)
        $stmt_exp = $db->query("SELECT COUNT(*) FROM inventory i WHERE i.is_active = 1 AND i.expiry_date IS NOT NULL AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND i.expiry_date >= CURDATE() AND IFNULL((SELECT SUM(s.quantity) FROM inventory_stocks s WHERE s.ingredient_id = i.id AND s.warehouse_id NOT IN (6, 7)), 0) > 0");
        $expiry_count = (int)$stmt_exp->fetchColumn();

        $total_alerts = $low_stock_count + $expiry_count + $pending_transfers_count;
    }
} catch (Exception $e) {
    $low_stock_count = $expiry_count = $total_alerts = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="google" content="notranslate">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon -->
    <link rel="icon" href="data:,">
    <title>Quản trị hệ thống - Restaurantly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- BOOTSTRAP JS REQUIRED FOR MODALS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        :root {
            --sidebar-bg: #ffffff; /* Trắng */
            --sidebar-border: #e8e1d5;
            --topbar-bg: #ffffff;

            --accent-color: #C9A66B; /* Gold */
            --accent-green: #4F5B3A; /* Olive */
            --accent-green-light: #6A7A4E;
            
            /* Dành cho nền sáng (Sidebar) */
            --text-sidebar: #222222; /* Xám Đen */
            --text-sidebar-muted: #666666;
            
            /* Dành cho nền sáng (Main Content) */
            --text-main: #222222;
            --text-muted: #777777;
            
            --bg-main: #fcfcfc;
            
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            overflow-x: hidden;
            color: var(--text-main);
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
            background: var(--accent-green);
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
            font-size: 18px;
            flex-shrink: 0;
            border: 1px solid var(--accent-green-light);
        }

        .brand-text h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-sidebar);
            margin: 0;
            letter-spacing: 1.2px;
        }

        .brand-text span {
            font-size: 11px;
            color: var(--text-sidebar-muted);
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
            padding: 11px 14px;
            color: var(--text-sidebar-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-left: 3px solid transparent;
            border-radius: 0;
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
            background-color: #e3f2fd; /* Xanh nhạt */
            color: #0277bd; /* Chữ xanh nước biển */
        }

        .menu-list li.active > a {
            background: #bbdefb; /* Xanh đậm hơn chút */
            color: #0277bd;
            font-weight: 600;
            border-left: 3px solid #0277bd;
            border-radius: 0;
        }

        .menu-list li.active > a i {
            color: #0277bd;
        }

        /* View Homepage pill */
        .view-home-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 14px 8px;
            padding: 10px 14px;
            background: transparent;
            border: 1px solid var(--accent-green-light);
            border-radius: 0;
            color: var(--accent-color) !important;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: 0.3px;
            transition: all 0.2s;
        }

        .view-home-btn:hover {
            background: var(--accent-light);
        }

        /* Menu section header */
        .menu-header {
            padding: 14px 28px 5px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #7b8364; /* Olive nhạt hơn chút so với Xám đen */
            letter-spacing: 1.4px;
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
            border-radius: 0;
            background: rgba(214, 69, 69, 0.1);
            color: #ff6b6b !important;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            border: 1px solid rgba(214, 69, 69, 0.2);
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: rgba(214, 69, 69, 0.2);
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
            border-bottom: 1px solid var(--sidebar-border);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-sidebar);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 20px;
            background: var(--accent-color);
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
            color: var(--text-sidebar);
        }

        .badge-admin {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 0;
            background: var(--accent-color);
            color: var(--sidebar-bg);
            border: 1px solid var(--accent-color);
            letter-spacing: 0.3px;
        }

        .badge-staff {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 0;
            background: var(--accent-green);
            color: var(--accent-color);
            letter-spacing: 0.3px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: var(--accent-green);
            border: 1px solid var(--accent-color);
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
            font-size: 16px;
        }

        /* Content */
        .content-area {
            padding: 28px 32px;
            flex-grow: 1;
        }

        /* Badge Style */
        .badge-notify {
            background: #ff4757;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 0;
            margin-left: auto;
            box-shadow: 0 2px 5px rgba(255, 71, 87, 0.3);
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 71, 87, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0); }
        }
        
        /* --- NOTIFICATION BELL --- */
        .notification-wrapper { position: relative; margin-right: 20px; }
        .notification-btn { 
            background: none; border: none; font-size: 20px; color: var(--text-sidebar); position: relative; cursor: pointer;
            transition: all 0.3s; padding: 5px; border-radius: 0; display: flex; align-items: center; justify-content: center;
        }
        .notification-btn:hover { background: rgba(0,0,0,0.05); color: var(--accent-green); }
        .notification-badge {
            position: absolute; top: -2px; right: -2px; background: #ff4757; color: white;
            font-size: 10px; min-width: 16px; height: 16px; border-radius: 0;
            display: flex; align-items: center; justify-content: center; border: 2px solid #fff;
            animation: pulse-red 2s infinite;
        }
        .notification-dropdown {
            position: absolute; top: 45px; right: 0; width: 320px; background: #fff;
            border-radius: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #eee;
            display: none; z-index: 1000; overflow: hidden;
        }
        .notification-dropdown.show { display: block; animation: slideDown 0.3s ease; }
        .notify-header { padding: 12px 15px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 14px; background: #f9f9f9; }
        .notify-item { 
            padding: 12px 15px; border-bottom: 1px solid #f5f5f5; display: flex; align-items: center; gap: 12px;
            text-decoration: none; color: #333; transition: background 0.2s;
        }
        .notify-item:hover { background: #f8f9fa; color: inherit; }
        .notify-icon { width: 32px; height: 32px; border-radius: 0; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
        .notify-content { flex: 1; }
        .notify-title { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
        .notify-desc { font-size: 11px; color: #777; }
        
        .bg-warning-subtle { background-color: #fff3cd; }
        .bg-danger-subtle { background-color: #f8d7da; }
        .bg-info-subtle { background-color: #cff4fc; }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes bellShake {
            0% { transform: rotate(0); } 15% { transform: rotate(15deg); } 30% { transform: rotate(-15deg); }
            45% { transform: rotate(10deg); } 60% { transform: rotate(-10deg); } 75% { transform: rotate(5deg); }
            85% { transform: rotate(-5deg); } 100% { transform: rotate(0); }
        }
        .bell-ring { animation: bellShake 1s ease infinite; }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            .sidebar-overlay.show {
                display: block;
            }
            .mobile-menu-toggle {
                display: block !important;
            }
            .content-area {
                padding: 15px;
            }
            .topbar {
                padding: 0 15px;
            }
            .topbar-title {
                font-size: 15px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const adminSidebar = document.getElementById('adminSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if(mobileMenuToggle && adminSidebar && sidebarOverlay) {
                mobileMenuToggle.addEventListener('click', function() {
                    adminSidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                });

                sidebarOverlay.addEventListener('click', function() {
                    adminSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }
        });
    </script>
</head>

<body>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="adminSidebar">
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
                    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="view-home-btn">
                        <i class="fas fa-external-link-alt"></i> Xem Trang Chủ
                    </a>
                </li>

                <!-- Màn Hình Tác Nghiệp -->
                <?php if (checkMenuAccess($user_role, ['admin', 'manager', 'chef', 'cashier'])): ?>
                <div class="menu-header">Màn Hình Tác Nghiệp</div>
                <?php if (checkMenuAccess($user_role, ['admin', 'manager', 'cashier'])): ?>
                <li class="<?= isActive('pos.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/pos.php" target="_blank" style="color: #10b981; font-weight: 600;">
                        <i class="fas fa-cash-register"></i>
                        <span>Màn Hình Thu Ngân (POS)</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkMenuAccess($user_role, ['admin', 'manager', 'chef'])): ?>
                <li class="<?= isActive('kds.php') ?>">
                    <a href="<?= BASE_URL ?>/kds.php" target="_blank" style="color: #e0b060; font-weight: 600;">
                        <i class="fas fa-fire-burner"></i>
                        <span>Màn Hình Bếp (KDS)</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Vận hành Nhà hàng -->
                <div class="menu-header">Vận hành Nhà hàng</div>

                <li class="<?= isActive('admin_dashboard.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/admin_dashboard.php">
                        <i class="fas fa-chart-pie"></i> Tổng Quan
                    </a>
                </li>

                <?php if (checkMenuAccess($user_role, ['chef', 'waiter', 'cashier'])): ?>
                <?php 
                    $isFoodMenu = isActive('manage_themes.php') || isActive('FoodController.php') || isActive('manage_toppings.php') || ($current_page == 'ComboController.php' || $current_page == 'add_combo.php' || $current_page == 'edit_combo.php');
                ?>
                <li class="<?= $isFoodMenu ? 'active' : '' ?>">
                    <a href="javascript:void(0)" data-bs-target="#foodSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isFoodMenu ? 'true' : 'false' ?>" class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-utensils"></i> Quản Lý Thực Đơn
                        </div>
                        <div>
                            <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: auto;"></i>
                        </div>
                    </a>
                    <ul class="collapse list-unstyled <?= $isFoodMenu ? 'show' : '' ?>" id="foodSubmenu" style="background: rgba(0,0,0,0.03);">
                        <li class="<?= isActive('manage_themes.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/controllers/manage_themes.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-layer-group" style="font-size: 12px; margin-right: 6px;"></i> Chủ đề Thực đơn
                            </a>
                        </li>
                        <li class="<?= isActive('FoodController.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/controllers/FoodController.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-concierge-bell" style="font-size: 12px; margin-right: 6px;"></i> Món ăn (Món tự chọn)
                            </a>
                        </li>
                        <li class="<?= isActive('manage_toppings.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/controllers/manage_toppings.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-cheese" style="font-size: 12px; margin-right: 6px;"></i> Topping / Tùy chọn
                            </a>
                        </li>
                        <li class="<?= ($current_page == 'ComboController.php' || $current_page == 'add_combo.php' || $current_page == 'edit_combo.php') ? 'active' : '' ?>">
                            <a href="<?= BASE_URL ?>/admin/controllers/ComboController.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-cubes" style="font-size: 12px; margin-right: 6px;"></i> Set Menu (Combo)
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (checkMenuAccess($user_role, ['waiter', 'cashier'])): ?>
                <?php 
                    $isServiceMenu = isActive('manage_services.php') || isActive('manage_events.php') || isActive('manage_decors.php') || isActive('manage_bespoke.php') || isActive('manage_tables.php');
                ?>
                <li class="<?= $isServiceMenu ? 'active' : '' ?>">
                    <a href="javascript:void(0)" data-bs-target="#servicesSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isServiceMenu ? 'true' : 'false' ?>" class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-concierge-bell"></i>
                            <span>Quản lý Dịch vụ</span>
                        </div>
                        <div>
                            <?php if ($pending_services_count > 0): ?>
                                <span class="badge-notify d-inline-block position-static ms-0 me-2"><?= $pending_services_count ?></span>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: auto;"></i>
                        </div>
                    </a>
                    <ul class="collapse list-unstyled <?= $isServiceMenu ? 'show' : '' ?>" id="servicesSubmenu" style="background: rgba(0,0,0,0.03);">
                        <li class="<?= isActive('manage_services.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/controllers/manage_services.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-list-ul" style="font-size: 12px; margin-right: 6px;"></i> Danh sách Đơn đặt
                            </a>
                        </li>
                        <li class="<?= isActive('manage_tables.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/controllers/manage_tables.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-chair" style="font-size: 12px; margin-right: 6px;"></i> Quản lý Bàn
                            </a>
                        </li>
                        <li class="<?= isActive('manage_events.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/manage_events.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-glass-cheers" style="font-size: 12px; margin-right: 6px;"></i> Loại hình Sự Kiện
                            </a>
                        </li>
                        <li class="<?= isActive('manage_decors.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/manage_decors.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-gift" style="font-size: 12px; margin-right: 6px;"></i> Gói Trang Trí
                            </a>
                        </li>
                        <li class="<?= isActive('manage_bespoke.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/manage_bespoke.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-cogs" style="font-size: 12px; margin-right: 6px;"></i> Cấu hình Bespoke
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (checkMenuAccess($user_role, ['chef'])): ?>
                <li class="<?= ($current_page == 'manage_inventory.php') ? 'active' : isActive('InventoryController.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/controllers/InventoryController.php">
                        <i class="fas fa-warehouse"></i>
                        <span>Quản lý Kho</span>
                        <?php if ($pending_transfers_count > 0): ?>
                            <span class="badge-notify"><?= $pending_transfers_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (checkMenuAccess($user_role, ['chef', 'cashier'])): ?>
                <li class="<?= isActive('ReportController.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/controllers/ReportController.php">
                        <i class="fas fa-chart-line"></i> Báo cáo & Thống kê
                    </a>
                </li>
                <li class="<?= isActive('manage_expenses.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/manage_expenses.php">
                        <i class="fas fa-file-invoice-dollar"></i> Quản lý Chi Phí
                    </a>
                </li>
                <?php endif; ?>


                <?php if (checkMenuAccess($user_role, ['chef'])): ?>
                <?php 
                    $isChefMenu = isActive('manage_chefs.php') || isActive('manage_chef_reviews.php');
                ?>
                <li class="<?= $isChefMenu ? 'active' : '' ?> chef-menu-toggle">
                    <a href="javascript:void(0)" data-bs-target="#chefSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isChefMenu ? 'true' : 'false' ?>" class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-users"></i> Quản lý Đầu bếp
                        </div>
                        <div>
                            <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: auto;"></i>
                        </div>
                    </a>
                    <ul class="collapse list-unstyled <?= $isChefMenu ? 'show' : '' ?>" id="chefSubmenu" style="background: rgba(0,0,0,0.03);">
                        <li class="<?= isActive('manage_chefs.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/manage_chefs.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-users" style="font-size: 12px; margin-right: 6px;"></i> Danh sách Đầu bếp
                            </a>
                        </li>
                        <li class="<?= isActive('manage_chef_reviews.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/manage_chef_reviews.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-comments" style="font-size: 12px; margin-right: 6px;"></i> Đánh giá Đầu bếp
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (checkMenuAccess($user_role, ['waiter', 'cashier'])): ?>
                <li class="<?= isActive('manage_contacts.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/manage_contacts.php">
                        <i class="fas fa-envelope"></i> Quản lý Liên hệ
                    </a>
                </li>
                
                <?php 
                    $isChatMenu = isActive('chat_console.php') || isActive('bot_training.php') || isActive('chat_analytics.php');
                ?>
                <li class="<?= $isChatMenu ? 'active' : '' ?>">
                    <a href="javascript:void(0)" data-bs-target="#chatSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isChatMenu ? 'true' : 'false' ?>" class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-comments"></i>
                            <span>Hỗ trợ Khách hàng</span>
                        </div>
                        <div>
                            <span class="badge-notify chat-waiting-badge position-static ms-0 me-2" style="display:none;">0</span>
                            <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: auto;"></i>
                        </div>
                    </a>
                    <ul class="collapse list-unstyled <?= $isChatMenu ? 'show' : '' ?>" id="chatSubmenu" style="background: rgba(0,0,0,0.03);">
                        <li class="<?= isActive('chat_console.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/chat_console.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-headset" style="font-size: 12px; margin-right: 6px;"></i> Trò chuyện trực tuyến
                            </a>
                        </li>
                        <li class="<?= isActive('bot_training.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/bot_training.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-robot" style="font-size: 12px; margin-right: 6px;"></i> Quản lý kịch bản Bot
                            </a>
                        </li>
                        <li class="<?= isActive('chat_analytics.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/chat_analytics.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-chart-bar" style="font-size: 12px; margin-right: 6px;"></i> Thống kê Chat
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                <div class="menu-header">Cấu hình</div>



                <li class="<?= isActive('manage_users.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/manage_users.php">
                        <i class="fas fa-users-cog"></i> Quản lý Nhân sự
                    </a>
                </li>

                <li class="<?= isActive('manage_banners.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/controllers/manage_banners.php">
                        <i class="fas fa-image"></i> Quản lý Banner
                    </a>
                </li>



                <li class="<?= isActive('manage_about.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/manage_about.php">
                        <i class="fas fa-newspaper"></i> Quản lý Tin tức                    </a>
                </li>

                                <li class="<?= isActive('settings.php') ?>">
                    <a href="<?= BASE_URL ?>/admin/controllers/settings.php">
                        <i class="fas fa-cog"></i> Cài Đặt Chung
                    </a>
                </li>




                <?php $isCustomerMenu = isActive('UserController.php') || isActive('manage_milestones.php'); ?>
                <li class="<?= $isCustomerMenu ? 'active' : '' ?> customer-menu-toggle">
                    <a href="javascript:void(0)" data-bs-target="#customerSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isCustomerMenu ? 'true' : 'false' ?>" class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-users-cog"></i> Quản lý Khách hàng
                        </div>
                        <div>
                            <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: auto;"></i>
                        </div>
                    </a>
                    <ul class="collapse list-unstyled <?= $isCustomerMenu ? 'show' : '' ?>" id="customerSubmenu" style="background: rgba(0,0,0,0.03);">
                        <li class="<?= isActive('UserController.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/controllers/UserController.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-users" style="font-size: 12px; margin-right: 6px;"></i> Danh sách Khách hàng
                            </a>
                        </li>
                        <li class="<?= isActive('manage_milestones.php') ?>">
                            <a href="<?= BASE_URL ?>/admin/manage_milestones.php" style="padding-left: 42px; font-size: 12.5px;">
                                <i class="fas fa-award" style="font-size: 12px; margin-right: 6px;"></i> Đặc quyền / Cột mốc
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>



        </ul>

        <!-- Logout -->
        <div class="logout-area">
            <a href="<?= BASE_URL ?>/public/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="mobile-menu-toggle d-none" id="mobileMenuToggle" style="background:none; border:none; font-size:20px; padding:0; color:var(--text-sidebar); cursor:pointer;">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="topbar-title">
                <?php
                $page_titles = [
                    'admin_dashboard.php'     => 'Bảng Điều Khiển Tổng Quan',
                    'FoodController.php'      => 'Quản Lý Thực Đơn',
                    'manage_toppings.php'     => 'Quản Lý Topping / Tùy Chọn',
                    'ComboController.php'     => 'Quản Lý Set',
                    'list_combos.php'         => 'Quản Lý Set',
                    'add_combo.php'           => 'Thêm Set',
                    'edit_combo.php'          => 'Chỉnh Sửa Set',
                    'manage_services.php'     => 'Quản Lý Dịch Vụ',
                    'manage_bespoke.php'      => 'Cấu Hình Bespoke',
                    'InventoryController.php' => 'Quản Lý Kho Nguyên Liệu',
                    'manage_inventory.php'    => 'Quản Lý Kho Nguyên Liệu',
                    'ReportController.php'    => 'Báo Cáo & Thống Kê Kho',
                    'manage_chefs.php'        => 'Quản Lý Đầu Bếp',
                    'manage_chef_reviews.php' => 'Quản Lý Đánh Giá Đầu Bếp',
                    'manage_banners.php'      => 'Quản Lý Banner',
                    'manage_videos.php'       => 'Quản Lý Video',
                    'settings.php'            => 'Cài Đặt Hệ Thống Chung',
                    'footer_settings.php'     => 'Cấu Hình Giao Diện Footer',
                    'manage_users.php'        => 'Quản Lý Nhân Sự',
                    'manage_milestones.php'   => 'Quản Lý Cột Mốc Đặc Quyền',
                    'manage_about.php'        => 'Quản Lý Tin Tức',
                    'manage_contacts.php'     => 'Quản Lý Liên Hệ',

                    'UserController.php'      => 'Quản Lý Người Dùng',
                    'chat_console.php'        => 'Trò Chuyện Trực Tuyến',
                    'chat_analytics.php'      => 'Thống Kê Trò Chuyện',
                ];
                echo $page_titles[$current_page] ?? 'Khu Vực Quản Trị';
                ?>
            </h4>
            </div>

            <div class="d-flex align-items-center">
                <!-- NOTIFICATION BELL -->
                <div class="notification-wrapper">
                    <button class="notification-btn <?= ($total_alerts > 0) ? 'bell-ring' : '' ?>" onclick="toggleNotify()">
                        <i class="fas fa-bell"></i>
                        <?php if ($total_alerts > 0): ?>
                            <span class="notification-badge"><?= $total_alerts ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notification-dropdown shadow-lg" id="notifyDropdown">
                        <div class="notify-header d-flex justify-content-between">
                            <span>Thông báo thông minh</span>
                            <span class="badge bg-danger"><?= $total_alerts ?> mới</span>
                        </div>
                        <div class="notify-body">
                            <?php if ($total_alerts == 0): ?>
                                <div class="p-4 text-center text-muted small">
                                    <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50"></i>
                                    <p class="m-0">Tuyệt vời! Không có cảnh báo nào.</p>
                                </div>
                            <?php else: ?>
                                <?php if ($pending_transfers_count > 0): ?>
                                    <a href="<?= BASE_URL ?>/admin/controllers/InventoryController.php?tab=transfers" class="notify-item">
                                        <div class="notify-icon bg-warning-subtle text-warning"><i class="fas fa-exchange-alt"></i></div>
                                        <div class="notify-content">
                                            <div class="notify-title">Chuyển kho chờ duyệt</div>
                                            <div class="notify-desc">Có <?= $pending_transfers_count ?> lệnh cần bạn xác nhận ngay.</div>
                                        </div>
                                    </a>
                                <?php endif; ?>

                                <?php if ($low_stock_count > 0): ?>
                                    <a href="<?= BASE_URL ?>/admin/controllers/ReportController.php?action=low_stock" class="notify-item">
                                        <div class="notify-icon bg-danger-subtle text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                                        <div class="notify-content">
                                            <div class="notify-title">Tồn kho sắp hết</div>
                                            <div class="notify-desc"><?= $low_stock_count ?> nguyên liệu dưới định mức tối thiểu.</div>
                                        </div>
                                    </a>
                                <?php endif; ?>

                                <?php if ($expiry_count > 0): ?>
                                    <a href="<?= BASE_URL ?>/admin/controllers/InventoryController.php?tab=all" class="notify-item">
                                        <div class="notify-icon bg-info-subtle text-info"><i class="fas fa-clock"></i></div>
                                        <div class="notify-content">
                                            <div class="notify-title">Hàng sắp hết hạn</div>
                                            <div class="notify-desc">Có <?= $expiry_count ?> mặt hàng sẽ hết hạn trong 7 ngày tới.</div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 text-center border-top">
                            <a href="<?= BASE_URL ?>/admin/admin_dashboard.php" class="small text-decoration-none fw-bold">Xem tất cả Dashboard</a>
                        </div>
                    </div>
                </div>

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
            </div>
        </header>

        <div class="content-area">
            <script>
                function toggleNotify() {
                    const dropdown = document.getElementById('notifyDropdown');
                    dropdown.classList.toggle('show');
                }
                window.addEventListener('click', function(event) {
                    if (!event.target.closest('.notification-wrapper')) {
                        const dropdown = document.getElementById('notifyDropdown');
                        if (dropdown && dropdown.classList.contains('show')) {
                            dropdown.classList.remove('show');
                        }
                    }
                });

                // Global Chat Alert Polling
                setInterval(() => {
                    fetch('/admin/api/chat_admin_api.php?action=check_alerts')
                    .then(res => res.json())
                    .then(data => {
                        const badges = document.querySelectorAll('.chat-waiting-badge');
                        badges.forEach(b => {
                            b.innerText = data.waiting_count;
                            b.style.display = data.waiting_count > 0 ? 'inline-block' : 'none';
                        });
                        
                        const countLbl = document.getElementById('waitingCount');
                        if (countLbl) {
                            countLbl.innerText = data.waiting_count + ' chờ';
                        }

                        if(data.waiting_count > 0 && !window.lastTingPlayed) {
                            let audio = new Audio('/public/assets/audio/ting.mp3');
                            audio.play().catch(e => {});
                            window.lastTingPlayed = true; 
                        } else if (data.waiting_count === 0) {
                            window.lastTingPlayed = false;
                        }
                    }).catch(e => {});
                }, 3000);

            </script>

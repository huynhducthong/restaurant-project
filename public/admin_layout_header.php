<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// 1. KIỂM TRA PHÂN QUYỀN TRUY CẬP (Cho phép Admin và Nhân viên)
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';

// Các vai trò được phép vào Backend (Admin = 1/'admin', Staff = 2/'staff', etc.)
$allowed_roles = ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2];

if (!$user_id || !in_array($user_role, $allowed_roles)) {
    header("Location: /restaurant-project/public/login.php?error=access_denied");
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
        return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
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
        $stmt_low = $db->query("SELECT COUNT(*) FROM inventory i WHERE i.is_active = 1 AND i.min_stock > 0 AND IFNULL((SELECT SUM(s.quantity) FROM inventory_stocks s WHERE s.ingredient_id = i.id), 0) <= i.min_stock");
        $low_stock_count = (int)$stmt_low->fetchColumn();

        // Cảnh báo hết hạn (7 ngày tới)
        $stmt_exp = $db->query("SELECT COUNT(*) FROM inventory WHERE is_active = 1 AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND expiry_date >= CURDATE()");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị hệ thống - Restaurantly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- BOOTSTRAP JS REQUIRED FOR MODALS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.07);
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
            color: var(--text-dark);
            margin: 0;
            letter-spacing: 1.2px;
        }

        .brand-text span {
            font-size: 11px;
            color: var(--text-muted);
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
            background: linear-gradient(135deg, var(--accent-light), #fcefd4);
            color: var(--accent-color);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(184, 134, 46, 0.12);
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
            background: var(--accent-light);
            border: 1.5px dashed #d4aa5a;
            border-radius: 10px;
            color: var(--accent-color) !important;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: 0.3px;
            transition: all 0.2s;
        }

        .view-home-btn:hover {
            background: var(--accent-mid);
        }

        /* Menu section header */
        .menu-header {
            padding: 14px 28px 5px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #c4b89e;
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
            border-radius: 10px;
            background: #fff5f5;
            color: #d64545 !important;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            border: 1px solid #fdd;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #ffe8e8;
            border-color: #f5b8b8;
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
            height: 20px;
            background: linear-gradient(180deg, #b8862e, #e0b060);
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
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            background: linear-gradient(135deg, #b8862e, #e0b060);
            color: #fff;
            letter-spacing: 0.3px;
        }

        .badge-staff {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            background: #e6f4ea;
            color: #2d7a3a;
            letter-spacing: 0.3px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #b8862e, #e0b060);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
            box-shadow: 0 3px 10px rgba(184, 134, 46, 0.3);
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
            border-radius: 50px;
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
            background: none; border: none; font-size: 20px; color: #8a7f72; position: relative; cursor: pointer;
            transition: all 0.3s; padding: 5px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        }
        .notification-btn:hover { background: rgba(0,0,0,0.05); color: var(--accent-color); }
        .notification-badge {
            position: absolute; top: -2px; right: -2px; background: #ff4757; color: white;
            font-size: 10px; min-width: 16px; height: 16px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; border: 2px solid #fff;
            animation: pulse-red 2s infinite;
        }
        .notification-dropdown {
            position: absolute; top: 45px; right: 0; width: 320px; background: #fff;
            border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #eee;
            display: none; z-index: 1000; overflow: hidden;
        }
        .notification-dropdown.show { display: block; animation: slideDown 0.3s ease; }
        .notify-header { padding: 12px 15px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 14px; background: #f9f9f9; }
        .notify-item { 
            padding: 12px 15px; border-bottom: 1px solid #f5f5f5; display: flex; align-items: center; gap: 12px;
            text-decoration: none; color: #333; transition: background 0.2s;
        }
        .notify-item:hover { background: #f8f9fa; color: inherit; }
        .notify-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
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

                <!-- Vận hành Nhà hàng -->
                <div class="menu-header">Vận hành Nhà hàng</div>

                <li class="<?= isActive('admin_dashboard.php') ?>">
                    <a href="/restaurant-project/admin/admin_dashboard.php">
                        <i class="fas fa-chart-pie"></i> Tổng Quan
                    </a>
                </li>

                <li class="<?= isActive('FoodController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/FoodController.php">
                        <i class="fas fa-utensils"></i> Quản lý Món ăn
                    </a>
                </li>

                <li class="<?= ($current_page == 'list_combos.php' || $current_page == 'add_combo.php' || $current_page == 'edit_combo.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/list_combos.php">
                        <i class="fas fa-layer-group"></i> Quản lý Combo
                    </a>
                </li>

                <li class="<?= isActive('manage_services.php') ?>">
                    <a href="/restaurant-project/admin/controllers/manage_services.php">
                        <i class="fas fa-concierge-bell"></i>
                        <span>Quản lý Dịch vụ</span>
                        <?php if ($pending_services_count > 0): ?>
                            <span class="badge-notify"><?= $pending_services_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="<?= ($current_page == 'manage_inventory.php') ? 'active' : isActive('InventoryController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/InventoryController.php">
                        <i class="fas fa-warehouse"></i>
                        <span>Quản lý Kho</span>
                        <?php if ($pending_transfers_count > 0): ?>
                            <span class="badge-notify"><?= $pending_transfers_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="<?= isActive('ReportController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/ReportController.php">
                        <i class="fas fa-chart-line"></i> Báo cáo & Thống kê
                    </a>
                </li>

                <li class="<?= ($current_page == 'manage_videos.php') ? 'active' : '' ?>">
                    <a href="/restaurant-project/admin/manage_videos.php">
                        <i class="fas fa-video"></i> Quản lý Video
                    </a>
                </li>

                <li class="<?= isActive('manage_chefs.php') ?>">
                    <a href="/restaurant-project/admin/manage_chefs.php">
                        <i class="fas fa-users"></i> Quản lý Đầu bếp
                    </a>
                </li>

                <li class="<?= isActive('manage_contacts.php') ?>">
                    <a href="/restaurant-project/admin/manage_contacts.php">
                        <i class="fas fa-envelope"></i> Quản lý Liên hệ
                    </a>
                </li>

                <!-- Chỉ Admin mới thấy phần Cấu hình -->
                <?php if ($is_admin): ?>
                <div class="menu-header">Cấu hình</div>

                <li class="<?= isActive('manage_users.php') ?>">
                    <a href="/restaurant-project/admin/manage_users.php">
                        <i class="fas fa-users-cog"></i> Quản lý Nhân sự
                    </a>
                </li>

                <li class="<?= isActive('manage_banners.php') ?>">
                    <a href="/restaurant-project/admin/controllers/manage_banners.php">
                        <i class="fas fa-image"></i> Quản lý Banner
                    </a>
                </li>

                <li class="<?= isActive('manage_about.php') ?>">
                    <a href="/restaurant-project/admin/manage_about.php">
                        <i class="fas fa-newspaper"></i> Quản lý tin tức
                    </a>
                </li>

                <li class="<?= isActive('settings.php') ?>">
                    <a href="/restaurant-project/admin/controllers/settings.php">
                        <i class="fas fa-cog"></i> Cài Đặt Chung
                    </a>
                </li>

                <li class="<?= isActive('footer_settings.php') ?>">
                    <a href="/restaurant-project/admin/footer_settings.php">
                        <i class="fas fa-palette"></i> Cấu hình Footer
                    </a>
                </li>

                <li class="<?= isActive('manage_shifts.php') ?>">
                    <a href="/restaurant-project/admin/manage_shifts.php">
                        <i class="fas fa-calendar-alt"></i> Chia lịch làm việc
                    </a>
                </li>

                <li class="<?= isActive('manage_attendance.php') ?>">
                    <a href="/restaurant-project/admin/views/attendance/manage_attendance.php">
                        <i class="fas fa-user-check"></i> Kiểm tra Chấm công
                    </a>
                </li>

                <li class="<?= isActive('manage_payroll.php') ?>">
                    <a href="/restaurant-project/admin/manage_payroll.php">
                        <i class="fas fa-file-invoice-dollar"></i> Quản lý Bảng lương
                    </a>
                </li>

                <li class="<?= isActive('UserController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/UserController.php">
                        <i class="fas fa-users-cog"></i> Quản lý Người dùng
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                <div class="menu-header">Nhân sự & Chấm công</div>
                <li class="<?= isActive('employee_dashboard.php') ?>">
                    <a href="/restaurant-project/views/client/employee_dashboard.php">
                        <i class="fas fa-clock"></i> Lịch làm & Chấm công
                    </a>
                </li>
                <?php endif; ?>

        </ul>

        <!-- Logout -->
        <div class="logout-area">
            <a href="/restaurant-project/public/logout.php" class="logout-btn">
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
                    'admin_dashboard.php'     => 'Bảng Điều Khiển Tổng Quan',
                    'FoodController.php'      => 'Quản Lý Thực Đơn',
                    'list_combos.php'         => 'Quản Lý Combo',
                    'add_combo.php'           => 'Thêm Combo',
                    'edit_combo.php'          => 'Chỉnh Sửa Combo',
                    'manage_services.php'     => 'Quản Lý Dịch Vụ',
                    'InventoryController.php' => 'Quản Lý Kho Nguyên Liệu',
                    'manage_inventory.php'    => 'Quản Lý Kho Nguyên Liệu',
                    'ReportController.php'    => 'Báo Cáo & Thống Kê Kho',
                    'manage_chefs.php'        => 'Quản Lý Đầu Bếp',
                    'manage_banners.php'      => 'Quản Lý Banner',
                    'manage_videos.php'       => 'Quản Lý Video',
                    'settings.php'            => 'Cài Đặt Hệ Thống Chung',
                    'footer_settings.php'     => 'Cấu Hình Giao Diện Footer',
                    'manage_users.php'        => 'Quản Lý Nhân Sự',
                    'manage_about.php'        => 'Quản Lý Tin Tức',
                    'manage_contacts.php'     => 'Quản Lý Liên Hệ',
                    'manage_shifts.php'       => 'Chia Lịch Làm Việc',
                    'manage_attendance.php'   => 'Kiểm Tra Chấm Công',
                    'manage_payroll.php'      => 'Quản Lý Bảng Lương',
                    'UserController.php'      => 'Quản Lý Người Dùng',
                ];
                echo $page_titles[$current_page] ?? 'Khu Vực Quản Trị';
                ?>
            </h4>

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
                                    <a href="/restaurant-project/admin/controllers/InventoryController.php?tab=transfers" class="notify-item">
                                        <div class="notify-icon bg-warning-subtle text-warning"><i class="fas fa-exchange-alt"></i></div>
                                        <div class="notify-content">
                                            <div class="notify-title">Chuyển kho chờ duyệt</div>
                                            <div class="notify-desc">Có <?= $pending_transfers_count ?> lệnh cần bạn xác nhận ngay.</div>
                                        </div>
                                    </a>
                                <?php endif; ?>

                                <?php if ($low_stock_count > 0): ?>
                                    <a href="/restaurant-project/admin/controllers/ReportController.php?action=low_stock" class="notify-item">
                                        <div class="notify-icon bg-danger-subtle text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                                        <div class="notify-content">
                                            <div class="notify-title">Tồn kho sắp hết</div>
                                            <div class="notify-desc"><?= $low_stock_count ?> nguyên liệu dưới định mức tối thiểu.</div>
                                        </div>
                                    </a>
                                <?php endif; ?>

                                <?php if ($expiry_count > 0): ?>
                                    <a href="/restaurant-project/admin/controllers/InventoryController.php?tab=all" class="notify-item">
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
                            <a href="/restaurant-project/admin/admin_dashboard.php" class="small text-decoration-none fw-bold">Xem tất cả Dashboard</a>
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
            </script>

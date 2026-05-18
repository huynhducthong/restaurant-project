<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

/* =========================================================
   KIỂM TRA ĐĂNG NHẬP & PHÂN QUYỀN
========================================================= */

$user_id   = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';

$allowed_roles = ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2];

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
        $current_page = basename($_SERVER['PHP_SELF']);
        return $current_page === $path ? 'active' : '';
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

// Fetch pending transfers count for badge
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

        // THÊM: Cảnh báo tồn kho thấp
        $stmt_low = $db->query("SELECT COUNT(*) FROM inventory i WHERE i.is_active = 1 AND i.min_stock > 0 AND IFNULL((SELECT SUM(s.quantity) FROM inventory_stocks s WHERE s.ingredient_id = i.id), 0) <= i.min_stock");
        $low_stock_count = (int)$stmt_low->fetchColumn();

        // THÊM: Cảnh báo hết hạn (7 ngày tới)
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
            border-left: 3px solid var(--accent);
            border-radius: 0 10px 10px 0;
            margin-left: -12px;
            padding-left: 23px;
        }

        .menu-list li a {
            transition: background 0.2s, color 0.2s;
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
            background: none; border: none; font-size: 20px; color: #555; position: relative; cursor: pointer;
            transition: all 0.3s; padding: 5px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        }
        .notification-btn:hover { background: rgba(0,0,0,0.05); color: var(--accent); }
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
                    <span>Quản lý Dịch vụ</span>
                    <?php if ($pending_services_count > 0): ?>
                        <span class="badge-notify"><?= $pending_services_count ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="<?= isActive('InventoryController.php') ?>">
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
                        Quản lý Tin tức
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

                <li class="<?= isActive('manage_shifts.php') ?>">
                    <a href="/restaurant-project/admin/manage_shifts.php">
                        <i class="fas fa-calendar-alt"></i>
                        Chia lịch làm việc
                    </a>
                </li>

                <li class="<?= isActive('manage_attendance.php') ?>">
                    <a href="/restaurant-project/admin/views/attendance/manage_attendance.php">
                        <i class="fas fa-user-check"></i>
                        Kiểm tra Chấm công
                    </a>
                </li>

                <li class="<?= isActive('manage_payroll.php') ?>">
                    <a href="/restaurant-project/admin/manage_payroll.php">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Quản lý Bảng lương
                    </a>
                </li>

                <li class="<?= isActive('UserController.php') ?>">
                    <a href="/restaurant-project/admin/controllers/UserController.php">
                        <i class="fas fa-users-cog"></i>
                        Quản lý Người dùng
                    </a>
                </li>

            <?php endif; ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                <div class="menu-header">Nhân sự & Chấm công</div>
                <li>
                    <a href="/restaurant-project/views/client/employee_dashboard.php">
                        <i class="fas fa-clock"></i>
                        Lịch làm & Chấm công
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
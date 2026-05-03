<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * KIỂM TRA QUYỀN TRUY CẬP
 * Cần kiểm tra cả 'admin' (chuỗi) và 1 (số) để tương thích với database
 */
$user_role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || ($user_role !== 'admin' && $user_role != 1)) {
    header("Location: login.php"); 
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Restaurantly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #f4f7f6; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background-color: #2c3e50; color: #ecf0f1; position: fixed; height: 100%; display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-header { padding: 20px; text-align: center; background-color: #1a252f; font-size: 1.2rem; font-weight: bold; color: #cda45e; }
        .sidebar-menu { list-style: none; padding: 10px 0; flex-grow: 1; }
        .sidebar-menu li { padding: 0; transition: 0.3s; }
        .sidebar-menu li a { padding: 15px 25px; color: #ecf0f1; text-decoration: none; display: flex; align-items: center; gap: 15px; width: 100%; }
        
        /* Hiệu ứng Active & Hover */
        .sidebar-menu li.active { background-color: #34495e; border-left: 5px solid #cda45e; }
        .sidebar-menu li.active a { color: #cda45e; }
        .sidebar-menu li:hover { background-color: #3e5871; }
        
        .logout-item { border-top: 1px solid #3e4f5f; margin-top: auto; }

        /* MAIN CONTENT AREA */
        .main-content { margin-left: 260px; flex-grow: 1; padding: 30px; width: calc(100% - 260px); }
        header.content-header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 30px; margin: -30px -30px 30px -30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">RESTO ADMIN</div>
        <ul class="sidebar-menu">
    <li>
        <a href="/restaurant-project/admin/admin_dashboard.php"><i class="fas fa-home"></i> Tổng quan</a>
    </li>
    <li>
        <a href="/restaurant-project/index.php"><i class="fas fa-globe"></i> Xem Trang chủ</a>
    </li>
    
    <li class="<?= (strpos($_SERVER['REQUEST_URI'], 'FoodController.php') !== false) ? 'active' : '' ?>">
        <a href="/restaurant-project/admin/controllers/FoodController.php"><i class="fas fa-utensils"></i> Quản lý món ăn</a>
    </li>

    <li>
        <a href="/restaurant-project/admin/controllers/settings.php"><i class="fas fa-concierge-bell"></i> Cài Đặt Chung</a>
    </li>

    <li>
        <a href="/restaurant-project/admin/controllers/manage_banners.php"><i class="fas fa-image"></i> Quản lý Banner</a>
    </li>

    <li class="<?= (strpos($_SERVER['REQUEST_URI'], 'ComboController.php') !== false) ? 'active' : '' ?>">
        <a href="/restaurant-project/admin/controllers/ComboController.php"><i class="fas fa-layer-group"></i> Quản lý Combo</a>
    </li>
    
    <li>
        <a href="/restaurant-project/admin/controllers/manage_videos.php"><i class="fas fa-video"></i> Quản lý Video</a>
    </li>
    
    <li>
        <a href="/restaurant-project/admin/controllers/InventoryController.php"><i class="fas fa-warehouse"></i> Quản lý Kho</a>
    </li>
    
    <li>
        <a href="/restaurant-project/admin/controllers/manage_services.php"><i class="fas fa-concierge-bell"></i> Quản lý Dịch vụ</a>
    </li>

    <li class="logout-item">
        <a href="/restaurant-project/admin/logout.php" style="color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
    </li>
</ul>
    </nav>

    <div class="main-content">
        <header class="content-header">
            <h2>Bảng Điều Khiển</h2>
            <div class="user-info d-flex align-items-center gap-2">
                <span>Xin chào, <strong><?= $_SESSION['user_name'] ?? 'Admin'; ?></strong></span>
                <i class="fas fa-user-circle fa-2x text-primary"></i>
            </div>
        </header>
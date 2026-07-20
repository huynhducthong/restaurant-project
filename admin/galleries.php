<?php
session_start();
require_once __DIR__ . '/auth_check.php';

// Kiểm tra quyền (chỉ admin/manager được truy cập)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager', 'chef'])) {
    header("Location: /public/login.php");
    exit;
}

require_once __DIR__ . '/controllers/GalleryController.php';

$controller = new GalleryController();

<?php
// Tự động thêm charset để báo lỗi tiếng Việt nếu cần
header('Content-Type: text/html; charset=utf-8');

// Load config
require_once __DIR__ . '/config/database.php';

// Router: Phân tích đường dẫn
$request = $_SERVER['REQUEST_URI'] ?? '';

// Loại bỏ query string (?id=1)
$path = strtok($request, '?');

// Loại bỏ BASE_URL (Ví dụ: /restaurant-project)
if (defined('BASE_URL') && BASE_URL !== '') {
    if (strpos($path, BASE_URL) === 0) {
        $path = substr($path, strlen(BASE_URL));
    }
}

// Xóa các dấu / thừa
$path = trim($path, '/');

// Loại bỏ đuôi .php nếu có để tránh việc bị nhân đôi thành .php.php
if (preg_match('/\.php$/', $path)) {
    $path = preg_replace('/\.php$/', '', $path);
}

// 1. Nếu là trang chủ
if ($path === '' || $path === 'index' || $path === 'index.php') {
    require_once __DIR__ . '/public/home.php';
    exit;
}

// 2. Ưu tiên tìm trong thư mục public/ trước (các file ĐÃ DỜI)
$public_file = __DIR__ . '/public/' . $path . '.php';
if (file_exists($public_file)) {
    require_once $public_file;
    exit;
}

// 3. Nếu không có, tìm trong thư mục gốc (các file CHƯA DỜI)
$root_file = __DIR__ . '/' . $path . '.php';
if (file_exists($root_file)) {
    require_once $root_file;
    exit;
}

// 4. Nếu không tìm thấy ở cả 2 nơi => 404
http_response_code(404);
echo "<h1>404 - Không tìm thấy trang</h1>";
echo "<p>Đường dẫn <b>/{$path}</b> không tồn tại trên hệ thống.</p>";
echo "<a href='" . (defined('BASE_URL') ? BASE_URL : '') . "/'>Quay về Trang chủ</a>";
exit;

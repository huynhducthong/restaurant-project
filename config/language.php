<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra nếu có tham số ?lang= được truyền lên URL
if (isset($_GET['lang'])) {
    $lang = strtolower($_GET['lang']);
    // Chỉ cho phép en hoặc vn
    if (in_array($lang, ['en', 'vn'])) {
        $_SESSION['lang'] = $lang;
    }
    
    // Refresh trang để xóa tham số lang khỏi URL (tùy chọn, để URL đẹp hơn)
    if (isset($_SERVER["REQUEST_URI"])) {
        $url = strtok($_SERVER["REQUEST_URI"], '?');
        $query = $_GET;
        unset($query['lang']);
        $query_string = http_build_query($query);
        $redirect_url = $url . ($query_string ? '?' . $query_string : '');
        header("Location: $redirect_url");
        exit;
    }
}

// 2. Xác định ngôn ngữ hiện tại (mặc định là vn)
$current_lang = $_SESSION['lang'] ?? 'vn';

// 3. Load file từ điển tương ứng
$lang_file = __DIR__ . "/../lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    $dictionary = require $lang_file;
} else {
    $dictionary = [];
}

// 4. Tạo hàm dịch thuật toàn cục
if (!function_exists('__')) {
    function __($key) {
        global $dictionary;
        // Trả về từ đã dịch, hoặc trả về chính key nếu không tìm thấy
        return $dictionary[$key] ?? $key;
    }
}

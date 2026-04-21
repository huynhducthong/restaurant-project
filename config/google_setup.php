<?php
// config/google_setup.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// 1. Khởi tạo và tải các biến từ file .env
// __DIR__ . '/../' có nghĩa là lùi lại 1 thư mục để tìm file .env ở thư mục gốc
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 2. Lấy thông tin từ file .env thông qua biến siêu toàn cục $_ENV
$clientID     = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri  = $_ENV['GOOGLE_REDIRECT_URL'];

// 3. Khởi tạo Google Client
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// Yêu cầu quyền lấy Email và Profile
$client->addScope("email");
$client->addScope("profile");
?>
<?php
// config/google_setup.php
require_once __DIR__ . '/../vendor/autoload.php';

// Thay thế thông tin của bạn vào đây
$clientID = '1053282487934-shr625av7ndeuvba5kp9vahet59qd2vd.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/restaurant-project/public/google_callback.php';

// Khởi tạo Google Client
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// Yêu cầu quyền lấy Email và Profile
$client->addScope("email");
$client->addScope("profile");
?>
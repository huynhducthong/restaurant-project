<?php
// public/google_callback.php
session_start();
require_once '../config/database.php';
require_once '../config/google_setup.php';

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['code'])) {
    // Đổi code lấy Token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // Lấy thông tin từ Google
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $google_id = $google_account_info->id;
        $email = $google_account_info->email;
        $name = $google_account_info->name; // Tên từ Google

        try {
            // 1. Kiểm tra user đã tồn tại chưa (Dùng SELECT * để lấy đủ username)
            $stmt = $db->prepare("SELECT * FROM users WHERE google_id = :google_id OR email = :email");
            $stmt->execute([':google_id' => $google_id, ':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Nếu đã có email nhưng chưa có google_id thì cập nhật liên kết
                if (empty($user['google_id'])) {
                    $update = $db->prepare("UPDATE users SET google_id = :gid WHERE id = :id");
                    $update->execute([':gid' => $google_id, ':id' => $user['id']]);
                }
            } else {
                // 2. Tạo user mới (Dùng cột username như bạn yêu cầu)
                $insert = $db->prepare("INSERT INTO users (username, email, google_id, role) VALUES (:name, :email, :gid, 'customer')");
                $insert->execute([':name' => $name, ':email' => $email, ':gid' => $google_id]);
                
                // Lấy lại thông tin user vừa insert để có dữ liệu trong biến $user
                $stmt->execute([':google_id' => $google_id, ':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // 3. Lưu session đăng nhập (Đảm bảo key là 'username' khớp với DB)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username']; // Đã khớp với cột username
            $_SESSION['role'] = $user['role'];

            header('Location: ../index.php'); 
            exit();

        } catch (Exception $e) {
            die("Lỗi xử lý database: " . $e->getMessage());
        }
    }
} else {
    header('Location: login.php');
    exit();
}
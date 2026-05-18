<?php
// File: config/database.php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // 1. Load biến môi trường từ file .env an toàn
        if (!isset($_ENV['DB_HOST'])) {
            try {
                $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
                $dotenv->load();
            } catch (Exception $e) {
                // Dừng ngay hệ thống nếu không có file .env (Bảo vệ dự án)
                die("Lỗi hệ thống nghiêm trọng: Không tìm thấy file cấu hình môi trường (.env).");
            }
        }

        // 2. Lấy dữ liệu, có giá trị mặc định dự phòng
        $this->host     = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $this->port     = $_ENV['DB_PORT'] ?? '3306'; // Hỗ trợ linh hoạt Port cho Host thật
        $this->db_name  = $_ENV['DB_NAME'] ?? 'restaurant_db';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            // Thiết lập DSN chuẩn, gắn kèm utf8mb4 ngay từ lúc gọi
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Các tùy chọn nâng cao bảo mật và tối ưu cho PDO
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Báo lỗi nghiêm ngặt để dễ phát hiện bug
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Mặc định trả về Mảng kết hợp (Tiết kiệm code)
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Bảo vệ tuyệt đối khỏi SQL Injection
            ];

            // Khởi tạo kết nối
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            // BẢO MẬT: Ẩn thông báo lỗi chi tiết ra màn hình (Tránh lộ đường dẫn thư mục, mật khẩu)
            error_log("Database Connection Error: " . $exception->getMessage());
            die("Lỗi kết nối cơ sở dữ liệu. Vui lòng liên hệ quản trị viên.");
        }

        return $this->conn;
    }
}
?>
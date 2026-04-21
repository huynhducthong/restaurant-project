<?php
// config/database.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // 1. Tải các biến môi trường từ file .env
        // Kiểm tra nếu chưa load Dotenv thì mới load (tránh lỗi nếu file khác đã load rồi)
        if (!isset($_ENV['DB_HOST'])) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }

        // 2. Gán giá trị từ file .env vào các thuộc tính của class
        $this->host     = $_ENV['DB_HOST'];
        $this->db_name  = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8mb4"); // Hỗ trợ tiếng Việt
        } catch(PDOException $exception) {
            echo "Lỗi kết nối database: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
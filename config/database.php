<?php
class Database {
    private $host = "localhost";
    private $db_name = "restaurant_db";
    private $username = "root";
    private $password = ""; // XAMPP mặc định để trống, MAMP là 'root'
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8mb4"); // Hỗ trợ tiếng Việt có dấu
        } catch(PDOException $exception) {
            echo "Lỗi kết nối: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
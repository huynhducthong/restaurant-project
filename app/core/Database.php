<?php
class Database {
    private $host = "localhost";
    private $db_name = "restaurant_db";
    private $username = "root"; // Mặc định của XAMPP là root
    private $password = "";     // Mặc định của XAMPP là để trống
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            // Thiết lập font chữ tiếng Việt
            $this->conn->exec("set names utf8");
            // Thiết lập chế độ báo lỗi
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Lỗi kết nối: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
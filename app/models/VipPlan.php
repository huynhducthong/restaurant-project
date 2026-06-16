<?php
// app/models/VipPlan.php

class VipPlan {
    private $conn;
    private $table_name = "vip_plans";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy tất cả các gói VIP
    public function getAllPlans() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY price ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy thông tin một gói VIP theo ID
    public function getPlanById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

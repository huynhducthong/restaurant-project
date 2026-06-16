<?php
// app/models/UserVip.php

class UserVip {
    private $conn;
    private $table_name = "user_vip";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy thông tin VIP đang active của người dùng
    public function getActiveVipStatus($user_id) {
        // Cập nhật trạng thái trước khi lấy
        $this->checkAndExpireVipStatus($user_id);

        $query = "
            SELECT uv.*, vp.name as plan_name, vp.discount_percent, vp.duration_days, vp.price 
            FROM " . $this->table_name . " uv
            JOIN vip_plans vp ON uv.plan_id = vp.id
            WHERE uv.user_id = :user_id AND uv.status = 'active'
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Nâng cấp hoặc gia hạn VIP
    public function upgradeVip($user_id, $plan_id, $duration_days) {
        // Kiểm tra xem user đang có gói VIP active không
        $current_vip = $this->getActiveVipStatus($user_id);
        
        if ($current_vip) {
            // Cập nhật gói mới và set ngày từ hôm nay (dành cho người mới học để dễ hiểu)
            $start_date = date('Y-m-d H:i:s');
            $end_date = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
            
            $query = "UPDATE " . $this->table_name . " 
                      SET plan_id = :plan_id, start_date = :start_date, end_date = :end_date, status = 'active' 
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':id', $current_vip['id'], PDO::PARAM_INT);
            return $stmt->execute();
        } else {
            // Tạo mới trạng thái VIP
            $start_date = date('Y-m-d H:i:s');
            $end_date = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
            
            $query = "INSERT INTO " . $this->table_name . " (user_id, plan_id, start_date, end_date, status) 
                      VALUES (:user_id, :plan_id, :start_date, :end_date, 'active')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            return $stmt->execute();
        }
    }

    // Cập nhật những gói đã hết hạn
    public function checkAndExpireVipStatus($user_id) {
        $now = date('Y-m-d H:i:s');
        $query = "UPDATE " . $this->table_name . " SET status = 'expired' WHERE user_id = :user_id AND end_date <= :now AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':now', $now);
        $stmt->execute();
    }
}
?>

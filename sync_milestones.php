<?php
require 'config/database.php';
$db = (new Database())->getConnection();

try {
    $users = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;

    foreach ($users as $u) {
        $user_id = $u['id'];
        
        // Tính tổng booking đã hoàn thành
        $stmt = $db->prepare("SELECT COUNT(*) FROM service_bookings WHERE user_id = ? AND status = 'Completed'");
        $stmt->execute([$user_id]);
        $booking_count = $stmt->fetchColumn();
        
        // Tính tổng tiền booking
        $stmt = $db->prepare("SELECT SUM(total_amount) FROM service_bookings WHERE user_id = ? AND status = 'Completed'");
        $stmt->execute([$user_id]);
        $booking_spent = $stmt->fetchColumn() ?: 0;

        // Tính tổng tiền từ các hóa đơn POS liên kết với booking của user
        $stmt = $db->prepare("
            SELECT COUNT(p.id) as c, SUM(p.total_amount) as s 
            FROM pos_orders p 
            JOIN service_bookings b ON p.booking_id = b.id 
            WHERE b.user_id = ? AND p.status = 'paid'
        ");
        $stmt->execute([$user_id]);
        $pos = $stmt->fetch(PDO::FETCH_ASSOC);
        $pos_count = $pos['c'] ?: 0;
        $pos_spent = $pos['s'] ?: 0;

        // Nếu hệ thống trước đây không dùng POS mà tính tiền thẳng vào booking thì lấy booking total_amount (tránh cộng double)
        // Ta chỉ lấy số lần từ booking_count (mỗi booking là 1 lần đến)
        $total_visits = max($booking_count, $pos_count);
        $total_spent = max($booking_spent, $pos_spent);

        if ($total_visits > 0 || $total_spent > 0) {
            // Cập nhật lại số liệu cho user
            $db->prepare("UPDATE users SET visit_count = ?, total_spent = ? WHERE id = ?")
               ->execute([$total_visits, $total_spent, $user_id]);
               
            $updated++;
            
            // Tự động trao milestone cho những mốc họ đã vượt qua
            $stmt = $db->prepare("SELECT * FROM milestones");
            $stmt->execute();
            $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($milestones as $m) {
                $achieved = false;
                if ($m['type'] == 'visit' && $total_visits >= $m['threshold']) $achieved = true;
                if ($m['type'] == 'spend' && $total_spent >= $m['threshold']) $achieved = true;
                
                if ($achieved) {
                    // Kiểm tra xem đã có trong user_milestones chưa
                    $check = $db->prepare("SELECT id FROM user_milestones WHERE user_id = ? AND milestone_id = ?");
                    $check->execute([$user_id, $m['id']]);
                    if (!$check->fetch()) {
                        // Thêm vào
                        $insert = $db->prepare("INSERT INTO user_milestones (user_id, milestone_id, achieved_at, is_redeemed) VALUES (?, ?, NOW(), 0)");
                        $insert->execute([$user_id, $m['id']]);
                    }
                }
            }
        }
    }
    echo "Đồng bộ thành công dữ liệu cho $updated người dùng!";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}

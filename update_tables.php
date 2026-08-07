<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = (new Database())->getConnection();
    
    $sql = "
    TRUNCATE TABLE `restaurant_tables`;
    INSERT INTO `restaurant_tables` (`id`, `table_code`, `table_number`, `room_type`, `category`, `capacity`, `price`, `status`, `is_available`, `pos_x`, `pos_y`) VALUES
    (4, 'R1', '4', 'gần cửa sổ', 'open', 2, 0.00, 'available', 1, 320, 380),
    (5, 'R2', '5', 'Khu vực chung', 'open', 2, 0.00, 'available', 1, 320, 560),
    (6, 'R3', '6', 'Khu vực chung', 'open', 2, 0.00, 'available', 1, 720, 580),
    (7, 'R4', '7', 'Khu vực chung', 'open', 2, 0.00, 'available', 1, 860, 470),
    (8, 'R5', '8', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 860, 290),
    (9, 'R6', '9', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 720, 220),
    (10, 'W1', '10', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 500, 290),
    (11, 'W2', '11', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 720, 380),
    (12, 'W3', '12', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 500, 580),
    (13, 'W4', '13', 'Khu vực chung', 'open', 6, 0.00, 'available', 1, 320, 200),
    (14, 'W5', '14', 'Khu vực chung', 'open', 6, 0.00, 'available', 1, 130, 270),
    (15, 'W6', '15', 'Khu vực chung', 'open', 6, 0.00, 'available', 1, 130, 460),
    (17, 'V1', '101', 'Khu vực chung', 'open', 8, 0.00, 'available', 1, 125, 675),
    (18, 'V2', '102', 'Khu vực chung', 'open', 8, 0.00, 'available', 1, 1000, 100),
    (19, 'V3', '103', 'Phòng VIP', 'room', 16, 0.00, 'available', 1, 125, 90),
    (20, 'V4', '104', 'Phòng VIP', 'room', 16, 0.00, 'available', 1, 1000, 675),
    (28, 'EXT-01', '1', 'Dịch vụ tại gia', 'external', 99, 0.00, 'available', 0, 0, 0);
    ";
    
    $db->exec($sql);
    echo "<h1>Cập nhật Database thành công!</h1>";
    echo "<p>Đã khôi phục toàn bộ 17 bàn/phòng.</p>";
    
    // Optionally remove the file after running to secure it
    unlink(__FILE__);
} catch (Exception $e) {
    echo "<h1>Lỗi cập nhật: " . $e->getMessage() . "</h1>";
}

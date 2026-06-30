<?php
require 'config/database.php';
$db = (new Database())->getConnection();

try {
    // 1. Create milestones table
    $db->exec("
        CREATE TABLE IF NOT EXISTS milestones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('visit', 'spend') NOT NULL,
            threshold INT NOT NULL,
            reward_title VARCHAR(255) NOT NULL,
            reward_desc TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Insert some default milestones
    $stmt = $db->query("SELECT COUNT(*) FROM milestones");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("
            INSERT INTO milestones (type, threshold, reward_title, reward_desc) VALUES
            ('visit', 3, 'Ly Champagne Chào Mừng', 'Tặng 1 ly Champagne cao cấp khi khách vừa ngồi vào bàn, kèm lời chúc.'),
            ('visit', 5, 'Món Tráng Miệng Signature', 'Bếp trưởng trực tiếp ra bàn gửi lời chào và tặng một phần tráng miệng đặc biệt không có trong Menu.'),
            ('visit', 10, 'Private Tasting', 'Tặng thư mời tham gia buổi thử nếm Private Tasting menu mùa mới cùng Bếp Trưởng.')
        ");
    }

    // 2. Create user_milestones table
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_milestones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            milestone_id INT NOT NULL,
            achieved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_redeemed TINYINT(1) DEFAULT 0,
            redeemed_at DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Add columns to users table safely
    try {
        $db->exec("ALTER TABLE users ADD COLUMN visit_count INT DEFAULT 0");
    } catch(PDOException $e) {} // ignore if exists
    
    try {
        $db->exec("ALTER TABLE users ADD COLUMN total_spent DECIMAL(15,2) DEFAULT 0.00");
    } catch(PDOException $e) {} // ignore if exists

    echo "Migration successful!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}

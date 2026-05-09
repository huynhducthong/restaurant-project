<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `employees` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `full_name` varchar(255) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `identity_card` varchar(20) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `dob` date DEFAULT NULL,
        `gender` enum('male', 'female', 'other') DEFAULT 'other',
        `email` varchar(100) DEFAULT NULL,
        `position` varchar(100) DEFAULT NULL,
        `branch_id` int(11) DEFAULT NULL,
        `salary` decimal(15,2) DEFAULT 0,
        `status` enum('working', 'on_leave', 'resigned') DEFAULT 'working',
        `avatar` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "Table employees created.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

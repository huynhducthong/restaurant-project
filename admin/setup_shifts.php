<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    
    // Create shifts table
    $sql1 = "CREATE TABLE IF NOT EXISTS `shifts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `shift_name` varchar(100) NOT NULL,
        `start_time` time NOT NULL,
        `end_time` time NOT NULL,
        `description` text DEFAULT NULL,
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql1);

    // Create shift_assignments table
    $sql2 = "CREATE TABLE IF NOT EXISTS `shift_assignments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `shift_id` int(11) NOT NULL,
        `work_date` date NOT NULL,
        `status` enum('scheduled', 'present', 'absent', 'late') DEFAULT 'scheduled',
        `check_in` datetime DEFAULT NULL,
        `check_out` datetime DEFAULT NULL,
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql2);

    echo "Tables shifts and shift_assignments created.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

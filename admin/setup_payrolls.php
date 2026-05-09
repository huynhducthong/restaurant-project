<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `payrolls` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `month` int(2) NOT NULL,
        `year` int(4) NOT NULL,
        `base_salary` decimal(15,2) NOT NULL DEFAULT 0,
        `work_days` int(11) DEFAULT 0,
        `allowance` decimal(15,2) DEFAULT 0,
        `bonus` decimal(15,2) DEFAULT 0,
        `deduction` decimal(15,2) DEFAULT 0,
        `net_salary` decimal(15,2) NOT NULL DEFAULT 0,
        `status` enum('draft', 'approved', 'paid') DEFAULT 'draft',
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `emp_month_year` (`employee_id`, `month`, `year`),
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "Table payrolls created.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

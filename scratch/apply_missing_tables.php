<?php
require_once __DIR__ . '/../config/database.php';

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();
    
    echo "==================================================\n";
    echo "       SAFE DATABASE MIGRATION & SYNC\n";
    echo "==================================================\n";
    
    // Disable foreign key checks temporarily to avoid constraint errors during creation
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Tạo bảng positions
    echo "Tạo bảng `positions`... ";
    $sqlPositions = "CREATE TABLE IF NOT EXISTS `positions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `position_name` varchar(100) NOT NULL,
      `base_salary` float DEFAULT 0,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->exec($sqlPositions);
    echo "Đồng bộ positions thành công!\n";

    // 2. Tạo bảng shifts
    echo "Tạo bảng `shifts`... ";
    $sqlShifts = "CREATE TABLE IF NOT EXISTS `shifts` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `shift_name` varchar(50) NOT NULL,
      `start_time` time NOT NULL,
      `end_time` time NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->exec($sqlShifts);
    echo "Đồng bộ shifts thành công!\n";

    // 3. Tạo bảng payrolls
    echo "Tạo bảng `payrolls`... ";
    $sqlPayrolls = "CREATE TABLE IF NOT EXISTS `payrolls` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` int(11) NOT NULL,
      `month` int(11) NOT NULL,
      `year` int(11) NOT NULL,
      `base_salary` decimal(15,2) NOT NULL,
      `work_days` decimal(10,2) DEFAULT 0.00,
      `allowance` decimal(15,2) DEFAULT 0.00,
      `bonus` decimal(15,2) DEFAULT 0.00,
      `deduction` decimal(15,2) DEFAULT 0.00,
      `net_salary` decimal(15,2) NOT NULL,
      `status` enum('draft','approved') DEFAULT 'draft',
      PRIMARY KEY (`id`),
      UNIQUE KEY `employee_id` (`employee_id`,`month`,`year`),
      CONSTRAINT `payrolls_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->exec($sqlPayrolls);
    echo "Đồng bộ payrolls thành công!\n";

    // 4. Tạo bảng shift_assignments
    echo "Tạo bảng `shift_assignments`... ";
    $sqlShiftAssignments = "CREATE TABLE IF NOT EXISTS `shift_assignments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` int(11) NOT NULL,
      `shift_id` int(11) NOT NULL,
      `work_date` date NOT NULL,
      `check_in` datetime DEFAULT NULL,
      `check_out` datetime DEFAULT NULL,
      `status` enum('scheduled','present','absent') DEFAULT 'scheduled',
      `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
      PRIMARY KEY (`id`),
      KEY `employee_id` (`employee_id`),
      KEY `shift_id` (`shift_id`),
      CONSTRAINT `shift_assignments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
      CONSTRAINT `shift_assignments_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->exec($sqlShiftAssignments);
    echo "Đồng bộ shift_assignments thành công!\n";

    // 5. Tạo bảng inventory_batches
    echo "Tạo bảng `inventory_batches`... ";
    $sqlInventoryBatches = "CREATE TABLE IF NOT EXISTS `inventory_batches` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `ingredient_id` int(11) NOT NULL,
      `warehouse_id` int(11) NOT NULL,
      `batch_code` varchar(50) DEFAULT NULL,
      `quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
      `expiry_date` date DEFAULT NULL,
      `cost_price` decimal(15,2) DEFAULT 0.00,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `ingredient_id` (`ingredient_id`),
      KEY `warehouse_id` (`warehouse_id`),
      KEY `expiry_date` (`expiry_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->exec($sqlInventoryBatches);
    echo "Đồng bộ inventory_batches thành công!\n";

    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n🎉 CHÚC MỪNG: Đồng bộ và cập nhật database thành công 100%! Không mất bất kỳ dữ liệu hiện tại nào.\n";
    echo "==================================================\n";

} catch (Exception $e) {
    // Re-enable foreign key checks in case of error
    if (isset($db)) {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
    echo "LỖI KHI CẬP NHẬT DATABASE: " . $e->getMessage() . "\n";
}

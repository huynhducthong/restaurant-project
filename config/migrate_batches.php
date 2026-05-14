<?php
require_once __DIR__ . '/database.php';

try {
    $db = (new Database())->getConnection();
    
    // 1. Tạo bảng inventory_batches
    $sql = "CREATE TABLE IF NOT EXISTS `inventory_batches` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    
    echo "<h1>Migration Success!</h1>";
    echo "<p>Table `inventory_batches` created successfully.</p>";
    echo "<a href='../admin/controllers/InventoryController.php'>Back to Inventory</a>";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
?>

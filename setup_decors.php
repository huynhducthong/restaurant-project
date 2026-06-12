<?php
$host = '127.0.0.1';
$db   = 'restaurant_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create decor_packages table
    $sql = "CREATE TABLE IF NOT EXISTS `decor_packages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `price` DECIMAL(12,2) DEFAULT 0,
        `image_url` VARCHAR(255),
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);

    // 2. Add decor_id to service_bookings
    try {
        $pdo->exec("ALTER TABLE `service_bookings` ADD COLUMN `decor_id` INT DEFAULT NULL");
    } catch (Exception $e) {
        // Column might already exist
    }

    // 3. Insert default packages if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM decor_packages");
    if ($stmt->fetchColumn() == 0) {
        $insert = "INSERT INTO `decor_packages` (`name`, `description`, `price`, `image_url`) VALUES 
        ('Gói Mặc Định', 'Gói trang trí cơ bản tiêu chuẩn của nhà hàng bao gồm hoa tươi để bàn nhỏ, nến thơm lung linh và setup khăn ăn nghệ thuật.', 0, 'public/assets/images/decors/decor_1.jpg'),
        ('Gói Lãng Mạn', 'Tạo không gian vô cùng lãng mạn với bóng bay nghệ thuật bay bổng trần nhà, ánh nến cao cấp và bản nhạc nhẹ nhàng theo yêu cầu.', 500000, 'public/assets/images/decors/decor_2.jpg'),
        ('Gói Hoàng Gia', 'Khẳng định đẳng cấp với thảm hoa hồng, rượu vang thượng hạng hảo hạng, ánh sáng chuyên nghiệp và backdrop thiết kế riêng.', 3000000, 'public/assets/images/decors/decor_3.jpg')";
        $pdo->exec($insert);
    }

    echo "Database setup for Decor Packages completed successfully.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

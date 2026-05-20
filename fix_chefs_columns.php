<?php
// File: fix_chefs_columns.php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Bắt đầu nâng cấp cấu trúc bảng `chefs`...</h2>";
    
    // Danh sách các cột cần thêm và định nghĩa SQL của chúng
    $columns_to_add = [
        'experience' => "INT(11) DEFAULT 0",
        'specialty' => "VARCHAR(255) DEFAULT NULL",
        'description' => "TEXT DEFAULT NULL",
        'quote' => "VARCHAR(255) DEFAULT NULL",
        'facebook' => "VARCHAR(255) DEFAULT NULL",
        'instagram' => "VARCHAR(255) DEFAULT NULL",
        'email' => "VARCHAR(100) DEFAULT NULL",
        'is_active' => "TINYINT(1) NOT NULL DEFAULT 1",
        'is_featured' => "TINYINT(1) NOT NULL DEFAULT 0",
        'sort_order' => "INT(11) DEFAULT 0"
    ];
    
    // Lấy danh sách cột hiện tại của bảng chefs
    $stmt = $db->query("DESCRIBE chefs");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $added = 0;
    foreach ($columns_to_add as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $alter_query = "ALTER TABLE chefs ADD COLUMN `$column` $definition";
            $db->exec($alter_query);
            echo "<p style='color: green;'>✓ Đã thêm cột: <strong>$column</strong> thành công!</p>";
            $added++;
        } else {
            echo "<p style='color: blue;'>i Cột: <strong>$column</strong> đã tồn tại.</p>";
        }
    }
    
    if ($added > 0) {
        echo "<h3 style='color: green;'>Nâng cấp thành công! Đã thêm $added cột mới vào bảng `chefs`.</h3>";
        
        // Cập nhật một số dữ liệu mẫu nếu vừa thêm cột để giao diện đẹp hơn
        $db->exec("UPDATE chefs SET experience = 10, specialty = 'Ẩm thực Việt Nam', description = 'Hơn 10 năm kinh nghiệm đứng bếp và sáng tạo ẩm thực truyền thống.', quote = 'Mỗi món ăn là một câu chuyện.', email = 'minhchef@gmail.com', is_active = 1, is_featured = 1, sort_order = 1 WHERE id = 1");
        $db->exec("UPDATE chefs SET experience = 8, specialty = 'Bánh & Tráng miệng', description = 'Chuyên gia thiết kế các món tráng miệng ngọt ngào, tinh tế.', quote = 'Vị ngọt thanh tao chạm đến cảm xúc.', email = 'lanchef@gmail.com', is_active = 1, is_featured = 0, sort_order = 2 WHERE id = 2");
        echo "<p style='color: green;'>✓ Đã cập nhật dữ liệu mẫu thành công cho các đầu bếp hiện tại.</p>";
    } else {
        echo "<h3 style='color: blue;'>Bảng `chefs` đã có đầy đủ cấu trúc cột mới. Không cần thay đổi gì thêm.</h3>";
    }
    
    echo "<p><a href='/restaurant-project/views/client/chefs.php' style='display: inline-block; padding: 10px 20px; background-color: #cda45e; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>Quay lại trang Đầu Bếp để kiểm tra</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Có lỗi xảy ra: " . $e->getMessage() . "</h3>";
}
?>

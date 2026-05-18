<?php
require_once __DIR__ . '/../config/database.php';

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();
    
    // Lấy tên database hiện tại
    $stmtDb = $db->query("SELECT DATABASE()");
    $dbName = $stmtDb->fetchColumn();
    
    echo "==================================================\n";
    echo "    DATABASE INTEGRITY & TABLES CHECK\n";
    echo "==================================================\n";
    echo "Kết nối tới database: '$dbName'\n\n";

    // 1. Lấy danh sách các bảng hiện có trong database
    $stmt = $db->query("SHOW TABLES");
    $activeTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Phân tích file restaurant_db.sql để tìm danh sách bảng mong đợi
    $sqlFilePath = __DIR__ . '/../restaurant_db.sql';
    if (!file_exists($sqlFilePath)) {
        echo "Cảnh báo: Không tìm thấy file restaurant_db.sql ở thư mục gốc dự án!\n";
        $expectedTables = [];
    } else {
        $sqlContent = file_get_contents($sqlFilePath);
        // Tìm pattern CREATE TABLE `tên_bảng`
        preg_match_all('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $sqlContent, $matches);
        $expectedTables = array_unique($matches[1]);
    }

    echo "--- THỐNG KÊ BẢNG DỮ LIỆU ---\n";
    echo "Số bảng đang HOẠT ĐỘNG trong database '$dbName': " . count($activeTables) . "\n";
    echo "Số bảng MONG ĐỢI trong file 'restaurant_db.sql': " . count($expectedTables) . "\n\n";

    // 3. So sánh đối chiếu các bảng
    $missingTables = array_diff($expectedTables, $activeTables);
    $extraTables = array_diff($activeTables, $expectedTables);

    if (empty($missingTables)) {
        echo "✅ THÀNH CÔNG: Cơ sở dữ liệu đã có ĐẦY ĐỦ các bảng mong đợi! Không thiếu bảng nào.\n";
    } else {
        echo "❌ CẢNH BÁO: Phát hiện thiếu " . count($missingTables) . " bảng sau trong database:\n";
        foreach ($missingTables as $t) {
            echo "   - $t\n";
        }
        echo "\n👉 Để cập nhật cơ sở dữ liệu và thêm các bảng thiếu này, bạn có thể chạy import file restaurant_db.sql bằng CLI hoặc qua phpMyAdmin.\n";
    }

    if (!empty($extraTables)) {
        echo "\nℹ️ Thông tin bổ sung: Các bảng sau có trong database nhưng không định nghĩa trong restaurant_db.sql (bảng tạo ngoài):\n";
        foreach ($extraTables as $t) {
            echo "   - $t\n";
        }
    }

    echo "\n--- DANH SÁCH CHI TIẾT CÁC BẢNG ĐANG HOẠT ĐỘNG ---\n";
    foreach ($activeTables as $i => $t) {
        try {
            $stmtCount = $db->query("SELECT COUNT(*) FROM `$t`");
            $rowCount = $stmtCount->fetchColumn();
            echo sprintf(" %2d. %-30s (%d bản ghi)\n", $i + 1, $t, $rowCount);
        } catch (Exception $e) {
            echo sprintf(" %2d. %-30s (Lỗi đọc số dòng: %s)\n", $i + 1, $t, $e->getMessage());
        }
    }
    
    echo "==================================================\n";

} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../config/database.php';

// Thiết lập phản hồi JSON để AJAX nhận diện được
header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Lấy dữ liệu từ form và làm sạch khoảng trắng
        $item_name = trim($_POST['item_name']);
        $category = $_POST['category']; 
        $unit_name = $_POST['unit_name'];
        $cost_price = (float)$_POST['cost_price'];

        // Kiểm tra dữ liệu đầu vào cơ bản
        if (empty($item_name) || empty($category)) {
            echo json_encode(['status' => 'error', 'message' => 'Tên và danh mục không được để trống!']);
            exit;
        }

        // Chuẩn bị câu lệnh SQL (Mặc định tồn kho ban đầu là 0)
        $query = "INSERT INTO inventory (item_name, category, unit_name, cost_price, stock_quantity) 
                  VALUES (?, ?, ?, ?, 0)";
        $stmt = $db->prepare($query);
        
        // CHỈ THỰC THI 1 LẦN DUY NHẤT TRONG LỆNH IF DƯỚI ĐÂY
        if ($stmt->execute([$item_name, $category, $unit_name, $cost_price])) {
            echo json_encode(['status' => 'success', 'message' => 'Đã thêm nguyên liệu: ' . $item_name]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi: Không thể lưu vào cơ sở dữ liệu.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
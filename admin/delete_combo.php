<?php
require_once '../config/database.php'; // Đường dẫn tới file database.php của bạn
$database = new Database();
$db = $database->getConnection();

// 1. Kiểm tra xem có ID được gửi tới không
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $combo_id = $_GET['id'];

    try {
        // 2. Chuẩn bị câu lệnh xóa (Sử dụng Prepared Statement để bảo mật)
        $query = "DELETE FROM combos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $combo_id, PDO::PARAM_INT);

        // 3. Thực thi lệnh xóa
        if ($stmt->execute()) {
            // Xóa thành công, quay lại danh sách với thông báo
            echo "<script>
                    alert('Đã xóa combo thành công!');
                    window.location.href = 'list_combos.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Không thể xóa combo này. Vui lòng thử lại.');
                    window.location.href = 'list_combos.php';
                  </script>";
        }
    } catch (PDOException $e) {
        // Trả về lỗi nếu có vấn đề về Database
        echo "Lỗi hệ thống: " . $e->getMessage();
    }
} else {
    // Nếu không có ID, đẩy người dùng về trang danh sách
    header("Location: list_combos.php");
    exit();
}
?>
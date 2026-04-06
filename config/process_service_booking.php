<?php
require_once 'database.php';
$db = (new Database())->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type       = $_POST['service_type'] ?? 'table';
    $name       = $_POST['customer_name'] ?? '';   
    $phone      = $_POST['customer_phone'] ?? '';  
    $date       = $_POST['booking_date'] ?? '';   
    $guests     = $_POST['guests'] ?? 1;
    $msg        = $_POST['message'] ?? '';
    $table_id   = $_POST['table_id'] ?? null;

    if (empty($name) || empty($phone) || empty($date)) {
        echo "<script>alert('Vui lòng điền đầy đủ các thông tin bắt buộc!'); window.history.back();</script>";
        exit;
    }

    try {
        $db->beginTransaction();

        // --- BỔ SUNG: Tính toán tổng tiền để lưu cọc ---
        $total_amount = 0;
        // 1. Lấy giá bàn/phòng
        if ($table_id) {
            $t_stmt = $db->prepare("SELECT price FROM restaurant_tables WHERE id = ?");
            $t_stmt->execute([$table_id]);
            $total_amount += $t_stmt->fetchColumn() ?: 0;
        }
        // 2. Lấy giá món ăn
        if (!empty($_POST['menu_items'])) {
            foreach ($_POST['menu_items'] as $m_id) {
                $qty = $_POST['quantity'][$m_id] ?? 1;
                $f_stmt = $db->prepare("SELECT price FROM foods WHERE id = ?");
                $f_stmt->execute([$m_id]);
                $total_amount += ($f_stmt->fetchColumn() * $qty);
            }
        }
        $deposit_amount = $total_amount * 0.3;

        // --- LƯU ĐƠN HÀNG (Cập nhật SQL để nhận thêm table_id và tiền cọc) ---
        $query = "INSERT INTO service_bookings (customer_name, customer_phone, service_type, booking_date, guests, message, table_id, total_amount, deposit_amount, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $phone, $type, $date, $guests, $msg, $table_id, $total_amount, $deposit_amount]);
        $last_id = $db->lastInsertId();
            
        // Lưu chi tiết món ăn
        if (!empty($_POST['menu_items'])) {
            foreach ($_POST['menu_items'] as $menu_id) {
                $qty = $_POST['quantity'][$menu_id] ?? 1;
                $db->prepare("INSERT INTO booking_details (booking_id, menu_id, quantity) VALUES (?, ?, ?)")
                   ->execute([$last_id, $menu_id, $qty]);
            }
        }

        // Cập nhật trạng thái bàn thành "Đã đặt"
        if ($table_id) {
            $db->prepare("UPDATE restaurant_tables SET is_available = 0 WHERE id = ?")->execute([$table_id]);
        }

        $db->commit();
        header("Location: ../booking_success.php?id=" . $last_id);
        exit();

    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>
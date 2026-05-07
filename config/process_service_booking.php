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
    $combo_id   = $_POST['selected_combo_id'] ?? 0;

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
        // 2. Lấy giá món ăn lẻ
        if (!empty($_POST['menu_items'])) {
            foreach ($_POST['menu_items'] as $m_id) {
                $qty = $_POST['quantity'][$m_id] ?? 1;
                $f_stmt = $db->prepare("SELECT price FROM foods WHERE id = ?");
                $f_stmt->execute([$m_id]);
                $total_amount += ($f_stmt->fetchColumn() * $qty);
            }
        }
        // 3. Lấy giá combo
        if ($combo_id > 0) {
            $c_stmt = $db->prepare("SELECT price FROM combos WHERE id = ?");
            $c_stmt->execute([$combo_id]);
            $total_amount += $c_stmt->fetchColumn() ?: 0;
        }

        $deposit_amount = $total_amount * 0.3;

        // --- LƯU ĐƠN HÀNG (Cập nhật SQL để nhận thêm table_id, combo_id và tiền cọc) ---
        $query = "INSERT INTO service_bookings (customer_name, customer_phone, service_type, booking_date, guests, message, table_id, combo_id, total_amount, deposit_amount, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $phone, $type, $date, $guests, $msg, $table_id, ($combo_id > 0 ? $combo_id : null), $total_amount, $deposit_amount]);
        $last_id = $db->lastInsertId();
            
        // Lưu chi tiết món ăn lẻ
        if (!empty($_POST['menu_items'])) {
            foreach ($_POST['menu_items'] as $menu_id) {
                $qty = $_POST['quantity'][$menu_id] ?? 1;
                $db->prepare("INSERT INTO booking_details (booking_id, menu_id, item_type, quantity) VALUES (?, ?, 'food', ?)")
                   ->execute([$last_id, $menu_id, $qty]);
            }
        }

        // Lưu chi tiết món ăn từ Combo (để Admin có thể trừ kho khi xác nhận)
        if ($combo_id > 0) {
            $stmt_combo_foods = $db->prepare("SELECT food_id FROM combo_items WHERE combo_id = ?");
            $stmt_combo_foods->execute([$combo_id]);
            $combo_foods = $stmt_combo_foods->fetchAll(PDO::FETCH_ASSOC);
            foreach ($combo_foods as $cf) {
                // Thêm vào booking_details với item_type='combo' hoặc 'food'
                $db->prepare("INSERT INTO booking_details (booking_id, menu_id, item_type, quantity) VALUES (?, ?, 'food', 1)")
                   ->execute([$last_id, $cf['food_id']]);
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
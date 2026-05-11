<?php
require_once 'database.php';
$db = (new Database())->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['service_type'] ?? 'table';
    $name = $_POST['customer_name'] ?? '';
    $phone = $_POST['customer_phone'] ?? '';
    $date = $_POST['booking_date'] ?? '';
    $guests = $_POST['guests'] ?? 1;
    $msg = $_POST['message'] ?? '';
    $table_id = $_POST['table_id'] ?? null;
    $combo_id = $_POST['selected_combo_id'] ?? 0;

    if (empty($name) || empty($phone) || empty($date)) {
        echo "<script>alert('Vui lòng điền đầy đủ các thông tin bắt buộc!'); window.history.back();</script>";
        exit;
    }

    try {
        // Bắt đầu giao dịch (Transaction)
        $db->beginTransaction();

        // 1. Tính toán tổng tiền để lưu cọc
        $total_amount = 0;

        // Tính giá bàn/phòng nếu có
        if ($table_id) {
            $t_stmt = $db->prepare("SELECT price FROM restaurant_tables WHERE id = ?");
            $t_stmt->execute([$table_id]);
            $total_amount += $t_stmt->fetchColumn() ?: 0;
        }

        // Tính tiền món ăn khách chọn lẻ
        if (!empty($_POST['menu_items'])) {
            $food_ids = implode(',', array_map('intval', $_POST['menu_items']));
            $f_stmt = $db->query("SELECT id, price FROM foods WHERE id IN ($food_ids)");
            $foods_price = $f_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($_POST['menu_items'] as $m_id) {
                $qty = $_POST['quantity'][$m_id] ?? 1;
                $total_amount += ($foods_price[$m_id] ?? 0) * $qty;
            }
        }

        // 2. Lưu đơn đặt bàn chính vào service_bookings
        $stmt_booking = $db->prepare("INSERT INTO service_bookings (service_type, customer_name, customer_phone, booking_date, guests, message, table_id, combo_id, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt_booking->execute([$type, $name, $phone, $date, $guests, $msg, $table_id, $combo_id, $total_amount]);
        $last_id = $db->lastInsertId();

        // 3. Lưu chi tiết các món ăn khách chọn lẻ
        if (!empty($_POST['menu_items'])) {
            $stmt_details = $db->prepare("INSERT INTO booking_details (booking_id, menu_id, item_type, quantity) VALUES (?, ?, 'food', ?)");
            foreach ($_POST['menu_items'] as $menu_id) {
                $qty = $_POST['quantity'][$menu_id] ?? 1;
                $stmt_details->execute([$last_id, $menu_id, $qty]);
            }
        }

        // 4. Lưu chi tiết món ăn từ Combo (nếu có chọn Combo)
        if ($combo_id > 0) {
            $stmt_combo_foods = $db->prepare("SELECT food_id FROM combo_items WHERE combo_id = ?");
            $stmt_combo_foods->execute([$combo_id]);
            $combo_foods = $stmt_combo_foods->fetchAll(PDO::FETCH_ASSOC);

            $stmt_combo_details = $db->prepare("INSERT INTO booking_details (booking_id, menu_id, item_type, quantity) VALUES (?, ?, 'food', 1)");
            foreach ($combo_foods as $cf) {
                $stmt_combo_details->execute([$last_id, $cf['food_id']]);
            }
        }

        // 5. Cập nhật trạng thái bàn thành "Đã đặt"
        if ($table_id) {
            $db->prepare("UPDATE restaurant_tables SET is_available = 0 WHERE id = ?")->execute([$table_id]);
        }

        // Xác nhận thành công toàn bộ quá trình
        $db->commit();

        echo "<script>
            alert('Đặt dịch vụ thành công! Nhà hàng sẽ liên hệ lại để xác nhận sớm nhất.');
            window.location.href = 'index.php';
        </script>";
        exit;

    } catch (Exception $e) {
        // QUAN TRỌNG: Nếu có lỗi (như trùng lặp, rớt mạng giữa chừng), quay ngược (rollback) lại ngay!
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        // Báo lỗi cho người dùng
        echo "<script>
            alert('Đã xảy ra lỗi khi đặt bàn: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
        exit;
    }
} else {
    // Truy cập trái phép
    header("Location: index.php");
    exit;
}
?>
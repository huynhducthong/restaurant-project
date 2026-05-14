<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;

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
    
    // Anniversary fields
    $event_type = $_POST['event_type'] ?? null;
    $decor_package = $_POST['decor_package'] ?? null;
    $has_cake = isset($_POST['has_cake']) ? 1 : 0;
    $has_flower = isset($_POST['has_flower']) ? 1 : 0;

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
        
        // Tính tiền Combo
        if ($combo_id > 0) {
            $c_stmt = $db->prepare("SELECT price FROM combos WHERE id = ?");
            $c_stmt->execute([$combo_id]);
            $total_amount += $c_stmt->fetchColumn() ?: 0;
        }

        // Tính tiền trang trí (Sinh nhật / Sự kiện)
        if ($event_type === 'birthday') {
            if ($decor_package === 'standard') $total_amount += 500000;
            if ($decor_package === 'premium') $total_amount += 1500000;
            if ($decor_package === 'luxury') $total_amount += 3000000;
            if ($has_cake) $total_amount += 300000;
            if ($has_flower) $total_amount += 200000;
        }

        // Tính 30% cọc
        $deposit_amount = $total_amount * 0.3;

        // 2. Lưu đơn đặt bàn chính vào service_bookings (Bổ sung user_id và deposit_amount)
        $stmt_booking = $db->prepare("INSERT INTO service_bookings (user_id, service_type, customer_name, customer_phone, booking_date, guests, message, table_id, combo_id, total_amount, deposit_amount, status, event_type, decor_package, has_cake, has_flower) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?)");
        $stmt_booking->execute([$user_id, $type, $name, $phone, $date, $guests, $msg, $table_id, $combo_id, $total_amount, $deposit_amount, $event_type, $decor_package, $has_cake, $has_flower]);
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

        // --- THÊM: GỬI THÔNG BÁO TELEGRAM CHO QUẢN TRỊ VIÊN ---
        require_once 'notification_helper.php';
        $time_str = date('H:i d/m', strtotime($date));
        $event_str = $event_type ? " ($event_type)" : "";
        $booking_msg = "<b>🆕 ĐƠN ĐẶT BÀN MỚI</b>\n\n";
        $booking_msg .= "👤 Khách: <b>$name</b>\n";
        $booking_msg .= "📞 SĐT: $phone\n";
        $booking_msg .= "⏰ Lúc: $time_str\n";
        $booking_msg .= "👥 Khách: $guests người\n";
        $booking_msg .= "🏷 Loại: " . ucfirst($type) . $event_str . "\n";
        if ($msg) $booking_msg .= "📝 Ghi chú: <i>$msg</i>\n\n";
        $booking_msg .= "💰 Dự kiến: " . number_format($total_amount) . " VNĐ\n";
        $booking_msg .= "👉 Vui lòng duyệt đơn trên Admin.";

        @sendTelegramNotification($booking_msg);

        echo "<script>
            alert('Đặt dịch vụ thành công! Nhà hàng sẽ liên hệ lại để xác nhận sớm nhất.');
            window.location.href = '../booking_success.php?success=1&id=" . $last_id . "';
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
    header("Location: ../index.php");
    exit;
}
?>
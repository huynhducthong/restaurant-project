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
    
    $allergies = isset($_POST['allergies']) ? implode(', ', $_POST['allergies']) : '';
    $diet = $_POST['diet'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $msg = trim($_POST['message'] ?? '');

    $extra_info = [];
    if (!empty($purpose)) $extra_info[] = "Mục đích: " . $purpose;
    if (!empty($diet) && $diet !== 'Không yêu cầu') $extra_info[] = "Chế độ ăn: " . $diet;
    if (!empty($allergies)) $extra_info[] = "DỊ ỨNG: " . $allergies;

    if (!empty($extra_info)) {
        $msg = implode(" | ", $extra_info) . ($msg ? "\n\nGhi chú khác: " . $msg : "");
    }
    $table_id = $_POST['table_id'] ?? null;
    $combo_id = $_POST['selected_combo_id'] ?? 0;
    
    // Anniversary fields
    $event_type = $_POST['event_type'] ?? $_POST['bespoke_occasion'] ?? null;
    $decor_package = $_POST['decor_package'] ?? null;
    $has_cake = isset($_POST['has_cake']) ? 1 : 0;
    $has_flower = (isset($_POST['has_flower']) || isset($_POST['has_bespoke_flower'])) ? 1 : 0;
    
    // Bespoke fields
    $has_candle = isset($_POST['has_candle']) ? 1 : 0;
    $has_handwritten_card = isset($_POST['has_handwritten_card']) ? 1 : 0;
    $card_message = $_POST['card_message'] ?? null;
    $flower_preference = $_POST['flower_preference'] ?? null;
    $music_playlist = $_POST['music_playlist'] ?? null;
    $light_tone = $_POST['light_tone'] ?? null;
    
    // Xử lý yêu cầu Phục vụ riêng
    $dedicated_server = $_POST['dedicated_server'] ?? '';
    if ($dedicated_server) {
        $ds_name = $_POST['dedicated_server_name'] ?? '';
        $ds_text = $dedicated_server === 'other' ? "Khách quen: $ds_name" : $dedicated_server;
        $msg .= "\n[Phục vụ riêng] " . $ds_text;
    }

    $chef_requirements = $_POST['chef_requirements'] ?? null;

    // Gắn thêm Hồ sơ Khẩu vị (Culinary DNA) nếu có
    if ($user_id) {
        $u_stmt = $db->prepare("SELECT doneness, flavor_profile, fav_ingredients, disliked_ingredients, allergies FROM users WHERE id = ?");
        $u_stmt->execute([$user_id]);
        $u_profile = $u_stmt->fetch(PDO::FETCH_ASSOC);
        if ($u_profile) {
            $dna_parts = [];
            if (!empty($u_profile['doneness'])) $dna_parts[] = "- Độ chín: " . $u_profile['doneness'];
            if (!empty($u_profile['flavor_profile'])) $dna_parts[] = "- Hương vị: " . $u_profile['flavor_profile'];
            if (!empty($u_profile['fav_ingredients'])) $dna_parts[] = "- Yêu thích: " . $u_profile['fav_ingredients'];
            if (!empty($u_profile['disliked_ingredients'])) $dna_parts[] = "- Không thích: " . $u_profile['disliked_ingredients'];
            if (!empty($u_profile['allergies'])) $dna_parts[] = "- DỊ ỨNG: " . $u_profile['allergies'];
            
            if (!empty($dna_parts)) {
                $culinary_dna = "--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n" . implode("\n", $dna_parts);
                if ($chef_requirements) {
                    $chef_requirements .= "\n\n" . $culinary_dna;
                } else {
                    $chef_requirements = $culinary_dna;
                }
            }
        }
    }


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
        
        // Tính tiền dịch vụ Bespoke
        if ($type === 'bespoke') {
            $budget_str = $_POST['chef_budget'] ?? '';
            $per_guest = 0;
            if (strpos($budget_str, 'Dưới 1.500.000') !== false) $per_guest = 1500000;
            else if (strpos($budget_str, '1.500.000 đ - 3.000.000') !== false) $per_guest = 2000000;
            else if (strpos($budget_str, '3.000.000 đ - 5.000.000') !== false) $per_guest = 4000000;
            else if (strpos($budget_str, 'Trên 5.000.000') !== false) $per_guest = 5000000;
            $total_amount += ($per_guest * $guests);
        }

        if ($has_candle) $total_amount += 50000;
        if ($has_handwritten_card) $total_amount += 30000;
        if (isset($_POST['has_bespoke_flower'])) $total_amount += 200000;

        // Tính phí Đầu bếp tại gia
        if ($type === 'chef') {
            if ($guests <= 2) $total_amount += 250000;
            else if ($guests <= 6) $total_amount += 500000;
            else if ($guests <= 12) $total_amount += 1000000;
            else $total_amount += 1200000;
        }

        // Tính 30% cọc
        $deposit_amount = $total_amount * 0.3;

        // 2. Lưu đơn đặt bàn chính vào service_bookings (Bổ sung user_id và deposit_amount)
        $stmt_booking = $db->prepare("INSERT INTO service_bookings (user_id, service_type, customer_name, customer_phone, booking_date, guests, message, table_id, combo_id, total_amount, deposit_amount, status, event_type, decor_package, has_cake, has_flower, has_candle, has_handwritten_card, card_message, flower_preference, music_playlist, light_tone, chef_requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_booking->execute([$user_id, $type, $name, $phone, $date, $guests, $msg, $table_id, $combo_id, $total_amount, $deposit_amount, $event_type, $decor_package, $has_cake, $has_flower, $has_candle, $has_handwritten_card, $card_message, $flower_preference, $music_playlist, $light_tone, $chef_requirements]);
        $last_id = $db->lastInsertId();

        // 3. Lưu chi tiết các món ăn khách chọn lẻ
        if (!empty($_POST['menu_items'])) {
            $stmt_details = $db->prepare("INSERT INTO booking_details (booking_id, menu_id, item_type, quantity, notes) VALUES (?, ?, 'food', ?, ?)");
            foreach ($_POST['menu_items'] as $menu_id) {
                $qty = $_POST['quantity'][$menu_id] ?? 1;
                $note = $_POST['food_notes'][$menu_id] ?? '';
                $stmt_details->execute([$last_id, $menu_id, $qty, $note]);
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
        // Không khóa cứng bàn nữa, trạng thái bàn sẽ được tính toán động (Dynamic Availability) dựa vào thời gian đặt (booking_date)

        // Xác nhận thành công toàn bộ quá trình
        $db->commit();

        // --- THÊM: GỬI THÔNG BÁO TELEGRAM CHO QUẢN TRỊ VIÊN ---
        require_once 'notification_helper.php';
        $time_str = date('H:i d/m', strtotime($date));
        $event_str = $event_type ? " ($event_type)" : "";
        $booking_msg = "<b>🆕 ĐƠN ĐẶT BÀN MỚI</b>\n\n";
        $booking_msg .= "👤 Khách: <b>$name</b>\n";
        $booking_msg .= "📞 SĐT: $phone\n";
        
        // Fetch VIP DNA for Telegram
        $vip_dna = "";
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt_dna = $db->prepare("SELECT allergies, doneness, flavor_profile FROM users WHERE id = ?");
                $stmt_dna->execute([$_SESSION['user_id']]);
                if ($dna_row = $stmt_dna->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($dna_row['allergies'])) {
                        $booking_msg .= "⚠️ Dị ứng y tế: <b>{$dna_row['allergies']}</b>\n";
                    }
                    if (!empty($dna_row['doneness']) || !empty($dna_row['flavor_profile'])) {
                        $booking_msg .= "🧬 DNA: " . trim("{$dna_row['doneness']} | {$dna_row['flavor_profile']}", " |") . "\n";
                    }
                }
            } catch(Exception $e) {}
        }

        $booking_msg .= "⏰ Lúc: $time_str\n";
        $booking_msg .= "👥 Khách: $guests người\n";
        $booking_msg .= "🏷 Loại: " . ucfirst($type) . $event_str . "\n";
        if ($has_candle) $booking_msg .= "🕯 Bespoke: Nến thơm\n";
        if ($has_handwritten_card) $booking_msg .= "✉️ Bespoke: Thiệp viết tay ($card_message)\n";
        if (isset($_POST['has_bespoke_flower'])) $booking_msg .= "💐 Bespoke: Hoa ($flower_preference)\n";
        if ($chef_requirements) $booking_msg .= "👨‍🍳 Yêu cầu Bếp trưởng: <i>$chef_requirements</i>\n";
        if ($msg) $booking_msg .= "📝 Ghi chú: <i>$msg</i>\n\n";
        $booking_msg .= "💰 Dự kiến: " . number_format($total_amount) . " VNĐ\n";
        $booking_msg .= "👉 Vui lòng duyệt đơn trên Admin.";

        @sendTelegramNotification($booking_msg);

        if ($deposit_amount > 0) {
            echo "<script>
                window.location.href = '../booking_payment.php?id=" . $last_id . "';
            </script>";
        } else {
            echo "<script>
                alert('Đặt dịch vụ thành công! Nhà hàng sẽ liên hệ lại để xác nhận sớm nhất.');
                window.location.href = '../booking_success.php?success=1&id=" . $last_id . "';
            </script>";
        }
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
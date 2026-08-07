<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;

require_once 'database.php';
require_once 'inventory_helper.php';
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

    // Tính toán quy mô ekip Đầu bếp tại gia
    if ($type === 'chef') {
        $crew_msg = "";
        if ($guests <= 4) {
            $crew_msg = "Quy mô ekip (1-4 khách): 1 Chef + 1 Phục vụ";
        } elseif ($guests <= 8) {
            $crew_msg = "Quy mô ekip (5-8 khách): 1 Chef + 1 Phụ bếp + 1 Phục vụ";
        } else {
            $crew_msg = "Quy mô ekip (9-16 khách): 1 Chef + 1 Phụ bếp + 2 Phục vụ";
        }
        $msg .= "\n[Đầu bếp tại gia] " . $crew_msg;
    }

    $chef_requirements = $_POST['chef_requirements'] ?? null;
    $chef_id_val = isset($_POST['chef_id']) && (int)$_POST['chef_id'] > 0 ? (int)$_POST['chef_id'] : null;

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

    $booking_timestamp = strtotime($date);

    // BẢO MẬT: BẮT BUỘC ĐẶT TRƯỚC THEO TỪNG LOẠI DỊCH VỤ
    $min_hours = 1;
    $is_anniversary = isset($_POST['add_anniversary_service']) && $_POST['add_anniversary_service'] == '1';
    
    if ($type === 'event' || $is_anniversary) $min_hours = 3;
    if ($type === 'chef') $min_hours = 24;
    if ($type === 'bespoke') $min_hours = 48;
    
    // Cho phép chênh lệch tối đa 5 phút (300 giây) để trừ hao độ trễ thao tác điền form
    $min_timestamp = time() + ($min_hours * 3600) - 300;

    if ($booking_timestamp < $min_timestamp) {
        $setting_stmt = $db->query("SELECT key_value FROM settings WHERE key_name = 'open_time'");
        $open_time_setting = $setting_stmt->fetchColumn() ?: '09:00 AM - 11:00 PM';
        
        $error_msg = "Quý khách vui lòng chọn giờ đến sau thời điểm hiện tại ít nhất {$min_hours} tiếng.";
        if ($type === 'event' || $is_anniversary) $error_msg = "Dịch vụ Tiệc kỷ niệm yêu cầu chuẩn bị chu đáo, quý khách vui lòng đặt trước ít nhất 3 tiếng.";
        if ($type === 'chef') $error_msg = "Dịch vụ Đầu bếp tại gia cần chọn lọc nguyên liệu riêng, quý khách vui lòng đặt trước ít nhất 24 tiếng.";
        if ($type === 'bespoke') $error_msg = "Dịch vụ Thiết kế riêng đòi hỏi sự chuẩn bị hoàn mỹ nhất, quý khách vui lòng đặt trước ít nhất 48 tiếng.";

        echo "<script>alert('{$error_msg}'); window.history.back();</script>";
        exit;
    }

    // BẢO MẬT: KIỂM TRA GIỜ MỞ CỬA CỦA NHÀ HÀNG
    $setting_stmt = $db->query("SELECT key_value FROM settings WHERE key_name = 'open_time'");
    $open_time_setting = $setting_stmt->fetchColumn();
    if (!$open_time_setting) $open_time_setting = '09:00 AM - 11:00 PM'; // Mặc định nếu chưa cài

    $time_parts = explode('-', $open_time_setting);
    if (count($time_parts) == 2) {
        $start_time_24 = date('H:i', strtotime(trim($time_parts[0])));
        $end_str = trim($time_parts[1]);
        $end_time_24 = date('H:i', strtotime($end_str));
        
        if (stripos($end_str, '12:00 PM') !== false || stripos($end_str, '12:00 AM') !== false) {
            $end_time_24 = '23:59';
        }
        
        $booking_time_only = date('H:i', $booking_timestamp);
        
        $isOutsideHours = false;
        if ($end_time_24 < $start_time_24) {
            if ($booking_time_only < $start_time_24 && $booking_time_only > $end_time_24) $isOutsideHours = true;
        } else {
            if ($booking_time_only < $start_time_24 || $booking_time_only > $end_time_24) $isOutsideHours = true;
        }
        
        if ($isOutsideHours) {
            echo "<script>alert('Nhà hàng hân hạnh phục vụ quý khách trong khung giờ " . htmlspecialchars($open_time_setting) . ". Vui lòng chọn lại thời gian phù hợp.'); window.history.back();</script>";
            exit;
        }
    }

    // TÍNH NĂNG ĐỒNG BỘ: KIỂM TRA TRẠNG THÁI BÀN (Luồng 1 & Luồng 2)
    if ($table_id) {
        $booking_timestamp = strtotime($date);
        $two_hours = 90 * 60;

        // 1. Kiểm tra Luồng 1 (Đặt trước trùng giờ)
        // Lấy các đơn 'Pending', 'Confirmed' trong vòng ± 2 tiếng
        $check_stmt = $db->prepare("
            SELECT id, booking_date FROM service_bookings 
            WHERE table_id = ? 
            AND status IN ('Pending', 'Confirmed') 
            AND ABS(TIMESTAMPDIFF(SECOND, booking_date, ?)) < ?
        ");
        $check_stmt->execute([$table_id, $date, $two_hours]);
        if ($check_stmt->rowCount() > 0) {
            echo "<script>alert('Bàn này đã được khách khác đặt trước hoặc sau giờ bạn chọn quá sát (trong vòng 1 tiếng rưỡi). Vui lòng chọn giờ khác hoặc bàn khác!'); window.history.back();</script>";
            exit;
        }
        
        // 2. Kiểm tra Luồng 2 (Khách vãng lai đang ngồi ăn ở POS)
        // Nếu giờ đặt bàn nằm trong khoảng từ quá khứ 1 tiếng đến tương lai 2 tiếng so với HIỆN TẠI
        $time_diff_from_now = $booking_timestamp - time();
        if ($time_diff_from_now > -3600 && $time_diff_from_now < $two_hours) {
            $pos_stmt = $db->prepare("SELECT id FROM pos_orders WHERE table_id = ? AND status = 'open'");
            $pos_stmt->execute([$table_id]);
            if ($pos_stmt->fetch()) {
                echo "<script>alert('Bàn này hiện đang có khách ngồi ăn tại quán (Luồng POS). Vui lòng chọn bàn khác!'); window.history.back();</script>";
                exit;
            }
        }
    }

    // TÍNH NĂNG ĐỒNG BỘ: CHỐNG TRÙNG LỊCH ĐẦU BẾP TẠI GIA (Luồng 3)
    if ($type === 'chef' && $chef_id_val) {
        $four_hours = 4 * 3600;
        $check_chef_stmt = $db->prepare("
            SELECT id FROM service_bookings 
            WHERE chef_id = ? 
            AND status IN ('Pending', 'Confirmed') 
            AND ABS(TIMESTAMPDIFF(SECOND, booking_date, ?)) < ?
        ");
        $check_chef_stmt->execute([$chef_id_val, $date, $four_hours]);
        if ($check_chef_stmt->rowCount() > 0) {
            echo "<script>alert('Bếp trưởng đã có lịch trình vào khung giờ này. Vui lòng chọn thời gian khác cách ít nhất 4 tiếng, hoặc chọn Đầu bếp khác!'); window.history.back();</script>";
            exit;
        }
    }

    // Validation and Sanitization phase for menu items
    $validated_menu_items = [];
    if (!empty($_POST['menu_items'])) {
        foreach ($_POST['menu_items'] as $m_id) {
            $m_id = (int)$m_id;
            // Fetch food info
            $f_stmt = $db->prepare("SELECT id, name, price, max_toppings FROM foods WHERE id = ? AND status = 1");
            $f_stmt->execute([$m_id]);
            $food = $f_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$food) {
                // Invalid or inactive food item
                continue;
            }
            
            // Check stock limit
            $qty = isset($_POST['quantity'][$m_id]) ? (int)$_POST['quantity'][$m_id] : 1;
            if ($qty < 1) $qty = 1;
            $stock = getFoodInventory($db, $m_id);
            if ($qty > $stock) {
                echo "<script>alert('Món {$food['name']} vượt quá tồn kho khả dụng (Tồn kho: {$stock}).'); window.history.back();</script>";
                exit;
            }
            
            // Validate toppings
            $selected_topping_ids = $_POST['food_toppings'][$m_id] ?? [];
            $valid_toppings = [];
            $checkbox_count = 0;
            
            if (!empty($selected_topping_ids)) {
                foreach ($selected_topping_ids as $t_id) {
                    $t_id = (int)$t_id;
                    // Check if topping is associated with this food and is active
                    $ft_stmt = $db->prepare("
                        SELECT t.id, t.name, t.price, t.selection_type, t.topping_group 
                        FROM food_toppings ft
                        JOIN toppings t ON ft.topping_id = t.id
                        WHERE ft.food_id = ? AND ft.topping_id = ? AND t.status = 1
                    ");
                    $ft_stmt->execute([$m_id, $t_id]);
                    $topping = $ft_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($topping) {
                        $valid_toppings[] = $topping;
                        if ($topping['selection_type'] === 'checkbox') {
                            $checkbox_count++;
                        }
                    }
                }
            }
            
            // Validate max checkbox toppings count against max_toppings
            if ($checkbox_count > $food['max_toppings']) {
                echo "<script>alert('Số lượng topping của món {$food['name']} vượt quá giới hạn tối đa cho phép ({$food['max_toppings']}).'); window.history.back();</script>";
                exit;
            }
            
            // Sanitize notes
            $note = isset($_POST['food_notes'][$m_id]) ? $_POST['food_notes'][$m_id] : '';
            $note = strip_tags($note);
            $note = mb_substr(trim($note), 0, 255, 'UTF-8');
            
            $validated_menu_items[$m_id] = [
                'id' => $m_id,
                'name' => $food['name'],
                'base_price' => (float)$food['price'],
                'qty' => $qty,
                'toppings' => $valid_toppings,
                'note' => $note
            ];
        }
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

        $is_bespoke_menu = isset($_POST['is_bespoke_menu']) ? $_POST['is_bespoke_menu'] === '1' : false;
        
        // Đánh dấu cho Admin Panel nhận diện đơn Bespoke
        if ($is_bespoke_menu) {
            $combo_id = -1;
        }

        // Tính tiền món ăn khách chọn lẻ
        if (!$is_bespoke_menu && !empty($validated_menu_items)) {
            foreach ($validated_menu_items as $item) {
                $item_price = $item['base_price'];
                foreach ($item['toppings'] as $tp) {
                    $item_price += (float)$tp['price'];
                }
                $total_amount += $item_price * $item['qty'];
            }
        }
        
        // Tính tiền Combo
        if (!$is_bespoke_menu && $combo_id > 0) {
            $c_stmt = $db->prepare("SELECT price FROM combos WHERE id = ?");
            $c_stmt->execute([$combo_id]);
            $total_amount += $c_stmt->fetchColumn() ?: 0;
        }

        // Tính tiền trang trí (Sinh nhật / Sự kiện)
        $decor_id = $_POST['decor_package'] ?? null;
        $decor_package = null;
        $decor_price = 0;
        if ($decor_id) {
            $stmt_dec = $db->prepare("SELECT name, price FROM decor_packages WHERE id = ?");
            $stmt_dec->execute([$decor_id]);
            $dec_data = $stmt_dec->fetch(PDO::FETCH_ASSOC);
            if ($dec_data) {
                $decor_package = $dec_data['name'];
                $decor_price = (float)$dec_data['price'];
            }
        }

        if ($event_type === 'birthday') {
            $total_amount += $decor_price;
            if ($has_cake) $total_amount += 300000;
            if ($has_flower) $total_amount += 200000;
        }
        
        // Tính tiền dịch vụ Bespoke
        if ($is_bespoke_menu) {
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

        // Tính phụ phí Bàn VIP (Table Fee)
        $table_fee = 0;
        if ($table_id > 0) {
            $stmt_tf = $db->prepare("SELECT price FROM restaurant_tables WHERE id = ?");
            $stmt_tf->execute([$table_id]);
            $table_fee = (float)$stmt_tf->fetchColumn();
            $total_amount += $table_fee;
        }

        // --- TÍNH GIẢM GIÁ TỪ CỘT MỐC (MILESTONES) ---
        $milestone_id_to_redeem = null;
        if (isset($_SESSION['user_id'])) {
            $stmt_ms = $db->prepare("
                SELECT um.id as user_milestone_id, m.discount_percent, m.reward_title
                FROM user_milestones um
                JOIN milestones m ON um.milestone_id = m.id
                WHERE um.user_id = ? AND um.is_redeemed = 0 AND m.discount_percent > 0
                ORDER BY m.discount_percent DESC
                LIMIT 1
            ");
            $stmt_ms->execute([$_SESSION['user_id']]);
            $unredeemed = $stmt_ms->fetch(PDO::FETCH_ASSOC);
            if ($unredeemed) {
                $ms_discount_percent = $unredeemed['discount_percent'];
                $ms_discount_amount = ($total_amount * $ms_discount_percent) / 100;
                $total_amount -= $ms_discount_amount;
                $msg .= "\n[Hệ thống: Đã tự động giảm {$ms_discount_percent}% nhờ đặc quyền cột mốc: {$unredeemed['reward_title']}]";
                $milestone_id_to_redeem = $unredeemed['user_milestone_id'];
            }
        }
        // ---------------------------------------------

        // --- TÍNH GIẢM GIÁ SINH NHẬT (10%) ---
        if (isset($_SESSION['user_id'])) {
            $stmt_bd = $db->prepare("SELECT birthday FROM users WHERE id = ?");
            $stmt_bd->execute([$_SESSION['user_id']]);
            $u_bd = $stmt_bd->fetchColumn();
            if ($u_bd) {
                $bd_parts = explode('-', (string)$u_bd);
                if (count($bd_parts) == 3 && date('m') == $bd_parts[1] && date('d') == $bd_parts[2]) {
                    $bd_discount = $total_amount * 0.10;
                    $total_amount -= $bd_discount;
                    $msg .= "\n[Hệ thống: Đã tự động giảm 10% - Tặng Sinh nhật Quý khách]";
                }
            }
        }
        // ---------------------------------------------

        // Tính 30% cọc
        $deposit_amount = $total_amount * 0.3;

        // 2. Lưu đơn đặt bàn chính vào service_bookings (Bổ sung user_id, deposit_amount, chef_id)
        $stmt_booking = $db->prepare("INSERT INTO service_bookings (user_id, service_type, customer_name, customer_phone, booking_date, guests, message, table_id, combo_id, total_amount, table_fee, deposit_amount, status, event_type, decor_package, decor_id, has_cake, has_flower, has_candle, has_handwritten_card, card_message, flower_preference, music_playlist, light_tone, chef_requirements, chef_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_booking->execute([$user_id, $type, $name, $phone, $date, $guests, $msg, $table_id, $combo_id, $total_amount, $table_fee, $deposit_amount, $event_type, $decor_package, $decor_id, $has_cake, $has_flower, $has_candle, $has_handwritten_card, $card_message, $flower_preference, $music_playlist, $light_tone, $chef_requirements, $chef_id_val]);
        $last_id = $db->lastInsertId();

        // 3. Lưu chi tiết các món ăn khách chọn lẻ và toppings
        if (!empty($validated_menu_items)) {
            $stmt_details = $db->prepare("INSERT INTO booking_details (booking_id, menu_id, item_type, quantity, notes, toppings_info) VALUES (?, ?, 'food', ?, ?, ?)");
            $stmt_order_item_topping = $db->prepare("INSERT INTO order_item_toppings (order_item_id, topping_id, price) VALUES (?, ?, ?)");
            
            foreach ($validated_menu_items as $item) {
                $toppings_info = null;
                if (!empty($item['toppings'])) {
                    $topping_ids = array_map(function($t) { return $t['id']; }, $item['toppings']);
                    $toppings_info = implode(',', $topping_ids);
                }
                
                // Insert into booking_details
                $stmt_details->execute([$last_id, $item['id'], $item['qty'], $item['note'], $toppings_info]);
                $detail_id = $db->lastInsertId();
                
                // Insert into order_item_toppings
                if (!empty($item['toppings'])) {
                    foreach ($item['toppings'] as $tp) {
                        $stmt_order_item_topping->execute([$detail_id, $tp['id'], $tp['price']]);
                    }
                }
            }
        }

        // 4. Lưu chi tiết món ăn từ Combo (nếu có chọn Combo)
        if ($combo_id > 0) {
            $stmt_combo_foods = $db->prepare("SELECT food_id FROM combo_items WHERE combo_id = ?");
            $stmt_combo_foods->execute([$combo_id]);
            $combo_foods = $stmt_combo_foods->fetchAll(PDO::FETCH_ASSOC);

            // Fetch excluded items
            $excluded_str = $_POST['excluded_combo_items'][$combo_id] ?? '';
            $excluded_items = array_filter(explode(',', $excluded_str));

            $stmt_combo_details = $db->prepare("INSERT INTO booking_details (booking_id, menu_id, item_type, quantity, excluded_combo_items) VALUES (?, ?, 'food', 1, ?)");
            foreach ($combo_foods as $cf) {
                if(!in_array($cf['food_id'], $excluded_items)) {
                    $stmt_combo_details->execute([$last_id, $cf['food_id'], null]);
                } else {
                    // Món bị bỏ khỏi combo, có thể lưu để báo bếp hoặc không lưu. Ở đây mình chọn không chèn item này vào booking_details, hoặc chèn với ghi chú "Excluded".
                    // Quyết định tốt nhất: Bỏ qua (không insert) nhưng có thể thêm dòng thông tin chung vào ghi chú tổng.
                }
            }
        }

        // 5. Cập nhật trạng thái bàn thành "Đã đặt"
        // Không khóa cứng bàn nữa, trạng thái bàn sẽ được tính toán động (Dynamic Availability) dựa vào thời gian đặt (booking_date)

        // Đánh dấu phần thưởng cột mốc đã được sử dụng (nếu có)
        if ($milestone_id_to_redeem) {
            $stmt_redeem = $db->prepare("UPDATE user_milestones SET is_redeemed = 1, redeemed_at = NOW() WHERE id = ?");
            $stmt_redeem->execute([$milestone_id_to_redeem]);
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
        if ($type === 'chef') {
            $arrive_time = date('H:i d/m', strtotime($date) - 90 * 60);
            $booking_msg .= "👨‍🍳 Bếp có mặt lúc: $arrive_time (setup)\n";
        }
        $booking_msg .= "👥 Khách: $guests người\n";
        $booking_msg .= "🏷 Loại: " . ucfirst($type) . $event_str . "\n";
        if (isset($is_bespoke_menu) && $is_bespoke_menu) {
            $b_style = $_POST['chef_style'] ?? 'Tự do';
            $b_budget = $_POST['chef_budget'] ?? 'Chưa rõ';
            $b_occasion = $_POST['chef_occasion'] ?? 'Khác';
            $booking_msg .= "📜 Thực Đơn: Thiết Kế Riêng ($b_style - $b_budget)\n";
            $booking_msg .= "🎉 Dịp tổ chức: $b_occasion\n";
        }
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
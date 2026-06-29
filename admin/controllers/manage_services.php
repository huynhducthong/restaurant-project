<?php
// File: admin/controllers/manage_services.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /restaurant-project/public/login.php');
    exit;
}
$current_user = $_SESSION['username'] ?? 'Admin';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/inventory_helper.php';
require_once __DIR__ . '/../../config/notification_helper.php';

$db = (new Database())->getConnection();

// Lấy cấu hình kho
$auto_deduct_cfg = $db->query("SELECT key_value FROM settings WHERE key_name = 'inv_auto_deduct'")->fetchColumn();
$is_auto_deduct = (int)($auto_deduct_cfg !== false ? $auto_deduct_cfg : 1);

// --- 1. XỬ LÝ HÀNH ĐỘNG ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    // A0. HÀNH ĐỘNG BÁO GIÁ (CẬP NHẬT TIỀN CHO THỰC ĐƠN RIÊNG)
    if ($action == 'update_price' && isset($_GET['price'])) {
        $price = (float)$_GET['price'];
        $deposit = $price * 0.3; // Cọc 30%
        $db->prepare("UPDATE service_bookings SET total_amount = ?, deposit_amount = ? WHERE id = ?")->execute([$price, $deposit, $id]);
        header("Location: manage_services.php?msg=price_updated");
        exit;
    }

    // A. HÀNH ĐỘNG XÁC NHẬN (CONFIRM) & TRỪ KHO BẾP
    if ($action == 'confirm') {
        $db->beginTransaction();
        try {
            // Thông tin đơn để gửi Telegram sau khi xác nhận
            $booking_info = null;

            // 1. Kiểm tra trạng thái: Nếu đã xác nhận rồi thì không trừ kho nữa
            $check_status = $db->prepare("SELECT status FROM service_bookings WHERE id = ?");
            $check_status->execute([$id]);
            $current_status = $check_status->fetchColumn();
            if ($current_status === 'Confirmed') {
                throw new Exception("Đơn hàng này đã được xác nhận và trừ kho từ trước!");
            }

            // 2. Cập nhật trạng thái đơn hàng sang Confirmed
            $db->prepare("UPDATE service_bookings SET status = 'Confirmed' WHERE id = ?")->execute([$id]);

            // Lấy thông tin đơn (dùng cho thông báo)
            $stmt_bk = $db->prepare("SELECT sb.id, sb.service_type, sb.customer_name, sb.customer_phone, sb.booking_date, sb.guests, sb.total_amount, sb.deposit_amount, u.email FROM service_bookings sb LEFT JOIN users u ON sb.user_id = u.id WHERE sb.id = ?");
            $stmt_bk->execute([$id]);
            $booking_info = $stmt_bk->fetch(PDO::FETCH_ASSOC) ?: null;

            // NẾU TẮT TỰ ĐỘNG TRỪ KHO THÌ DỪNG LẠI TẠI ĐÂY
            if ($is_auto_deduct == 0) {
                $db->commit();
                // --- THÔNG BÁO TELEGRAM: ĐÃ XÁC NHẬN ĐƠN (không trừ kho) ---
                if ($booking_info) {
                    $time_str = date('H:i d/m/Y', strtotime($booking_info['booking_date']));
                    $svc = htmlspecialchars((string)$booking_info['service_type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $name = htmlspecialchars((string)$booking_info['customer_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $phone = htmlspecialchars((string)$booking_info['customer_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $who = htmlspecialchars((string)$current_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $msg = "✅ <b>ĐƠN DỊCH VỤ ĐÃ XÁC NHẬN</b>\n\n";
                    $msg .= "🧾 Mã đơn: <b>#{$booking_info['id']}</b>\n";
                    $msg .= "👤 Khách: <b>{$name}</b>\n";
                    $msg .= "📞 SĐT: {$phone}\n";
                    $msg .= "🏷 Loại: <b>{$svc}</b>\n";
                    $msg .= "⏰ Lúc: {$time_str}\n";
                    $msg .= "👥 Số khách: {$booking_info['guests']}\n";
                    $msg .= "👤 Xác nhận bởi: <b>{$who}</b>\n";
                    $msg .= "\n<i>Ghi chú: hệ thống đang tắt tự động trừ kho (inv_auto_deduct=0).</i>";
                    @sendTelegramNotification($msg);
                    @sendBookingEmailConfirmation($booking_info['email'] ?? '', $booking_info);
                }
                header("Location: manage_services.php?msg=confirmed_no_stock");
                exit;
            }

            // 3. Lấy danh sách các món ăn trong đơn hàng này
            $stmt_items = $db->prepare("SELECT menu_id, quantity, toppings_info FROM booking_details WHERE booking_id = ?");
            $stmt_items->execute([$id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            // Lấy cấu hình tự động trừ kho
            $auto_deduct = $db->query("SELECT key_value FROM settings WHERE key_name = 'inv_auto_deduct'")->fetchColumn();

            foreach ($items as $item) {
                $food_id = $item['menu_id'];
                $order_qty = $item['quantity'];

                // 4. Lấy định mức nguyên liệu
                $stmt_recipe = $db->prepare("
                    SELECT r.ingredient_id, r.quantity_required, r.unit as r_unit, i.item_name, i.unit_name as i_unit, i.category, i.expiry_date,
                           ic.default_warehouse_id, w.name as warehouse_name
                    FROM food_recipes r
                    JOIN inventory i ON r.ingredient_id = i.id
                    LEFT JOIN inventory_categories ic ON i.category = ic.name
                    LEFT JOIN warehouses w ON ic.default_warehouse_id = w.id
                    WHERE r.food_id = ?
                ");

                $stmt_recipe->execute([$food_id]);
                $recipes = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);

                // Lấy định mức Topping nếu khách có chọn
                if (!empty($item['toppings_info'])) {
                    $t_ids = explode(',', $item['toppings_info']);
                    $valid_t_ids = [];
                    foreach ($t_ids as $tid) {
                        if ((int)$tid > 0) $valid_t_ids[] = (int)$tid;
                    }
                    if (!empty($valid_t_ids)) {
                        $placeholders = implode(',', array_fill(0, count($valid_t_ids), '?'));
                        $stmt_topping_recipe = $db->prepare("
                            SELECT tr.item_id as ingredient_id, tr.quantity_required, '' as r_unit, i.item_name, i.unit_name as i_unit, i.category, i.expiry_date,
                                   ic.default_warehouse_id, w.name as warehouse_name
                            FROM topping_recipes tr
                            JOIN inventory i ON tr.item_id = i.id
                            LEFT JOIN inventory_categories ic ON i.category = ic.name
                            LEFT JOIN warehouses w ON ic.default_warehouse_id = w.id
                            WHERE tr.topping_id IN ($placeholders)
                        ");
                        $stmt_topping_recipe->execute($valid_t_ids);
                        $t_recipes = $stmt_topping_recipe->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Gộp vào mảng recipes chung để trừ kho
                        foreach ($t_recipes as $tr) {
                            $recipes[] = $tr;
                        }
                    }
                }

                foreach ($recipes as $rcp) {
                    // KIỂM TRA HẠN SỬ DỤNG TRƯỚC
                    if ($rcp['expiry_date'] && strtotime($rcp['expiry_date']) < strtotime('today')) {
                        throw new Exception("Nguyên liệu '" . $rcp['item_name'] . "' đã HẾT HẠN SỬ DỤNG (" . date('d/m/Y', strtotime($rcp['expiry_date'])) . "). Không thể thực hiện đơn hàng này!");
                    }

                    $ing_id = $rcp['ingredient_id'];
                    $qty_req = (float)$rcp['quantity_required'];
                    
                    $r_unit = strtolower(trim($rcp['r_unit']));
                    $i_unit = strtolower(trim($rcp['i_unit']));
                    
                    $qty_in_stock_unit = convert_to_base_unit($qty_req, $r_unit, $i_unit);
                    $total_needed = $qty_in_stock_unit * $order_qty;

                    // NẾU CÓ BẬT TỰ ĐỘNG TRỪ KHO
                    if ($auto_deduct == '1') {
                        $target_warehouse_id = (int)($rcp['default_warehouse_id'] ?: 2);
                        $warehouse_name = $rcp['warehouse_name'] ?: 'Bếp';

                        // 1. Kiểm tra kho mặc định
                        $stmt_stock = $db->prepare("SELECT quantity FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = ?");
                        $stmt_stock->execute([$ing_id, $target_warehouse_id]);
                        $primary_stock = (float)$stmt_stock->fetchColumn();

                        $deduct_primary = 0;
                        $deduct_kitchen = 0;

                        if ($primary_stock >= $total_needed) {
                            $deduct_primary = $total_needed;
                        } else {
                            // Nếu kho mặc định không đủ, lấy hết số hiện có
                            $deduct_primary = $primary_stock;
                            $remaining_needed = $total_needed - $deduct_primary;

                            // Kiểm tra kho dự phòng (Kho Bếp ID 2) nếu kho mặc định không phải là Bếp
                            if ($target_warehouse_id != 2) {
                                $stmt_kitchen = $db->prepare("SELECT quantity FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = 2");
                                $stmt_kitchen->execute([$ing_id]);
                                $kitchen_stock = (float)$stmt_kitchen->fetchColumn();

                                if ($kitchen_stock >= $remaining_needed) {
                                    $deduct_kitchen = $remaining_needed;
                                } else {
                                    // Vẫn không đủ sau khi vét cả 2 kho
                                    throw new Exception("Không đủ nguyên liệu '" . $rcp['item_name'] . "'. Tổng tồn kho (Kho $warehouse_name + Kho Bếp) chỉ có " . ($primary_stock + $kitchen_stock) . " $i_unit nhưng cần $total_needed $i_unit.");
                                }
                            } else {
                                // Nếu kho mặc định đã là Bếp mà vẫn không đủ
                                throw new Exception("Kho Bếp không đủ nguyên liệu '" . $rcp['item_name'] . "' (Cần: $total_needed, Có: $primary_stock $i_unit).");
                            }
                        }

                        // THỰC HIỆN TRỪ KHO
                        if ($deduct_primary > 0) {
                            $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE ingredient_id = ? AND warehouse_id = ?")
                               ->execute([$deduct_primary, $ing_id, $target_warehouse_id]);
                            
                            // TRỪ THEO LÔ (FEFO)
                            deductStockFEFO($db, $ing_id, $target_warehouse_id, $deduct_primary, ($current_user ?? 'Admin'), 'export');

                            $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'export', ?, ?)")
                               ->execute([$ing_id, $target_warehouse_id, $deduct_primary, 'POS (Xác nhận Món & Topping #' . $id . ')']);
                            // Ghi nhớ để hoàn kho sau này
                            $db->prepare("INSERT INTO booking_inventory_deductions (booking_id, ingredient_id, warehouse_id, quantity) VALUES (?, ?, ?, ?)")
                               ->execute([$id, $ing_id, $target_warehouse_id, $deduct_primary]);
                        }
                        if ($deduct_kitchen > 0) {
                            $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE ingredient_id = ? AND warehouse_id = 2")
                               ->execute([$deduct_kitchen, $ing_id]);
                            
                            // TRỪ THEO LÔ (FEFO)
                            deductStockFEFO($db, $ing_id, 2, $deduct_kitchen, ($current_user ?? 'Admin'), 'export');

                            $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, 2, 'export', ?, ?)")
                               ->execute([$ing_id, $deduct_kitchen, 'POS (Vét kho dự phòng #' . $id . ')']);
                            // Ghi nhớ để hoàn kho sau này
                            $db->prepare("INSERT INTO booking_inventory_deductions (booking_id, ingredient_id, warehouse_id, quantity) VALUES (?, ?, 2, ?)")
                               ->execute([$id, $ing_id, $deduct_kitchen]);
                        }

                        // Ghi nhận vào Kho Xuất (ID 6)
                        $db->prepare("INSERT INTO inventory_stocks (warehouse_id, ingredient_id, quantity) VALUES (6, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?")
                           ->execute([$ing_id, $total_needed, $total_needed]);
                    }
                }
            }

            // Cập nhật trạng thái bàn
            $stmt_table = $db->prepare("SELECT table_id FROM service_bookings WHERE id = ?");
            $stmt_table->execute([$id]);
            $table_id = $stmt_table->fetchColumn();
            if ($table_id) {
                $db->prepare("UPDATE restaurant_tables SET is_available = 0 WHERE id = ?")->execute([$table_id]);
            }

            $db->commit();

            // --- THÔNG BÁO TELEGRAM: ĐÃ XÁC NHẬN ĐƠN ---
            if ($booking_info) {
                $time_str = date('H:i d/m/Y', strtotime($booking_info['booking_date']));
                $svc = htmlspecialchars((string)$booking_info['service_type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $name = htmlspecialchars((string)$booking_info['customer_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $phone = htmlspecialchars((string)$booking_info['customer_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $who = htmlspecialchars((string)$current_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $money_total = number_format((float)($booking_info['total_amount'] ?? 0), 0, ',', '.');
                $money_dep = number_format((float)($booking_info['deposit_amount'] ?? 0), 0, ',', '.');

                $msg = "✅ <b>ĐƠN DỊCH VỤ ĐÃ XÁC NHẬN</b>\n\n";
                $msg .= "🧾 Mã đơn: <b>#{$booking_info['id']}</b>\n";
                $msg .= "👤 Khách: <b>{$name}</b>\n";
                $msg .= "📞 SĐT: {$phone}\n";
                $msg .= "🏷 Loại: <b>{$svc}</b>\n";
                $msg .= "⏰ Lúc: {$time_str}\n";
                $msg .= "👥 Số khách: {$booking_info['guests']}\n";
                $msg .= "💰 Tổng: <b>{$money_total} VNĐ</b>\n";
                $msg .= "🧾 Cọc (30%): <b>{$money_dep} VNĐ</b>\n";
                $msg .= "👤 Xác nhận bởi: <b>{$who}</b>\n";
                @sendTelegramNotification($msg);
                @sendBookingEmailConfirmation($booking_info['email'] ?? '', $booking_info);
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Đã xác nhận đơn hàng.', 'table_id' => $table_id]);
                exit;
            }
            header("Location: manage_services.php?msg=confirmed");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            echo "<script>alert('Lỗi: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "'); window.location.href='manage_services.php';</script>";
            exit;
        }
    }

    // B. XÓA
    elseif ($action == 'delete') {
        $db->beginTransaction();
        try {
            $stmt_chk = $db->prepare("SELECT sb.id, sb.table_id, sb.status, sb.service_type, sb.customer_name, sb.booking_date, sb.guests, u.email FROM service_bookings sb LEFT JOIN users u ON sb.user_id = u.id WHERE sb.id = ?");
            $stmt_chk->execute([$id]);
            $b = $stmt_chk->fetch(PDO::FETCH_ASSOC);
            if ($b) {
                    // Nếu đơn đã Confirmed → hoàn kho đúng chính xác vị trí đã trừ
                    if ($b['status'] === 'Confirmed') {
                        $stmt_deduct = $db->prepare("SELECT ingredient_id, warehouse_id, quantity FROM booking_inventory_deductions WHERE booking_id = ?");
                        $stmt_deduct->execute([$id]);
                        $deductions = $stmt_deduct->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($deductions as $d) {
                            $db->prepare("UPDATE inventory_stocks SET quantity = quantity + ? WHERE ingredient_id = ? AND warehouse_id = ?")
                               ->execute([$d['quantity'], $d['ingredient_id'], $d['warehouse_id']]);
                            
                            $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)")
                               ->execute([$d['ingredient_id'], $d['warehouse_id'], $d['quantity'], ($current_user ?? 'Admin') . ' (Hoàn kho #' . $id . ')']);
                            
                            // Giảm số lượng ở Kho Xuất (ID 6)
                            $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE ingredient_id = ? AND warehouse_id = 6")
                               ->execute([$d['quantity'], $d['ingredient_id']]);
                        }
                        // Xóa dữ liệu theo dõi sau khi đã hoàn kho
                        $db->prepare("DELETE FROM booking_inventory_deductions WHERE booking_id = ?")->execute([$id]);
                    }

                // Giải phóng bàn nếu có
                if ($b['table_id']) {
                    $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$b['table_id']]);
                }
                
                // Thay vì xóa cứng, ta đánh dấu is_archived = 1.
                // Đồng thời, nếu đơn CHƯA Hoàn Thành, ta mới chuyển trạng thái thành Cancelled.
                // Nếu đơn ĐÃ Hoàn Thành, ta GIỮ NGUYÊN trạng thái để Khách hàng xem lại.
                if ($b['status'] !== 'Completed') {
                    $db->prepare("UPDATE service_bookings SET status = 'Cancelled', is_archived = 1 WHERE id = ?")->execute([$id]);
                    if ($b['status'] !== 'Cancelled') {
                        @sendBookingCancelEmail($b['email'] ?? '', $b);
                    }
                } else {
                    $db->prepare("UPDATE service_bookings SET is_archived = 1 WHERE id = ?")->execute([$id]);
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'table_id' => $b['table_id'] ?? null]);
            exit;
        }
        header("Location: manage_services.php?msg=deleted");
        exit;
    }
    
    // B2. NO-SHOW (Khách không đến)
    elseif ($action == 'no_show') {
        $db->beginTransaction();
        try {
            $stmt_chk = $db->prepare("SELECT table_id, status FROM service_bookings WHERE id = ?");
            $stmt_chk->execute([$id]);
            $b = $stmt_chk->fetch();
            if ($b) {
                // Hoàn kho nếu đơn đã Confirmed
                if ($b['status'] === 'Confirmed') {
                    $stmt_deduct = $db->prepare("SELECT ingredient_id, warehouse_id, quantity FROM booking_inventory_deductions WHERE booking_id = ?");
                    $stmt_deduct->execute([$id]);
                    $deductions = $stmt_deduct->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($deductions as $d) {
                        $db->prepare("UPDATE inventory_stocks SET quantity = quantity + ? WHERE ingredient_id = ? AND warehouse_id = ?")
                           ->execute([$d['quantity'], $d['ingredient_id'], $d['warehouse_id']]);
                        
                        $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)")
                           ->execute([$d['ingredient_id'], $d['warehouse_id'], $d['quantity'], ($current_user ?? 'Admin') . ' (Hoàn kho No-Show #' . $id . ')']);
                        
                        $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE ingredient_id = ? AND warehouse_id = 6")
                           ->execute([$d['quantity'], $d['ingredient_id']]);
                    }
                    $db->prepare("DELETE FROM booking_inventory_deductions WHERE booking_id = ?")->execute([$id]);
                }

                // Giải phóng bàn nếu có
                if ($b['table_id']) {
                    $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$b['table_id']]);
                }
                
                // Cập nhật trạng thái thành No-Show (giữ trong danh sách hoặc lưu trữ)
                $db->prepare("UPDATE service_bookings SET status = 'No-Show', is_archived = 1 WHERE id = ?")->execute([$id]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'table_id' => $b['table_id'] ?? null]);
            exit;
        }
        header("Location: manage_services.php?msg=noshow");
        exit;
    }

    // C. HOÀN THÀNH (COMPLETED)
    elseif ($action == 'complete') {
        $db->beginTransaction();
        try {
            $stmt_chk = $db->prepare("SELECT table_id, status FROM service_bookings WHERE id = ?");
            $stmt_chk->execute([$id]);
            $b = $stmt_chk->fetch();
            if ($b && $b['status'] == 'Confirmed') {
                $db->prepare("UPDATE service_bookings SET status = 'Completed' WHERE id = ?")->execute([$id]);
                // Giải phóng bàn
                if ($b['table_id']) {
                    $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$b['table_id']]);
                }
            }
            $db->commit();
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Đã hoàn thành đơn hàng.']);
                exit;
            }
            header("Location: manage_services.php?msg=completed");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    // E. QUICK EDIT
    elseif ($action == 'quick_edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $edit_date = $_POST['booking_date'] ?? '';
        $edit_guests = (int)($_POST['guests'] ?? 0);
        $edit_table = !empty($_POST['table_id']) ? (int)$_POST['table_id'] : null;

        $db->beginTransaction();
        try {
            $stmt_old = $db->prepare("SELECT table_id, status FROM service_bookings WHERE id = ?");
            $stmt_old->execute([$id]);
            $old_data = $stmt_old->fetch();

            if ($old_data) {
                // Giải phóng bàn cũ nếu đổi bàn (chỉ khi đơn đã confirm và đang giữ bàn cũ)
                if ($old_data['table_id'] && $old_data['table_id'] != $edit_table && $old_data['status'] === 'Confirmed') {
                    $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$old_data['table_id']]);
                }
                
                $db->prepare("UPDATE service_bookings SET booking_date = ?, guests = ?, table_id = ? WHERE id = ?")
                   ->execute([$edit_date, $edit_guests, $edit_table, $id]);

                // Chiếm bàn mới (nếu đơn đã Confirmed)
                if ($old_data['status'] === 'Confirmed' && $edit_table && $old_data['table_id'] != $edit_table) {
                    $db->prepare("UPDATE restaurant_tables SET is_available = 0 WHERE id = ?")->execute([$edit_table]);
                }
            }
            $db->commit();
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật đơn hàng thành công!']);
                exit;
            }
            header("Location: manage_services.php?msg=edited");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }
    }
}

// D. RESET BÀN
if (isset($_GET['action']) && $_GET['action'] == 'reset_table' && isset($_GET['table_id'])) {
    $t_id = (int) $_GET['table_id'];
    $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$t_id]);
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'table_id' => $t_id]);
        exit;
    }
    header("Location: manage_services.php?msg=table_reset");
    exit;
}

// --- 2. DỮ LIỆU HIỂN THỊ ---
$tables = $db->query("SELECT * FROM restaurant_tables WHERE category = 'open' ORDER BY id ASC LIMIT 16")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $db->query("SELECT * FROM restaurant_tables WHERE category = 'room' ORDER BY id ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Lấy lịch đặt bàn tiếp theo (trong tương lai hoặc đang diễn ra)
$upcoming_stmt = $db->query("
    SELECT table_id, MIN(booking_date) as next_booking 
    FROM service_bookings 
    WHERE status IN ('pending', 'confirmed') 
      AND booking_date >= DATE_SUB(NOW(), INTERVAL 150 MINUTE)
    GROUP BY table_id
")->fetchAll(PDO::FETCH_KEY_PAIR);

$filter = $_GET['filter'] ?? 'all';
$services = [];

if ($filter != 'pos') {
    if ($filter == 'all') {
        $stmt = $db->prepare("SELECT sb.*, c.name AS chef_name FROM service_bookings sb LEFT JOIN chefs c ON sb.chef_id = c.id WHERE sb.is_archived = 0 ORDER BY sb.created_at DESC");
        $stmt->execute();
    } elseif ($filter == 'bespoke') {
        $stmt = $db->prepare("SELECT sb.*, c.name AS chef_name FROM service_bookings sb LEFT JOIN chefs c ON sb.chef_id = c.id WHERE sb.combo_id = -1 AND sb.is_archived = 0 ORDER BY sb.created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT sb.*, c.name AS chef_name FROM service_bookings sb LEFT JOIN chefs c ON sb.chef_id = c.id WHERE sb.service_type = :type AND sb.is_archived = 0 ORDER BY sb.created_at DESC");
        $stmt->execute([':type' => $filter]);
    }
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($filter == 'all' || $filter == 'pos') {
    $stmt_pos = $db->prepare("
        SELECT id, 'pos' as service_type, 'Khách Vãng Lai' as customer_name, '' as customer_phone, created_at as booking_date, guests, status, total_amount, '' as chef_requirements, '' as message, 1 as is_pos, NULL as combo_id, NULL as chef_name
        FROM pos_orders 
        WHERE status IN ('paid', 'open')
    ");
    $stmt_pos->execute();
    $pos_orders = $stmt_pos->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pos_orders as &$po) {
        $po['status'] = ($po['status'] == 'paid') ? 'Completed' : 'Pending';
        $services[] = $po;
    }
    
    usort($services, function($a, $b) {
        return strtotime($b['booking_date']) - strtotime($a['booking_date']);
    });
}

include '../../public/admin_layout_header.php';
?>

<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }
</style>

<div class="main-content p-4">
    <!-- SƠ ĐỒ BÀN -->
    <div class="card card-custom p-4">
        <div class="section-header">
            <i class="fas fa-th-large" style="color: var(--gold); font-size: 1.5rem;"></i>
            <h4 class="fw-bold m-0">Sơ đồ bàn & Phòng VIP</h4>
        </div>
        <div class="floor-plan-wrapper" style="background: var(--bg-cream); border-radius: 10px; overflow: hidden; border: 1px solid rgba(0,0,0,0.1);">
            <?php 
                $is_admin = true;
                $t_open = $tables;
                $t_room = $rooms;
                include '../../views/shared/floor_plan.php'; 
            ?>
        </div>
    </div>

    <!-- DANH SÁCH YÊU CẦU -->
    <div class="card card-custom p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0"><i class="fas fa-clipboard-list me-2" style="color: var(--gold);"></i>Danh sách yêu cầu dịch vụ</h4>
            <div class="d-flex gap-2">
                <div class="btn-group">
                    <?php foreach (['all' => 'Tất cả', 'table' => 'Đặt bàn', 'birthday' => 'Tiệc', 'chef' => 'Đầu bếp', 'pos' => 'Khách Vãng Lai (POS)', 'bespoke' => '✨ Thiết kế riêng'] as $k => $v): ?>
                        <a href="?filter=<?= $k ?>"
                            class="btn filter-btn <?= $filter == $k ? 'btn-dark' : 'btn-outline-gold' ?>"><?= $v ?></a>
                    <?php endforeach; ?>
                </div>
                <a href="export_bookings.php?filter=<?= $filter ?>" class="btn btn-success" style="border-radius: 0; display: flex; align-items: center;"><i class="fas fa-file-excel me-1"></i> Xuất Excel (CSV)</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Khách hàng</th>
                        <th>Dịch vụ</th>
                        <th>Thời gian</th>
                        <th>Khách</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">
                                        <?= htmlspecialchars(strtoupper(substr($s['customer_name'], 0, 1))) ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($s['customer_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($s['customer_phone']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($s['service_type'])) ?></span>
                                <?php if ($s['combo_id'] == -1): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-warning text-dark border-gold" style="font-size: 10px; border: 1px solid var(--gold); background: #fff5e6;"><i class="fas fa-scroll me-1" style="color:#b38600;"></i>Thiết kế riêng</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($s['service_type'] == 'home' && !empty($s['chef_name'])): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-info text-dark border" style="font-size: 10px;"><i class="fas fa-user-tie me-1"></i>Bếp: <?= htmlspecialchars($s['chef_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= date('d/m/Y H:i', strtotime($s['booking_date'])) ?></strong>
                                <?php if($s['service_type'] == 'birthday'): ?>
                                    <div class="small text-danger fw-bold mt-1"><i class="fas fa-crown me-1"></i>PHÒNG VIP</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($s['guests']) ?></strong></td>
                            <td>
                                <?php if ($s['status'] == 'Pending'): ?>
                                    <span class="badge-status bg-warning text-dark">Chờ duyệt</span>
                                <?php elseif ($s['status'] == 'Confirmed'): ?>
                                    <span class="badge-status bg-success">Đã xác nhận</span>
                                <?php elseif ($s['status'] == 'Cancelled'): ?>
                                    <span class="badge-status bg-danger">Đã hủy</span>
                                    <?php if (strpos($s['message'], 'Khách tự hủy') !== false): ?>
                                        <i class="fas fa-exclamation-triangle text-danger ms-1" title="Khách tự hủy từ trang cá nhân"></i>
                                    <?php endif; ?>
                                <?php elseif ($s['status'] == 'Completed'): ?>
                                    <span class="badge-status bg-info text-dark">Đã hoàn thành</span>
                                <?php elseif ($s['status'] == 'No-Show'): ?>
                                    <span class="badge-status bg-dark text-white border"><i class="fas fa-user-slash me-1"></i>Không đến</span>
                                <?php else: ?>
                                    <span class="badge-status bg-secondary"><?= $s['status'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (!isset($s['is_pos'])): ?>
                                    <button class="btn btn-sm btn-outline-secondary btn-view-detail" data-id="<?= $s['id'] ?>"
                                        data-name="<?= htmlspecialchars($s['customer_name']) ?>"
                                        data-status="<?= $s['status'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <?php if (in_array($s['status'], ['Pending', 'Confirmed'])): ?>
                                        <button class="btn btn-sm btn-outline-info btn-edit-service" data-id="<?= $s['id'] ?>" title="Chỉnh sửa đơn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($s['status'] == 'Pending'): ?>
                                        <?php if ($s['total_amount'] == 0 || !empty($s['chef_requirements'])): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-quote-price" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['customer_name']) ?>" title="Báo giá cho khách">
                                                <i class="fas fa-file-invoice-dollar"></i> Báo giá
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-gold btn-confirm-ajax" data-id="<?= $s['id'] ?>"
                                            data-name="<?= htmlspecialchars($s['customer_name']) ?>">
                                            <i class="fas fa-check me-1"></i> Xác nhận
                                        </button>
                                    <?php elseif ($s['status'] == 'Confirmed'): ?>
                                        <button class="btn btn-sm btn-outline-success btn-complete-service" data-id="<?= $s['id'] ?>" title="Khách đã dùng bữa xong">
                                            <i class="fas fa-check-double me-1"></i> Hoàn thành
                                        </button>
                                        <button class="btn btn-sm btn-dark btn-noshow-service" data-id="<?= $s['id'] ?>" title="Khách không đến (No-Show)">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-outline-danger btn-delete-service" data-id="<?= $s['id'] ?>" title="Lưu trữ (Ẩn khỏi danh sách)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary btn-view-detail" data-id="<?= $s['id'] ?>" data-is-pos="1"
                                        data-name="<?= htmlspecialchars($s['customer_name']) ?>"
                                        data-status="<?= $s['status'] ?>">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </button>
                                    <span class="badge bg-light text-muted border py-2 px-3 ms-2"><i class="fas fa-receipt me-1"></i> Đơn tại quầy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--gold);">Chi tiết dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <div class="avatar-circle mx-auto mb-3" style="width:64px;height:64px;font-size:24px;" id="m-avatar">
                </div>
                <h4 class="fw-bold mb-1" id="m-name"></h4>
                <p class="text-muted mb-3"><i class="fas fa-phone-alt me-2"></i><span id="m-phone"></span></p>
                <div id="m-status" class="mb-4"></div>

                <div class="bg-light rounded p-3 text-start mb-4" style="border: 1px solid var(--border);">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Loại dịch vụ:</div>
                        <div class="col-7 fw-bold" id="m-type"></div>
                    </div>
                    <div class="row border-bottom py-2" id="row-table">
                        <div class="col-5 text-muted" id="lbl-table">Bàn/Phòng:</div>
                        <div class="col-7 fw-bold" id="m-table"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Thời gian:</div>
                        <div class="col-7 fw-bold" id="m-date"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Số khách:</div>
                        <div class="col-7 fw-bold" id="m-guests"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Combo:</div>
                        <div class="col-7 fw-bold text-dark" id="m-combo"></div>
                    </div>

                    <!-- BESPOKE SPECIAL FIELDS -->
                    <div id="bespoke-details" style="display:none;" class="mt-2 pt-2 border-top">
                        <h6 class="fw-bold mb-2" style="color:var(--gold); font-size:13px;"><i class="fas fa-magic me-1"></i> Trải nghiệm Cá nhân hóa</h6>
                        <div class="row mb-2" id="row-event-type" style="display:none;">
                            <div class="col-5 text-muted small">Dịp đặc biệt:</div>
                            <div class="col-7 fw-bold text-primary" id="m-event-type"></div>
                        </div>
                        <div class="row mb-2" id="row-decor" style="display:none;">
                            <div class="col-5 text-muted small">Gói trang trí:</div>
                            <div class="col-7 fw-bold" id="m-decor"></div>
                        </div>
                        <div class="row mb-2" id="row-addons" style="display:none;">
                            <div class="col-5 text-muted small">Dịch vụ thêm:</div>
                            <div class="col-7" id="m-addons"></div>
                        </div>
                        <div class="row mb-2" id="row-vip" style="display:none;">
                            <div class="col-5 text-muted small">Cấu hình VIP:</div>
                            <div class="col-7 fw-bold" id="m-vip"></div>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 text-muted">Món ăn:</div>
                        <div class="col-7" id="m-foods"></div>
                    </div>
                    <div class="row border-bottom py-2" id="row-chef-req" style="display:none;">
                        <div class="col-5 text-muted"><i class="fas fa-scroll"></i> Y/c Bếp trưởng:</div>
                        <div class="col-7">
                            <div id="m-chef-req" style="font-size: 0.9em; line-height: 1.5; color: #333;"></div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Ghi chú:</div>
                        <div class="col-7" id="m-msg"></div>
                    </div>
                    <hr class="border-secondary my-2">
                    <div class="row mb-2" id="row-chef-fee" style="display:none;">
                        <div class="col-5 text-muted">Phí phục vụ (Đầu bếp):</div>
                        <div class="col-7" id="m-chef-fee"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Tổng ước tính:</div>
                        <div class="col-7 fw-bold text-success" id="m-total"></div>
                    </div>
                    <div class="row" id="row-deposit">
                        <div class="col-5 text-muted fw-bold">Tiền cọc (30%):</div>
                        <div class="col-7 fw-bold text-warning fs-5" id="m-deposit"></div>
                    </div>
                </div>

                <!-- NEW: INVENTORY CHECK SECTION -->
                <div id="m-inventory-section" class="mt-3 p-3 rounded" style="display:none; background: #fff9f0; border: 1px solid #ffeeba;">
                    <h6 class="fw-bold mb-2 text-warning" style="font-size: 13px;"><i class="fas fa-exclamation-triangle me-1"></i> Kiểm tra tồn kho nguyên liệu</h6>
                    <div id="m-inventory-list" class="text-start small mb-3"></div>
                    <button type="button" id="btn-fast-transfer" class="btn btn-warning btn-sm w-100 fw-bold">
                        <i class="fas fa-truck-loading me-1"></i> Chuyển kho nhanh từ Kho Tổng
                    </button>
                </div>
            </div>
            <div class="modal-footer border-top-0 justify-content-center">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Đóng</button>
                <a href="#" id="btn-export-pdf" class="btn btn-outline-danger px-4 rounded-pill"><i
                        class="fas fa-file-pdf me-2"></i>Xuất PDF</a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL QUICK EDIT -->
<div class="modal fade" id="modalQuickEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formQuickEdit" class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-info"><i class="fas fa-edit me-2"></i>Chỉnh sửa nhanh Đơn #<span id="qe-id-text"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="booking_id" id="qe-id">
                <div class="mb-3">
                    <label class="form-label text-muted">Thời gian đặt:</label>
                    <input type="datetime-local" class="form-control rounded-0" name="booking_date" id="qe-date" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Số khách:</label>
                    <input type="number" class="form-control rounded-0" name="guests" id="qe-guests" min="1" required>
                </div>
                <div class="mb-3" id="qe-table-wrapper">
                    <label class="form-label text-muted">Đổi Bàn/Phòng:</label>
                    <select class="form-select rounded-0" name="table_id" id="qe-table">
                        <option value="">-- Không chọn (Mặc định) --</option>
                        <?php foreach ($tables as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= $t['table_code'] ?> (Bàn Lẻ <?= $t['is_available']?'Trống':'Đã đặt' ?>)</option>
                        <?php endforeach; ?>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= $r['table_code'] ?> (Phòng VIP <?= $r['is_available']?'Trống':'Đã đặt' ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary rounded-0" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-info text-white rounded-0"><i class="fas fa-save me-1"></i> Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    $(document).ready(function () {
        // Cập nhật giao diện sơ đồ bàn
        function updateSeatStatus(tableId, isAvailable) {
            if (!tableId) return;
            const seat = $(`.admin-seat[data-table-id="${tableId}"]`);
            if (seat.length) {
                if (isAvailable) {
                    seat.removeClass('seat-booked').addClass('seat-available');
                    seat.find('small').text('Trống');
                    seat.find('.btn-reset-table').remove();
                } else {
                    seat.removeClass('seat-available').addClass('seat-booked');
                    seat.find('small').text('Đã đặt');
                    if (seat.find('.btn-reset-table').length === 0) {
                        seat.prepend(`<a href="#" class="btn-reset-table" data-table-id="${tableId}" title="Reset bàn"><i class="fa fa-times"></i></a>`);
                    }
                }
            }
        }

        // --- XÁC NHẬN BẰNG AJAX ---
        $(document).on('click', '.btn-confirm-ajax', function (e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            const name = btn.data('name');

            if (!confirm(`Xác nhận yêu cầu của "${name}"?`)) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý');

            $.ajax({
                url: `manage_services.php?action=confirm&id=${id}`,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response.status === 'success') {
                        const row = btn.closest('tr');
                        row.find('.badge-status').removeClass('bg-warning text-dark').addClass('bg-success').text('Đã xác nhận');
                        
                        // Đổi nút Xác nhận thành nút Hoàn thành
                        btn.replaceWith(`
                            <button class="btn btn-sm btn-outline-success btn-complete-service" data-id="${id}" title="Khách đã dùng bữa xong">
                                <i class="fas fa-check-double me-1"></i> Hoàn thành
                            </button>
                        `);
                        
                        if (response.table_id) {
                            updateSeatStatus(response.table_id, false);
                        }
                    } else {
                        alert('Lỗi: ' + (response.message || 'Không thể xác nhận'));
                        btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Xác nhận');
                    }
                },
                error: function () {
                    alert('Lỗi kết nối, thử lại sau.');
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Xác nhận');
                }
            });
        });

        // --- HOÀN THÀNH BẰNG AJAX ---
        $(document).on('click', '.btn-complete-service', function (e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            if (!confirm('Xác nhận khách đã dùng bữa xong và thanh toán?')) return;
            
            btn.prop('disabled', true);
            $.ajax({
                url: `manage_services.php?action=complete&id=${id}`,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response.status === 'success') {
                        location.reload();
                    }
                }
            });
        });

        // --- XÓA (LƯU TRỮ) BẰNG AJAX ---
        $(document).on('click', '.btn-delete-service', function (e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            if (!confirm('Ẩn yêu cầu này khỏi danh sách quản lý? (Khách vẫn có thể xem lại trong lịch sử cá nhân)')) return;
            
            btn.prop('disabled', true);
            $.ajax({
                url: `manage_services.php?action=delete&id=${id}`,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response.status === 'success') {
                        btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
                        if (response.table_id) {
                            updateSeatStatus(response.table_id, true);
                        }
                    } else {
                        btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Lỗi kết nối');
                    btn.prop('disabled', false);
                }
            });
        });

        // --- NO-SHOW (KHÁCH KHÔNG ĐẾN) BẰNG AJAX ---
        $(document).on('click', '.btn-noshow-service', function (e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            if (!confirm('Khách không đến (No-Show)? Thao tác này sẽ hoàn trả bàn và nguyên liệu, nhưng vẫn tính tiền cọc vào doanh thu.')) return;
            
            btn.prop('disabled', true);
            $.ajax({
                url: `manage_services.php?action=no_show&id=${id}`,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response.status === 'success') {
                        btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
                        if (response.table_id) {
                            updateSeatStatus(response.table_id, true);
                        }
                    } else {
                        btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Lỗi kết nối');
                    btn.prop('disabled', false);
                }
            });
        });

        // --- RESET BÀN BẰNG AJAX ---
        $(document).on('click', '.btn-reset-table', function (e) {
            e.preventDefault();
            const btn = $(this);
            let id = btn.data('table-id');
            if (!id && btn.attr('href')) {
                id = btn.attr('href').split('table_id=')[1];
            }
            if (!id || !confirm('Reset bàn này về trạng thái Trống?')) return;
            
            $.ajax({
                url: `manage_services.php?action=reset_table&table_id=${id}`,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response.status === 'success') {
                        updateSeatStatus(id, true);
                    }
                }
            });
        });

        // --- XEM CHI TIẾT (AJAX lấy thông tin) ---
        $(document).on('click', '.btn-view-detail', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const status = $(this).data('status');
            const isPos = $(this).data('is-pos') == 1;

            $('#m-name').text(name);
            $('#m-avatar').text(name.charAt(0).toUpperCase());
            $('#m-status').html(status === 'Pending'
                ? '<span class="badge bg-warning text-dark">Chờ duyệt</span>'
                : '<span class="badge bg-success">Đã hoàn thành</span>');
            
            const apiUrl = isPos ? `../ajax/ajax_get_pos_detail.php?id=${id}` : `../ajax/ajax_get_booking_detail.php?id=${id}`;

            // Lấy chi tiết bằng AJAX
            $.getJSON(apiUrl, function (data) {
                if (data) {
                    $('#m-phone').text(data.customer_phone);
                    $('#m-type').text(data.service_type.toUpperCase());
                    if (data.service_type === 'chef') {
                        $('#row-table').hide();
                    } else {
                        $('#row-table').show();
                        $('#lbl-table').text('Bàn/Phòng:');
                        $('#m-table').html(data.table_code ? `<span class="text-primary">${data.table_code}</span>` : '<span class="text-danger">Chưa chọn</span>');
                    }
                    $('#m-date').text(data.booking_date);
                    $('#m-guests').text(data.guests + ' người');
                    $('#m-combo').text(data.combo_name ? data.combo_name : 'Không');
                    
                    // Xử lý thông tin Bespoke / Kỷ niệm
                    let showBespoke = false;
                    
                    if (data.event_type) {
                        $('#row-event-type').show();
                        $('#m-event-type').text(data.event_type);
                        showBespoke = true;
                    } else { $('#row-event-type').hide(); }
                    
                    if (data.decor_package) {
                        $('#row-decor').show();
                        $('#m-decor').text(data.decor_package);
                        showBespoke = true;
                    } else { $('#row-decor').hide(); }
                    
                    let addons = [];
                    if (parseInt(data.has_cake)) addons.push('<span class="badge bg-light text-dark border me-1 mb-1"><i class="fas fa-birthday-cake text-danger me-1"></i>Bánh kem</span>');
                    if (parseInt(data.has_flower)) addons.push(`<span class="badge bg-light text-dark border me-1 mb-1" title="${data.flower_preference || ''}"><i class="fas fa-seedling text-success me-1"></i>Hoa tươi${data.flower_preference ? ' (' + data.flower_preference + ')' : ''}</span>`);
                    if (parseInt(data.has_candle)) addons.push('<span class="badge bg-light text-dark border me-1 mb-1"><i class="fas fa-fire text-warning me-1"></i>Nến thơm</span>');
                    if (parseInt(data.has_handwritten_card)) addons.push(`<span class="badge bg-light text-dark border me-1 mb-1" title="${data.card_message || ''}"><i class="fas fa-envelope text-primary me-1"></i>Thiệp tay${data.card_message ? ' (' + data.card_message + ')' : ''}</span>`);
                    
                    if (addons.length > 0) {
                        $('#row-addons').show();
                        $('#m-addons').html(addons.join(''));
                        showBespoke = true;
                    } else { $('#row-addons').hide(); }
                    
                    if (data.music_playlist || data.light_tone) {
                        $('#row-vip').show();
                        let vipConfig = [];
                        if (data.music_playlist) vipConfig.push('<i class="fas fa-music me-1"></i> ' + data.music_playlist);
                        if (data.light_tone) vipConfig.push('<i class="fas fa-lightbulb me-1"></i> ' + data.light_tone);
                        $('#m-vip').html(vipConfig.join('<br>'));
                        showBespoke = true;
                    } else { $('#row-vip').hide(); }
                    
                    if (showBespoke) {
                        $('#bespoke-details').show();
                    } else {
                        $('#bespoke-details').hide();
                    }
                    
                    let foodsHtml = '';
                    if (data.foods && data.foods.length > 0) {
                        data.foods.forEach(f => {
                            let warningHtml = '';
                            if (f.allergy_warning) {
                                warningHtml = ` <span class="badge bg-danger ms-1" style="font-size: 10px;" title="Nguyên liệu món này chứa chất gây dị ứng cho khách!"><i class="fas fa-exclamation-triangle me-1"></i>Dị ứng: ${f.conflict_allergens}</span>`;
                            }
                            foodsHtml += `<div class="small fw-bold text-dark" style="margin-top: 5px;">- ${f.food_name} (x${f.quantity})${warningHtml}</div>`;
                            if (f.toppings_list && f.toppings_list.length > 0) {
                                foodsHtml += `<div class="small text-muted" style="margin-left: 10px; font-size: 11.5px; color: #cda45e !important;"><i class="fas fa-plus-circle me-1" style="font-size:10px;"></i>${f.toppings_list.join(', ')}</div>`;
                            }
                            if (f.notes && f.notes.trim() !== '') {
                                foodsHtml += `<div class="small text-danger" style="margin-left: 10px; font-size: 11.5px; font-style: italic;"><i class="fas fa-pen me-1" style="font-size:9px;"></i>${f.notes}</div>`;
                            }
                        });
                    } else {
                        foodsHtml = 'Không có';
                    }
                    $('#m-foods').html(foodsHtml);

                    function formatNotes(text) {
                        if (!text) return '';
                        let html = text.replace(/\n/g, '<br>');
                        html = html.replace(/- Độ chín:/g, '<span class="fw-bold">- Độ chín:</span>');
                        html = html.replace(/- Hương vị:/g, '<span class="fw-bold">- Hương vị:</span>');
                        
                        html = html.replace(/(?:-\s*)?DỊ ỨNG:\s*(.*?)(?=\s*\||<br>|$)/g, '<span class="badge bg-danger text-white" style="font-size: 11px;"><i class="fas fa-exclamation-triangle me-1"></i>DỊ ỨNG</span> <strong class="text-danger">$1</strong>');
                        html = html.replace(/Chế độ ăn:\s*(.*?)(?=\s*\||<br>|$)/g, '<span class="fw-bold"><i class="fas fa-leaf me-1 text-success"></i>Chế độ ăn:</span> <strong>$1</strong>');
                        html = html.replace(/Mục đích:\s*(.*?)(?=\s*\||<br>|$)/g, '<span class="fw-bold"><i class="fas fa-glass-cheers me-1 text-info"></i>Mục đích:</span> <strong>$1</strong>');
                        html = html.replace(/--- HỒ SƠ KHẨU VỊ \(CULINARY DNA\) ---/g, '<br><span class="fw-bold"><i class="fas fa-dna me-1 text-secondary"></i>HỒ SƠ KHẨU VỊ (CULINARY DNA)</span><br>');
                        
                        return html;
                    }

                    $('#m-msg').html(data.message ? formatNotes(data.message) : '<span class="text-muted">Không có ghi chú.</span>');
                    
                    // --- YÊU CẦU BẾP TRƯỞNG ---
                    if (data.chef_requirements) {
                        $('#row-chef-req').show();
                        $('#m-chef-req').html(formatNotes(data.chef_requirements));
                    } else {
                        $('#row-chef-req').hide();
                    }

                    let formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
                    
                    if (data.service_type === 'chef') {
                        let g = parseInt(data.guests) || 2;
                        let cf = 0;
                        if (g <= 2) cf = 250000;
                        else if (g <= 6) cf = 500000;
                        else if (g <= 12) cf = 1000000;
                        else cf = 1200000;
                        
                        $('#row-chef-fee').show();
                        $('#m-chef-fee').text(formatter.format(cf));
                    } else {
                        $('#row-chef-fee').hide();
                    }

                    $('#m-total').text(formatter.format(data.total_amount || 0));
                    
                    if (isPos) {
                        $('#row-deposit').hide();
                    } else {
                        $('#row-deposit').show();
                        $('#m-deposit').text(formatter.format(data.deposit_amount || 0));
                    }

                    $('#btn-export-pdf').attr('href', '../export_pdf.php?id=' + id);

                    // --- XỬ LÝ TỒN KHO ---
                    let invHtml = '';
                    let missingItems = [];
                    if (data.inventory_check && data.inventory_check.length > 0) {
                        data.inventory_check.forEach(ing => {
                            if (!ing.is_sufficient) {
                                let statusColor = ing.can_transfer ? 'text-warning' : 'text-danger';
                                let mainInfo = ing.can_transfer ? `(Kho Tổng còn ${ing.stock_main} ${ing.unit})` : `<span class="text-danger fw-bold">(Kho Tổng cũng hết!)</span>`;
                                
                                invHtml += `<div class="mb-1 ${statusColor}">
                                    <strong>${ing.name}</strong>: Cần thêm ${ing.missing_qty.toFixed(2)} ${ing.unit} 
                                    tại kho ${ing.target_warehouse_name} ${mainInfo}
                                </div>`;
                                
                                if (ing.can_transfer) {
                                    missingItems.push({
                                        id: ing.id,
                                        qty: ing.missing_qty,
                                        target_warehouse_id: ing.target_warehouse_id
                                    });
                                }
                            }
                        });
                    }

                    if (invHtml !== '') {
                        $('#m-inventory-list').html(invHtml);
                        $('#m-inventory-section').show();
                        if (missingItems.length > 0) {
                            $('#btn-fast-transfer').show().data('items', missingItems).data('booking-id', id);
                        } else {
                            $('#btn-fast-transfer').hide();
                        }
                    } else {
                        $('#m-inventory-section').hide();
                    }
                }
            });
            new bootstrap.Modal(document.getElementById('modalDetail')).show();
        });

        // --- XỬ LÝ CHUYỂN KHO NHANH ---
        $(document).on('click', '#btn-fast-transfer', function() {
            const btn = $(this);
            const items = btn.data('items');
            const bookingId = btn.data('booking-id');
            if (!items || items.length === 0) return;
            if (!confirm('Hệ thống sẽ tự động chuyển hàng từ Kho Tổng vào kho Bếp/Bar. Bạn có chắc chắn?')) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang chuyển hàng...');
            $.ajax({
                url: '../ajax/ajax_fast_transfer.php',
                type: 'POST',
                data: { booking_id: bookingId, items: items },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        $('#m-inventory-section').fadeOut();
                    } else {
                        alert('Lỗi: ' + response.message);
                        btn.prop('disabled', false).html('<i class="fas fa-truck-loading me-1"></i> Chuyển kho nhanh từ Kho Tổng');
                    }
                },
                error: function() {
                    alert('Lỗi kết nối hệ thống.');
                    btn.prop('disabled', false).html('<i class="fas fa-truck-loading me-1"></i> Chuyển kho nhanh từ Kho Tổng');
                }
            });
        });

        // --- TỰ ĐỘNG CẬP NHẬT TRANG (POLLING) MỖI 10 GIÂY ---
        setInterval(function() {
            // Không làm phiền admin nếu họ đang xem bảng chi tiết (modal đang mở)
            if ($('.modal.show').length === 0) {
                $.ajax({
                    url: window.location.href, // Lấy trang hiện tại (kèm filter)
                    type: 'GET',
                    success: function(html) {
                        // 1. Cập nhật Bảng Dịch Vụ
                        var newTbody = $(html).find('.table tbody').html();
                        if (newTbody) {
                            $('.table tbody').html(newTbody);
                        }
                        
                        // 2. Cập nhật Sơ đồ bàn lẻ & Phòng VIP
                        var newGrid4 = $(html).find('.grid-4-cols').html();
                        var newGrid2 = $(html).find('.grid-2-cols').html();
                        if (newGrid4) $('.grid-4-cols').html(newGrid4);
                        if (newGrid2) $('.grid-2-cols').html(newGrid2);
                        
                        // 3. Cập nhật cảnh báo số màu đỏ ở Sidebar
                        var newBadge = $(html).find('a[href*="manage_services.php"] .badge-notify');
                        var oldBadge = $('a[href*="manage_services.php"] .badge-notify');
                        if (newBadge.length > 0) {
                            if (oldBadge.length > 0) {
                                oldBadge.text(newBadge.text());
                            } else {
                                $('a[href*="manage_services.php"]').append(newBadge);
                            }
                        } else {
                            oldBadge.remove();
                        }
                    }
                });
            }
        }, 10000);
    });
    
    // --- BÁO GIÁ (QUOTATION) ---
    $(document).on('click', '.btn-quote-price', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        let price = prompt(`Nhập tổng tiền (VNĐ) muốn báo giá cho khách "${name}":\nHệ thống sẽ tự động tính ra 30% tiền cọc.`);
        if (price !== null) {
            price = price.replace(/\D/g, ''); // Xóa các ký tự không phải số
            if (price !== '') {
                window.location.href = `manage_services.php?action=update_price&id=${id}&price=${price}`;
            }
        }
    });

    // --- QUICK EDIT AJAX ---
    $(document).on('click', '.btn-edit-service', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        // Fetch current data
        $.getJSON(`../ajax/ajax_get_booking_detail.php?id=${id}`, function (data) {
            if (data) {
                $('#qe-id-text').text(data.id);
                $('#qe-id').val(data.id);
                
                // Format datetime-local from Y-m-d H:i:s to Y-m-dTH:i
                let dateStr = data.booking_date;
                if(dateStr && dateStr.includes(' ')) {
                    dateStr = dateStr.replace(' ', 'T');
                    if(dateStr.length > 16) dateStr = dateStr.substring(0, 16);
                }
                
                $('#qe-date').val(dateStr);
                $('#qe-guests').val(data.guests);
                $('#qe-table').val(data.table_id || '');
                
                if (data.service_type === 'chef') {
                    $('#qe-table-wrapper').hide();
                } else {
                    $('#qe-table-wrapper').show();
                }
                
                const modal = new bootstrap.Modal(document.getElementById('modalQuickEdit'));
                modal.show();
            }
        });
    });

    $('#formQuickEdit').on('submit', function(e) {
        e.preventDefault();
        const id = $('#qe-id').val();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu');

        $.ajax({
            url: `manage_services.php?action=quick_edit&id=${id}`,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.status === 'success') {
                    location.reload();
                } else {
                    alert('Lỗi: ' + res.message);
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Lưu thay đổi');
                }
            },
            error: function() {
                alert('Có lỗi xảy ra!');
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Lưu thay đổi');
            }
        });
    });

    // DYNAMIC MAP UPDATER (ADMIN OVERVIEW)
    function updateMapAvailabilityAdmin() {
        var now = new Date();
        var date = now.toISOString().split('T')[0];
        var time = now.toTimeString().substring(0, 5);

        fetch('../../ajax_tables.php?date=' + date + '&time=' + time)
        .then(res => res.json())
        .then(data => {
            document.querySelectorAll('.fp-table').forEach(el => {
                var tid = el.getAttribute('data-id');
                if (data[tid]) {
                    el.classList.remove('available', 'booked');
                    el.classList.add(data[tid]);
                    
                    var code = el.getAttribute('data-code');
                    var statusText = data[tid] === 'available' ? 'Trống' : 'Đã đặt / Đang bận';
                    el.setAttribute('title', code + ' - ' + statusText);
                }
            });
        })
        .catch(e => console.error("Error updating map:", e));
    }

    $(document).ready(function() {
        updateMapAvailabilityAdmin();
        // Refresh every 30 seconds
        setInterval(updateMapAvailabilityAdmin, 30000);
    });
</script>
</body>
</html>

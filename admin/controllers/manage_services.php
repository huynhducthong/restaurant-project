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
            $stmt_bk = $db->prepare("SELECT id, service_type, customer_name, customer_phone, booking_date, guests, total_amount, deposit_amount FROM service_bookings WHERE id = ?");
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
                }
                header("Location: manage_services.php?msg=confirmed_no_stock");
                exit;
            }

            // 3. Lấy danh sách các món ăn trong đơn hàng này
            $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
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
                               ->execute([$ing_id, $target_warehouse_id, $deduct_primary, 'POS (Xác nhận #' . $id . ')']);
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
            $stmt_chk = $db->prepare("SELECT table_id, status FROM service_bookings WHERE id = ?");
            $stmt_chk->execute([$id]);
            $b = $stmt_chk->fetch();
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

$filter = $_GET['filter'] ?? 'all';
if ($filter == 'all') {
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE is_archived = 0 ORDER BY created_at DESC");
    $stmt->execute();
} elseif ($filter == 'bespoke') {
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE chef_requirements IS NOT NULL AND chef_requirements != '' AND is_archived = 0 ORDER BY created_at DESC");
    $stmt->execute();
} else {
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE service_type = :type AND is_archived = 0 ORDER BY created_at DESC");
    $stmt->execute([':type' => $filter]);
}
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <div class="row">
            <div class="col-md-8">
                <p class="fw-bold small mb-2">BÀN LẺ (tối đa 6 người)</p>
                <div class="grid-4-cols">
                    <?php foreach ($tables as $t):
                        $status_class = ($t['is_available'] == 1) ? 'seat-available' : 'seat-booked';
                        ?>
                        <div class="admin-seat <?= $status_class ?>" data-table-id="<?= $t['id'] ?>">
                            <?php if ($t['is_available'] == 0): ?>
                                <a href="#" class="btn-reset-table" data-table-id="<?= $t['id'] ?>" title="Reset bàn">
                                    <i class="fa fa-times"></i>
                                </a>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($t['table_code']) ?></span>
                            <small><?= $t['is_available'] ? 'Trống' : 'Đã đặt' ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <p class="fw-bold small mb-2">PHÒNG VIP</p>
                <div class="grid-2-cols">
                    <?php foreach ($rooms as $r):
                        $status_class = ($r['is_available'] == 1) ? 'seat-available' : 'seat-booked';
                        ?>
                        <div class="admin-seat <?= $status_class ?>" data-table-id="<?= $r['id'] ?>">
                            <?php if ($r['is_available'] == 0): ?>
                                <a href="#" class="btn-reset-table" data-table-id="<?= $r['id'] ?>" title="Reset bàn">
                                    <i class="fa fa-times"></i>
                                </a>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($r['table_code']) ?></span>
                            <small><?= $r['is_available'] ? 'Trống' : 'Đã đặt' ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH YÊU CẦU -->
    <div class="card card-custom p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0"><i class="fas fa-clipboard-list me-2" style="color: var(--gold);"></i>Danh sách yêu
                cầu dịch vụ</h4>
            <div class="btn-group">
                <?php foreach (['all' => 'Tất cả', 'table' => 'Đặt bàn', 'birthday' => 'Sinh nhật', 'chef' => 'Đầu bếp', 'bespoke' => '✨ Thiết kế riêng'] as $k => $v): ?>
                    <a href="?filter=<?= $k ?>"
                        class="btn filter-btn <?= $filter == $k ? 'btn-dark' : 'btn-outline-gold' ?>"><?= $v ?></a>
                <?php endforeach; ?>
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
                                <?php if (!empty($s['chef_requirements'])): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-warning text-dark border-gold" style="font-size: 10px; border: 1px solid var(--gold); background: #fff5e6;"><i class="fas fa-scroll me-1" style="color:#b38600;"></i>Thiết kế riêng</span>
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
                                <?php else: ?>
                                    <span class="badge-status bg-secondary"><?= $s['status'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary btn-view-detail" data-id="<?= $s['id'] ?>"
                                    data-name="<?= htmlspecialchars($s['customer_name']) ?>"
                                    data-status="<?= $s['status'] ?>">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <?php if ($s['status'] == 'Pending'): ?>
                                    <button class="btn btn-sm btn-outline-gold btn-confirm-ajax" data-id="<?= $s['id'] ?>"
                                        data-name="<?= htmlspecialchars($s['customer_name']) ?>">
                                        <i class="fas fa-check me-1"></i> Xác nhận
                                    </button>
                                <?php elseif ($s['status'] == 'Confirmed'): ?>
                                    <button class="btn btn-sm btn-outline-success btn-complete-service" data-id="<?= $s['id'] ?>" title="Khách đã dùng bữa xong">
                                        <i class="fas fa-check-double me-1"></i> Hoàn thành
                                    </button>
                                <?php endif; ?>

                                <button class="btn btn-sm btn-outline-danger btn-delete-service" data-id="<?= $s['id'] ?>" title="Lưu trữ (Ẩn khỏi danh sách)">
                                    <i class="fas fa-trash"></i>
                                </button>
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
    <div class="modal-dialog modal-dialog-centered">
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
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Bàn/Phòng:</div>
                        <div class="col-7 fw-bold text-danger" id="m-table"></div>
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
                    <div class="row mb-2" id="row-chef-req" style="display:none;">
                        <div class="col-5 text-muted" style="color:#e6a817;"><i class="fas fa-scroll me-1"></i>Y/c Bếp trưởng:</div>
                        <div class="col-7 fst-italic text-warning fw-semibold" id="m-chef-req" style="white-space: pre-wrap;"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Ghi chú:</div>
                        <div class="col-7" id="m-msg"></div>
                    </div>
                    <hr class="border-secondary my-2">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Tổng ước tính:</div>
                        <div class="col-7 fw-bold text-success" id="m-total"></div>
                    </div>
                    <div class="row">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
            $('#m-name').text(name);
            $('#m-avatar').text(name.charAt(0).toUpperCase());
            $('#m-status').html(status === 'Pending'
                ? '<span class="badge bg-warning text-dark">Chờ duyệt</span>'
                : '<span class="badge bg-success">Đã xác nhận</span>');
            // Lấy chi tiết bằng AJAX
            $.getJSON(`../ajax/ajax_get_booking_detail.php?id=${id}`, function (data) {
                if (data) {
                    $('#m-phone').text(data.customer_phone);
                    $('#m-type').text(data.service_type.toUpperCase());
                    $('#m-table').text(data.table_code ? data.table_code : 'Chưa chọn');
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
                            foodsHtml += `<div class="small">- ${f.name} (x${f.quantity})</div>`;
                        });
                    } else {
                        foodsHtml = 'Không có';
                    }
                    $('#m-foods').html(foodsHtml);

                    $('#m-msg').text(data.message || 'Không có ghi chú.');
                    
                    // --- YÊU CẦU BẾP TRƯỞNG ---
                    if (data.chef_requirements) {
                        $('#row-chef-req').show();
                        $('#m-chef-req').text(data.chef_requirements);
                    } else {
                        $('#row-chef-req').hide();
                    }

                    
                    let formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
                    $('#m-total').text(formatter.format(data.total_amount || 0));
                    $('#m-deposit').text(formatter.format(data.deposit_amount || 0));

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
</script>

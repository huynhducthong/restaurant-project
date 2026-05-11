<?php
// File: admin/controllers/manage_services.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /restaurant-project/login.php');
    exit;
}
$current_user = $_SESSION['username'] ?? 'Admin';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/inventory_helper.php';

$db = (new Database())->getConnection();

// --- 1. XỬ LÝ HÀNH ĐỘNG ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    // A. HÀNH ĐỘNG XÁC NHẬN (CONFIRM) & TRỪ KHO BẾP
    if ($action == 'confirm') {
        $db->beginTransaction();
        try {
            // 1. Kiểm tra trạng thái: Nếu đã xác nhận rồi thì không trừ kho nữa
            $check_status = $db->prepare("SELECT status FROM service_bookings WHERE id = ?");
            $check_status->execute([$id]);
            $current_status = $check_status->fetchColumn();
            if ($current_status === 'Confirmed') {
                throw new Exception("Đơn hàng này đã được xác nhận và trừ kho từ trước!");
            }

            // 2. Cập nhật trạng thái đơn hàng sang Confirmed
            $db->prepare("UPDATE service_bookings SET status = 'Confirmed' WHERE id = ?")->execute([$id]);

            // 3. Lấy danh sách các món ăn trong đơn hàng này
            $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
            $stmt_items->execute([$id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $food_id = $item['menu_id'];
                $order_qty = $item['quantity'];

                // 4. Lấy định mức nguyên liệu và KIỂM TRA TỒN KHO TRONG KHO TƯƠNG ỨNG (BẾP/BAR)
                $stmt_recipe = $db->prepare("
                    SELECT r.ingredient_id, r.quantity_required, r.unit as r_unit, i.item_name, i.unit_name as i_unit, i.category
                    FROM food_recipes r
                    JOIN inventory i ON r.ingredient_id = i.id
                    WHERE r.food_id = ?
                ");

                $stmt_recipe->execute([$food_id]);
                $recipes = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);

                foreach ($recipes as $rcp) {
                    $ing_id = $rcp['ingredient_id'];
                    $qty_req = (float)$rcp['quantity_required'];
                    $category = $rcp['category'];

                    // XÁC ĐỊNH KHO TƯƠNG ỨNG: Đồ uống -> Kho Bar (3), Còn lại -> Kho Bếp (2)
                    $target_warehouse_id = ($category === 'Đồ uống') ? 3 : 2;
                    $warehouse_name = ($target_warehouse_id === 3) ? 'Bar' : 'Bếp';

                    // Lấy tồn kho hiện tại của nguyên liệu này tại kho đích
                    $stmt_stock = $db->prepare("SELECT quantity FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = ?");
                    $stmt_stock->execute([$ing_id, $target_warehouse_id]);
                    $current_stock = (float)$stmt_stock->fetchColumn();
                    
                    $r_unit = strtolower(trim($rcp['r_unit']));
                    $i_unit = strtolower(trim($rcp['i_unit']));
                    
                    // SỬ DỤNG HELPER ĐỂ QUY ĐỔI ĐƠN VỊ TẬP TRUNG
                    $qty_in_stock_unit = convert_to_base_unit($qty_req, $r_unit, $i_unit);
                    $total_deduct = $qty_in_stock_unit * $order_qty;

                    // KIỂM TRA TỒN KHO: Nếu kho tương ứng không đủ nguyên liệu thì báo lỗi
                    if ($current_stock < $total_deduct) {
                        throw new Exception("Kho $warehouse_name không đủ nguyên liệu '" . $rcp['item_name'] . "' (Cần: $total_deduct $i_unit, Hiện có tại $warehouse_name: " . $current_stock . " $i_unit). Vui lòng yêu cầu xuất chuyển từ Kho Tổng xuống Kho $warehouse_name!");
                    }

                    // 4. Trực tiếp trừ số lượng trong bảng Đa Kho (Kho tương ứng)
                    $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE ingredient_id = ? AND warehouse_id = ?")
                       ->execute([$total_deduct, $ing_id, $target_warehouse_id]);

                    // 5. Ghi lịch sử xuất kho (Kèm mã kho tương ứng)
                    $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'export', ?, 'Hệ thống POS')")
                       ->execute([$ing_id, $target_warehouse_id, $total_deduct]);
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
                // Nếu đơn đã Confirmed → hoàn kho đúng vị trí trước khi xóa
                if ($b['status'] === 'Confirmed') {
                    $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
                    $stmt_items->execute([$id]);
                    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($items as $item) {
                        $stmt_recipe = $db->prepare("
                            SELECT r.ingredient_id, r.quantity_required, r.unit as r_unit, i.unit_name as i_unit, i.category
                            FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id
                            WHERE r.food_id = ?
                        ");
                        $stmt_recipe->execute([$item['menu_id']]);
                        foreach ($stmt_recipe->fetchAll(PDO::FETCH_ASSOC) as $rcp) {
                            $target_warehouse_id = ($rcp['category'] === 'Đồ uống') ? 3 : 2;
                            $qty_back = convert_to_base_unit((float)$rcp['quantity_required'], strtolower(trim($rcp['r_unit'])), strtolower(trim($rcp['i_unit']))) * $item['quantity'];
                            $db->prepare("UPDATE inventory_stocks SET quantity = quantity + ? WHERE ingredient_id = ? AND warehouse_id = ?")
                               ->execute([$qty_back, $rcp['ingredient_id'], $target_warehouse_id]);
                            $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'import', ?, ?)")
                               ->execute([$rcp['ingredient_id'], $target_warehouse_id, $qty_back, ($current_user ?? 'Admin') . ' (Hoàn kho #' . $id . ')']);
                        }
                    }
                }

                // Giải phóng bàn nếu có
                if ($b['table_id']) {
                    $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$b['table_id']]);
                }
                $db->prepare("DELETE FROM booking_details WHERE booking_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM service_bookings WHERE id = ?")->execute([$id]);
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
}

// C. RESET BÀN
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
    $stmt = $db->prepare("SELECT * FROM service_bookings ORDER BY created_at DESC");
    $stmt->execute();
} else {
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE service_type = :type ORDER BY created_at DESC");
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
                <?php foreach (['all' => 'Tất cả', 'table' => 'Đặt bàn', 'birthday' => 'Sinh nhật', 'chef' => 'Đầu bếp'] as $k => $v): ?>
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
                            <td><span
                                    class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($s['service_type'])) ?></span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($s['booking_date'])) ?></td>
                            <td><strong><?= htmlspecialchars($s['guests']) ?></strong></td>
                            <td>
                                <?php if ($s['status'] == 'Pending'): ?>
                                    <span class="badge-status bg-warning text-dark">Chờ duyệt</span>
                                <?php else: ?>
                                    <span class="badge-status bg-success">Đã xác nhận</span>
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
                                <?php endif; ?>

                                <button class="btn btn-sm btn-outline-danger btn-delete-service" data-id="<?= $s['id'] ?>">
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
                        <div class="col-7 fw-bold text-info" id="m-combo"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Món ăn:</div>
                        <div class="col-7" id="m-foods"></div>
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
                        btn.remove();
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

        // --- XÓA BẰNG AJAX ---
        $(document).on('click', '.btn-delete-service', function (e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            if (!confirm('Xóa yêu cầu này?')) return;
            
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
    });
</script>
<?php
include '../../public/admin_layout_header.php';
require_once __DIR__ . '/../../config/database.php';

$db = (new Database())->getConnection();

// --- 1. XỬ LÝ HÀNH ĐỘNG ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    // A. XÁC NHẬN (CONFIRM) & TRỪ KHO & ĐÁNH DẤU BÀN
    if ($action == 'confirm') {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE service_bookings SET status = 'Confirmed' WHERE id = ?")->execute([$id]);

            $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
            $stmt_items->execute([$id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $food_id = $item['menu_id'];
                $order_qty = $item['quantity'];

                $stmt_recipe = $db->prepare("SELECT r.ingredient_id, r.quantity_required, i.item_name, i.stock_quantity 
                    FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id WHERE r.food_id = ?");
                $stmt_recipe->execute([$food_id]);
                $recipes = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);

                foreach ($recipes as $rcp) {
                    $ing_id = $rcp['ingredient_id'];
                    $total_deduct = $rcp['quantity_required'] * $order_qty;
                    if ($rcp['stock_quantity'] < $total_deduct) {
                        throw new Exception("Nguyên liệu '{$rcp['item_name']}' không đủ trong kho.");
                    }
                    $db->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$total_deduct, $ing_id]);
                    $db->prepare("INSERT INTO inventory_history (ingredient_id, type, quantity, created_at) VALUES (?, 'export', ?, NOW())")->execute([$ing_id, $total_deduct]);
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
        // Cần giải phóng bàn và xóa details trước để tránh lỗi khóa ngoại
        $stmt_chk = $db->prepare("SELECT table_id, status FROM service_bookings WHERE id = ?");
        $stmt_chk->execute([$id]);
        $b = $stmt_chk->fetch();
        if ($b) {
            if ($b['status'] == 'Confirmed' && $b['table_id']) {
                $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$b['table_id']]);
            }
            $db->prepare("DELETE FROM booking_details WHERE booking_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM service_bookings WHERE id = ?")->execute([$id]);
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
                        <div class="col-5 text-muted">Thời gian:</div>
                        <div class="col-7 fw-bold" id="m-date"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Số khách:</div>
                        <div class="col-7 fw-bold" id="m-guests"></div>
                    </div>
                    <div class="row">
                        <div class="col-5 text-muted">Ghi chú:</div>
                        <div class="col-7" id="m-msg"></div>
                    </div>
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
            $.getJSON(`ajax_get_booking_detail.php?id=${id}`, function (data) {
                if (data) {
                    $('#m-phone').text(data.customer_phone);
                    $('#m-type').text(data.service_type.toUpperCase());
                    $('#m-date').text(data.booking_date);
                    $('#m-guests').text(data.guests + ' người');
                    $('#m-msg').text(data.message || 'Không có ghi chú.');
                    $('#btn-export-pdf').attr('href', '../export_pdf.php?id=' + id);
                }
            });
            new bootstrap.Modal(document.getElementById('modalDetail')).show();
        });
    });
</script>
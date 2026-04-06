<?php
include '../public/admin_layout_header.php'; 
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();

// --- 1. XỬ LÝ HÀNH ĐỘNG ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    // A. HÀNH ĐỘNG XÁC NHẬN (CONFIRM) & TRỪ KHO
    if ($action == 'confirm') {
        $db->beginTransaction();
        try {
            // 1. Cập nhật trạng thái đơn hàng sang Confirmed
            $db->prepare("UPDATE service_bookings SET status = 'Confirmed' WHERE id = ?")->execute([$id]);

            // 2. Lấy danh sách các món ăn trong đơn hàng này
            $stmt_items = $db->prepare("SELECT menu_id, quantity FROM booking_details WHERE booking_id = ?");
            $stmt_items->execute([$id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $food_id = $item['menu_id'];
                $order_qty = $item['quantity'];

                // 3. Lấy định mức nguyên liệu của từng món
                $stmt_recipe = $db->prepare("
                    SELECT r.ingredient_id, r.quantity_required, i.item_name, i.stock_quantity 
                    FROM food_recipes r
                    JOIN inventory i ON r.ingredient_id = i.id
                    WHERE r.food_id = ?
                ");
                $stmt_recipe->execute([$food_id]);
                $recipes = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);

                foreach ($recipes as $rcp) {
                    $ing_id = $rcp['ingredient_id'];
                    $total_deduct = $rcp['quantity_required'] * $order_qty;

                    // KIỂM TRA TỒN KHO: Nếu không đủ nguyên liệu thì báo lỗi và dừng lại
                    if ($rcp['stock_quantity'] < $total_deduct) {
                        throw new Exception("Nguyên liệu '" . $rcp['item_name'] . "' không đủ trong kho (Cần: $total_deduct, Hiện có: " . $rcp['stock_quantity'] . ")");
                    }

                    // 4. Trực tiếp trừ số lượng trong bảng inventory
                    $db->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE id = ?")
                       ->execute([$total_deduct, $ing_id]);

                    // 5. Ghi lịch sử xuất kho
                    $db->prepare("INSERT INTO inventory_history (ingredient_id, type, quantity, created_at) VALUES (?, 'export', ?, NOW())")
                       ->execute([$ing_id, $total_deduct]);
                }
            }

            $db->commit();
            header("Location: manage_services.php?msg=confirmed");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            echo "<script>alert('Lỗi: " . $e->getMessage() . "'); window.location.href='manage_services.php';</script>";
            exit;
        }
    } 
    
    // B. HÀNH ĐỘNG XÓA (DELETE)
    elseif ($action == 'delete') {
        $db->prepare("DELETE FROM service_bookings WHERE id = ?")->execute([$id]);
        header("Location: manage_services.php?msg=deleted");
        exit;
    }
}

// C. HÀNH ĐỘNG RESET TRẠNG THÁI BÀN
if (isset($_GET['action']) && $_GET['action'] == 'reset_table' && isset($_GET['table_id'])) {
    $t_id = (int)$_GET['table_id'];
    $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$t_id]);
    header("Location: manage_services.php?msg=table_reset");
    exit;
}

// --- 2. TRUY VẤN DỮ LIỆU HIỂN THỊ ---

// Lấy danh sách bàn (Khu vực mở & Phòng)
$tables = $db->query("SELECT * FROM restaurant_tables WHERE category = 'open' ORDER BY id ASC LIMIT 16")->fetchAll(PDO::FETCH_ASSOC);
$rooms  = $db->query("SELECT * FROM restaurant_tables WHERE category = 'room' ORDER BY id ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách dịch vụ kèm bộ lọc
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

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; margin-bottom: 30px; }
    .admin-grid-layout { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; }
    .admin-seat { border-radius: 8px; padding: 12px 5px; text-align: center; font-weight: 600; color: #fff; transition: transform 0.2s; font-size: 13px; min-height: 75px; display: flex; flex-direction: column; justify-content: center; position: relative; }
    .seat-available { background-color: #28a745 !important; }
    .seat-booked { background-color: #dc3545 !important; }
    .grid-4-cols { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .grid-2-cols { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .btn-reset-table { position: absolute; top: -5px; right: -5px; width: 22px; height: 22px; background: #fff; color: #dc3545; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 11px; border: 1px solid #dc3545; z-index: 10; text-decoration: none; }
    .admin-seat:hover .btn-reset-table { display: flex; }
    .badge-status { padding: 6px 12px; border-radius: 50px; font-weight: 500; font-size: 11px; }
    .avatar-circle { width: 45px; height: 45px; background: #f0e6d2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #cda45e; font-weight: 700; border: 2px solid #fff; }
</style>

<div class="main-content p-4">
    <div class="card card-custom p-4">
        <div class="admin-map-container">
            <h4 class="mb-4 fw-bold"><i class="fa fa-map-marked-alt me-2 text-warning"></i>Tình trạng & Đếm ngược</h4>
            <div class="admin-grid-layout">
                <div>
                    <p class="text-muted small fw-bold text-center mb-3">BÀN LẺ (1 NGƯỜI / 40 PHÚT)</p>
                    <div class="grid-4-cols">
                        <?php foreach($tables as $t): 
                            $status_class = ($t['is_available'] == 1) ? 'seat-available' : 'seat-booked';
                            $b_info = $db->prepare("SELECT guests, created_at FROM service_bookings WHERE table_id = ? AND status = 'Confirmed' ORDER BY created_at DESC LIMIT 1");
                            $b_info->execute([$t['id']]);
                            $booking = $b_info->fetch(PDO::FETCH_ASSOC);
                            $duration = $booking ? ($booking['guests'] * 40) : 0;
                        ?>
                            <div class="admin-seat <?= $status_class ?>" data-id="<?= $t['id'] ?>" data-start="<?= $booking ? strtotime($booking['created_at']) : 0 ?>" data-duration="<?= $duration ?>">
                                <?php if($t['is_available'] == 0): ?>
                                    <a href="?action=reset_table&table_id=<?= $t['id'] ?>" class="btn-reset-table" onclick="return confirm('Reset bàn?')"><i class="fa fa-times"></i></a>
                                <?php endif; ?>
                                <span><?= $t['table_code'] ?></span>
                                <div class="countdown-timer" id="timer-<?= $t['id'] ?>" style="font-size: 10px;"><?= $t['is_available'] ? 'TRỐNG' : '...' ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="border-start ps-4">
                    <p class="text-muted small fw-bold text-center mb-3">PHÒNG VIP</p>
                    <div class="grid-2-cols">
                        <?php foreach($rooms as $r): 
                            $status_class = ($r['is_available'] == 1) ? 'seat-available' : 'seat-booked';
                        ?>
                            <div class="admin-seat <?= $status_class ?>">
                                <span><?= $r['table_code'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-custom p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="fas fa-list-ul me-2 text-warning"></i>Danh sách Yêu cầu</h4>
            <div class="btn-group shadow-sm" style="border-radius: 50px; overflow: hidden;">
                <?php foreach(['all'=>'Tất cả', 'table'=>'Đặt bàn', 'birthday'=>'Sinh nhật', 'chef'=>'Đầu bếp'] as $k => $v): ?>
                    <a href="?filter=<?= $k ?>" class="btn btn-sm <?= $filter == $k ? 'btn-dark' : 'btn-outline-secondary' ?> px-3 border-0"><?= $v ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-muted text-uppercase" style="font-size: 11px;">
                        <th class="ps-4">Khách hàng</th>
                        <th>Dịch vụ</th>
                        <th>Thời gian</th>
                        <th>Quy mô</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3"><?= strtoupper(substr($s['customer_name'], 0, 1)) ?></div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['customer_name']) ?></div>
                                    <small class="text-muted"><?= $s['customer_phone'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border-0 shadow-sm"><?= ucfirst($s['service_type']) ?></span></td>
                        <td>
                            <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($s['booking_date'])) ?></div>
                            <small class="text-muted"><?= date('H:i', strtotime($s['booking_date'])) ?></small>
                        </td>
                        <td><strong><?= $s['guests'] ?? 0 ?></strong> <small>khách</small></td>
                        <td>
                            <?php if(($s['status'] ?? 'Pending') == 'Pending'): ?>
                                <span class="badge-status bg-warning-subtle text-warning">Chờ duyệt</span>
                            <?php else: ?>
                                <span class="badge-status bg-success-subtle text-success">Đã xác nhận</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-light text-primary btn-view-detail" 
                                    data-id="<?= $s['id'] ?>"
                                    data-name="<?= htmlspecialchars($s['customer_name']) ?>"
                                    data-status="<?= $s['status'] ?? 'Pending' ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            
                            <?php if(($s['status'] ?? 'Pending') == 'Pending'): ?>
                                <button class="btn btn-sm btn-light text-success btn-confirm-booking" data-id="<?= $s['id'] ?>">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>

                            <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Xóa yêu cầu?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 25px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Thông tin chi tiết yêu cầu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5 border-end">
                        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-4">
                            <div id="m-avatar" class="avatar-circle fs-4 me-3" style="width: 60px; height: 60px;">?</div>
                            <div>
                                <h5 id="m-name" class="fw-bold mb-0 text-dark">Họ tên</h5>
                                <small id="m-phone" class="text-muted">Số điện thoại</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px;">Trạng thái</div>
                            <div id="m-status"></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px;">Dịch vụ</div>
                            <div id="m-type" class="fw-bold text-primary">TABLE</div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px;">Ngày & Giờ</div>
                                <div id="m-date" class="fw-bold text-dark">01/01/2026</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px;">Số khách</div>
                                <div id="m-guests" class="fw-bold text-dark">1 người</div>
                            </div>
                        </div>
                        <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px;">Ghi chú</div>
                        <div id="m-msg" class="p-3 bg-light rounded-3 italic" style="font-style: italic;">Không có ghi chú.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                <a id="btn-export-pdf" href="#" class="btn btn-warning rounded-pill px-4 fw-bold text-white shadow-sm" style="background: #cda45e; border: none;">Xuất PDF</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/assets/admin/js/admin.js"></script>
<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href = 'public/login.php';</script>";
    exit();
}

$db = (new Database())->getConnection();
include 'views/client/layouts/header.php'; 

$type = $_GET['type'] ?? 'table'; 

$service_config = [
    'table' => ['title' => 'Đặt Bàn Trải Nghiệm', 'icon' => 'ph-hand-plate'],
    'birthday' => ['title' => 'Kỷ Niệm Đặc Biệt', 'icon' => 'ph-cake'],
    'chef' => ['title' => 'Đầu Bếp Riêng', 'icon' => 'ph-cooking-pot']
];
$config = $service_config[$type] ?? $service_config['table'];

// --- LẤY DANH SÁCH COMBO ---
$sql_combos = "SELECT c.*, GROUP_CONCAT(f.name SEPARATOR ' + ') as list_foods 
               FROM combos c
               LEFT JOIN combo_items ci ON c.id = ci.combo_id
               LEFT JOIN foods f ON ci.food_id = f.id
               WHERE c.status = 1 
               GROUP BY c.id";
$stmt_combos = $db->prepare($sql_combos);
$stmt_combos->execute();
$combos = $stmt_combos->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    :root {
        --bg-dark: #121212;
        --card-bg: #1a1a1a;
        --accent-gold: #cda45e;
        --text-main: #e0e0e0;
        --text-muted: #888888;
        --border-color: #2a2a2a;
    }

    /* Giao diện chung */
    .booking-page { background-color: var(--bg-dark); font-family: 'Inter', 'Poppins', sans-serif; color: var(--text-main); padding: 100px 0; min-height: 100vh; }
    .form-container { background: var(--card-bg); border-radius: 12px; padding: 60px; border: 1px solid var(--border-color); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
    .service-header { text-align: left; margin-bottom: 50px; }
    .service-header i { font-size: 32px; color: var(--accent-gold); margin-bottom: 10px; }
    .service-header h2 { font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 500; margin-bottom: 10px; }
    .form-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--accent-gold); margin-bottom: 10px; display: block; }
    .form-control, .form-select { background: transparent !important; border: none; border-bottom: 1px solid var(--border-color); border-radius: 0; color: #fff !important; padding: 12px 0; font-size: 16px; transition: all 0.4s ease; }
    .form-control:focus, .form-select:focus { background: transparent; border-bottom-color: var(--accent-gold); box-shadow: none; color: #fff; }
    .form-select option { background: var(--card-bg); color: #fff; }

    /* Nút xem bản đồ */
    .table-selection-wrapper { display: flex; align-items: center; gap: 15px; background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 8px; border: 1px solid #2a2a2a; }
    .btn-view-map { color: var(--accent-gold); border: 1px solid var(--accent-gold); background: transparent; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; transition: all 0.3s; flex-shrink: 0; }
    .btn-view-map:hover { background: var(--accent-gold); color: #000; }

    /* GIAO DIỆN COMBO MỚI */
    .combo-selection-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 10px; }
    .combo-card-item { background: #121212; border: 1px solid var(--border-color); border-radius: 10px; padding: 15px; cursor: pointer; transition: 0.3s; position: relative; }
    .combo-card-item:hover { border-color: var(--accent-gold); }
    .combo-card-item.active { border-color: var(--accent-gold); background: rgba(205, 164, 94, 0.05); box-shadow: 0 0 15px rgba(205, 164, 94, 0.1); }
    .combo-card-item .combo-name { color: #fff; font-weight: 600; margin-bottom: 5px; display: block; }
    .combo-card-item .combo-price { color: var(--accent-gold); font-size: 14px; font-weight: bold; }
    .combo-card-item .combo-foods { font-size: 11px; color: var(--text-muted); font-style: italic; line-height: 1.4; margin-top: 5px; }
    .check-mark { position: absolute; top: 10px; right: 10px; color: var(--accent-gold); display: none; }
    .combo-card-item.active .check-mark { display: block; }

    /* Modal Sơ đồ */
    .modal-content { background-color: #1a1a1a; border: 1px solid var(--accent-gold); color: #fff; }
    .modal-header { border-bottom: 1px solid #333; }
    .btn-close { filter: invert(1); }

    .restaurant-map-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: 25px; padding: 20px; background: #1a1a1a; min-height: 450px; }
    .grid-tables { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .grid-rooms { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .map-seat { border: 2px solid #333; border-radius: 8px; padding: 12px 5px; text-align: center; cursor: pointer; transition: all 0.3s ease; color: #fff; min-height: 70px; display: flex; flex-direction: column; justify-content: center; }
    .map-seat.available { background-color: #28a745 !important; border-color: #1e7e34; }
    .map-seat.booked { background-color: #dc3545 !important; border-color: #bd2130; cursor: not-allowed; opacity: 0.6; }
    .map-seat.selected { background-color: #ffc107 !important; border-color: #e0a800; color: #000 !important; font-weight: bold; }

    .btn-submit { background: transparent; color: var(--accent-gold); border: 1px solid var(--accent-gold); border-radius: 4px; padding: 15px 40px; font-weight: 500; letter-spacing: 2px; transition: all 0.3s ease; margin-top: 30px; width: 100%; }
    .btn-submit:hover { background: var(--accent-gold); color: #000; }
</style>

<main id="main" class="booking-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 form-container">
                <form action="config/process_service_booking.php" method="POST">
                    <input type="hidden" name="service_type" value="<?= $type ?>">
                    <input type="hidden" name="selected_combo_id" id="selected_combo_id" value="0">

                    <div class="service-header text-center">
                        <i class="ph-fill <?= $config['icon'] ?>"></i>
                        <h2 class="text-gold"><?= $config['title'] ?></h2>
                        <p class="text-muted small">Vui lòng điền đầy đủ thông tin để Restaurantly phục vụ bạn tốt nhất.</p>
                    </div>

                    <div class="row group-spacer">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($_SESSION['user_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" name="customer_phone" class="form-control" placeholder="09xx xxx xxx" required>
                        </div>
                    </div>

                    <div class="row group-spacer">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Ngày & Giờ</label>
                            <input type="datetime-local" name="booking_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Số lượng khách</label>
                            <input type="number" name="guests" class="form-control" id="guests_input" value="2" min="1">
                        </div>
                    </div>

                    <div class="group-spacer mb-4">
                        <label class="form-label">Vị trí bàn / Phòng</label>
                        <div class="table-selection-wrapper">
                            <button type="button" class="btn-view-map" data-bs-toggle="modal" data-bs-target="#mapModal" title="Xem sơ đồ">
                                <i class="ph ph-map-trifold fs-4"></i>
                            </button>
                            <select name="table_id" id="table_id" class="form-select" required>
                                <option value="" data-price="0">-- Nhấn icon để xem sơ đồ --</option>
                                <optgroup label="KHU VỰC KHÁCH ĂN LẺ">
                                    <?php
                                    $tables = $db->query("SELECT * FROM restaurant_tables WHERE category = 'open' ORDER BY id ASC")->fetchAll();
                                    foreach($tables as $t):
                                    ?>
                                    <option value="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" <?= !$t['is_available'] ? 'disabled' : '' ?>>
                                        Bàn <?= $t['table_code'] ?> (<?= number_format($t['price']) ?>đ) <?= !$t['is_available'] ? '- Đã đặt' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="HÀNH LANG VIP">
                                    <?php
                                    $rooms = $db->query("SELECT * FROM restaurant_tables WHERE category = 'room' ORDER BY id ASC")->fetchAll();
                                    foreach($rooms as $r):
                                    ?>
                                    <option value="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" <?= !$r['is_available'] ? 'disabled' : '' ?>>
                                        Phòng <?= $r['table_code'] ?> (<?= number_format($r['price']) ?>đ) <?= !$r['is_available'] ? '- Đã đặt' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="group-spacer mb-4">
                        <label class="form-label">Thực đơn chọn trước (Món lẻ)</label>
                        <div class="p-3 rounded" style="background: #121212; border: 1px solid var(--border-color);">
                            <div class="menu-selection-container" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $menu_stmt = $db->query("SELECT * FROM foods WHERE status = 1");
                                while ($item = $menu_stmt->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded menu-item-row" style="border-bottom: 1px solid #2a2a2a;">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" name="menu_items[]" value="<?= $item['id'] ?>" class="form-check-input me-3 menu-check">
                                        <span class="text-white"><?= htmlspecialchars($item['name']) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="me-3 text-gold small"><?= number_format($item['price']) ?>đ</span>
                                        <input type="number" name="quantity[<?= $item['id'] ?>]" class="form-control py-1 menu-quantity" value="1" min="1" style="width: 50px; background: #1a1a1a; text-align: center; color: white; border: 1px solid #333;">
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <div class="group-spacer mb-4">
                        <label class="form-label">Gói Combo ưu đãi (Tiết kiệm hơn)</label>
                        <div class="combo-selection-grid">
                            <div class="combo-card-item active" onclick="selectBookingCombo(0, this, 0)">
                                <i class="ph ph-check-circle check-mark"></i>
                                <span class="combo-name">Không chọn Combo</span>
                                <span class="combo-price">Duy trì thực đơn lẻ</span>
                            </div>
                            <?php foreach($combos as $cb): ?>
                            <div class="combo-card-item" onclick="selectBookingCombo(<?= $cb['id'] ?>, this, <?= $cb['price'] ?>)">
                                <i class="ph ph-check-circle check-mark"></i>
                                <span class="combo-name"><?= htmlspecialchars($cb['name']) ?></span>
                                <span class="combo-price"><?= number_format($cb['price']) ?>đ</span>
                                <p class="combo-foods"><?= htmlspecialchars($cb['list_foods']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="p-3 mt-4 rounded" style="background: rgba(205, 164, 94, 0.08); border: 1px dashed var(--accent-gold);">
                        <div class="d-flex justify-content-between mb-2 small text-muted"><span>Phí vị trí:</span> <span id="display-table-price">0đ</span></div>
                        <div class="d-flex justify-content-between mb-2 small text-muted"><span>Tiền món ăn / Combo:</span> <span id="display-menu-price">0đ</span></div>
                        <hr class="border-secondary my-2">
                        <div class="d-flex justify-content-between fw-bold text-warning align-items-center">
                            <span>TIỀN CỌC TRƯỚC (30%):</span> <span id="display-deposit" class="fs-3">0đ</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">GỬI YÊU CẦU DỊCH VỤ</button>
                </form>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-gold fw-bold"><i class="ph ph-map-trifold me-2"></i>SƠ ĐỒ VỊ TRÍ NHÀ HÀNG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="restaurant-map-layout">
                    <div class="area-left">
                        <p class="text-center text-warning small mb-3 fw-bold">KHU VỰC KHÁCH ĂN LẺ (Max: 6)</p>
                        <div class="grid-tables">
                            <?php
                            $tables = $db->query("SELECT * FROM restaurant_tables WHERE category = 'open' ORDER BY id ASC LIMIT 16")->fetchAll();
                            foreach($tables as $t):
                                $status = $t['is_available'] ? 'available' : 'booked';
                            ?>
                            <div class="map-seat <?= $status ?>" data-id="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="<?= $t['table_code'] ?>">
                                <strong><?= $t['table_code'] ?></strong>
                                <small>Tối đa: 6</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="area-right border-start border-secondary ps-4">
                        <p class="text-center text-warning small mb-3 fw-bold">HÀNH LANG VIP (Max: 16)</p>
                        <div class="grid-rooms">
                            <?php
                            $rooms = $db->query("SELECT * FROM restaurant_tables WHERE category = 'room' ORDER BY id ASC LIMIT 6")->fetchAll();
                            foreach($rooms as $r):
                                $status = $r['is_available'] ? 'available' : 'booked';
                            ?>
                            <div class="map-seat <?= $status ?>" data-id="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="<?= $r['table_code'] ?>">
                                <strong><?= $r['table_code'] ?></strong>
                                <small>Tối đa: 16</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning fw-bold px-5" data-bs-dismiss="modal">XÁC NHẬN VỊ TRÍ</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Hàm xử lý chọn Combo
    function selectBookingCombo(comboId, element, price) {
        // Xóa trạng thái active của tất cả card combo
        document.querySelectorAll('.combo-card-item').forEach(item => item.classList.remove('active'));
        // Thêm active cho card được click
        element.classList.add('active');
        // Gán giá trị vào hidden input
        document.getElementById('selected_combo_id').value = comboId;
        
        // Gọi hàm tính toán lại giá tiền (nếu bạn đã có hàm JS tính tiền)
        // updateTotalPrice(); 
    }

    // Các logic JS cũ cho bản đồ và chọn bàn của bạn vẫn giữ nguyên ở đây...
</script>

<?php include 'views/client/layouts/footer.php'; ?>
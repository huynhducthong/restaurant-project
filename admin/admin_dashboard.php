<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/notification_helper.php';
$db = (new Database())->getConnection();

$total_foods = $total_users = $total_bookings = $selected_revenue = 0;
$chart_revenue = array_fill(0, 12, 0);
$status_data = [0, 0, 0];

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

$year_revenue = (int) ($_GET['year_revenue'] ?? date('Y'));
$year_booking = (int) ($_GET['year_booking'] ?? date('Y'));

// Validate and build WHERE clause for the date range
$conditions = [];
$params = [];

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $conditions[] = "DATE(created_at) >= ?";
    $params[] = $start_date;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $conditions[] = "DATE(created_at) <= ?";
    $params[] = $end_date;
}

$where_sql = count($conditions) > 0 ? implode(" AND ", $conditions) : "1=1";

// ============================================================
// KHỞI TẠO
// ============================================================
$total_foods    = $total_users = $total_bookings = 0;
$selected_revenue = $prev_month_revenue = $this_month_rev = 0;
$chart_revenue  = array_fill(0, 12, 0);
$status_data    = [0, 0, 0];
$recent_bookings = [];
$low_stock_count = 0;
$expiry_warn_count = 0;
$trend_pct = 0;
$trend_up  = true;
$absents   = [];
$cur_year  = (int)date('Y');

try {
    $total_foods = $db->query("SELECT COUNT(*) FROM foods")->fetchColumn() ?: 0;
    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
    $stmt = $db->prepare("SELECT COUNT(*) FROM service_bookings WHERE YEAR(booking_date) = ? AND is_archived = 0");
    $stmt->execute([$year_booking]);
    $total_bookings = $stmt->fetchColumn() ?: 0;
    
    // Revenue from bookings
    $stmt = $db->prepare("SELECT SUM(total_amount) FROM service_bookings WHERE status != 'Cancelled' AND $where_sql");
    $stmt->execute($params);
    $booking_rev = $stmt->fetchColumn() ?: 0;
    
    // Revenue from walk-in POS
    $stmt = $db->prepare("SELECT SUM(total_amount) FROM pos_orders WHERE status = 'paid' AND booking_id IS NULL AND $where_sql");
    $stmt->execute($params);
    $pos_rev = $stmt->fetchColumn() ?: 0;
    
    $selected_revenue = $booking_rev + $pos_rev;
    
    for ($m = 1; $m <= 12; $m++) {
        $stmt1 = $db->prepare("SELECT SUM(total_amount) FROM service_bookings WHERE status != 'Cancelled' AND MONTH(created_at)=? AND YEAR(created_at)=?");
        $stmt1->execute([$m, $year_revenue]);
        $b_rev = (int) $stmt1->fetchColumn();
        
        $stmt2 = $db->prepare("SELECT SUM(total_amount) FROM pos_orders WHERE status = 'paid' AND booking_id IS NULL AND MONTH(created_at)=? AND YEAR(created_at)=?");
        $stmt2->execute([$m, $year_revenue]);
        $p_rev = (int) $stmt2->fetchColumn();
        
        $chart_revenue[$m - 1] = $b_rev + $p_rev;
    }
    
    foreach (['Completed', 'Pending', 'Cancelled'] as $i => $st) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM service_bookings WHERE status=? AND YEAR(booking_date)=? AND is_archived = 0");
        $stmt->execute([$st, $year_booking]);
        $status_data[$i] = (int) $stmt->fetchColumn();
    }

    // Đặt bàn gần nhất
    $recent_bookings = $db->query(
        "SELECT b.*, u.full_name as user_name
         FROM service_bookings b
         LEFT JOIN users u ON b.user_id = u.id
         WHERE b.is_archived = 0
         ORDER BY b.created_at DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Cảnh báo tồn kho thấp — đúng với hệ thống đa kho
    $low_stock_count = (int)$db->query(
        "SELECT COUNT(*) FROM inventory i
         WHERE i.is_active = 1
           AND i.min_stock > 0
           AND IFNULL((SELECT SUM(s.quantity) FROM inventory_stocks s WHERE s.ingredient_id = i.id), 0) <= i.min_stock"
    )->fetchColumn();

    // Cảnh báo hàng sắp hết hạn (trong vòng 7 ngày)
    $expiry_warn_count = (int)$db->query(
        "SELECT COUNT(*) FROM inventory
         WHERE is_active = 1
           AND expiry_date IS NOT NULL
           AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
           AND expiry_date >= CURDATE()"
    )->fetchColumn();

    // 4. Cảnh báo Vắng mặt hôm nay (Bọc try-catch riêng đề phòng thiếu bảng trong DB)
    try {
        $stmt_absent = $db->query("
            SELECT e.full_name, s.shift_name 
            FROM shift_assignments sa
            JOIN employees e ON sa.employee_id = e.id
            JOIN shifts s ON sa.shift_id = s.id
            WHERE sa.work_date = CURRENT_DATE AND sa.status = 'absent'
        ");
        $absents = $stmt_absent->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $absents = []; // Bảng chưa tồn tại hoặc lỗi query
    }

    // --- TỰ ĐỘNG GỬI TELEGRAM BUỔI SÁNG (Chạy 1 lần mỗi ngày khi Admin vào Dashboard) ---
    $last_alert = $db->query("SELECT key_value FROM settings WHERE key_name = 'last_telegram_alert_date'")->fetchColumn();
    $today_str = date('Y-m-d');
    
    if ($last_alert !== $today_str) {
        $report_msg = generateMorningReport();
        if ($report_msg) {
            $sent = sendTelegramNotification($report_msg);
            if ($sent) {
                // Cập nhật ngày đã gửi để không gửi lại trong ngày hôm nay
                $db->prepare("INSERT INTO settings (key_name, key_value) VALUES ('last_telegram_alert_date', ?) 
                              ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)")->execute([$today_str]);
            }
        }
    }

    // --- BÁO CÁO DOANH THU CUỐI NGÀY (TELEGRAM): sau giờ cấu hình, tối đa 1 lần/ngày khi có người vào Dashboard ---
    $eod_enabled = ($db->query("SELECT key_value FROM settings WHERE key_name = 'telegram_eod_enabled'")->fetchColumn() ?? '1') === '1';
    $last_eod = $db->query("SELECT key_value FROM settings WHERE key_name = 'last_telegram_eod_date'")->fetchColumn();
    $eod_hour = getTelegramEodHour($db);
    if ($eod_enabled && (int) date('G') >= $eod_hour && $last_eod !== $today_str) {
        $eod_msg = generateEndOfDayRevenueReport($db, $today_str);
        $sent_eod = sendTelegramNotification($eod_msg);
        if ($sent_eod) {
            $db->prepare("INSERT INTO settings (key_name, key_value) VALUES ('last_telegram_eod_date', ?)
                          ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)")->execute([$today_str]);
        }
    }
    // Tính % tăng/giảm so với tháng trước (cho badge trend)
    $cur_m  = (int)date('m');
    $prev_m = $cur_m === 1 ? 12 : $cur_m - 1;
    $prev_y = $cur_m === 1 ? $cur_year - 1 : $cur_year;

    $stmt_prev = $db->prepare("SELECT IFNULL(SUM(total_amount),0) FROM service_bookings WHERE status != 'Cancelled' AND MONTH(created_at)=? AND YEAR(created_at)=?");
    $stmt_prev->execute([$prev_m, $prev_y]);
    $prev_month_revenue = (float)$stmt_prev->fetchColumn();

    $stmt_this = $db->prepare("SELECT IFNULL(SUM(total_amount),0) FROM service_bookings WHERE status != 'Cancelled' AND MONTH(created_at)=? AND YEAR(created_at)=?");
    $stmt_this->execute([$cur_m, $cur_year]);
    $this_month_rev = (float)$stmt_this->fetchColumn();

    if ($prev_month_revenue > 0) {
        $trend_pct = round(abs($this_month_rev - $prev_month_revenue) / $prev_month_revenue * 100, 1);
        $trend_up  = $this_month_rev >= $prev_month_revenue;
    }

    // Lấy số lượng liên hệ chưa đọc
    $new_contacts_count = (int)$db->query("SELECT COUNT(*) FROM contacts WHERE status = 'new'")->fetchColumn();

} catch (Exception $e) {
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.stat-card {
    border-radius: 14px; border: none;
    padding: 22px 24px;
    display: flex; align-items: center; gap: 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    transition: transform .2s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
.stat-icon {
    width: 54px; height: 54px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.stat-val  { font-size: 1.8rem; font-weight: 700; line-height: 1; }
.stat-label{ font-size: 12px; color: #888; margin-top: 3px; }
.trend-badge { font-size: 11px; padding: 2px 7px; border-radius: 20px; font-weight: 600; }
.filter-card { border-radius: 12px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
.chart-card  { border-radius: 14px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
.recent-table td { font-size: 13px; vertical-align: middle; }
.warn-banner {
    background: linear-gradient(135deg,#fff3cd,#ffe69c);
    border-left: 4px solid #ffc107;
    border-radius: 10px; padding: 12px 18px;
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 20px;
}
</style>

<div class="content-wrapper" style="padding:24px">

    <!-- ===== CẢNH BÁO TỒN KHO & HẾT HẠN ===== -->
    <?php if ($low_stock_count > 0): ?>
    <div class="warn-banner mb-2">
        <i class="fas fa-boxes fa-lg text-warning"></i>
        <div class="flex-grow-1">
            <strong><?= $low_stock_count ?> nguyên liệu</strong> đang ở dưới mức tồn kho tối thiểu.
        </div>
        <a href="controllers/InventoryController.php?tab=reorder" class="btn btn-sm btn-warning fw-bold shadow-sm" style="white-space:nowrap">
            <i class="fas fa-arrow-right me-1" style="pointer-events:none"></i>Xem danh sách
        </a>
    </div>
    <?php endif; ?>
    <?php if ($expiry_warn_count > 0): ?>
    <div class="warn-banner" style="background:linear-gradient(135deg,#fde8e8,#ffc5c5);border-left-color:#dc3545;">
        <i class="fas fa-calendar-times fa-lg text-danger"></i>
        <div class="flex-grow-1">
            <strong><?= $expiry_warn_count ?> mặt hàng</strong> sẽ hết hạn sử dụng trong vòng <strong>7 ngày</strong> tới.
        </div>
        <a href="controllers/InventoryController.php?tab=all" class="btn btn-sm btn-danger fw-bold shadow-sm" style="white-space:nowrap">
            <i class="fas fa-arrow-right me-1" style="pointer-events:none"></i>Kiểm tra ngay
        </a>
    </div>
    <?php endif; ?>

    <!-- ===== CẢNH BÁO VẮNG MẶT ===== -->
    <?php if (count($absents) > 0): ?>
    <div class="alert alert-danger shadow-sm border-start border-4 border-danger rounded-3 p-3">
        <h6 class="fw-bold text-danger mb-2"><i class="fas fa-user-times me-2"></i> Cảnh báo Vắng mặt hôm nay:</h6>
        <ul class="mb-0">
            <?php foreach ($absents as $a): ?>
                <li><strong><?= htmlspecialchars($a['full_name']) ?></strong> - Ca: <?= htmlspecialchars($a['shift_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- ===== FILTER CHUNG ===== -->
    <div class="card filter-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">Từ ngày</label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($start_date) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">Đến ngày</label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($end_date) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <input type="hidden" name="year_revenue" value="<?= $year_revenue ?>">
            <input type="hidden" name="year_booking" value="<?= $year_booking ?>">
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-filter me-1"></i>Lọc
                </button>
            </div>
            <div class="col-md-2">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm w-100">Tháng này</a>
            </div>
        </form>
    </div>

    <!-- ===== 5 STAT CARDS ===== -->
    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5">

        <!-- Doanh thu -->
        <div class="col">
            <div class="stat-card bg-white h-100">
                <div class="stat-icon" style="background:#fff3e0">💰</div>
                <div>
                    <div class="stat-label small">Doanh thu</div>
                    <div class="stat-val text-warning" style="font-size: 1.4rem;"><?= number_format($selected_revenue) ?>đ</div>
                    <?php if ($trend_pct > 0): ?>
                    <span class="trend-badge mt-1 d-inline-block
                                 <?= $trend_up ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">
                        <?= $trend_up ? '▲' : '▼' ?> <?= $trend_pct ?>%
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Món ăn -->
        <div class="col">
            <div class="stat-card bg-white h-100">
                <div class="stat-icon" style="background:#e8f5e9">🍽</div>
                <div>
                    <div class="stat-label small">Món ăn</div>
                    <div class="stat-val text-success"><?= $total_foods ?></div>
                </div>
            </div>
        </div>

        <!-- Đặt bàn -->
        <div class="col">
            <div class="stat-card bg-white h-100">
                <div class="stat-icon" style="background:#e3f2fd">📅</div>
                <div>
                    <div class="stat-label small">Đặt bàn <?= $year_booking ?></div>
                    <div class="stat-val text-primary"><?= number_format($total_bookings) ?></div>
                </div>
            </div>
        </div>

        <!-- Khách hàng -->
        <div class="col">
            <div class="stat-card bg-white h-100">
                <div class="stat-icon" style="background:#fce4ec">👤</div>
                <div>
                    <div class="stat-label small">Khách hàng</div>
                    <div class="stat-val text-danger"><?= number_format($total_users) ?></div>
                </div>
            </div>
        </div>

        <!-- Liên hệ mới -->
        <div class="col">
            <a href="manage_contacts.php" class="text-decoration-none">
                <div class="stat-card bg-white h-100 border-start border-4 border-primary">
                    <div class="stat-icon" style="background:#f3e5f5">✉️</div>
                    <div>
                        <div class="stat-label small">Liên hệ mới</div>
                        <div class="stat-val" style="color: #9c27b0;"><?= $new_contacts_count ?></div>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <!-- ===== CHARTS ===== -->
    <div class="row g-3 mb-4">

        <!-- Bar chart doanh thu -->
        <div class="col-lg-8">
            <div class="card chart-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold m-0">Doanh thu năm <?= $year_revenue ?></h5>
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="start_date"   value="<?= htmlspecialchars($start_date) ?>">
                        <input type="hidden" name="end_date"     value="<?= htmlspecialchars($end_date) ?>">
                        <input type="hidden" name="year_booking" value="<?= $year_booking ?>">
                        <input type="number" name="year_revenue"
                               class="form-control form-control-sm" style="width:90px"
                               value="<?= $year_revenue ?>" min="2000" max="<?= $cur_year + 1 ?>">
                        <button class="btn btn-sm btn-outline-primary">Xem</button>
                    </form>
                </div>
                <canvas id="barChart" height="100"></canvas>
            </div>
        </div>

        <!-- Doughnut đặt bàn -->
        <div class="col-lg-4">
            <div class="card chart-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold m-0">Đặt bàn <?= $year_booking ?></h5>
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="start_date"   value="<?= htmlspecialchars($start_date) ?>">
                        <input type="hidden" name="end_date"     value="<?= htmlspecialchars($end_date) ?>">
                        <input type="hidden" name="year_revenue" value="<?= $year_revenue ?>">
                        <input type="number" name="year_booking"
                               class="form-control form-control-sm" style="width:90px"
                               value="<?= $year_booking ?>" min="2000" max="<?= $cur_year + 1 ?>">
                        <button class="btn btn-sm btn-outline-primary">Xem</button>
                    </form>
                </div>
                <canvas id="pieChart" height="200"></canvas>
                <!-- Legend số liệu -->
                <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
                    <?php
                    $legend = [
                        ['Hoàn thành', '#28a745', $status_data[0]],
                        ['Đang chờ',   '#ffc107', $status_data[1]],
                        ['Đã hủy',     '#dc3545', $status_data[2]],
                    ];
                    foreach ($legend as [$lbl, $color, $val]):
                    ?>
                    <div class="text-center">
                        <div style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;display:inline-block;margin-right:4px"></div>
                        <span style="font-size:11px;color:#666"><?= $lbl ?></span>
                        <div class="fw-bold" style="font-size:14px"><?= $val ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ĐẶT BÀN GẦN NHẤT ===== -->
    <?php if (!empty($recent_bookings)): ?>
    <div class="card chart-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">
                <i class="fas fa-clock me-2 text-primary"></i>Đặt bàn gần nhất
            </h5>
            <a href="#" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 recent-table">
                <thead class="table-light">
                    <tr>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Giờ</th>
                        <th>Số người</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_bookings as $b):
                    $st_badge = [
                        'Completed' => ['bg-success','Hoàn thành'],
                        'Pending'   => ['bg-warning text-dark','Đang chờ'],
                        'Cancelled' => ['bg-danger','Đã hủy'],
                    ];
                    [$bc, $bl] = $st_badge[$b['status'] ?? ''] ?? ['bg-secondary','Không rõ'];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($b['user_name'] ?? $b['customer_name'] ?? '—') ?></strong></td>
                    <td><?= date('d/m/Y', strtotime($b['booking_date'])) ?></td>
                    <td><?= date('H:i', strtotime($b['booking_date'])) ?></td>
                    <td><?= (int)($b['guests'] ?? $b['number_of_people'] ?? 0) ?> người</td>
                    <td><span class="badge <?= $bc ?> rounded-pill"><?= $bl ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
var revenueData = <?= json_encode($chart_revenue) ?>;
var statusData  = <?= json_encode($status_data) ?>;

// Bar chart doanh thu
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
        datasets: [{
            label: 'Doanh thu (đ)',
            data: revenueData,
            backgroundColor: 'rgba(205,164,94,0.7)',
            borderColor: '#cda45e',
            borderWidth: 1,
            borderRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function (ctx) {
                        return ' ' + ctx.parsed.y.toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function (v) {
                        return v >= 1000000
                            ? (v/1000000).toFixed(1) + 'M'
                            : v >= 1000 ? (v/1000) + 'K' : v;
                    }
                }
            }
        }
    }
});

// Doughnut đặt bàn
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Hoàn thành','Đang chờ','Đã hủy'],
        datasets: [{
            data: statusData,
            backgroundColor: ['#28a745','#ffc107','#dc3545'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});
</script>

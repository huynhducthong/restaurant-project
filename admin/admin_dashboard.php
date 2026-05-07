<?php
// =============================================================
// File: admin/admin_dashboard.php
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// ============================================================
// FILTER — validate chặt, không ghép thẳng vào SQL
// ============================================================
$filter_date    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '')
                  ? $_GET['date'] : '';
$filter_month   = (int)($_GET['month']   ?? 0);
$filter_quarter = (int)($_GET['quarter'] ?? 0);
$filter_year    = (int)($_GET['year']    ?? date('Y'));
$year_revenue   = (int)($_GET['year_revenue'] ?? $filter_year);
$year_booking   = (int)($_GET['year_booking'] ?? $filter_year);

// Clamp năm hợp lệ
$cur_year = (int)date('Y');
foreach ([&$filter_year, &$year_revenue, &$year_booking] as &$y) {
    if ($y < 2000 || $y > $cur_year + 1) $y = $cur_year;
}
unset($y);

if ($filter_month   < 0 || $filter_month   > 12) $filter_month   = 0;
if ($filter_quarter < 0 || $filter_quarter > 4)  $filter_quarter = 0;

// ============================================================
// KHỞI TẠO
// ============================================================
$total_foods    = $total_users = $total_bookings = 0;
$selected_revenue = $prev_month_revenue = $this_month_rev = 0;
$chart_revenue  = array_fill(0, 12, 0);
$status_data    = [0, 0, 0];
$recent_bookings = [];
$low_stock_count = 0;

try {
    // ── Tổng quan ──
    $total_foods    = (int)$db->query("SELECT COUNT(*) FROM foods WHERE is_active = 1")->fetchColumn();
    $total_users    = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_bookings = (int)$db->prepare(
        "SELECT COUNT(*) FROM bookings WHERE YEAR(date) = ?"
    )->execute([$filter_year]) ? $db->query(
        "SELECT COUNT(*) FROM bookings WHERE YEAR(date) = $filter_year"
    )->fetchColumn() : 0;

    // Doanh thu theo filter — dùng prepared statement
    $rev_where = ["YEAR(created_at) = ?"];
    $rev_params = [$filter_year];
    if ($filter_date)    { $rev_where[] = "DATE(created_at) = ?";     $rev_params[] = $filter_date; }
    if ($filter_month)   { $rev_where[] = "MONTH(created_at) = ?";    $rev_params[] = $filter_month; }
    if ($filter_quarter) { $rev_where[] = "QUARTER(created_at) = ?";  $rev_params[] = $filter_quarter; }

    $rev_sql = "SELECT COALESCE(SUM(total_price),0) FROM orders WHERE " . implode(' AND ', $rev_where);
    $rev_s   = $db->prepare($rev_sql);
    $rev_s->execute($rev_params);
    $selected_revenue = (float)$rev_s->fetchColumn();

    // Doanh thu tháng này vs tháng trước (trend)
    $this_month = (int)date('m');
    $this_year  = $cur_year;
    $prev_month = $this_month === 1 ? 12 : $this_month - 1;
    $prev_year  = $this_month === 1 ? $this_year - 1 : $this_year;

    $s1 = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE MONTH(created_at)=? AND YEAR(created_at)=?");
    $s1->execute([$this_month, $this_year]);
    $this_month_rev = (float)$s1->fetchColumn();

    $s2 = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE MONTH(created_at)=? AND YEAR(created_at)=?");
    $s2->execute([$prev_month, $prev_year]);
    $prev_month_revenue = (float)$s2->fetchColumn();

    // ✅ FIX N+1: Chart doanh thu — 1 query GROUP BY thay vì 12 query
    $cr_s = $db->prepare(
        "SELECT MONTH(created_at) as m, SUM(total_price) as total
         FROM orders WHERE YEAR(created_at) = ?
         GROUP BY m"
    );
    $cr_s->execute([$year_revenue]);
    foreach ($cr_s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $chart_revenue[(int)$row['m'] - 1] = (int)$row['total'];
    }

    // ✅ FIX N+1: Chart đặt bàn — 1 query GROUP BY thay vì 3 query
    $st_map = ['Completed' => 0, 'Pending' => 1, 'Cancelled' => 2];
    $bk_s = $db->prepare(
        "SELECT status, COUNT(*) as cnt FROM bookings
         WHERE YEAR(date) = ? AND status IN ('Completed','Pending','Cancelled')
         GROUP BY status"
    );
    $bk_s->execute([$year_booking]);
    foreach ($bk_s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (isset($st_map[$row['status']])) {
            $status_data[$st_map[$row['status']]] = (int)$row['cnt'];
        }
    }

    // Đặt bàn gần nhất
    $recent_bookings = $db->query(
        "SELECT b.*, u.name as user_name
         FROM bookings b
         LEFT JOIN users u ON b.user_id = u.id
         ORDER BY b.created_at DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Cảnh báo tồn kho thấp
    $low_stock_count = (int)$db->query(
        "SELECT COUNT(*) FROM inventory WHERE min_stock > 0 AND stock_quantity <= min_stock"
    )->fetchColumn();

} catch (Exception $e) {
    // Dashboard không crash — chỉ hiện dữ liệu mặc định 0
}

// Tính trend doanh thu tháng
$trend_pct = 0;
$trend_up  = true;
if ($prev_month_revenue > 0) {
    $trend_pct = round(abs($this_month_rev - $prev_month_revenue) / $prev_month_revenue * 100);
    $trend_up  = $this_month_rev >= $prev_month_revenue;
} elseif ($this_month_rev > 0) {
    $trend_pct = 100; $trend_up = true;
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

    <!-- ===== CẢNH BÁO TỒN KHO ===== -->
    <?php if ($low_stock_count > 0): ?>
    <div class="warn-banner">
        <i class="fas fa-exclamation-triangle fa-lg text-warning"></i>
        <div>
            <strong><?= $low_stock_count ?> nguyên liệu</strong> đang ở mức tồn kho thấp.
            <a href="controllers/ReportController.php?action=low_stock" class="ms-2 text-warning fw-bold">
                Xem chi tiết →
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== FILTER CHUNG ===== -->
    <div class="card filter-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-bold mb-1">Ngày</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold mb-1">Tháng</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $filter_month === $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold mb-1">Quý</label>
                <select name="quarter" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    <?php foreach ([1=>'Q1',2=>'Q2',3=>'Q3',4=>'Q4'] as $q=>$ql): ?>
                    <option value="<?= $q ?>" <?= $filter_quarter === $q ? 'selected' : '' ?>><?= $ql ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold mb-1">Năm</label>
                <input type="number" name="year" class="form-control form-control-sm"
                       value="<?= $filter_year ?>" min="2000" max="<?= $cur_year + 1 ?>">
            </div>
            <input type="hidden" name="year_revenue" value="<?= $year_revenue ?>">
            <input type="hidden" name="year_booking" value="<?= $year_booking ?>">
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-filter me-1"></i>Lọc
                </button>
            </div>
            <div class="col-md-2">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </form>
    </div>

    <!-- ===== 4 STAT CARDS ===== -->
    <div class="row g-3 mb-4">

        <!-- Doanh thu -->
        <div class="col-md-3">
            <div class="stat-card bg-white">
                <div class="stat-icon" style="background:#fff3e0">💰</div>
                <div>
                    <div class="stat-label">Doanh thu</div>
                    <div class="stat-val text-warning"><?= number_format($selected_revenue) ?>đ</div>
                    <?php if ($trend_pct > 0): ?>
                    <span class="trend-badge mt-1 d-inline-block
                                 <?= $trend_up ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">
                        <?= $trend_up ? '▲' : '▼' ?> <?= $trend_pct ?>% so tháng trước
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Món ăn -->
        <div class="col-md-3">
            <div class="stat-card bg-white">
                <div class="stat-icon" style="background:#e8f5e9">🍽</div>
                <div>
                    <div class="stat-label">Món ăn đang hiển thị</div>
                    <div class="stat-val text-success"><?= $total_foods ?></div>
                </div>
            </div>
        </div>

        <!-- Đặt bàn -->
        <div class="col-md-3">
            <div class="stat-card bg-white">
                <div class="stat-icon" style="background:#e3f2fd">📅</div>
                <div>
                    <div class="stat-label">Lượt đặt bàn năm <?= $filter_year ?></div>
                    <div class="stat-val text-primary"><?= number_format($total_bookings) ?></div>
                </div>
            </div>
        </div>

        <!-- Khách hàng -->
        <div class="col-md-3">
            <div class="stat-card bg-white">
                <div class="stat-icon" style="background:#fce4ec">👤</div>
                <div>
                    <div class="stat-label">Khách hàng đã đăng ký</div>
                    <div class="stat-val text-danger"><?= number_format($total_users) ?></div>
                </div>
            </div>
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
                        <input type="hidden" name="date"     value="<?= htmlspecialchars($filter_date) ?>">
                        <input type="hidden" name="month"    value="<?= $filter_month ?>">
                        <input type="hidden" name="quarter"  value="<?= $filter_quarter ?>">
                        <input type="hidden" name="year"     value="<?= $filter_year ?>">
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
                        <input type="hidden" name="date"     value="<?= htmlspecialchars($filter_date) ?>">
                        <input type="hidden" name="month"    value="<?= $filter_month ?>">
                        <input type="hidden" name="quarter"  value="<?= $filter_quarter ?>">
                        <input type="hidden" name="year"     value="<?= $filter_year ?>">
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
                    <td><?= htmlspecialchars($b['date'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($b['time'] ?? '—') ?></td>
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
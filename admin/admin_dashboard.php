<?php
include '../public/admin_layout_header.php'; 
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();

// ================== 1. KHỞI TẠO ==================
$total_foods = $total_users = $total_bookings = $selected_revenue = 0;
$chart_revenue = array_fill(0, 12, 0); 
$status_data = [0, 0, 0];

// ================== 2. FILTER CHUNG ==================
$filter_date    = $_GET['date'] ?? ''; 
$filter_month   = (int)($_GET['month'] ?? 0);
$filter_quarter = (int)($_GET['quarter'] ?? 0);
$filter_year    = (int)($_GET['year'] ?? date('Y'));

// ================== 3. FILTER RIÊNG CHART ==================
$year_revenue = (int)($_GET['year_revenue'] ?? $filter_year);
$year_booking = (int)($_GET['year_booking'] ?? $filter_year);

// ================== 4. WHERE ==================
$where = ["YEAR(created_at) = $filter_year"];
if ($filter_date)    $where[] = "DATE(created_at) = '$filter_date'";
if ($filter_month)   $where[] = "MONTH(created_at) = $filter_month";
if ($filter_quarter) $where[] = "QUARTER(created_at) = $filter_quarter";

$where_sql = implode(" AND ", $where);
$where_booking = "YEAR(date) = $filter_year";

// ================== 5. QUERY ==================
try {

    // Tổng
    $total_foods = $db->query("SELECT COUNT(*) FROM foods")->fetchColumn() ?: 0;
    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;

    $total_bookings = $db->query("
        SELECT COUNT(*) FROM bookings 
        WHERE $where_booking
    ")->fetchColumn() ?: 0;

    $selected_revenue = $db->query("
        SELECT SUM(total_price) FROM orders 
        WHERE $where_sql
    ")->fetchColumn() ?: 0;

    // ===== BIỂU ĐỒ DOANH THU =====
    for ($m = 1; $m <= 12; $m++) {
        $sql = "
            SELECT SUM(total_price) 
            FROM orders 
            WHERE MONTH(created_at) = $m 
            AND YEAR(created_at) = $year_revenue
        ";
        $chart_revenue[$m-1] = (int)($db->query($sql)->fetchColumn() ?: 0);
    }

    // ===== BIỂU ĐỒ TRÒN =====
    $st_list = ['Completed', 'Pending', 'Cancelled'];

    foreach ($st_list as $i => $st) {
        $sql = "
            SELECT COUNT(*) 
            FROM bookings 
            WHERE status = '$st' 
            AND YEAR(date) = $year_booking
        ";
        $status_data[$i] = (int)($db->query($sql)->fetchColumn() ?: 0);
    }

} catch (Exception $e) {}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="content-wrapper" style="padding:20px;">

    <!-- ================= FILTER CHUNG ================= -->
    <div class="card p-3 mb-4 shadow-sm">
        <form method="GET" class="row g-2 align-items-end">
            
            <div class="col-md-2">
                <label>Ngày</label>
                <input type="date" name="date" class="form-control form-control-sm"
                    value="<?php echo $filter_date; ?>">
            </div>

            <div class="col-md-2">
                <label>Tháng</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?= $m ?>" <?= $filter_month==$m?'selected':'' ?>>
                            Tháng <?= $m ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label>Quý</label>
                <select name="quarter" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    <option value="1" <?= $filter_quarter==1?'selected':'' ?>>Q1</option>
                    <option value="2" <?= $filter_quarter==2?'selected':'' ?>>Q2</option>
                    <option value="3" <?= $filter_quarter==3?'selected':'' ?>>Q3</option>
                    <option value="4" <?= $filter_quarter==4?'selected':'' ?>>Q4</option>
                </select>
            </div>

            <div class="col-md-2">
                <label>Năm</label>
                <input type="number" name="year" class="form-control form-control-sm"
                    value="<?php echo $filter_year; ?>">
            </div>

            <!-- GIỮ FILTER CHART -->
            <input type="hidden" name="year_revenue" value="<?php echo $year_revenue; ?>">
            <input type="hidden" name="year_booking" value="<?php echo $year_booking; ?>">

            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">Lọc</button>
            </div>

            <div class="col-md-2">
                <a href="admin_dashboard.php" class="btn btn-secondary btn-sm w-100">Reset</a>
            </div>

        </form>
    </div>

    <!-- ================= DOANH THU ================= -->
    <div class="alert bg-dark text-warning d-flex justify-content-between">
        <strong>Doanh thu:</strong>
        <strong><?php echo number_format($selected_revenue); ?> VNĐ</strong>
    </div>

    <!-- ================= THỐNG KÊ ================= -->
    <div class="row mb-4">
        <div class="col-md-4"><div class="card p-3 shadow-sm">Món ăn: <?php echo $total_foods; ?></div></div>
        <div class="col-md-4"><div class="card p-3 shadow-sm">Đặt bàn: <?php echo $total_bookings; ?></div></div>
        <div class="col-md-4"><div class="card p-3 shadow-sm">Users: <?php echo $total_users; ?></div></div>
    </div>

    <div class="row">

        <!-- ===== CHART DOANH THU ===== -->
        <div class="col-lg-8">
            <div class="card p-3 shadow-sm">

                <form method="GET" class="mb-2">
                    <!-- giữ filter chung -->
                    <input type="hidden" name="date" value="<?php echo $filter_date; ?>">
                    <input type="hidden" name="month" value="<?php echo $filter_month; ?>">
                    <input type="hidden" name="quarter" value="<?php echo $filter_quarter; ?>">
                    <input type="hidden" name="year" value="<?php echo $filter_year; ?>">
                    <input type="hidden" name="year_booking" value="<?php echo $year_booking; ?>">

                    <label>Năm doanh thu</label>
                    <input type="number" name="year_revenue"
                        value="<?php echo $year_revenue; ?>"
                        class="form-control form-control-sm">

                    <button class="btn btn-primary btn-sm mt-1">Lọc</button>
                </form>

                <h5>Doanh thu năm <?php echo $year_revenue; ?></h5>
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- ===== CHART TRÒN ===== -->
        <div class="col-lg-4">
            <div class="card p-3 shadow-sm">

                <form method="GET" class="mb-2">
                    <!-- giữ filter chung -->
                    <input type="hidden" name="date" value="<?php echo $filter_date; ?>">
                    <input type="hidden" name="month" value="<?php echo $filter_month; ?>">
                    <input type="hidden" name="quarter" value="<?php echo $filter_quarter; ?>">
                    <input type="hidden" name="year" value="<?php echo $filter_year; ?>">
                    <input type="hidden" name="year_revenue" value="<?php echo $year_revenue; ?>">

                    <label>Năm đặt bàn</label>
                    <input type="number" name="year_booking"
                        value="<?php echo $year_booking; ?>"
                        class="form-control form-control-sm">

                    <button class="btn btn-primary btn-sm mt-1">Lọc</button>
                </form>

                <h5>Đặt bàn năm <?php echo $year_booking; ?></h5>
                <canvas id="pieChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
const revenueData = <?php echo json_encode($chart_revenue); ?>;
const statusData = <?php echo json_encode($status_data); ?>;

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
        datasets: [{
            data: revenueData,
            backgroundColor: '#cda45e'
        }]
    }
});

new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Hoàn thành','Đang chờ','Đã hủy'],
        datasets: [{
            data: statusData,
            backgroundColor: ['#28a745','#ffc107','#dc3545']
        }]
    }
});
</script>
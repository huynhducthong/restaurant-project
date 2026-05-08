<?php
// =============================================================
// File: admin/controllers/ReportController.php
// Route: ?action=stats|low_stock  (mặc định: stats)
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

$action = $_GET['action'] ?? 'stats';

// ============================================================
// ACTION: THỐNG KÊ (FIX LỖI MULTI-WAREHOUSE)
// ============================================================
if ($action === 'stats') {

    $month = (int)($_GET['month'] ?? date('m'));
    $year  = (int)($_GET['year']  ?? date('Y'));
    if ($month < 1 || $month > 12) $month = (int)date('m');
    if ($year < 2000 || $year > 2100) $year = (int)date('Y');

    // 1. Thống kê Nhập / Xuất / Hủy theo tháng
    $stmt_stats = $db->prepare("
        SELECT
            SUM(CASE WHEN type='import' THEN quantity ELSE 0 END) as total_import,
            SUM(CASE WHEN type='export' THEN quantity ELSE 0 END) as total_export,
            SUM(CASE WHEN type='loss'   THEN quantity ELSE 0 END) as total_loss
        FROM inventory_history
        WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    ");
    $stmt_stats->execute([$month, $year]);
    $general_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // 2. Tính Tổng giá trị tồn kho (ĐÃ FIX: Tổng hợp từ bảng Đa kho - inventory_stocks)
    $inventory_value = (float)$db->query("
        SELECT SUM(s.quantity * i.cost_price) 
        FROM inventory_stocks s
        JOIN inventory i ON s.ingredient_id = i.id
    ")->fetchColumn();

    // 3. Top 5 xuất nhiều nhất
    $stmt_top = $db->prepare("
        SELECT i.item_name, SUM(h.quantity) as total_used, i.unit_name
        FROM inventory_history h
        JOIN inventory i ON h.ingredient_id = i.id
        WHERE h.type = 'export'
          AND MONTH(h.created_at) = ? AND YEAR(h.created_at) = ?
        GROUP BY h.ingredient_id
        ORDER BY total_used DESC LIMIT 5
    ");
    $stmt_top->execute([$month, $year]);
    $top_list = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

    // 4. Dữ liệu vẽ biểu đồ Xuất Kho
    $stmt_chart = $db->prepare("
        SELECT DAY(created_at) as day_num, SUM(quantity) as total
        FROM inventory_history
        WHERE type='export'
          AND MONTH(created_at) = ? AND YEAR(created_at) = ?
        GROUP BY day_num ORDER BY day_num ASC
    ");
    $stmt_chart->execute([$month, $year]);
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    // 5. Đếm số món sắp hết (ĐÃ FIX: Cộng dồn số lượng từ tất cả kho)
    $low_stock_count = (int)$db->query("
        SELECT COUNT(*) FROM (
            SELECT i.id, i.min_stock, IFNULL(SUM(s.quantity), 0) as total_stock
            FROM inventory i
            LEFT JOIN inventory_stocks s ON i.id = s.ingredient_id
            WHERE i.is_active = 1
            GROUP BY i.id
        ) as t WHERE t.min_stock > 0 AND t.total_stock <= t.min_stock
    ")->fetchColumn();

    $export_qty = (float)($general_stats['total_export'] ?? 0);
    $loss_qty   = (float)($general_stats['total_loss']   ?? 0);
    $loss_rate  = ($export_qty > 0) ? min(100, $loss_qty / $export_qty * 100) : 0;

    $prev_m = $month == 1  ? 12 : $month - 1;
    $prev_y = $month == 1  ? $year - 1 : $year;
    $next_m = $month == 12 ? 1  : $month + 1;
    $next_y = $month == 12 ? $year + 1 : $year;

    include '../../public/admin_layout_header.php';
?>

<!-- ... (Phần HTML & Giao diện của Tab Thống Kê giữ nguyên giống file cũ của bạn) ... -->
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold m-0"><i class="fas fa-chart-line me-2 text-primary"></i>Báo cáo & Thống kê Kho</h3>
        <div class="d-flex gap-2">
            <a href="ReportController.php?action=stats" class="btn btn-sm <?= $action==='stats'?'btn-primary fw-bold':'btn-outline-secondary' ?> px-3"><i class="fas fa-chart-bar me-1"></i>Thống kê</a>
            <a href="ReportController.php?action=low_stock" class="btn btn-sm <?= $action==='low_stock'?'btn-danger fw-bold':'btn-outline-danger' ?> px-3">
                <i class="fas fa-exclamation-triangle me-1"></i>Tồn kho thấp
                <?php if ($low_stock_count > 0): ?><span class="badge bg-danger ms-1"><?= $low_stock_count ?></span><?php endif; ?>
            </a>
            <a href="ReportController.php?action=food_cost" class="btn btn-sm <?= $action==='food_cost'?'btn-warning fw-bold text-dark':'btn-outline-warning' ?> px-3"><i class="fas fa-dollar-sign me-1"></i>Giá vốn món ăn</a>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="card border-0 shadow-sm p-3 mb-4" style="border-radius:12px">
        <form method="GET" class="d-flex align-items-center flex-wrap gap-3">
            <input type="hidden" name="action" value="stats">
            <span class="small text-muted fw-bold">Lọc theo:</span>
            <div class="d-flex align-items-center gap-2"><label class="small text-muted mb-0">Tháng</label><input type="number" name="month" class="form-control form-control-sm" style="width:70px" value="<?= $month ?>" min="1" max="12"></div>
            <div class="d-flex align-items-center gap-2"><label class="small text-muted mb-0">Năm</label><input type="number" name="year" class="form-control form-control-sm" style="width:90px" value="<?= $year ?>" min="2000" max="2100"></div>
            <button type="submit" class="btn btn-sm btn-primary px-4">Xem báo cáo</button>
            <a href="?action=stats&month=<?= $prev_m ?>&year=<?= $prev_y ?>" class="btn btn-sm btn-outline-secondary">‹ Tháng trước</a>
            <a href="?action=stats&month=<?= $next_m ?>&year=<?= $next_y ?>" class="btn btn-sm btn-outline-secondary">Tháng sau ›</a>
        </form>
    </div>

    <!-- 4 card số liệu -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm p-4 text-center h-100"><div class="text-muted small mb-1">Tổng nhập kho</div><div class="fs-3 fw-bold text-success"><?= number_format($general_stats['total_import'] ?? 0, 1) ?></div><div class="small text-muted">đơn vị · <?= $month ?>/<?= $year ?></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-4 text-center h-100"><div class="text-muted small mb-1">Tổng xuất kho</div><div class="fs-3 fw-bold text-primary"><?= number_format($general_stats['total_export'] ?? 0, 1) ?></div><div class="small text-muted">đơn vị · <?= $month ?>/<?= $year ?></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-4 text-center h-100"><div class="text-muted small mb-1">Tỷ lệ hao hụt</div><div class="fs-3 fw-bold text-warning"><?= number_format($loss_rate, 1) ?>%</div><div class="progress mt-2" style="height:5px"><div class="progress-bar bg-warning" style="width:<?= $loss_rate ?>%"></div></div><div class="small text-muted mt-1">Hủy: <?= number_format($loss_qty, 1) ?> đv</div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-4 text-center h-100 bg-dark text-white"><div class="small mb-1 opacity-75">Giá trị tồn kho</div><div class="fs-3 fw-bold"><?= number_format($inventory_value, 0, ',', '.') ?>đ</div><div class="small opacity-75">Tổng hiện tại (mọi kho × giá vốn)</div></div></div>
    </div>

    <!-- Top 5 + Biểu đồ -->
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="fas fa-fire text-warning me-2"></i>Top 5 xuất kho <span class="badge bg-light text-muted border ms-1" style="font-size:11px"><?= $month ?>/<?= $year ?></span></h5>
                <?php if (empty($top_list)): ?><div class="text-muted fst-italic text-center py-3">Không có dữ liệu xuất kho.</div><?php else: ?>
                <?php $max_used = (float)($top_list[0]['total_used'] ?? 1); foreach ($top_list as $item): $pct = $max_used > 0 ? ($item['total_used'] / $max_used * 100) : 0; ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-bold small"><?= htmlspecialchars($item['item_name']) ?></span><span class="text-primary fw-bold small"><?= number_format($item['total_used'], 1) ?> <?= htmlspecialchars($item['unit_name']) ?></span></div>
                    <div class="progress" style="height:8px;border-radius:4px"><div class="progress-bar bg-primary" style="width:<?= $pct ?>%;border-radius:4px"></div></div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="col-lg-7"><div class="card border-0 shadow-sm p-4 h-100"><h5 class="fw-bold mb-3"><i class="fas fa-chart-area text-info me-2"></i>Xuất kho theo ngày</h5><canvas id="dailyChart" height="160"></canvas></div></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var raw = <?= json_encode($chart_data) ?>;
    var daysInMonth = new Date(<?= $year ?>, <?= $month ?>, 0).getDate();
    var labels = [], data = [], map = {};
    raw.forEach(function (r) { map[parseInt(r.day_num)] = parseFloat(r.total); });
    for (var d = 1; d <= daysInMonth; d++) { labels.push(d); data.push(map[d] || 0); }
    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: { labels: labels, datasets: [{ label: 'Số lượng xuất', data: data, backgroundColor: 'rgba(13,110,253,0.55)', borderRadius: 3 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 10 } } }, y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>

<?php
    exit;
} // end action=stats

// ============================================================
// ACTION: CẢNH BÁO TỒN KHO THẤP (FIX LỖI MULTI-WAREHOUSE)
// ============================================================
if ($action === 'low_stock') {

    $threshold = max(0, (int)($_GET['threshold'] ?? 5));

    // ĐÃ FIX: Gom nhóm và tính tổng từ Đa kho
    $stmt_low = $db->prepare("
        SELECT i.id, i.item_name, i.category, i.unit_name, i.min_stock, s.name as s_name,
               IFNULL(SUM(st.quantity), 0) as total_stock,
               CASE WHEN i.min_stock > 0 THEN i.min_stock ELSE ? END as effective_min
        FROM inventory i
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        LEFT JOIN inventory_stocks st ON i.id = st.ingredient_id
        WHERE i.is_active = 1
        GROUP BY i.id, s.name
        HAVING total_stock < effective_min
        ORDER BY total_stock ASC
    ");
    $stmt_low->execute([$threshold]);
    $low_stock = $stmt_low->fetchAll(PDO::FETCH_ASSOC);

    $total_items = (int)$db->query("SELECT COUNT(*) FROM inventory WHERE is_active = 1")->fetchColumn();

    include '../../public/admin_layout_header.php';
?>

<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold m-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo tồn kho thấp</h3>
        <div class="d-flex gap-2">
            <a href="ReportController.php?action=stats" class="btn btn-sm btn-outline-primary px-3"><i class="fas fa-chart-bar me-1"></i>Thống kê</a>
            <a href="ReportController.php?action=low_stock" class="btn btn-sm btn-danger fw-bold px-3"><i class="fas fa-exclamation-triangle me-1"></i>Tồn kho thấp</a>
        </div>
    </div>

    <!-- Tóm tắt + bộ lọc ngưỡng -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center h-100 <?= count($low_stock) > 0 ? 'border-start border-4 border-danger' : '' ?>">
                <div class="text-muted small mb-1">Cần nhập hàng</div>
                <div class="fs-2 fw-bold <?= count($low_stock) > 0 ? 'text-danger' : 'text-success' ?>"><?= count($low_stock) ?></div>
                <div class="small text-muted">/ <?= $total_items ?> nguyên liệu đang dùng</div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card border-0 shadow-sm p-3 h-100 d-flex flex-row align-items-center gap-3">
                <i class="fas fa-sliders-h fa-2x text-secondary opacity-50"></i>
                <div class="flex-grow-1">
                    <div class="small text-muted mb-1 fw-bold">Ngưỡng cảnh báo mặc định</div>
                    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="hidden" name="action" value="low_stock">
                        <input type="number" name="threshold" class="form-control form-control-sm" style="width:90px" value="<?= $threshold ?>" min="0" step="1">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cập nhật</button>
                        <span class="small text-muted">(Món nào có gán Tồn tối thiểu riêng sẽ ưu tiên dùng số đó)</span>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng Cảnh báo -->
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:14px">
        <table class="table align-middle mb-0 table-hover">
            <thead class="table-danger">
                <tr><th class="ps-4">Nguyên liệu</th><th>Danh mục</th><th>Nhà cung cấp</th><th>Tổng Tồn (Mọi kho)</th><th>Tồn tối thiểu</th><th>Trạng thái</th><th class="text-end pe-4">Thao tác</th></tr>
            </thead>
            <tbody style="background:#fff">
                <?php if (empty($low_stock)): ?>
                <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>Tuyệt vời! Tất cả kho đang dồi dào.</td></tr>
                <?php else: ?>
                <?php foreach ($low_stock as $item):
                    $stock   = (float)$item['total_stock'];
                    $min     = (float)$item['effective_min'];
                    $pct     = ($min > 0) ? min(100, $stock / $min * 100) : 100;
                    $is_zero = ($stock <= 0);
                ?>
                <tr class="<?= $is_zero ? 'table-danger' : '' ?>">
                    <td class="ps-4"><strong><?= htmlspecialchars($item['item_name']) ?></strong> <?php if ($is_zero): ?><span class="badge bg-danger ms-1" style="font-size:9px">HẾT SẠCH</span><?php endif; ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['category']) ?></span></td>
                    <td class="small text-muted"><?= htmlspecialchars($item['s_name'] ?? 'Chưa gán') ?></td>
                    <td>
                        <span class="fw-bold <?= $is_zero ? 'text-danger' : 'text-warning' ?>"><?= number_format($stock, 1) ?></span> <span class="text-muted small ms-1"><?= htmlspecialchars($item['unit_name']) ?></span>
                        <div class="progress mt-1" style="height:4px;width:80px"><div class="progress-bar <?= $is_zero ? 'bg-danger' : 'bg-warning' ?>" style="width:<?= $pct ?>%"></div></div>
                    </td>
                    <td class="small text-muted"><?= $min > 0 ? number_format($min, 1) . ' ' . htmlspecialchars($item['unit_name']) : '—' ?></td>
                    <td><span class="badge rounded-pill <?= $is_zero ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= $is_zero ? 'Hết hàng' : 'Cần nhập hàng' ?></span></td>
                    <td class="text-end pe-4"><a href="POController.php" class="btn btn-sm btn-primary rounded-pill px-3"><i class="fas fa-truck me-1"></i>Đặt hàng (PO)</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
    exit;
} // end action=low_stock

// ============================================================
// ACTION: GIÁ VỐN MÓN ĂN (tích hợp từ ReportCostController)
// ============================================================
if ($action === 'food_cost') {
    $food_costs = $db->query("
        SELECT
            f.id, f.name as food_name, f.price as selling_price, f.image,
            COALESCE(SUM(
                (CASE
                    WHEN LOWER(TRIM(r.unit)) = 'g'  AND LOWER(TRIM(i.unit_name)) = 'kg' THEN r.quantity_required / 1000
                    WHEN LOWER(TRIM(r.unit)) = 'ml' AND LOWER(TRIM(i.unit_name)) = 'l'  THEN r.quantity_required / 1000
                    ELSE r.quantity_required
                END) * i.cost_price
            ), 0) as real_cost,
            COUNT(r.ingredient_id) as ingredient_count
        FROM foods f
        LEFT JOIN food_recipes r ON f.id = r.food_id
        LEFT JOIN inventory i ON r.ingredient_id = i.id
        WHERE f.is_active = 1
        GROUP BY f.id
        ORDER BY (f.price - COALESCE(SUM(
            (CASE WHEN LOWER(TRIM(r.unit))='g' AND LOWER(TRIM(i.unit_name))='kg' THEN r.quantity_required/1000
                  WHEN LOWER(TRIM(r.unit))='ml' AND LOWER(TRIM(i.unit_name))='l' THEN r.quantity_required/1000
                  ELSE r.quantity_required END)*i.cost_price
        ),0)) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    include '../../public/admin_layout_header.php';
?>
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold m-0"><i class="fas fa-dollar-sign me-2 text-warning"></i>Phân tích Giá vốn Món ăn</h3>
        <div class="d-flex gap-2">
            <a href="ReportController.php?action=stats" class="btn btn-sm btn-outline-secondary px-3"><i class="fas fa-arrow-left me-1"></i>Thống kê kho</a>
            <a href="ReportController.php?action=low_stock" class="btn btn-sm btn-outline-danger px-3"><i class="fas fa-exclamation-triangle me-1"></i>Tồn kho thấp</a>
            <button class="btn btn-sm btn-dark px-3" onclick="window.print()"><i class="fas fa-print me-1"></i>In báo cáo</button>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Món ăn</th>
                    <th>Thành phần</th>
                    <th>Giá vốn</th>
                    <th>Giá bán</th>
                    <th>Lợi nhuận</th>
                    <th>Biên lợi</th>
                </tr>
            </thead>
            <tbody class="bg-white">
            <?php foreach ($food_costs as $fc):
                $profit = $fc['selling_price'] - $fc['real_cost'];
                $margin = $fc['selling_price'] > 0 ? ($profit / $fc['selling_price']) * 100 : 0;
                $margin_class = $margin < 30 ? 'text-danger fw-bold' : ($margin < 50 ? 'text-warning fw-bold' : 'text-success fw-bold');
                $bg_class = $margin < 30 ? 'table-danger' : '';
            ?>
            <tr class="<?= $bg_class ?>">
                <td class="ps-4">
                    <div class="d-flex align-items-center gap-2">
                        <img src="../../public/assets/img/menu/<?= htmlspecialchars($fc['image']) ?>"
                             onerror="this.src=''"
                             style="width:38px;height:38px;object-fit:cover;border-radius:8px;">
                        <strong><?= htmlspecialchars($fc['food_name']) ?></strong>
                    </div>
                </td>
                <td><span class="badge bg-secondary"><?= $fc['ingredient_count'] ?> NL</span></td>
                <td class="text-danger fw-bold"><?= number_format($fc['real_cost']) ?> đ</td>
                <td class="text-primary fw-bold"><?= number_format($fc['selling_price']) ?> đ</td>
                <td class="fw-bold"><?= number_format($profit) ?> đ</td>
                <td class="<?= $margin_class ?>"><?= round($margin, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
    exit;
} // end action=food_cost

// Fallback
header("Location: ReportController.php?action=stats"); exit;
<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
include '../public/admin_layout_header.php';

// Lấy tham số lọc thời gian (Mặc định là tháng hiện tại)
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// --- 1. THỐNG KÊ CHI PHÍ & TỔNG NHẬP/XUẤT ---
$stats_query = $db->prepare("
    SELECT 
        SUM(CASE WHEN type = 'import' THEN quantity ELSE 0 END) as total_import,
        SUM(CASE WHEN type = 'export' THEN quantity ELSE 0 END) as total_export,
        SUM(CASE WHEN type = 'loss' THEN quantity ELSE 0 END) as total_loss
    FROM inventory_history 
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
");
$stats_query->execute([$month, $year]);
$general_stats = $stats_query->fetch(PDO::FETCH_ASSOC);

// --- 2. NGUYÊN LIỆU DÙNG NHIỀU NHẤT ---
$top_ing = $db->prepare("
    SELECT i.item_name, SUM(h.quantity) as total_used, i.unit_name
    FROM inventory_history h
    JOIN inventory i ON h.ingredient_id = i.id
    WHERE h.type = 'export' AND MONTH(h.created_at) = ? AND YEAR(h.created_at) = ?
    GROUP BY h.ingredient_id
    ORDER BY total_used DESC LIMIT 5
");
$top_ing->execute([$month, $year]);
$top_list = $top_ing->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-content p-4">
    <h2 class="fw-bold mb-4"><i class="fa fa-chart-line me-2 text-primary"></i>Báo cáo & Thống kê Kho</h2>

    <div class="card p-3 mb-4 border-0 shadow-sm">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-auto">Lọc theo tháng:</div>
            <div class="col-auto"><input type="number" name="month" class="form-control" value="<?= $month ?>" min="1" max="12"></div>
            <div class="col-auto"><input type="number" name="year" class="form-control" value="<?= $year ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Xem báo cáo</button></div>
        </form>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-4 text-center border-0 shadow-sm mb-4">
                <h6 class="text-muted">Tổng nguyên liệu xuất</h6>
                <h2 class="text-danger fw-bold"><?= number_format($general_stats['total_export'] ?? 0, 2) ?> đơn vị</h2>
                <small>Trong tháng <?= $month ?>/<?= $year ?></small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 text-center border-0 shadow-sm mb-4 bg-primary text-white">
                <h6>Chi phí vốn (Cost)</h6>
                <?php
                    $cost = $db->query("SELECT SUM(revenue) FROM inventory")->fetchColumn();
                ?>
                <h2 class="fw-bold"><?= number_format($cost) ?>đ</h2>
                <small>Tổng giá trị nguyên liệu đã dùng</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 text-center border-0 shadow-sm mb-4">
                <h6 class="text-muted">Tỷ lệ hao hụt</h6>
                <?php 
                    $export = $general_stats['total_export'] ?: 1;
                    $loss = $general_stats['total_loss'] ?? 0;
                    $rate = ($loss / $export) * 100;
                ?>
                <h2 class="text-warning fw-bold"><?= number_format($rate, 1) ?>%</h2>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-warning" style="width: <?= $rate ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card p-4 border-0 shadow-sm">
                <h5 class="fw-bold mb-3">Top 5 Nguyên liệu dùng nhiều nhất</h5>
                <table class="table">
                    <thead><tr><th>Tên</th><th>Tổng dùng</th><th>Đơn vị</th></tr></thead>
                    <tbody>
                        <?php foreach($top_list as $item): ?>
                        <tr>
                            <td><strong><?= $item['item_name'] ?></strong></td>
                            <td class="text-primary fw-bold"><?= number_format($item['total_used'], 2) ?></td>
                            <td><?= $item['unit_name'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
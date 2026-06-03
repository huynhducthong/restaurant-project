<?php
require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

$user_id = $_GET['user_id'] ?? 0;
$show_all = isset($_GET['show_all']) ? (int)$_GET['show_all'] : 0;
if (!$user_id) exit;

// Count total
$count_stmt = $db->prepare("SELECT COUNT(*) FROM service_bookings WHERE user_id = ?");
$count_stmt->execute([$user_id]);
$total_bookings = $count_stmt->fetchColumn();

$limit_sql = $show_all ? "" : "LIMIT 10";
$stmt = $db->prepare("SELECT id, booking_date, status, total_amount, service_type FROM service_bookings WHERE user_id = ? ORDER BY booking_date DESC $limit_sql");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($bookings)) {
    echo '<div class="text-center text-muted p-3">Khách hàng chưa có lịch sử đặt bàn.</div>';
    exit;
}
?>
<div class="table-responsive">
    <table class="table table-hover table-sm align-middle mb-0" style="font-size: 13px;">
        <thead class="table-light">
            <tr>
                <th>Mã Đơn</th>
                <th>Thời gian dùng bữa</th>
                <th>Dịch Vụ</th>
                <th>Tổng Tiền</th>
                <th class="text-center">Trạng Thái</th>
                <th class="text-end">Chi tiết</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($bookings as $b): 
                $statusBadge = match($b['status']) {
                    'Pending' => '<span class="badge bg-warning text-dark">Chờ XN</span>',
                    'Confirmed' => '<span class="badge bg-primary">Đã XN</span>',
                    'Completed' => '<span class="badge bg-success">Hoàn thành</span>',
                    'Cancelled' => '<span class="badge bg-danger">Đã hủy</span>',
                    default => '<span class="badge bg-secondary">Unknown</span>'
                };
            ?>
            <tr>
                <td class="fw-bold">#BK-<?= $b['id'] ?></td>
                <td><?= date('H:i - d/m/Y', strtotime($b['booking_date'])) ?></td>
                <td class="text-uppercase"><?= htmlspecialchars($b['service_type']) ?></td>
                <td class="text-success fw-bold"><?= number_format($b['total_amount']) ?>đ</td>
                <td class="text-center"><?= $statusBadge ?></td>
                <td class="text-end">
                    <button type="button" onclick="viewBookingDetailsAdmin(<?= $b['id'] ?>)" class="btn btn-sm btn-outline-primary" style="font-size: 11px; padding: 2px 6px;">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!$show_all && $total_bookings > 10): ?>
<div class="text-center mt-3 mb-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadAllHistory(<?= $user_id ?>)">
        <i class="fas fa-chevron-down me-1"></i>Xem tất cả <?= $total_bookings ?> đơn
    </button>
</div>
<?php endif; ?>

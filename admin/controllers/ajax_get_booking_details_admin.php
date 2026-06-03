<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/database.php';

// Check admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    echo "Unauthorized";
    exit;
}

if (!isset($_GET['booking_id'])) {
    echo "Missing ID";
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$db = (new Database())->getConnection();

// Fetch booking info
$stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "Không tìm thấy thông tin đặt bàn.";
    exit;
}

// Fetch items
$detail_stmt = $db->prepare("SELECT f.name, bd.quantity, f.price FROM booking_details bd JOIN foods f ON bd.menu_id = f.id WHERE bd.booking_id = ?");
$detail_stmt->execute([$booking_id]);
$items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
?>
<div style="font-family:'Inter', sans-serif;">
    <div style="display:flex; justify-content:space-between; margin-bottom:15px; padding-bottom:15px; border-bottom:1px dashed #e8e2d9;">
        <div>
            <div style="font-size:12px; color:#888;">Mã Đặt Bàn</div>
            <div style="font-size:15px; font-weight:bold; color:var(--bs-primary);">#BK-<?= $booking_id ?></div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:12px; color:#888;">Trạng Thái</div>
            <?php 
              $status_badge = match($booking['status']) {
                'Pending' => '<span class="badge bg-warning text-dark">Đang chờ</span>',
                'Confirmed' => '<span class="badge bg-primary">Đã xác nhận</span>',
                'Completed' => '<span class="badge bg-success">Đã hoàn thành</span>',
                'Cancelled' => '<span class="badge bg-danger">Đã hủy</span>',
                default => '<span class="badge bg-secondary">Unknown</span>'
              };
              echo $status_badge;
            ?>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; font-size:13px;">
        <div><span style="color:#888;">Dịch vụ:</span> <strong style="color:var(--bs-primary); text-transform:uppercase;"><?= htmlspecialchars($booking['service_type']) ?></strong></div>
        <div><span style="color:#888;">Thời gian:</span> <strong><?= date('H:i - d/m/Y', strtotime($booking['booking_date'])) ?></strong></div>
        <div><span style="color:#888;">Khách:</span> <strong><?= $booking['guests'] ?> người</strong></div>
        <div><span style="color:#888;">Cọc:</span> <strong class="text-danger"><?= number_format($booking['deposit_amount']) ?>đ</strong></div>
    </div>

    <h6 style="font-size:13px; font-weight:bold; text-transform:uppercase; color:#888; border-bottom:1px solid #eee; padding-bottom:5px;">Thực Đơn Chi Tiết</h6>
    <?php if (empty($items)): ?>
        <p style="font-size:13px; color:#666;">Không có món ăn chọn thêm.</p>
    <?php else: ?>
        <table style="width:100%; font-size:13px; border-collapse:collapse; margin-bottom:20px;">
            <thead>
                <tr style="background:#f9f9f9;">
                    <th style="padding:8px; text-align:left; border-bottom:1px solid #ddd;">Tên món</th>
                    <th style="padding:8px; text-align:center; border-bottom:1px solid #ddd;">SL</th>
                    <th style="padding:8px; text-align:right; border-bottom:1px solid #ddd;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $it): 
                    $sub = $it['price'] * $it['quantity'];
                    $total += $sub;
                ?>
                <tr>
                    <td style="padding:8px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($it['name']) ?></td>
                    <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:center;">x<?= $it['quantity'] ?></td>
                    <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:500;"><?= number_format($sub) ?>đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="padding:8px; text-align:right; font-weight:bold; color:#555;">TỔNG CỘNG:</td>
                    <td style="padding:8px; text-align:right; font-weight:bold; color:var(--bs-primary); font-size:15px;"><?= number_format($booking['total_amount'] ?? $total) ?>đ</td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <?php if(!empty($booking['message'])): ?>
    <div style="background:#f8f9fa; border-left:3px solid var(--bs-primary); padding:10px; font-size:12px; font-style:italic;">
        <strong>Ghi chú:</strong> <?= nl2br(htmlspecialchars($booking['message'])) ?>
    </div>
    <?php endif; ?>
    
    <div style="text-align:center; margin-top:20px;">
        <a href="export_pdf.php?id=<?= $booking_id ?>" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
            <i class="fas fa-print me-1"></i>In Hóa Đơn PDF
        </a>
    </div>
</div>

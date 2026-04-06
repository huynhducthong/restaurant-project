<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// 1. Kết nối database từ thư mục gốc
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;
$db = (new Database())->getConnection();

// 2. Lấy thông tin phiếu dịch vụ (Sử dụng LEFT JOIN để lấy tên bàn/phòng)
$stmt = $db->prepare("
    SELECT sb.*, rt.table_code 
    FROM service_bookings sb 
    LEFT JOIN restaurant_tables rt ON sb.table_id = rt.id 
    WHERE sb.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy đơn hàng, quay về trang chủ
if (!$order) {
    header("Location: index.php");
    exit();
}

// 3. Lấy danh sách món ăn khách đã đặt đính kèm
$detail_stmt = $db->prepare("
    SELECT f.name, bd.quantity 
    FROM booking_details bd 
    JOIN foods f ON bd.menu_id = f.id 
    WHERE bd.booking_id = ?
");
$detail_stmt->execute([$id]);
$ordered_items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Gọi Header giao diện
include 'views/client/layouts/header.php'; 
?>

<style>
    #main {
        margin-top: 100px;
        background: #0c0b09;
        min-height: 85vh;
        display: flex;
        align-items: center;
        padding: 40px 0;
    }
    .success-card {
        background: rgba(26, 24, 20, 0.95);
        border: 1px solid #37332a;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 15px 40px rgba(0,0,0,0.6);
    }
    .text-gold { color: #cda45e !important; }
    
    .info-box {
        text-align: left;
        max-width: 500px;
        margin: 30px auto;
        padding-top: 20px;
        border-top: 1px solid #37332a;
    }
    .info-item { display: flex; justify-content: space-between; margin-bottom: 12px; color: #fff; }
    .label-muted { color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }

    /* Style cho danh sách món ăn */
    .ordered-foods {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }
    .food-item {
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dashed #333;
        padding: 5px 0;
    }

    .btn-pdf {
        background: #cda45e;
        color: #fff;
        border-radius: 50px;
        padding: 12px 35px;
        display: inline-block;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        margin-top: 20px;
    }
    .btn-pdf:hover { background: #d3af71; transform: translateY(-3px); color: #000; }
</style>

<main id="main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="success-card">
                    <div class="display-3 text-gold mb-3"><i class="bi bi-patch-check"></i></div>
                    <h2 class="text-gold" style="font-family: 'Playfair Display', serif; font-size: 32px;">GỬI YÊU CẦU THÀNH CÔNG</h2>
                    <p class="text-secondary">Cảm ơn bạn đã lựa chọn Restaurantly. Chúng tôi sẽ sớm liên hệ với bạn qua số điện thoại <strong><?= $order['customer_phone'] ?></strong>.</p>

                    <div class="info-box">
                        <div class="info-item">
                            <span class="label-muted">Mã phiếu ghi nhận:</span>
                            <strong class="text-white">#SVR-<?= $order['id'] ?></strong>
                        </div>
                        <div class="info-item">
                            <span class="label-muted">Loại dịch vụ:</span>
                            <span class="text-white">
                                <?php 
                                    // Chuyển đổi tên dịch vụ sang Tiếng Việt (Đã loại bỏ wedding)
                                    $service_names = [
                                        'table' => 'Đặt Bàn Trải Nghiệm',
                                        'birthday' => 'Tiệc Kỷ Niệm / Sinh Nhật',
                                        'chef' => 'Thuê Đầu Bếp Riêng'
                                    ];
                                    echo $service_names[$order['service_type']] ?? 'Dịch vụ đặc biệt';
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label-muted">Ngày & Giờ tổ chức:</span>
                            <span class="text-white"><?= date('H:i - d/m/Y', strtotime($order['booking_date'])) ?></span>
                        </div>

                        <?php if (!empty($ordered_items)): ?>
                            <div class="ordered-foods">
                                <span class="label-muted d-block mb-2">Thực đơn yêu cầu:</span>
                                <?php foreach ($ordered_items as $food): ?>
                                    <div class="food-item">
                                        <span><?= htmlspecialchars($food['name']) ?></span>
                                        <span class="text-gold">x<?= $food['quantity'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="admin/export_pdf.php?id=<?= $order['id'] ?>" class="btn-pdf">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> TẢI PHIẾU XÁC NHẬN (PDF)
                    </a>

                    <div class="mt-4">
                        <a href="index.php" class="text-secondary small text-decoration-none">
                            <i class="bi bi-house-door me-1"></i> Quay lại trang chủ
                        </a>
                    </div>
                    <div class="info-item">
                        <span class="label-muted">Số bàn/Phòng:</span>
                        <span class="text-white"><?= $order['table_code'] ?? 'Chưa chọn' ?></span>
                    </div>
                    <div class="p-3 bg-dark rounded border border-warning mt-3">
                        <p class="text-warning small mb-1">HƯỚNG DẪN THANH TOÁN TẠM ỨNG (30%)</p>
                        <h4 class="text-white"><?= number_format($order['deposit_amount']) ?>đ</h4>
                        <p class="small text-muted">Vui lòng chuyển khoản để xác nhận yêu cầu.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'views/client/layouts/footer.php'; ?>
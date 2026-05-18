<?php
// File: admin/export_pdf.php -> nên chuyển vào admin/helpers/export_pdf.php

session_start();

// ✅ FIX: Xác thực session admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id'])) {
    header('Location: manage_services.php');
    exit;
}

$id = (int)$_GET['id'];
$db = (new Database())->getConnection();

// Lấy thông tin phiếu dịch vụ
$stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ FIX: Redirect thay vì die()
if (!$s) {
    header('Location: manage_services.php?error=notfound');
    exit;
}

// Lấy danh sách món ăn
$detail_stmt = $db->prepare(
    "SELECT f.name, bd.quantity, f.price
     FROM booking_details bd
     JOIN foods f ON bd.menu_id = f.id
     WHERE bd.booking_id = ?"
);
$detail_stmt->execute([$id]);
$items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin nhà hàng từ settings
$settings_raw = $db->query("SELECT key_name, key_value FROM settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_raw as $r) $settings[$r['key_name']] = $r['key_value'];
$restaurant_name = htmlspecialchars($settings['restaurant_name'] ?? 'Restaurantly');
$restaurant_addr = htmlspecialchars($settings['address']         ?? '');
$restaurant_tel  = htmlspecialchars($settings['hotline']         ?? '');

// Tính tổng tiền
$grand_total = 0;
foreach ($items as $item) {
    $grand_total += $item['price'] * $item['quantity'];
}

// =====================================================
// BUILD HTML
// =====================================================
$html = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: DejaVu Sans, sans-serif; margin:0; padding:0; color:#222;">

<!-- HEADER -->
<div style="background:#1a1814; padding:22px 30px; display:table; width:100%; box-sizing:border-box;">
    <div style="display:table-cell; vertical-align:middle;">
        <div style="font-size:20px; font-weight:bold; color:#cda45e; letter-spacing:1px;">' . $restaurant_name . '</div>
        ' . ($restaurant_addr ? '<div style="font-size:10px; color:#aaa; margin-top:3px;">' . $restaurant_addr . '</div>' : '') . '
        ' . ($restaurant_tel  ? '<div style="font-size:10px; color:#aaa;">ĐT: ' . $restaurant_tel  . '</div>' : '') . '
    </div>
    <div style="display:table-cell; vertical-align:middle; text-align:right;">
        <div style="font-size:13px; color:#cda45e; font-weight:bold; letter-spacing:2px; text-transform:uppercase;">Phiếu Dịch Vụ</div>
        <div style="font-size:11px; color:#888; margin-top:4px;">Mã: #SVR-' . $id . '</div>
        <div style="font-size:10px; color:#888;">Ngày xuất: ' . date('d/m/Y H:i') . '</div>
    </div>
</div>

<div style="padding:24px 30px;">

    <!-- THÔNG TIN KHÁCH HÀNG -->
    <div style="border:1px solid #e8e2d9; border-radius:8px; padding:16px; margin-bottom:20px;">
        <div style="font-size:11px; font-weight:bold; letter-spacing:.15em; text-transform:uppercase; color:#888; margin-bottom:12px; border-bottom:1px solid #f0ece4; padding-bottom:6px;">
            Thông tin khách hàng
        </div>
        <table style="width:100%; font-size:13px; border-collapse:collapse;">
            <tr>
                <td style="padding:5px 0; width:40%; color:#888;">Khách hàng:</td>
                <td style="padding:5px 0; font-weight:bold;">' . htmlspecialchars($s['customer_name']) . '</td>
                <td style="padding:5px 0; width:30%; color:#888;">Điện thoại:</td>
                <td style="padding:5px 0;">' . htmlspecialchars($s['customer_phone']) . '</td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Dịch vụ:</td>
                <td style="padding:5px 0; font-weight:bold; color:#cda45e;">' . htmlspecialchars(strtoupper($s['service_type'])) . '</td>
                <td style="padding:5px 0; color:#888;">Thời gian:</td>
                <td style="padding:5px 0; font-weight:bold;">' . htmlspecialchars(date('H:i - d/m/Y', strtotime($s['booking_date']))) . '</td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Số lượng khách:</td>
                <td style="padding:5px 0;">' . htmlspecialchars((string)$s['guests']) . ' người</td>
                <td style="padding:5px 0; color:#888;">Phòng/Bàn:</td>
                <td style="padding:5px 0; font-weight:bold;">' . ($s['service_type'] === 'birthday' ? 'PHÒNG VIP (Mặc định)' : 'Bàn tiêu chuẩn') . '</td>
            </tr>
        </table>
    </div>';

// KHỐI CHI TIẾT SỰ KIỆN (CHỈ HIỆN KHI LÀ SINH NHẬT/KỶ NIỆM)
if ($s['service_type'] === 'birthday') {
    $addons = [];
    if ($s['has_cake']) $addons[] = 'Bánh kem';
    if ($s['has_flower']) $addons[] = 'Hoa tươi';
    $addon_str = !empty($addons) ? implode(', ', $addons) : 'Không';

    $html .= '
    <div style="border:1px solid #e8e2d9; background:#fafafa; border-radius:8px; padding:16px; margin-bottom:20px;">
        <div style="font-size:11px; font-weight:bold; letter-spacing:.15em; text-transform:uppercase; color:#cda45e; margin-bottom:12px; border-bottom:1px solid #f0ece4; padding-bottom:6px;">
            Chi tiết tiệc kỷ niệm
        </div>
        <table style="width:100%; font-size:13px; border-collapse:collapse;">
            <tr>
                <td style="padding:5px 0; width:40%; color:#888;">Loại kỷ niệm:</td>
                <td style="padding:5px 0; font-weight:bold;">' . htmlspecialchars($s['event_type'] ?: 'Sinh nhật') . '</td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Gói trang trí:</td>
                <td style="padding:5px 0;">' . htmlspecialchars($s['decor_package'] ?: 'Gói mặc định') . '</td>
            </tr>
            <tr>
                <td style="padding:5px 0; color:#888;">Dịch vụ đặt thêm:</td>
                <td style="padding:5px 0; font-weight:bold; color:#d63384;">' . $addon_str . '</td>
            </tr>
        </table>
    </div>';
}

// BẢNG MÓN ĂN
if (!empty($items)) {
    $html .= '
    <div style="margin-bottom:20px;">
        <div style="font-size:11px; font-weight:bold; letter-spacing:.15em; text-transform:uppercase; color:#888; margin-bottom:10px;">
            Thực đơn đã chọn
        </div>
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f8f4ee;">
                    <th style="text-align:left; padding:9px 10px; border-bottom:2px solid #cda45e; color:#555;">Tên món</th>
                    <th style="text-align:center; padding:9px 10px; border-bottom:2px solid #cda45e; color:#555;">SL</th>
                    <th style="text-align:right; padding:9px 10px; border-bottom:2px solid #cda45e; color:#555;">Đơn giá</th>
                    <th style="text-align:right; padding:9px 10px; border-bottom:2px solid #cda45e; color:#555;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($items as $item) {
        $sub = $item['price'] * $item['quantity'];
        $html .= '
                <tr>
                    <td style="padding:8px 10px; border-bottom:1px solid #f0ece4;">' . htmlspecialchars($item['name']) . '</td>
                    <td style="padding:8px 10px; border-bottom:1px solid #f0ece4; text-align:center;">×' . (int)$item['quantity'] . '</td>
                    <td style="padding:8px 10px; border-bottom:1px solid #f0ece4; text-align:right;">' . number_format($item['price'], 0, ',', '.') . 'đ</td>
                    <td style="padding:8px 10px; border-bottom:1px solid #f0ece4; text-align:right; font-weight:bold;">' . number_format($sub, 0, ',', '.') . 'đ</td>
                </tr>';
    }

    // ✅ THÊM: Dòng tổng tiền
    $html .= '
            </tbody>
            <tfoot>
                <tr style="background:#fdf6e9;">
                    <td colspan="3" style="padding:10px; font-weight:bold; text-align:right; color:#555;">TỔNG CỘNG:</td>
                    <td style="padding:10px; font-weight:bold; text-align:right; font-size:15px; color:#cda45e;">' . number_format($grand_total, 0, ',', '.') . 'đ</td>
                </tr>
            </tfoot>
        </table>
    </div>';
}

// GHI CHÚ
if (!empty(trim($s['message']))) {
    $html .= '
    <div style="padding:12px 16px; background:#fdfcf9; border-left:4px solid #cda45e; border-radius:4px; margin-bottom:20px; font-size:12px;">
        <strong style="color:#555;">Yêu cầu bổ sung:</strong><br>
        <span style="color:#666; font-style:italic; line-height:1.6;">' . nl2br(htmlspecialchars($s['message'])) . '</span>
    </div>';
}

// LƯU Ý & ĐIỀU KHOẢN
$html .= '
    <div style="margin-top:10px; padding:15px; border:1px dashed #cda45e; border-radius:8px; font-size:10px; color:#555; line-height:1.5; background:#fffdfa;">
        <strong style="color:#cda45e; text-transform:uppercase; letter-spacing:1px;">Lưu ý quan trọng:</strong><br>
        • Quý khách vui lòng đến đúng giờ đã hẹn. Sau 20 phút, nếu quý khách không có mặt và không thông báo, bàn sẽ được giải phóng.<br>
        • Tiền cọc (30%) sẽ không được hoàn lại nếu quý khách hủy lịch trong vòng 24 giờ trước giờ hẹn.<br>
        • Mọi thay đổi về số lượng khách hoặc thực đơn xin vui lòng thông báo trước ít nhất 12 giờ.
    </div>';

// FOOTER
$html .= '
    <div style="margin-top:40px; padding-top:16px; border-top:1px solid #e8e2d9; display:table; width:100%;">
        <div style="display:table-cell; font-size:10px; color:#aaa; vertical-align:top;">
            Cảm ơn quý khách đã tin tưởng và sử dụng dịch vụ tại ' . $restaurant_name . '.<br>
            Chúng tôi hân hạnh được đón tiếp quý khách!
        </div>
        <div style="display:table-cell; text-align:right;">
            <div style="display:inline-block; text-align:center; font-size:11px; color:#333;">
                <div style="font-style:italic; margin-bottom:40px; color:#888;">Xác nhận bởi nhà hàng</div>
                <div style="width:140px; border-top:1px solid #333; padding-top:5px; font-weight:bold;">BAN QUẢN LÝ</div>
            </div>
        </div>
    </div>

</div>
</body>
</html>';

// RENDER PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();

$filename = 'Phieu_Dich_Vu_SVR_' . $id . '.pdf';
$dompdf->stream($filename, ['Attachment' => 1]);
exit;

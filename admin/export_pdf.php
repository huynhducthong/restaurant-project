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
    "SELECT f.name, bd.quantity, f.price, bd.notes, bd.toppings_info
     FROM booking_details bd
     JOIN foods f ON bd.menu_id = f.id
     WHERE bd.booking_id = ?"
);
$detail_stmt->execute([$id]);
$items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

// Resolve toppings
foreach ($items as &$it) {
    $it['toppings_list'] = [];
    $it['toppings_price'] = 0;
    if (!empty($it['toppings_info'])) {
        $t_ids = explode(',', $it['toppings_info']);
        $t_ids_str = implode(',', array_map('intval', $t_ids));
        if (!empty($t_ids_str)) {
            $toppings_query = $db->query("SELECT name, price FROM toppings WHERE id IN ($t_ids_str)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($toppings_query as $tq) {
                $it['toppings_list'][] = $tq['name'] . " (+" . number_format($tq['price']) . "đ)";
                $it['toppings_price'] += $tq['price'];
            }
        }
    }
    $it['price'] += $it['toppings_price']; // Add topping price to item price
}
unset($it);

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
<body style="font-family: DejaVu Sans, sans-serif; margin:0; padding:0; color:#333; background-color:#fff;">

<!-- HEADER LUXURY -->
<div style="background:#143B36; padding:30px 40px; display:table; width:100%; box-sizing:border-box; border-bottom: 4px solid #cda45e;">
    <div style="display:table-cell; vertical-align:middle; width: 60%;">
        <div style="font-size:24px; font-weight:bold; color:#cda45e; letter-spacing:2px; text-transform:uppercase;">' . $restaurant_name . '</div>
        ' . ($restaurant_addr ? '<div style="font-size:11px; color:#e8f5f3; margin-top:5px; line-height: 1.4;">' . $restaurant_addr . '</div>' : '') . '
        ' . ($restaurant_tel  ? '<div style="font-size:11px; color:#e8f5f3; line-height: 1.4;">Điện thoại: ' . $restaurant_tel  . '</div>' : '') . '
    </div>
    <div style="display:table-cell; vertical-align:middle; text-align:right; width: 40%;">
        <div style="font-size:16px; color:#fff; font-weight:bold; letter-spacing:3px; text-transform:uppercase;">HÓA ĐƠN DỊCH VỤ</div>
        <div style="font-size:12px; color:#cda45e; margin-top:6px; letter-spacing:1px;">MÃ SỐ: SVR-' . $id . '</div>
        <div style="font-size:10px; color:#aaa; margin-top:4px;">Ngày lập: ' . date('d/m/Y H:i') . '</div>
    </div>
</div>

<div style="padding:30px 40px;">

    <!-- THÔNG TIN KHÁCH HÀNG -->
    <div style="border: 1px solid #143B36; border-radius:0px; padding:20px; margin-bottom:25px; background: #fafafa; border-left: 5px solid #cda45e;">
        <div style="font-size:12px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; color:#143B36; margin-bottom:15px; border-bottom:1px dashed #ccc; padding-bottom:8px;">
            THÔNG TIN KHÁCH HÀNG
        </div>
        <table style="width:100%; font-size:13px; border-collapse:collapse; line-height: 1.8;">
            <tr>
                <td style="width:20%; color:#555;">Khách hàng:</td>
                <td style="width:35%; font-weight:bold; color:#000;">' . htmlspecialchars($s['customer_name']) . '</td>
                <td style="width:20%; color:#555;">Điện thoại:</td>
                <td style="width:25%; font-weight:bold; color:#000;">' . htmlspecialchars($s['customer_phone']) . '</td>
            </tr>
            <tr>
                <td style="color:#555;">Dịch vụ:</td>
                <td style="font-weight:bold; color:#143B36; text-transform: uppercase;">' . htmlspecialchars($s['service_type']) . '</td>
                <td style="color:#555;">Thời gian:</td>
                <td style="font-weight:bold; color:#000;">' . htmlspecialchars(date('H:i d/m/y', strtotime($s['booking_date']))) . '</td>
            </tr>
            <tr>
                <td style="color:#555;">Số khách:</td>
                <td style="font-weight:bold; color:#000;">' . htmlspecialchars((string)$s['guests']) . ' người</td>
                <td style="color:#555;">' . ($s['service_type'] === 'chef' ? 'Địa điểm:' : 'Phòng/Bàn:') . '</td>
                <td style="font-weight:bold; color:#000;">' . ($s['service_type'] === 'chef' ? 'Tư gia' : ($s['service_type'] === 'birthday' ? 'PHÒNG VIP' : 'Bàn tiêu chuẩn')) . '</td>
            </tr>
        </table>
    </div>';

// KHỐI TRẢI NGHIỆM CÁ NHÂN HÓA (BESPOKE)
$has_bespoke = !empty($s['has_candle']) || !empty($s['has_handwritten_card']) || !empty($s['has_flower']) || !empty($s['event_type']) || !empty($s['music_playlist']) || !empty($s['chef_requirements']);

if ($has_bespoke) {
    $html .= '
    <div style="border:1px solid #cda45e; background:#fffcf5; padding:20px; margin-bottom:25px; position: relative;">
        <div style="font-size:12px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; color:#cda45e; margin-bottom:15px; border-bottom:1px solid #f0e6d2; padding-bottom:8px;">
            YÊU CẦU CÁ NHÂN HÓA (BESPOKE)
        </div>
        <table style="width:100%; font-size:13px; border-collapse:collapse; line-height: 1.8;">';

    if (!empty($s['chef_requirements'])) {
        $html .= '
            <tr>
                <td colspan="2" style="padding:5px 0; font-weight:bold; color:#143B36; line-height: 1.6; font-style: italic;">
                    "' . nl2br(htmlspecialchars($s['chef_requirements'])) . '"
                </td>
            </tr>';
    }

    if (!empty($s['event_type'])) {
        $html .= '
            <tr>
                <td style="width:35%; color:#555;">Dịp đặc biệt:</td>
                <td style="font-weight:bold; color:#000;">' . htmlspecialchars($s['event_type']) . '</td>
            </tr>';
    }

    if (!empty($s['has_candle'])) {
        $html .= '
            <tr>
                <td style="color:#555;">Trang trí:</td>
                <td style="font-weight:bold; color:#000;">Nến thơm cao cấp</td>
            </tr>';
    }

    if (!empty($s['has_handwritten_card'])) {
        $html .= '
            <tr>
                <td style="color:#555;">Thiệp viết tay:</td>
                <td style="font-weight:bold; color:#000;">' . htmlspecialchars($s['card_message'] ?: 'Có') . '</td>
            </tr>';
    }

    if (!empty($s['has_flower'])) {
        $html .= '
            <tr>
                <td style="color:#555;">Hoa tươi thiết kế:</td>
                <td style="font-weight:bold; color:#000;">' . htmlspecialchars($s['flower_preference'] ?: 'Có') . '</td>
            </tr>';
    }
    
    if (!empty($s['music_playlist']) || !empty($s['light_tone'])) {
        $vipInfo = trim(htmlspecialchars($s['music_playlist'] . (!empty($s['light_tone']) ? ' - ' . $s['light_tone'] : '')));
        $html .= '
            <tr>
                <td style="color:#555;">Cấu hình không gian VIP:</td>
                <td style="font-weight:bold; color:#000;">' . $vipInfo . '</td>
            </tr>';
    }

    $html .= '
        </table>
    </div>';
}

// BẢNG MÓN ĂN VÀ TỔNG TIỀN
$html .= '
<div style="margin-bottom:30px;">
    <div style="font-size:12px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; color:#143B36; margin-bottom:12px;">
        CHI TIẾT HÓA ĐƠN
    </div>
    <table style="width:100%; border-collapse:collapse; font-size:13px; border: 1px solid #143B36;">
        <thead>
            <tr style="background:#143B36; color:#cda45e;">
                <th style="text-align:left; padding:12px 15px; font-weight:bold; letter-spacing: 1px;">Tên món / Dịch vụ</th>
                <th style="text-align:center; padding:12px 15px; font-weight:bold;">SL</th>
                <th style="text-align:right; padding:12px 15px; font-weight:bold;">Đơn giá</th>
                <th style="text-align:right; padding:12px 15px; font-weight:bold;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>';

if (!empty($items)) {
    foreach ($items as $index => $item) {
        $sub = $item['price'] * $item['quantity'];
        $bg = ($index % 2 === 0) ? '#fff' : '#f9f9f9';
        
        $topping_str = '';
        if (!empty($item['toppings_list'])) {
            $topping_str = '<div style="font-size:10px; color:#cda45e; margin-top:3px;"><span style="font-weight:bold;">+ Toppings:</span> ' . implode(', ', $item['toppings_list']) . '</div>';
        }
        $note_str = '';
        if (!empty($item['notes'])) {
            $note_str = '<div style="font-size:10px; color:#c0392b; margin-top:3px; font-style:italic;"><span style="font-weight:bold;">* Ghi chú:</span> ' . htmlspecialchars($item['notes']) . '</div>';
        }
        
        $html .= '
            <tr style="background:' . $bg . ';">
                <td style="padding:10px 15px; border-bottom:1px solid #eee; line-height: 1.4;">
                    <span style="font-weight:bold; color:#111;">' . htmlspecialchars($item['name']) . '</span>
                    ' . $topping_str . '
                    ' . $note_str . '
                </td>
                <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:center; vertical-align:top;">' . (int)$item['quantity'] . '</td>
                <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:right; vertical-align:top;">' . number_format($item['price'], 0, ',', '.') . 'đ</td>
                <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:right; font-weight:bold; vertical-align:top;">' . number_format($sub, 0, ',', '.') . 'đ</td>
            </tr>';
    }
} else {
    // Nếu không có món ăn cụ thể, hiển thị phí dịch vụ cơ bản / Combo
    $html .= '
            <tr style="background:#fff;">
                <td style="padding:10px 15px; border-bottom:1px solid #eee; color:#555; font-style:italic;">Gói dịch vụ / Đặt bàn</td>
                <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:center;">1</td>
                <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:right;">-</td>
                <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:right; font-weight:bold;">-</td>
            </tr>';
}

// TÍNH PHÍ ĐẦU BẾP
if ($s['service_type'] === 'chef') {
    $g = (int)$s['guests'];
    $chef_fee = 0;
    if ($g <= 2) $chef_fee = 250000;
    elseif ($g <= 6) $chef_fee = 500000;
    elseif ($g <= 12) $chef_fee = 1000000;
    else $chef_fee = 1200000;
    
    $html .= '
            <tr>
                <td colspan="3" style="padding:10px 15px; border-bottom:1px solid #eee; text-align:right; color:#143B36; font-style:italic;">Phụ phí Đầu bếp tại gia:</td>
                <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:right; font-weight:bold; color:#143B36;">' . number_format($chef_fee, 0, ',', '.') . 'đ</td>
            </tr>';
}

$html .= '
        </tbody>
        <tfoot>
            <tr style="background:#fdfdfd; border-top: 2px solid #143B36;">
                <td colspan="3" style="padding:10px 15px; font-weight:bold; text-align:right; color:#143B36; letter-spacing:1px;">TỔNG CỘNG HÓA ĐƠN:</td>
                <td style="padding:10px 15px; font-weight:bold; text-align:right; font-size:15px; color:#cda45e;">' . number_format($s['total_amount'], 0, ',', '.') . ' VNĐ</td>
            </tr>
            <tr style="background:#fdfdfd;">
                <td colspan="3" style="padding:5px 15px; font-weight:bold; text-align:right; color:#555; letter-spacing:1px;">ĐÃ THANH TOÁN (CỌC):</td>
                <td style="padding:5px 15px; font-weight:bold; text-align:right; font-size:14px; color:#28a745;">- ' . number_format($s['deposit_amount'], 0, ',', '.') . ' VNĐ</td>
            </tr>
            <tr style="background:#fdfdfd;">
                <td colspan="3" style="padding:10px 15px 15px; font-weight:bold; text-align:right; color:#143B36; letter-spacing:1px;">SỐ TIỀN CẦN THANH TOÁN:</td>
                <td style="padding:10px 15px 15px; font-weight:bold; text-align:right; font-size:16px; color:#dc3545;">' . number_format($s['total_amount'] - $s['deposit_amount'], 0, ',', '.') . ' VNĐ</td>
            </tr>
        </tfoot>
    </table>
</div>';

// GHI CHÚ
if (!empty(trim($s['message']))) {
    $html .= '
    <div style="padding:15px; background:#fff; border-left:4px solid #143B36; margin-bottom:20px; font-size:13px; border: 1px solid #eee;">
        <strong style="color:#143B36; text-transform:uppercase; letter-spacing:1px;">Ghi chú của khách:</strong><br>
        <span style="color:#444; font-style:italic; line-height:1.6; display:inline-block; margin-top:5px;">' . nl2br(htmlspecialchars($s['message'])) . '</span>
    </div>';
}

// LƯU Ý & ĐIỀU KHOẢN
$html .= '
    <div style="margin-top:15px; padding:20px; border:1px solid #eee; background:#fafafa; font-size:11px; color:#555; line-height:1.6;">
        <strong style="color:#cda45e; text-transform:uppercase; letter-spacing:1px; font-size:12px;">ĐIỀU KHOẢN & LƯU Ý DỊCH VỤ:</strong><br>
        <div style="margin-top: 8px;">
            • Quý khách vui lòng đến đúng giờ đã hẹn. Nếu quá 20 phút mà không có thông báo, bàn sẽ được tự động giải phóng.<br>
            • Tiền cọc (30%) sẽ không được hoàn lại trong trường hợp quý khách hủy lịch sát giờ (dưới 24 tiếng).<br>
            • Mọi thay đổi về số lượng khách, thực đơn, hay yêu cầu cá nhân hóa (Bespoke) xin vui lòng thông báo trước ít nhất 12 giờ để nhà hàng chuẩn bị tốt nhất.
        </div>
    </div>';

// FOOTER
$html .= '
    <div style="margin-top:40px; padding-top:20px; border-top:2px solid #143B36; display:table; width:100%;">
        <div style="display:table-cell; font-size:11px; color:#666; vertical-align:top; width:60%; line-height: 1.6;">
            <strong>Trân trọng cảm ơn quý khách đã sử dụng dịch vụ tại ' . $restaurant_name . '.</strong><br>
            Kính chúc quý khách có một trải nghiệm ẩm thực tuyệt vời và đáng nhớ!<br>
            <span style="color:#cda45e; font-style: italic;">Hẹn gặp lại quý khách!</span>
        </div>
        <div style="display:table-cell; text-align:center; width:40%;">
            <div style="font-size:12px; color:#143B36; font-weight:bold; text-transform:uppercase; letter-spacing: 1px;">ĐẠI DIỆN NHÀ HÀNG</div>
            <div style="font-style:italic; margin-bottom:50px; color:#888; font-size: 11px;">(Đã xác nhận điện tử)</div>
            <div style="width:120px; margin: 0 auto; border-top:1px solid #143B36; padding-top:5px; font-weight:bold; color:#143B36;">BAN QUẢN LÝ</div>
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
$dompdf->stream($filename, ['Attachment' => 0]);
exit;

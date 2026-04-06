<?php
// 1. Thoát ra khỏi thư mục admin để vào vendor và config
require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db = (new Database())->getConnection();
    
    // Lấy thông tin phiếu dịch vụ từ database
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$s) {
        die("Dữ liệu không tồn tại!");
    }

    // --- MỚI: Lấy danh sách món ăn kèm theo từ bảng foods ---
    $detail_sql = "SELECT f.name, bd.quantity, f.price 
                   FROM booking_details bd 
                   JOIN foods f ON bd.menu_id = f.id 
                   WHERE bd.booking_id = ?";
    $detail_stmt = $db->prepare($detail_sql);
    $detail_stmt->execute([$id]);
    $items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cấu hình PDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); 
    $dompdf = new Dompdf($options);

    // Bắt đầu khởi tạo nội dung HTML
    $html = '
    <div style="font-family: DejaVu Sans, sans-serif; padding: 20px; border: 1px solid #cda45e;">
        <h2 style="color: #cda45e; text-align: center; text-transform: uppercase; margin-bottom: 5px;">Phiếu Yêu Cầu Dịch Vụ</h2>
        <p style="text-align: center; font-size: 12px; margin-top: 0;">Mã số: #SVR-'.$id.'</p>
        <hr style="border: 0.5px solid #eee;">
        
        <table style="width: 100%; margin-top: 15px; font-size: 14px;">
            <tr>
                <td style="font-weight: bold; width: 40%; padding: 5px 0;">Khách hàng:</td>
                <td>'.htmlspecialchars($s['customer_name']).'</td>
            </tr>
            <tr>
                <td style="font-weight: bold; padding: 5px 0;">Điện thoại:</td>
                <td>'.$s['customer_phone'].'</td>
            </tr>
            <tr>
                <td style="font-weight: bold; padding: 5px 0;">Loại dịch vụ:</td>
                <td>'.strtoupper($s['service_type']).'</td>
            </tr>
            <tr>
                <td style="font-weight: bold; padding: 5px 0;">Ngày & Giờ tổ chức:</td>
                <td>'.date('H:i - d/m/Y', strtotime($s['booking_date'])).'</td>
            </tr>
            <tr>
                <td style="font-weight: bold; padding: 5px 0;">Số lượng khách:</td>
                <td>'.$s['guests'].' người</td>
            </tr>
        </table>';

    // THÊM PHẦN DANH SÁCH MÓN ĂN VÀO GIỮA
    if (!empty($items)) {
        $html .= '<div style="margin-top: 20px;">
                    <h4 style="color:#cda45e; border-bottom: 1px solid #cda45e; padding-bottom: 5px; margin-bottom: 10px;">Thực đơn đã chọn:</h4>
                    <table style="width:100%; border-collapse: collapse; font-size: 13px;">
                        <tr style="background: #f9f9f9;">
                            <th style="text-align: left; padding: 5px;">Tên món</th>
                            <th style="text-align: center; padding: 5px;">Số lượng</th>
                            <th style="text-align: right; padding: 5px;">Đơn giá</th>
                        </tr>';
        foreach ($items as $item) {
            $html .= '<tr>
                        <td style="border-bottom: 1px solid #eee; padding: 8px 5px;">'.$item['name'].'</td>
                        <td style="border-bottom: 1px solid #eee; padding: 8px 5px; text-align: center;">x'.$item['quantity'].'</td>
                        <td style="border-bottom: 1px solid #eee; padding: 8px 5px; text-align: right;">'.number_format($item['price']).'đ</td>
                      </tr>';
        }
        $html .= '</table></div>';
    }

    // PHẦN GHI CHÚ VÀ FOOTER
    $html .= '
        <div style="margin-top: 20px; padding: 10px; background: #fdfcf9; border-left: 5px solid #cda45e; font-size: 13px;">
            <strong>Yêu cầu bổ sung:</strong><br>
            <p style="font-style: italic; color: #555; margin-top: 5px;">'.nl2br(htmlspecialchars($s['message'])).'</p>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #aaa;">
            Cảm ơn quý khách đã sử dụng dịch vụ tại Restaurantly<br>
            Thời gian xuất phiếu: '.date('d/m/Y H:i').'
        </div>
    </div>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A5', 'portrait');
    $dompdf->render();
    
    // Tên file tải về
    $filename = "Phieu_Dich_Vu_SVR_".$id.".pdf";
    $dompdf->stream($filename, ["Attachment" => 1]);
    exit();
}
?>
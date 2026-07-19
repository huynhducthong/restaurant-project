<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['booking_id'])) {
    echo "Unauthorized or missing ID";
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$user_id = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();

// Fetch booking info
$stmt = $db->prepare("SELECT s.*, t.table_code FROM service_bookings s LEFT JOIN restaurant_tables t ON s.table_id = t.id WHERE s.id = ? AND s.user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "Không tìm thấy thông tin đặt bàn.";
    exit;
}

$has_agreed = (strpos($booking['chef_requirements'] ?? '', '[Khách hàng ĐÃ ĐỒNG Ý thực đơn]') !== false);

// Fetch items
$detail_stmt = $db->prepare("SELECT f.name, bd.quantity, f.price, bd.notes, bd.toppings_info FROM booking_details bd JOIN foods f ON bd.menu_id = f.id WHERE bd.booking_id = ?");
$detail_stmt->execute([$booking_id]);
$items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $it['final_price'] = $it['price'] + $it['toppings_price'];
    $it['subtotal'] = $it['final_price'] * $it['quantity'];
}
unset($it);

$total = 0;
?>
<div style="font-family:'Source Sans 3', sans-serif;">
    <div style="display:flex; justify-content:space-between; margin-bottom:15px; padding-bottom:15px; border-bottom:1px dashed #e8e2d9;">
        <div>
            <div style="font-size:12px; color:#888;">Mã Đặt Bàn</div>
            <div style="font-size:15px; font-weight:bold; color:var(--F);">#BK-<?= $booking_id ?></div>
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
        <div><span style="color:#888;">Dịch vụ:</span> <strong style="color:var(--accent-burgundy); text-transform:uppercase;"><?= htmlspecialchars($booking['service_type']) ?> <?= $booking['table_code'] ? ' - Bàn: ' . htmlspecialchars($booking['table_code']) : '' ?></strong></div>
        <div><span style="color:#888;">Thời gian:</span> <strong><?= date('H:i - d/m/Y', strtotime($booking['booking_date'])) ?></strong></div>
        <div><span style="color:#888;">Khách:</span> <strong><?= $booking['guests'] ?> người</strong></div>
        <div><span style="color:#888;">Cọc:</span> <strong style="color:#d64545;"><?= number_format($booking['deposit_amount']) ?>đ</strong></div>
    </div>

    <h6 style="font-size:13px; font-weight:bold; text-transform:uppercase; color:#888; border-bottom:1px solid #eee; padding-bottom:5px;">Thực Đơn Chi Tiết</h6>
    <?php if (empty($items)): ?>
        <p style="font-size:13px; color:#666;">Không có món ăn chọn thêm.</p>
    <?php else: ?>
        <table style="width:100%; font-size:13px; border-collapse:collapse; margin-bottom:20px;">
            <thead>
                <tr style="background:rgba(128,128,128,0.1);">
                    <th style="padding:8px; text-align:left; border-bottom:1px solid rgba(128,128,128,0.2);">Tên món</th>
                    <th style="padding:8px; text-align:center; border-bottom:1px solid rgba(128,128,128,0.2);">SL</th>
                    <th style="padding:8px; text-align:right; border-bottom:1px solid rgba(128,128,128,0.2);">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $it): 
                    $total += $it['subtotal'];
                ?>
                <tr>
                    <td style="padding:8px; border-bottom:1px solid rgba(128,128,128,0.1);">
                        <strong><?= htmlspecialchars($it['name']) ?></strong>
                        <?php if (!empty($it['toppings_list'])): ?>
                            <div style="font-size:11px; opacity:0.8; margin-top:2px;">
                                <i class="fas fa-plus-circle me-1" style="font-size:10px;"></i>Topping: <?= implode(', ', $it['toppings_list']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($it['notes'])): ?>
                            <div style="font-size:11px; font-style:italic; opacity:0.6; margin-top:2px;"><i class="fas fa-comment-dots me-1"></i><?= htmlspecialchars($it['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px; text-align:center; border-bottom:1px solid rgba(128,128,128,0.1);"><strong>x<?= $it['quantity'] ?></strong></td>
                    <td style="padding:8px; text-align:right; border-bottom:1px solid rgba(128,128,128,0.1);"><strong><?= number_format($it['subtotal']) ?>đ</strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="padding:10px 8px; text-align:right; font-weight:bold; opacity:0.8;">TỔNG CỘNG:</td>
                    <td style="padding:10px 8px; text-align:right; font-weight:bold; color:#A88746; font-size:14px;"><?= number_format($total) ?>đ</td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <?php 
    $is_negotiated = false;
    $step4 = false;
    if ($booking['combo_id'] == -1): 
        $s_status = $booking['status'];
        $step2 = in_array($s_status, ['Confirmed', 'Completed']);
        $step3 = in_array($s_status, ['Confirmed', 'Completed']);
        $step4 = in_array($s_status, ['Completed']);
        
        $is_negotiated = (strpos($booking['chef_requirements'] ?? '', 'Thỏa thuận') !== false);
        $step1_text = $is_negotiated ? '1. Gửi yêu cầu thiết kế' : '1. Gửi yêu cầu & Thanh toán cọc';
        $step3_text = $is_negotiated ? '3. Báo giá & Chốt thực đơn' : '3. Chốt thực đơn';
        
        // Nếu là Thỏa thuận và đang chờ duyệt (Pending) thì mới hiển thị chữ "Thanh toán cọc bổ sung"
        // Nếu đã xác nhận (Confirmed) hoặc hoàn thành (Completed) thì chỉ hiển thị "Chuẩn bị & Phục vụ"
        $step4_text = ($is_negotiated && $s_status == 'Pending') ? '4. Thanh toán cọc bổ sung & Chuẩn bị' : '4. Chuẩn bị & Phục vụ';
    ?>
    <div style="margin-top:20px; padding:15px; background:#fffbf5; border:1px solid #e8e2d9; border-radius:8px;">
        <h6 style="color:var(--accent-burgundy); font-weight:bold; font-size:15px; text-transform:uppercase; margin-bottom:10px;"><i class="fas fa-magic me-1"></i> Trải Nghiệm Thiết Kế Riêng</h6>
        <?php if (!empty($booking['chef_requirements'])): ?>
            <div style="font-size:14px; color:#555; margin-bottom:15px; font-style:italic;">
                <?php
                    $display_req = $booking['chef_requirements'];
                    $reply_part = '';
                    
                    // Nếu có phản hồi từ khách, tách nó ra để giữ lại
                    if (($pos2 = strpos($display_req, '[Phản hồi từ khách')) !== false) {
                        $reply_part = substr($display_req, $pos2);
                        $display_req = substr($display_req, 0, $pos2);
                    }
                    
                    // Loại bỏ phần DNA Ẩm thực khỏi phần chính
                    if (($pos = strpos($display_req, '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---')) !== false) {
                        $display_req = substr($display_req, 0, $pos);
                    }
                    
                    $display_req = trim($display_req);
                    
                    // Ghép lại
                    $final_display = $display_req . (!empty($reply_part) ? "\n\n" . $reply_part : "");
                    
                                        if (!empty($final_display)) {
                        $lines = explode("\n", trim($final_display));
                        $formatted_html = '<div style="background:#fff; border:1px solid #eee; border-radius:6px; padding:15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">';
                        $details_text = '';
                        $in_details = false;
                        
                        foreach ($lines as $line) {
                            $l = trim($line);
                            if (strpos($l, 'Dịp:') === 0 || strpos($l, 'Ngân sách:') === 0 || strpos($l, 'Phong cách:') === 0) {
                                $parts = explode(':', $l, 2);
                                if (count($parts) == 2) {
                                    $formatted_html .= '<div style="margin-bottom:8px; display:flex; align-items:flex-start;"><strong style="color:#222; min-width:100px; display:inline-block;">' . htmlspecialchars(trim($parts[0])) . ':</strong> <span style="color:#555; flex:1;">' . htmlspecialchars(trim($parts[1])) . '</span></div>';
                                }
                            } elseif (strpos($l, 'Chi tiết:') === 0) {
                                $in_details = true;
                                $details_text .= substr($l, strlen('Chi tiết:')) . "\n";
                            } else {
                                if ($in_details || (!empty($l) && !preg_match('/^(Dịp|Ngân sách|Phong cách)/', $l))) {
                                    $details_text .= $l . "\n";
                                }
                            }
                        }
                        
                        if (!empty(trim($details_text))) {
                            $formatted_html .= '<div style="margin-top:12px; padding-top:12px; border-top:1px dashed #ddd; font-style:normal; color:#444; line-height:1.6;">
                                <strong style="color:#222; display:block; margin-bottom:8px;"><i class="fas fa-magic text-warning me-2" style="font-size:13px;"></i>Tuyển chọn riêng:</strong>
                                <div style="padding:10px 12px; background:#fefcf9; border-left:3px solid var(--accent-burgundy); border-radius:0 4px 4px 0; font-size:13.5px; color:#444;">' . nl2br(htmlspecialchars(trim($details_text))) . '</div>
                            </div>';
                        }
                        
                        $formatted_html .= '</div>';
                        echo $formatted_html;
                    }
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($booking['ai_suggested_menu'])): ?>
            <div style="font-size:15px; color:#222; margin-bottom:15px; padding: 15px; border-left: 3px solid var(--gold); background: #fff;">
                <strong style="color: var(--gold); display: block; margin-bottom: 5px;"><i class="fas fa-utensils me-1"></i> Thực Đơn Đề Xuất / Phản Hồi Từ Bếp Trưởng:</strong>
                <?php
                $html_menu = htmlspecialchars($booking['ai_suggested_menu']);
                $html_menu = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html_menu);
                $html_menu = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html_menu);
                echo nl2br($html_menu);
                ?>
                
                <?php 
                if ($booking['status'] === 'Pending' && !$step4 && !$has_agreed): 
                ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e8e2d9;">
                    <strong style="font-size: 14px; color: #555; display: block; margin-bottom: 5px;"><i class="fas fa-reply me-1"></i> Gửi yêu cầu chỉnh sửa / Phản hồi lại Bếp trưởng:</strong>
                    <textarea id="customer-reply-text" class="form-control mb-2" rows="2" style="font-size: 14px; resize: none;" placeholder="Ví dụ: Đổi món khai vị thành món soup, hoặc tôi bị dị ứng với nấm..."></textarea>
                    <div style="display:flex; gap:10px; margin-top:5px;">
                        <button type="button" class="btn btn-sm" id="btn-send-reply" data-id="<?= $booking_id ?>" style="background-color: #333; color: #fff; font-size: 14px; padding: 6px 15px;">
                            <i class="fas fa-paper-plane me-1"></i> Gửi phản hồi
                        </button>
                        <button type="button" class="btn btn-sm" id="btn-accept-menu" data-id="<?= $booking_id ?>" style="background-color: #28a745; border-color: #28a745; color: #fff; font-size: 14px; padding: 6px 15px;">
                            <i class="fas fa-check-circle me-1"></i> Đồng ý thực đơn
                        </button>
                    </div>
                </div>
                <?php elseif ($has_agreed && $booking['status'] === 'Pending'): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e8e2d9; color: #28a745; font-size: 14px; font-weight: 500;">
                    <i class="fas fa-check-circle me-1"></i> Bạn đã xác nhận đồng ý với thực đơn này.
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <h6 style="font-size:12px; font-weight:bold; color:#888; margin-bottom:10px; letter-spacing:1px;">TRẠNG THÁI QUY TRÌNH</h6>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <!-- Step 1 -->
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:24px; height:24px; border-radius:50%; background:#d4b06a; color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:bold;">
                    <i class="fas fa-check"></i>
                </div>
                <div style="font-size:12px; color:#333; font-weight:bold;"><?= $step1_text ?> <span style="color:#28a745; font-size:10px; margin-left:5px;">(Hoàn thành)</span></div>
            </div>
            <div style="width:2px; height:10px; background:#d4b06a; margin-left:11px;"></div>
            
            <!-- Step 2 -->
            <div style="display:flex; align-items:center; gap:10px; opacity: <?= $step2 ? '1' : '0.8' ?>">
                <div style="width:24px; height:24px; border-radius:50%; border:2px solid #d4b06a; background:<?= $step2 ? '#d4b06a' : 'transparent' ?>; color:<?= $step2 ? '#fff' : '#d4b06a' ?>; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:bold;">
                    <?= $step2 ? '<i class="fas fa-check"></i>' : '2' ?>
                </div>
                <div style="font-size:12px; color:<?= $step2 ? '#333' : '#888' ?>; font-weight:<?= $step2 ? 'bold' : 'normal' ?>;">2. Bếp trưởng tư vấn & Lên menu <?= $step2 ? '<span style="color:#28a745; font-size:10px; margin-left:5px;">(Hoàn thành)</span>' : '<span style="color:#f39c12; font-size:10px; margin-left:5px; font-style:italic;">(Nhà hàng đang xử lý)</span>' ?></div>
            </div>
            <div style="width:2px; height:10px; background:<?= $step2 ? '#d4b06a' : 'rgba(212,176,106,0.3)' ?>; margin-left:11px;"></div>
            
            <!-- Step 3 -->
            <div style="display:flex; align-items:center; gap:10px; opacity: <?= $step3 ? '1' : '0.5' ?>">
                <div style="width:24px; height:24px; border-radius:50%; border:2px solid #d4b06a; background:<?= $step3 ? '#d4b06a' : 'transparent' ?>; color:<?= $step3 ? '#fff' : '#d4b06a' ?>; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:bold;">
                    <?= $step3 ? '<i class="fas fa-check"></i>' : '3' ?>
                </div>
                <div style="font-size:12px; color:<?= $step3 ? '#333' : '#888' ?>; font-weight:<?= $step3 ? 'bold' : 'normal' ?>;"><?= $step3_text ?> <?= $step3 ? '<span style="color:#28a745; font-size:10px; margin-left:5px;">(Hoàn thành)</span>' : '' ?></div>
            </div>
            <div style="width:2px; height:10px; background:<?= $step3 ? '#d4b06a' : 'rgba(212,176,106,0.3)' ?>; margin-left:11px;"></div>
            
            <!-- Step 4 -->
            <div style="display:flex; align-items:center; gap:10px; opacity: <?= $step4 ? '1' : '0.5' ?>">
                <div style="width:24px; height:24px; border-radius:50%; border:2px solid #d4b06a; background:<?= $step4 ? '#d4b06a' : 'transparent' ?>; color:<?= $step4 ? '#fff' : '#d4b06a' ?>; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:bold;">
                    <?= $step4 ? '<i class="fas fa-check"></i>' : '4' ?>
                </div>
                <div style="font-size:12px; color:<?= $step4 ? '#333' : '#888' ?>; font-weight:<?= $step4 ? 'bold' : 'normal' ?>;"><?= $step4_text ?> <?= $step4 ? '<span style="color:#28a745; font-size:10px; margin-left:5px;">(Hoàn thành)</span>' : '' ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php 
    // Nút thanh toán cọc bổ sung cho khách hàng
    // Chỉ hiển thị khi đơn đang chờ duyệt (Pending) VÀ khách hàng ĐÃ bấm đồng ý thực đơn
    if ($is_negotiated && $booking['deposit_amount'] > 0 && !$step4 && $booking['status'] === 'Pending' && $has_agreed): 
    ?>
    <div style="text-align:center; margin-top:15px; margin-bottom:5px;">
        <a href="booking_payment.php?id=<?= $booking_id ?>" class="btn btn-sm" style="background-color: var(--accent-burgundy); border-color: var(--accent-burgundy); color: #fff; font-size: 13px; font-weight: 500; padding: 6px 15px; border-radius: 6px;">
            <i class="fas fa-credit-card me-1"></i>Thanh toán cọc bổ sung
        </a>
    </div>
    <?php endif; ?>

    <?php if(!empty($booking['message'])): ?>
    <div style="background:#fdfcf9; border-left:3px solid var(--accent-burgundy); padding:10px; font-size:12px; font-style:italic;">
        <strong>Ghi chú:</strong> <?= nl2br(htmlspecialchars($booking['message'])) ?>
    </div>
    <?php endif; ?>
    
    <div style="text-align:center; margin-top:20px;">
        <a href="admin/export_pdf.php?id=<?= $booking_id ?>" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
            <i class="bi bi-printer me-1"></i>In Hóa Đơn PDF
        </a>
    </div>
</div>


<?php
// ✅ FIX 1: Xác thực session admin (Phải nằm trên cùng)
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

// KHÔNG ĐƯỢC INCLUDE GIAO DIỆN Ở ĐÂY.
// Di chuyển file admin_layout_header.php xuống dưới cùng phần logic.

require_once '../../config/database.php';
$db = (new Database())->getConnection();

// Flash message từ session
$flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);

// ============================================
// PHẦN XỬ LÝ LOGIC (Lưu dữ liệu, Upload ảnh, Redirect)
// Mọi lệnh header() chuyển trang phải nằm ở khu vực này
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'hotline'         => $_POST['hotline']         ?? '',
        'address'         => $_POST['address']         ?? '',
        'restaurant_name' => $_POST['restaurant_name'] ?? '',
        'name_position'   => $_POST['name_position']   ?? 'center',
        'open_time'       => $_POST['open_time']       ?? '09:00 AM - 11:00 PM',
        'open_days'       => $_POST['open_days']       ?? 'Thứ 2 - Chủ Nhật',
        'inv_expiry_days' => $_POST['inv_expiry_days'] ?? '7',
        'inv_low_stock'   => $_POST['inv_low_stock']   ?? '5',
        'inv_auto_deduct' => $_POST['inv_auto_deduct'] ?? '1',
        'enable_telegram'    => $_POST['enable_telegram']    ?? '0',
        'telegram_bot_token' => $_POST['telegram_bot_token'] ?? '',
        'telegram_chat_id'   => $_POST['telegram_chat_id']   ?? '',
        'telegram_eod_hour'    => (string) max(0, min(23, (int) ($_POST['telegram_eod_hour'] ?? 22))),
        'telegram_eod_enabled' => $_POST['telegram_eod_enabled'] ?? '1',
    ];

    // Prepare 1 lần ngoài loop, execute nhiều lần
    $stmt = $db->prepare(
        "INSERT INTO settings (key_name, key_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)"
    );
    foreach ($fields as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    // Validate upload logo - ext + MIME + size
    if (!empty($_FILES['logo']['name'])) {
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size     = 2 * 1024 * 1024; // 2MB

        $ext      = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $tmp_path = $_FILES['logo']['tmp_name'];
        $size     = $_FILES['logo']['size'];

        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'Logo chỉ chấp nhận: JPG, PNG, WEBP.'];
            header('Location: settings.php'); exit;
        }
        if ($size > $max_size) {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'Logo quá lớn. Tối đa 2MB.'];
            header('Location: settings.php'); exit;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp_path);
            finfo_close($finfo);
            if (!in_array($mime, $allowed_mime)) {
                $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'File không phải ảnh hợp lệ.'];
                header('Location: settings.php'); exit;
            }
        }

        $file_name   = 'logo.' . $ext;
        // ĐÃ FIX: Dùng __DIR__ để thiết lập đường dẫn tuyệt đối khi upload file
        $target_file = __DIR__ . '/../../public/assets/img/' . $file_name;
        
        if (move_uploaded_file($tmp_path, $target_file)) {
            // ĐÃ FIX: Chỉ lưu đường dẫn tính từ thư mục public vào DB (Ví dụ: assets/img/logo.png)
            $stmt->execute(['logo_url', 'assets/img/' . $file_name]);
            // Version chống cache browser
            $stmt->execute(['logo_ver', (string)time()]);
        } else {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục.'];
            header('Location: settings.php'); exit;
        }
    }

    // Flash session + redirect HTTP
    $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => 'Cập nhật cấu hình thành công!'];
    
    // Vì không có giao diện nào được in ra trước đó, lệnh header() này sẽ hoạt động an toàn
    header('Location: settings.php'); exit;
}

// THÊM: Xử lý gửi test Telegram
if (isset($_POST['test_telegram'])) {
    require_once '../../config/notification_helper.php';
    $msg = generateMorningReport();
    if (!$msg) {
        $msg = "🚨 <b>THÔNG BÁO TEST:</b> Hệ thống kết nối tốt. Hiện tại không có mặt hàng nào cần cảnh báo.";
    }
    
    $sent = sendTelegramNotification($msg);
    if ($sent) {
        $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => '✅ Đã gửi báo cáo đến Telegram của bạn!'];
    } else {
        $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => '❌ Gửi thất bại. Vui lòng kiểm tra Token/ChatID hoặc kết nối mạng.'];
    }
    header('Location: settings.php'); exit;
}

if (isset($_POST['test_telegram_eod'])) {
    require_once '../../config/notification_helper.php';
    $msg = generateEndOfDayRevenueReport($db);
    $sent = sendTelegramNotification($msg);
    if ($sent) {
        $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => '✅ Đã gửi thử báo cáo doanh thu cuối ngày (hôm nay) qua Telegram.'];
    } else {
        $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => '❌ Gửi báo cáo cuối ngày thất bại. Kiểm tra bật Telegram / Token / Chat ID.'];
    }
    header('Location: settings.php'); exit;
}

// Lấy dữ liệu hiện tại
$stmt = $db->prepare("SELECT * FROM settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['key_value'];
}

// ĐÃ FIX: Hiển thị logo preview trong trang cài đặt admin
$logo_src = '';
if (!empty($settings['logo_url'])) {
    $ver      = $settings['logo_ver'] ?? '1';
    // Thêm ../../public/ vì file cài đặt này nằm ở admin/controllers/
    $logo_src = '../../public/' . htmlspecialchars($settings['logo_url']) . '?v=' . $ver;
}


// ============================================
// PHẦN HIỂN THỊ GIAO DIỆN (UI)
// Bây giờ bạn có thể thoải mái include file Layout Header
// ============================================
include '../../public/admin_layout_header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cấu hình Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .settings-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .section-title { color: #cda45e; font-weight: 700; border-bottom: 2px solid #cda45e; padding-bottom: 10px; margin-bottom: 25px; }
        .btn-save { background: #cda45e; color: white; padding: 12px 30px; border-radius: 50px; font-weight: 600; transition: 0.3s; }
        .btn-save:hover { background: #b89252; transform: translateY(-2px); color: white; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($flash['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card settings-card p-4">
                <h3 class="section-title"><i class="bi bi-gear-fill me-2"></i>CẤU HÌNH HỆ THỐNG</h3>

                <form action="" method="POST" enctype="multipart/form-data">

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Tên nhà hàng</label>
                            <input type="text" name="restaurant_name" class="form-control"
                                   value="<?= htmlspecialchars($settings['restaurant_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Vị trí chữ Banner</label>
                            <select name="name_position" class="form-select">
                                <option value="left"   <?= ($settings['name_position'] ?? '') == 'left'   ? 'selected' : '' ?>>Trái</option>
                                <option value="center" <?= ($settings['name_position'] ?? '') == 'center' ? 'selected' : '' ?>>Giữa</option>
                                <option value="right"  <?= ($settings['name_position'] ?? '') == 'right'  ? 'selected' : '' ?>>Phải</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Giờ mở cửa</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                <input type="text" name="open_time" class="form-control"
                                       value="<?= htmlspecialchars($settings['open_time'] ?? '09:00 AM - 11:00 PM') ?>"
                                       placeholder="Ví dụ: 09:00 AM - 10:00 PM">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Ngày hoạt động</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                <input type="text" name="open_days" class="form-control"
                                       value="<?= htmlspecialchars($settings['open_days'] ?? 'Thứ 2 - Chủ Nhật') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Hotline</label>
                            <input type="text" name="hotline" class="form-control"
                                   value="<?= htmlspecialchars($settings['hotline'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Địa chỉ</label>
                            <input type="text" name="address" class="form-control"
                                   value="<?= htmlspecialchars($settings['address'] ?? '') ?>">
                        </div>
                    </div>

                    <h5 class="section-title mt-4" style="font-size: 1.1rem; border-color: #3498db; color: #3498db;">
                        <i class="bi bi-box-seam me-2"></i>CẤU HÌNH KHO
                    </h5>

                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold small text-muted">Cảnh báo HSD trước (ngày)</label>
                            <input type="number" name="inv_expiry_days" class="form-control"
                                   value="<?= htmlspecialchars($settings['inv_expiry_days'] ?? '7') ?>">
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold small text-muted">Ngưỡng tồn thấp chung</label>
                            <input type="number" name="inv_low_stock" class="form-control"
                                   value="<?= htmlspecialchars($settings['inv_low_stock'] ?? '5') ?>">
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold small text-muted">Tự động trừ kho khi bán</label>
                            <select name="inv_auto_deduct" class="form-select">
                                <option value="1" <?= ($settings['inv_auto_deduct'] ?? '1') == '1' ? 'selected' : '' ?>>Bật (Khuyên dùng)</option>
                                <option value="0" <?= ($settings['inv_auto_deduct'] ?? '1') == '0' ? 'selected' : '' ?>>Tắt (Thủ công)</option>
                            </select>
                        </div>
                    </div>
                    
                    <h5 class="section-title mt-4" style="font-size: 1.1rem; border-color: #0088cc; color: #0088cc;">
                        <i class="bi bi-telegram me-2"></i>THÔNG BÁO TELEGRAM (MOBILE)
                    </h5>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle me-1"></i> <b>Hướng dẫn:</b> Tạo Bot qua @BotFather để lấy <b>Token</b>. Gửi tin nhắn cho @userinfobot để lấy <b>Chat ID</b> của bạn.
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold small text-muted">Trạng thái</label>
                            <select name="enable_telegram" class="form-select">
                                <option value="1" <?= ($settings['enable_telegram'] ?? '0') == '1' ? 'selected' : '' ?>>Bật thông báo</option>
                                <option value="0" <?= ($settings['enable_telegram'] ?? '0') == '0' ? 'selected' : '' ?>>Tắt</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-4">
                            <label class="form-label fw-bold small text-muted">Bot Token</label>
                            <input type="text" name="telegram_bot_token" class="form-control" placeholder="123456789:ABCDefgh..."
                                   value="<?= htmlspecialchars($settings['telegram_bot_token'] ?? '') ?>">
                        </div>
                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-bold small text-muted">Chat ID (Người nhận)</label>
                            <input type="text" name="telegram_chat_id" class="form-control" placeholder="Ví dụ: 987654321"
                                   value="<?= htmlspecialchars($settings['telegram_chat_id'] ?? '') ?>">
                            <small class="text-muted">Báo cáo kho buổi sáng (khi có cảnh báo) và báo cáo doanh thu cuối ngày đều gửi vào chat này.</small>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold small text-muted">Giờ gửi báo cáo cuối ngày (0–23)</label>
                            <input type="number" name="telegram_eod_hour" class="form-control" min="0" max="23"
                                   value="<?= htmlspecialchars($settings['telegram_eod_hour'] ?? '22') ?>">
                            <small class="text-muted">Mặc định 22 (22h). Sau giờ này, lần đầu mở Dashboard trong ngày sẽ gửi Telegram.</small>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold small text-muted">Báo cáo cuối ngày</label>
                            <select name="telegram_eod_enabled" class="form-select">
                                <option value="1" <?= ($settings['telegram_eod_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Bật</option>
                                <option value="0" <?= ($settings['telegram_eod_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Tắt</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-center mt-2 mb-4 d-flex flex-wrap justify-content-center gap-2">
                        <button type="submit" name="test_telegram" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-send me-1"></i> Gửi thử báo cáo kho
                        </button>
                        <button type="submit" name="test_telegram_eod" class="btn btn-sm btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-graph-up me-1"></i> Gửi thử báo cáo cuối ngày
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Logo hiện tại</label>
                        <div class="mb-2">
                            <?php if ($logo_src): ?>
                                <img src="<?= $logo_src ?>"
                                     style="max-height: 60px; background: #333; padding: 5px;"
                                     alt="Logo">
                            <?php endif; ?>
                        </div>
                        <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        <small class="text-muted">Chỉ chấp nhận JPG, PNG, WEBP — tối đa 2MB. Để trống nếu không thay đổi.</small>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-save">
                            <i class="bi bi-check-circle me-2"></i>LƯU THAY ĐỔI
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
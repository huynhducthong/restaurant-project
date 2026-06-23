<?php
// ✅ FIX 1: Xác thực session admin (Phải nằm trên cùng)
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php'); exit;
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

// Lấy danh sách gallery
$stmt_gal = $db->query("SELECT * FROM galleries ORDER BY sort_order ASC, id DESC");
$galleries = $stmt_gal->fetchAll(PDO::FETCH_ASSOC);

// Footer Settings
$stmt_ft = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt_ft->fetch(PDO::FETCH_ASSOC)) {
    $ft[$row['setting_key']] = $row['setting_value'];
}
$links = $db->query("SELECT * FROM footer_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);

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
        
        /* FIX: Bootstrap 5 hides .tab-pane only if direct child of .tab-content. Since ours is in a form, we need to manually hide them */
        .tab-content form .tab-pane { display: none; }
        .tab-content form .tab-pane.active { display: block; }
    </style>
</head>
<body>

<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($flash['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card settings-card">
                <div class="card-body p-3">
                    <h3 class="section-title"><i class="bi bi-gear-fill me-2"></i>CẤU HÌNH HỆ THỐNG</h3>

                    <?php $active_tab = $_GET['tab'] ?? 'general'; ?>

                    <!-- Tab Navigation -->
                    <ul class="nav nav-pills mb-4 pb-2 border-bottom" id="settings-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $active_tab === 'general' ? 'active' : '' ?>" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="<?= $active_tab === 'general' ? 'true' : 'false' ?>">
                                <i class="bi bi-info-circle me-1"></i> Thông Tin Chung
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $active_tab === 'inventory' ? 'active' : '' ?>" id="inventory-tab" data-bs-toggle="pill" data-bs-target="#inventory" type="button" role="tab" aria-controls="inventory" aria-selected="<?= $active_tab === 'inventory' ? 'true' : 'false' ?>">
                                <i class="bi bi-box-seam me-1"></i> Cấu Hình Kho
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $active_tab === 'telegram' ? 'active' : '' ?>" id="telegram-tab" data-bs-toggle="pill" data-bs-target="#telegram" type="button" role="tab" aria-controls="telegram" aria-selected="<?= $active_tab === 'telegram' ? 'true' : 'false' ?>">
                                <i class="bi bi-telegram me-1"></i> Telegram
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $active_tab === 'gallery' ? 'active' : '' ?>" id="gallery-tab" data-bs-toggle="pill" data-bs-target="#gallery" type="button" role="tab" aria-controls="gallery" aria-selected="<?= $active_tab === 'gallery' ? 'true' : 'false' ?>">
                                <i class="bi bi-images me-1"></i> Thư Viện Ảnh (Gallery)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $active_tab === 'footer' ? 'active' : '' ?>" id="footer-tab" data-bs-toggle="pill" data-bs-target="#footer" type="button" role="tab" aria-controls="footer" aria-selected="<?= $active_tab === 'footer' ? 'true' : 'false' ?>">
                                <i class="bi bi-layout-text-window-reverse me-1"></i> Cấu hình Footer
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="settings-tabContent">

                        <!-- FORM BAO TRÙM CHO 3 TAB CÀI ĐẶT CHUNG -->
                        <div class="tab-pane <?= in_array($active_tab, ['general', 'inventory', 'telegram']) ? 'show active' : '' ?>" id="settings-forms-wrapper">
                            <form action="" method="POST" enctype="multipart/form-data">

                                <!-- TAB GENERAL -->
                                <div class="tab-pane <?= $active_tab === 'general' ? 'show active' : '' ?> pt-2" id="general" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
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
                                </div>

                                <!-- TAB INVENTORY -->
                                <div class="tab-pane <?= $active_tab === 'inventory' ? 'show active' : '' ?> pt-2" id="inventory" role="tabpanel" aria-labelledby="inventory-tab" tabindex="0">
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
                                </div>

                                <!-- TAB TELEGRAM -->
                                <div class="tab-pane <?= $active_tab === 'telegram' ? 'show active' : '' ?> pt-2" id="telegram" role="tabpanel" aria-labelledby="telegram-tab" tabindex="0">
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
                                </div>

                                <!-- Nút lưu cho các tab trên -->
                                <div class="text-center mt-4 border-top pt-4">
                                    <button type="submit" class="btn btn-save">
                                        <i class="bi bi-check-circle me-2"></i>LƯU THAY ĐỔI
                                    </button>
                                </div>

                            </form>
                        </div> <!-- End wrapper form settings -->

                        <!-- TAB GALLERY (Không nằm trong form cài đặt chung) -->
                        <div class="tab-pane fade <?= $active_tab === 'gallery' ? 'show active' : '' ?> pt-2" id="gallery" role="tabpanel" aria-labelledby="gallery-tab" tabindex="0">
                            
                            <div class="row">
                                <!-- Form thêm mới Gallery -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm border-0 bg-light mb-4">
                                        <div class="card-body">
                                            <h6 class="mb-3 text-uppercase fw-bold text-muted">Thêm Ảnh Atmosphere Mới</h6>
                                            <form action="/restaurant-project/admin/controllers/GalleryController.php?action=store" method="POST" enctype="multipart/form-data">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Tên hình ảnh (Tùy chọn)</label>
                                                    <input type="text" name="title" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Chọn ảnh <span class="text-danger">*</span></label>
                                                    <input type="file" name="image" class="form-control form-control-sm" accept="image/*" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Thứ tự ưu tiên (Sort Order)</label>
                                                    <input type="number" name="sort_order" class="form-control form-control-sm" value="0">
                                                    <small class="text-muted d-block mt-1" style="font-size:11px;">Trang chủ sẽ tự động ưu tiên lấy 7 bức ảnh mới nhất và đang bật để hiển thị lên lưới.</small>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                                    <i class="bi bi-upload"></i> Tải Lên
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bảng danh sách Gallery -->
                                <div class="col-md-8">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0 border">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="80">Ảnh</th>
                                                    <th>Thông Tin</th>
                                                    <th class="text-center">Thứ Tự</th>
                                                    <th class="text-center">Hiển Thị</th>
                                                    <th class="text-end">Hành Động</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($galleries)): ?>
                                                    <?php foreach ($galleries as $gallery): ?>
                                                        <tr>
                                                            <td>
                                                                <img src="/restaurant-project/public/assets/img/gallery/<?= htmlspecialchars($gallery['image_url']) ?>" 
                                                                     alt="Gallery" 
                                                                     class="img-thumbnail" 
                                                                     style="width: 70px; height: 50px; object-fit: cover;">
                                                            </td>
                                                            <td>
                                                                <strong class="d-block" style="font-size:14px;"><?= htmlspecialchars($gallery['title'] ?: 'Không có tên') ?></strong>
                                                                <span class="text-muted" style="font-size:11px;">Thêm: <?= date('d/m/Y', strtotime($gallery['created_at'])) ?></span>
                                                            </td>
                                                            <td class="text-center"><?= $gallery['sort_order'] ?></td>
                                                            <td class="text-center">
                                                                <a href="/restaurant-project/admin/controllers/GalleryController.php?action=toggle&id=<?= $gallery['id'] ?>" class="btn btn-sm <?= $gallery['is_active'] ? 'btn-success' : 'btn-secondary' ?> py-0 px-2" style="font-size:11px;">
                                                                    <?= $gallery['is_active'] ? 'Đang bật' : 'Đã ẩn' ?>
                                                                </a>
                                                            </td>
                                                            <td class="text-end">
                                                                <a href="/restaurant-project/admin/controllers/GalleryController.php?action=delete&id=<?= $gallery['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Bạn có chắc chắn muốn xóa hình ảnh này không?');">
                                                                    <i class="bi bi-trash"></i> Xóa
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4 text-muted">
                                                            Chưa có hình ảnh nào trong thư viện.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB FOOTER (Form riêng) -->
                        <div class="tab-pane <?= $active_tab === 'footer' ? 'show active' : '' ?>" id="footer" role="tabpanel" aria-labelledby="footer-tab">
                            <style>
                                .footer-admin { font-family: 'Poppins', sans-serif; }
                                .footer-admin .card { border-radius: 16px; border: 1px solid #e0d6c8; box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 24px; }
                                .footer-admin .card-header { background: #fff; border-bottom: 1px solid #e0d6c8; font-weight: 600; color: #1a1814; }
                                .footer-admin .section-title { font-size: 1.1rem; font-weight: 700; color: #cda45e; }
                                .footer-admin .btn-save-footer { background: #cda45e; color: #fff; border: none; padding: 12px 30px; border-radius: 30px; font-weight: 600; }
                                .footer-admin .btn-save-footer:hover { background: #b89252; }
                                .footer-admin .form-control:focus, .footer-admin .form-select:focus { border-color: #cda45e; box-shadow: 0 0 0 0.2rem rgba(205,164,94,0.25); }
                                .footer-admin .btn-outline-gold { border: 1px solid #cda45e; color: #cda45e; }
                                .footer-admin .btn-outline-gold:hover { background: #cda45e; color: #fff; }
                                .live-preview-wrapper { position: sticky; top: 20px; }
                                .live-preview { background: #0c0b09; color: #fff; border-radius: 12px; padding: 30px; min-height: 300px; font-family: 'Cormorant Garamond', serif; }
                                .live-preview h4 { color: #fff; font-size: 18px; margin-bottom: 15px; font-family: 'Inter', sans-serif; text-transform: uppercase; letter-spacing: 1px; font-weight: 500;}
                                .live-preview p, .live-preview span { font-size: 13px; color: #ccc; line-height: 1.6; }
                                .live-preview .social-icons i { margin-right: 10px; font-size: 16px; color: #fff; }
                                .live-preview .mock-links a { display: block; margin-bottom: 8px; font-size: 13px; color: #ccc; text-decoration: none; }
                                .link-table th, .link-table td { vertical-align: middle; }
                            </style>

                            <div class="content-area footer-admin mt-3">
                                <h3 class="mb-4"><i class="bi bi-paint-bucket me-2"></i>Thiết kế Footer <span class="text-muted small">(thay đổi sẽ hiển thị bên phải ngay lập tức)</span></h3>

                                <div class="row">
                                    <!-- FORM CHỈNH SỬA -->
                                    <div class="col-lg-7">
                                        <form action="../save_footer_settings.php" method="POST" id="footerForm">
                                            <?php include '../../config/csrf.php';
                                            echo csrf_field(); ?>

                                            <!-- Thương hiệu -->
                                            <div class="card p-4">
                                                <h5 class="section-title mb-3"><i class="bi bi-shop"></i> Thương hiệu</h5>
                                                <input type="text" name="restaurant_name" class="form-control mb-2" placeholder="Tên nhà hàng" value="<?= htmlspecialchars($ft['restaurant_name'] ?? '') ?>">
                                                <textarea name="footer_description" class="form-control mb-2" rows="2" placeholder="Mô tả ngắn"><?= htmlspecialchars($ft['footer_description'] ?? '') ?></textarea>
                                            </div>

                                            <!-- Màu sắc & Liên hệ -->
                                            <div class="card p-4">
                                                <h5 class="section-title mb-3"><i class="bi bi-palette"></i> Giao diện & Liên hệ</h5>
                                                <div class="row">
                                                    <div class="col-md-4"><label class="small">Màu nền</label><input type="color" name="footer_bg_color" class="form-control form-control-color" value="<?= $ft['footer_bg_color'] ?? '#0c0b09' ?>" oninput="updatePreview()"></div>
                                                    <div class="col-md-4"><label class="small">Màu chữ</label><input type="color" name="footer_text_color" class="form-control form-control-color" value="<?= $ft['footer_text_color'] ?? '#ffffff' ?>" oninput="updatePreview()"></div>
                                                    <div class="col-md-4"><label class="small">Địa chỉ</label><input type="text" name="address" class="form-control" value="<?= htmlspecialchars($ft['address'] ?? '') ?>"></div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-md-4"><label class="small">Hotline</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($ft['phone'] ?? '') ?>"></div>
                                                    <div class="col-md-4"><label class="small">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($ft['email'] ?? '') ?>"></div>
                                                    <div class="col-md-4"><label class="small">Giờ mở cửa</label><input type="text" name="opening_hours" class="form-control" value="<?= htmlspecialchars($ft['opening_hours'] ?? '') ?>"></div>
                                                </div>
                                                <div class="mt-3"><label class="small">Copyright</label><input type="text" name="copyright_text" class="form-control" value="<?= htmlspecialchars($ft['copyright_text'] ?? '') ?>"></div>
                                            </div>

                                            <!-- Mạng xã hội -->
                                            <div class="card p-4">
                                                <h5 class="section-title mb-3"><i class="bi bi-share"></i> Mạng xã hội</h5>
                                                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="show_social" value="1" <?= ($ft['show_social'] ?? '0') == '1' ? 'checked' : '' ?>><label class="form-check-label">Hiển thị liên kết mạng xã hội</label></div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted"><i class="bi bi-facebook text-primary"></i> Facebook</label>
                                                        <input type="text" name="facebook_url" class="form-control" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($ft['facebook_url'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted"><i class="bi bi-instagram text-danger"></i> Instagram</label>
                                                        <input type="text" name="instagram_url" class="form-control" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($ft['instagram_url'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted"><i class="bi bi-tiktok text-dark"></i> TikTok</label>
                                                        <input type="text" name="tiktok_url" class="form-control" placeholder="https://tiktok.com/..." value="<?= htmlspecialchars($ft['tiktok_url'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted"><i class="bi bi-chat-dots text-info"></i> Zalo</label>
                                                        <input type="text" name="zalo_url" class="form-control" placeholder="https://zalo.me/..." value="<?= htmlspecialchars($ft['zalo_url'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Bản đồ & Newsletter -->
                                            <div class="card p-4">
                                                <h5 class="section-title mb-3"><i class="bi bi-map"></i> Bản đồ & Newsletter</h5>
                                                <textarea name="google_map_iframe" class="form-control mb-2" rows="3" placeholder="Mã nhúng Google Maps"><?= htmlspecialchars($ft['google_map_iframe'] ?? '') ?></textarea>
                                                <div class="d-flex gap-4">
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="show_map" value="1" <?= ($ft['show_map'] ?? '0') == '1' ? 'checked' : '' ?>><label>Hiện bản đồ</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="show_newsletter" value="1" <?= ($ft['show_newsletter'] ?? '0') == '1' ? 'checked' : '' ?>><label>Hiện newsletter</label></div>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-save-footer w-100 mt-3"><i class="bi bi-save me-2"></i>Lưu cấu hình</button>
                                        </form>

                                        <!-- Quản lý Links -->
                                        <div class="card p-4 mt-4">
                                            <h5 class="section-title mb-3">Liên kết nhanh</h5>
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-4"><input type="text" id="linkTitle" class="form-control" placeholder="Tên"></div>
                                                <div class="col-md-4"><input type="text" id="linkUrl" class="form-control" placeholder="URL"></div>
                                                <div class="col-md-2"><input type="number" id="linkPriority" class="form-control" value="0"></div>
                                                <div class="col-md-2"><button class="btn btn-outline-gold w-100" onclick="addLink()">Thêm</button></div>
                                            </div>
                                            <table class="table link-table">
                                                <thead><tr><th>Tiêu đề</th><th>URL</th><th>Thứ tự</th><th>Thao tác</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($links as $l): ?>
                                                        <tr data-id="<?= $l['id'] ?>">
                                                            <td><input type="text" class="form-control form-control-sm" id="title_<?= $l['id'] ?>" value="<?= htmlspecialchars($l['title']) ?>"></td>
                                                            <td><input type="text" class="form-control form-control-sm" id="url_<?= $l['id'] ?>" value="<?= htmlspecialchars($l['url']) ?>"></td>
                                                            <td><input type="number" class="form-control form-control-sm" value="<?= $l['priority'] ?>" onchange="updatePriority(<?= $l['id'] ?>, this.value)" style="width:80px"></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary" onclick="editLink(<?= $l['id'] ?>)"><i class="bi bi-save"></i></button>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteLink(<?= $l['id'] ?>)"><i class="bi bi-trash"></i></button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- LIVE PREVIEW -->
                                    <div class="col-lg-5">
                                        <div class="live-preview-wrapper">
                                            <h5 class="mb-3">Xem trước Footer</h5>
                                            <div class="live-preview" id="previewFooter">
                                                <div class="row">
                                                    <div class="col-sm-4 mb-3">
                                                        <h4 id="previewName" style="color: #fff; font-family: 'Cormorant Garamond', serif; text-transform: none; font-size: 20px;"><?= htmlspecialchars($ft['restaurant_name'] ?? 'Restaurantly') ?></h4>
                                                        <p id="previewDesc" class="mb-3"><?= nl2br(htmlspecialchars($ft['footer_description'] ?? '')) ?></p>
                                                        
                                                        <div class="mb-1"><i class="bi bi-geo-alt" style="width: 15px;"></i> <span id="previewAddr"><?= htmlspecialchars($ft['address'] ?? '') ?></span></div>
                                                        <div class="mb-1"><i class="bi bi-telephone" style="width: 15px;"></i> <span id="previewPhone"><?= htmlspecialchars($ft['phone'] ?? '') ?></span></div>
                                                        <div class="mb-3"><i class="bi bi-envelope" style="width: 15px;"></i> <span id="previewEmail"><?= htmlspecialchars($ft['email'] ?? '') ?></span></div>
                                                        
                                                        <div class="social-icons" id="previewSocials" style="<?= ($ft['show_social'] ?? '0') == '1' ? '' : 'display:none;' ?>">
                                                            <i class="bi bi-facebook" id="icon-fb" style="<?= empty($ft['facebook_url']) ? 'display:none;' : '' ?>"></i>
                                                            <i class="bi bi-instagram" id="icon-ig" style="<?= empty($ft['instagram_url']) ? 'display:none;' : '' ?>"></i>
                                                            <i class="bi bi-tiktok" id="icon-tt" style="<?= empty($ft['tiktok_url']) ? 'display:none;' : '' ?>"></i>
                                                            <i class="bi bi-chat-dots" id="icon-zl" style="<?= empty($ft['zalo_url']) ? 'display:none;' : '' ?>"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 mb-3">
                                                        <h4>LIÊN KẾT NHANH</h4>
                                                        <div class="mock-links mb-2" id="previewLinks">
                                                            <?php foreach ($links as $l): ?>
                                                                <a href="#"><?= htmlspecialchars($l['title']) ?></a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 mb-3">
                                                        <h4>GIỜ MỞ CỬA</h4>
                                                        <div id="previewHours" style="white-space: pre-line; font-size: 13px; color: #ccc; line-height: 2; margin-bottom: 20px;">
                                                            <?= htmlspecialchars($ft['opening_hours'] ?? "Thứ 2: Nghỉ định kỳ
Thứ 3 - Thứ 6: 10:00 AM - 10:00 PM") ?>
                                                        </div>
                                                        
                                                        <div id="previewMapBox" style="<?= ($ft['show_map'] ?? '0') == '1' ? '' : 'display:none;' ?> position: relative; width: 100%; height: 80px; background: rgba(255,255,255,0.1); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                            <span style="font-size: 11px; color: #fff;">🗺️ Bản đồ Google</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div style="border-top: 1px solid rgba(255,255,255,0.2); margin-top: 20px; padding-top: 15px; text-align: center;">
                                                    <span id="previewCopyright" style="font-size: 12px; color: #aaa;"><?= htmlspecialchars($ft['copyright_text'] ?? '© 2026 Restaurantly.') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> <!-- End Tab Content -->
                </div>
            </div>
        </div>
    </div>
</div>


<script>
// Javascript để đồng bộ tabs (Tránh reload về tab mặc định)
document.addEventListener("DOMContentLoaded", function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('#settings-tabs button'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function (event) {
            tabTrigger.show();
            // Ẩn form chính nếu tab là gallery
            let activeTabId = event.target.getAttribute('data-bs-target');
            let formWrapper = document.getElementById('settings-forms-wrapper');
            if(activeTabId === '#gallery') {
                formWrapper.classList.remove('show', 'active');
            } else {
                formWrapper.classList.add('show', 'active');
            }
        })
    })
});
// Javascript cho Footer
function updatePreview() {
    const bg = $('[name="footer_bg_color"]').val();
    const textColor = $('[name="footer_text_color"]').val();
    $('#previewFooter').css({ backgroundColor: bg, color: textColor });
    $('#previewFooter').find('h4, i, p, span').css('color', textColor);
    
    $('#previewName').text($('[name="restaurant_name"]').val() || 'Restaurantly');
    $('#previewDesc').html(($('[name="footer_description"]').val() || '').replace(/\n/g,'<br>'));
    $('#previewAddr').text($('[name="address"]').val() || '');
    $('#previewPhone').text($('[name="phone"]').val() || '');
    $('#previewEmail').text($('[name="email"]').val() || '');
    $('#previewHours').text($('[name="opening_hours"]').val() || '');
    $('#previewCopyright').text($('[name="copyright_text"]').val() || '');
    
    if($('[name="show_social"]').is(':checked')) {
        $('#previewSocials').show();
        $('[name="facebook_url"]').val() ? $('#icon-fb').show() : $('#icon-fb').hide();
        $('[name="instagram_url"]').val() ? $('#icon-ig').show() : $('#icon-ig').hide();
        $('[name="tiktok_url"]').val() ? $('#icon-tt').show() : $('#icon-tt').hide();
        $('[name="zalo_url"]').val() ? $('#icon-zl').show() : $('#icon-zl').hide();
    } else {
        $('#previewSocials').hide();
    }
    
    if($('[name="show_map"]').is(':checked')) {
        $('#previewMapBox').show();
    } else {
        $('#previewMapBox').hide();
    }
}

$('input, textarea').on('input change', updatePreview);
// Gọi thủ công updatePreview khi tab footer được hiển thị
document.getElementById('footer-tab').addEventListener('shown.bs.tab', function (e) {
  updatePreview();
});

function addLink() {
    const title = $('#linkTitle').val().trim();
    const url = $('#linkUrl').val().trim();
    const p = $('#linkPriority').val() || 0;
    if (!title || !url) return alert('Nhập đầy đủ');
    $.post('../ajax/ajax_footer_links.php', { action: 'add', title, url, priority: p }, function(r) {
        if (r.status === 'success') location.reload();
        else alert(r.message);
    }, 'json');
}
function deleteLink(id) { if (confirm('Xóa?')) $.post('../ajax/ajax_footer_links.php', { action: 'delete', id }, () => location.reload()); }
function updatePriority(id, v) { $.post('../ajax/ajax_footer_links.php', { action: 'update', id, priority: v }); }
function editLink(id) {
    const title = $('#title_'+id).val().trim();
    const url = $('#url_'+id).val().trim();
    if (!title || !url) return alert('Nhập đầy đủ Tiêu đề và URL');
    $.post('../ajax/ajax_footer_links.php', { action: 'edit', id: id, title: title, url: url }, function(r) {
        if (r.status === 'success') {
            alert('Cập nhật thành công!');
            location.reload();
        } else {
            alert(r.message);
        }
    }, 'json');
}
</script>
</body>
</html>
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

// --- XỬ LÝ VIDEO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_video'])) {
    // ✅ Lấy video không hardcode id=1 — lấy bản ghi đầu tiên, tạo nếu chưa có
    $video = $db->query("SELECT * FROM videos ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$video) {
        $db->exec("INSERT INTO videos (video_type, video_url, file_path) VALUES ('youtube', '', '')");
        $video = $db->query("SELECT * FROM videos ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }
    $video_id_db = (int)$video['id'];

    $type      = $_POST['video_type'] ?? 'youtube';
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $video_url = '';
    $file_path = $video['file_path'] ?? ''; 

    if (in_array($type, ['youtube', 'vimeo', 'muse'])) {
        $url_input = trim($_POST['video_url'] ?? '');
        
        if ($type === 'youtube') {
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url_input, $match)) {
                $video_url = $match[1];
            } else {
                $video_url = preg_replace('/[^a-zA-Z0-9_\-]/', '', $url_input);
            }
        } elseif ($type === 'vimeo') {
            if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url_input, $match)) {
                $video_url = $match[1];
            } else {
                $video_url = preg_replace('/[^0-9]/', '', $url_input);
            }
        } elseif ($type === 'muse') {
            if (preg_match('/(?:muse\.ai\/v\/|muse\.ai\/embed\/)([a-zA-Z0-9]+)/', $url_input, $match)) {
                $video_url = $match[1];
            } else {
                $video_url = preg_replace('/[^a-zA-Z0-9]/', '', $url_input);
            }
        }
    } else {
        // Xử lý upload file local
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext  = ['mp4', 'webm', 'mov'];
            $allowed_mime = ['video/mp4', 'video/webm', 'video/quicktime'];
            $max_size     = 200 * 1024 * 1024;

            $ext      = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $tmp_path = $_FILES['video_file']['tmp_name'];
            $size     = $_FILES['video_file']['size'];

            $upload_error = '';
            if (!in_array($ext, $allowed_ext)) {
                $upload_error = 'Chỉ chấp nhận: MP4, WEBM, MOV.';
            } elseif ($size > $max_size) {
                $upload_error = 'File quá lớn. Tối đa 200MB.';
            }

            if ($upload_error) {
                $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => $upload_error];
                header('Location: settings.php?tab=video'); exit;
            }

            $upload_dir = __DIR__ . '/../../uploads/videos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_file_name = bin2hex(random_bytes(10)) . '.' . $ext;
            $target        = $upload_dir . $new_file_name;

            if (move_uploaded_file($tmp_path, $target)) {
                if (!empty($video['file_path'])) {
                    $old = __DIR__ . '/../../' . ltrim($video['file_path'], '/');
                    if (file_exists($old)) @unlink($old);
                }
                $file_path = 'uploads/videos/' . $new_file_name;
            }
        }
    }

    // Lưu vào DB
    $db->prepare("UPDATE videos SET video_type = ?, video_url = ?, file_path = ?, title = ?, description = ? WHERE id = ?")
       ->execute([$type, $video_url, $file_path, $title, $desc, $video_id_db]);

    $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => 'Cập nhật cấu hình Video thành công!'];
    header('Location: settings.php?tab=video'); exit;
}

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
        'google_map_iframe'    => $_POST['google_map_iframe'] ?? '',
        'promo_popup_enabled'  => $_POST['promo_popup_enabled'] ?? '0',
        'promo_popup_content'  => $_POST['promo_popup_content'] ?? '',
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

    // Validate upload promo popup
    if (!empty($_FILES['promo_popup_file']['name'])) {
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $max_size     = 5 * 1024 * 1024; // 5MB

        $ext      = strtolower(pathinfo($_FILES['promo_popup_file']['name'], PATHINFO_EXTENSION));
        $tmp_path = $_FILES['promo_popup_file']['tmp_name'];
        $size     = $_FILES['promo_popup_file']['size'];

        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'Popup chỉ chấp nhận: JPG, PNG, WEBP, PDF.'];
            header('Location: settings.php'); exit;
        }
        if ($size > $max_size) {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'File Popup quá lớn. Tối đa 5MB.'];
            header('Location: settings.php'); exit;
        }
        
        $mime = mime_content_type($tmp_path);
        if (!in_array($mime, $allowed_mime)) {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'File Popup không hợp lệ.'];
            header('Location: settings.php'); exit;
        }

        // Xóa file cũ trước khi lưu file mới
        $stmtOld = $db->prepare("SELECT key_value FROM settings WHERE key_name = 'promo_popup_file'");
        $stmtOld->execute();
        $oldFile = $stmtOld->fetchColumn();
        if ($oldFile && file_exists(__DIR__ . '/../../public/' . $oldFile)) {
            unlink(__DIR__ . '/../../public/' . $oldFile);
        }

        $file_name   = 'promo_popup_' . time() . '.' . $ext;
        $target_file = __DIR__ . '/../../public/assets/img/' . $file_name;
        
        if (move_uploaded_file($tmp_path, $target_file)) {
            $stmt->execute(['promo_popup_file', 'assets/img/' . $file_name]);
            $type = ($ext === 'pdf') ? 'pdf' : 'image';
            $stmt->execute(['promo_popup_type', $type]);
        } else {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'Không thể tải file Popup lên.'];
            header('Location: settings.php'); exit;
        }
    }

    
    // Validate upload footer images
    for ($i = 1; $i <= 3; $i++) {
        $input_name = 'footer_img_' . $i;
        if (!empty($_FILES[$input_name]['name'])) {
            $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
            $max_size     = 2 * 1024 * 1024; // 2MB
            $ext      = strtolower(pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION));
            $tmp_path = $_FILES[$input_name]['tmp_name'];
            $size     = $_FILES[$input_name]['size'];

            if (in_array($ext, $allowed_ext) && $size <= $max_size) {
                $file_name   = 'footer_img_' . $i . '_' . time() . '.' . $ext;
                $target_file = __DIR__ . '/../../public/assets/img/' . $file_name;
                
                if (move_uploaded_file($tmp_path, $target_file)) {
                    $stmt->execute([$input_name, $file_name]);
                }
            }
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

// Lấy thông tin video
$video_db = $db->query("SELECT * FROM videos ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

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

<style>
    /* body { background-color: #f8f9fa; }  -- Removed to not override layout body */
    .settings-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    .section-title { color: #cda45e; font-weight: 700; border-bottom: 2px solid #cda45e; padding-bottom: 10px; margin-bottom: 25px; }
    .btn-save { background: #cda45e; color: white; padding: 12px 30px; border-radius: 50px; font-weight: 600; transition: 0.3s; }
    .btn-save:hover { background: #b89252; transform: translateY(-2px); color: white; }
    
    /* FIX: Bootstrap 5 hides .tab-pane only if direct child of .tab-content. Since ours is in a form, we need to manually hide them */
    .tab-content form .tab-pane { display: none; }
    .tab-content form .tab-pane.active { display: block; }
</style>

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
                            <button class="nav-link <?= $active_tab === 'video' ? 'active' : '' ?>" id="video-tab" data-bs-toggle="pill" data-bs-target="#video" type="button" role="tab" aria-controls="video" aria-selected="<?= $active_tab === 'video' ? 'true' : 'false' ?>">
                                <i class="bi bi-camera-video me-1"></i> Video
                            </button>
                        </li>
                        
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="settings-tabContent">

                        <!-- FORM BAO TRÙM CHO CÁC TAB CÀI ĐẶT CHUNG -->
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
                                        <div class="col-md-12 mb-4">
                                            <div class="p-3 rounded" style="background: #fdf5e6; border-left: 4px solid #cda45e;">
                                                <h5 class="fw-bold mb-3"><i class="bi bi-window-stack me-2"></i>Cửa Sổ Nổi (Welcome Popup)</h5>
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label fw-bold">Trạng thái Popup</label>
                                                        <div class="form-check form-switch">
                                                            <input type="hidden" name="promo_popup_enabled" value="0">
                                                            <input class="form-check-input" type="checkbox" role="switch" name="promo_popup_enabled" value="1"
                                                                <?= ($settings['promo_popup_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                                            <label class="form-check-label">Bật hiển thị ở Trang Chủ</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-8 mb-3">
                                                        <label class="form-label fw-bold">Tệp đính kèm (Ảnh hoặc PDF)</label>
                                                        <input type="file" name="promo_popup_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                                        <?php if (!empty($settings['promo_popup_file'])): ?>
                                                            <div class="mt-3 p-3 bg-white border rounded text-center">
                                                                <p class="mb-2 text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Đã lưu file thành công!</p>
                                                                <?php if (($settings['promo_popup_type'] ?? 'image') === 'pdf'): ?>
                                                                    <div class="text-primary mb-2"><i class="bi bi-file-earmark-pdf fs-1"></i></div>
                                                                    <a href="../../public/<?= htmlspecialchars($settings['promo_popup_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Xem PDF Hiện Tại</a>
                                                                <?php else: ?>
                                                                    <img src="../../public/<?= htmlspecialchars($settings['promo_popup_file']) ?>" alt="Preview" style="max-height: 150px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                                                    <div class="mt-2">
                                                                        <a href="../../public/<?= htmlspecialchars($settings['promo_popup_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i> Mở ảnh lớn</a>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="mt-2 text-muted small">
                                                                    (Lưu ý: Ô chọn file ở trên luôn hiển thị "No file chosen" sau khi tải lại trang, nhưng file của bạn đã được lưu an toàn ở đây)
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <label class="form-label fw-bold">Nội dung đi kèm (Tùy chọn)</label>
                                                        <textarea name="promo_popup_content" class="form-control" rows="3" placeholder="Nhập nội dung ngắn gọn..."><?= htmlspecialchars($settings['promo_popup_content'] ?? '') ?></textarea>
                                                        <small class="text-muted">Nội dung này sẽ hiển thị bên dưới hình ảnh trong Popup.</small>
                                                    </div>
                                                </div>
                                            </div>
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

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Bản đồ Google Maps (Footer)</label>
                                        <textarea name="google_map_iframe" class="form-control" rows="3" placeholder="Nhập mã nhúng (iframe) Google Maps vào đây..."><?= htmlspecialchars($settings['google_map_iframe'] ?? '') ?></textarea>
                                        <small class="text-muted">Nhập đoạn mã iframe lấy từ Google Maps để hiển thị ở chân trang.</small>
                                    </div>
                                
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">3 Ảnh Dưới Footer</label>
                                        <div class="row g-3">
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <?php $key = 'footer_img_' . $i; ?>
                                                <div class="col-md-4 text-center">
                                                    <div class="mb-2">
                                                        <?php if (!empty($settings[$key])): ?>
                                                            <img src="../../public/assets/img/<?= htmlspecialchars($settings[$key]) ?>" class="img-thumbnail" style="height: 120px; object-fit: cover; width: 100%;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center border" style="height: 120px; width: 100%;">
                                                                <span class="text-muted small">Chưa có ảnh</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" name="<?= $key ?>" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                                                </div>
                                            <?php endfor; ?>
                                        </div>
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

                        </div>

                        <!-- TAB VIDEO -->
                        <div class="tab-pane fade <?= $active_tab === 'video' ? 'show active' : '' ?> pt-2" id="video" role="tabpanel" aria-labelledby="video-tab" tabindex="0">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="card shadow-sm border-0 bg-light mb-4 h-100">
                                        <div class="card-body">
                                            <h6 class="mb-3 text-uppercase fw-bold text-muted">Cấu hình Video Trải nghiệm</h6>
                                            <form action="settings.php" method="POST" enctype="multipart/form-data">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Nguồn Video</label>
                                                    <select name="video_type" id="video_type" class="form-select form-select-sm" onchange="toggleVideoType()">
                                                        <option value="youtube" <?= ($video_db['video_type'] ?? '') === 'youtube' ? 'selected' : '' ?>>YouTube</option>
                                                        <option value="vimeo" <?= ($video_db['video_type'] ?? '') === 'vimeo' ? 'selected' : '' ?>>Vimeo</option>
                                                        <option value="muse" <?= ($video_db['video_type'] ?? '') === 'muse' ? 'selected' : '' ?>>Muse.ai</option>
                                                        <option value="local" <?= ($video_db['video_type'] ?? '') === 'local' ? 'selected' : '' ?>>Tải lên (MP4/WEBM)</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3" id="url_input_group">
                                                    <label class="form-label small fw-bold">Đường dẫn Video (URL)</label>
                                                    <input type="text" name="video_url" class="form-control form-control-sm" value="<?= htmlspecialchars($video_db['video_url'] ?? '') ?>" placeholder="Nhập link Youtube, Vimeo...">
                                                </div>

                                                <div class="mb-3" id="file_input_group" style="display:none;">
                                                    <label class="form-label small fw-bold">Tải video lên</label>
                                                    <input type="file" name="video_file" class="form-control form-control-sm" accept="video/mp4,video/webm,video/quicktime">
                                                    <?php if(!empty($video_db['file_path'])): ?>
                                                        <small class="text-success d-block mt-1">Đã có video: <?= htmlspecialchars(basename($video_db['file_path'])) ?></small>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Tiêu đề (Tùy chọn)</label>
                                                    <input type="text" name="title" class="form-control form-control-sm" value="<?= htmlspecialchars($video_db['title'] ?? '') ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Mô tả (Tùy chọn)</label>
                                                    <textarea name="description" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($video_db['description'] ?? '') ?></textarea>
                                                </div>

                                                <button type="submit" name="btn_update_video" class="btn btn-save mt-3">
                                                    <i class="bi bi-check-circle me-2"></i> LƯU VIDEO
                                                </button>
                                            </form>
                                            
                                            <script>
                                                function toggleVideoType() {
                                                    let type = document.getElementById('video_type').value;
                                                    if(type === 'local') {
                                                        document.getElementById('url_input_group').style.display = 'none';
                                                        document.getElementById('file_input_group').style.display = 'block';
                                                    } else {
                                                        document.getElementById('url_input_group').style.display = 'block';
                                                        document.getElementById('file_input_group').style.display = 'none';
                                                    }
                                                }
                                                document.addEventListener("DOMContentLoaded", function() { toggleVideoType(); });
                                            </script>
                                        </div>
                                    </div>
                                </div>

                                <!-- CỘT 5: XEM TRƯỚC (LIVE PREVIEW) -->
                                <div class="col-md-5">
                                    <div class="card shadow-sm border-0 bg-light mb-4 h-100">
                                        <div class="card-body">
                                            <h6 class="mb-3 text-uppercase fw-bold text-muted">Xem trước</h6>
                                            <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm" style="background: #000;">
                                                <?php if(($video_db['video_type'] ?? '') === 'youtube' && !empty($video_db['video_url'])): ?>
                                                    <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($video_db['video_url']) ?>?autoplay=1&mute=1&loop=1&playlist=<?= htmlspecialchars($video_db['video_url']) ?>" allowfullscreen style="border:0;"></iframe>
                                                <?php elseif(($video_db['video_type'] ?? '') === 'vimeo' && !empty($video_db['video_url'])): ?>
                                                    <iframe src="https://player.vimeo.com/video/<?= htmlspecialchars($video_db['video_url']) ?>?autoplay=1&loop=1&muted=1" allowfullscreen style="border:0;"></iframe>
                                                <?php elseif(($video_db['video_type'] ?? '') === 'muse' && !empty($video_db['video_url'])): ?>
                                                    <iframe src="https://muse.ai/embed/<?= htmlspecialchars($video_db['video_url']) ?>?autoplay=1&loop=1&muted=1" allowfullscreen style="border:0;"></iframe>
                                                <?php elseif(($video_db['video_type'] ?? '') === 'local' && !empty($video_db['file_path'])): ?>
                                                    <video controls autoplay loop muted style="width: 100%; height: 100%; object-fit: cover;">
                                                        <source src="../../<?= htmlspecialchars($video_db['file_path']) ?>" type="video/mp4">
                                                        Trình duyệt của bạn không hỗ trợ video.
                                                    </video>
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center text-muted" style="height: 100%; background: #eee;">
                                                        <span>Chưa có video</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if(!empty($video_db['title'])): ?>
                                                <h5 class="mt-3" style="color: #cda45e;"><?= htmlspecialchars($video_db['title']) ?></h5>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($video_db['description'])): ?>
                                                <p class="text-muted small mt-2"><?= nl2br(htmlspecialchars($video_db['description'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- End Tab Content -->
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
            // Ẩn form chính nếu tab là gallery hoặc video
            let activeTabId = event.target.getAttribute('data-bs-target');
            let formWrapper = document.getElementById('settings-forms-wrapper');
            if(activeTabId === '#gallery' || activeTabId === '#video') {
                formWrapper.classList.remove('show', 'active');
            } else {
                formWrapper.classList.add('show', 'active');
            }
        })
    })
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
<?php include '../../public/admin_layout_footer.php'; ?>
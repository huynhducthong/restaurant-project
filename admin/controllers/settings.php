<?php
// ✅ FIX 1: Xác thực session admin
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

include '../../public/admin_layout_header.php';
require_once '../../config/database.php';
$db = (new Database())->getConnection();

// Flash message từ session
$flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'hotline'         => $_POST['hotline']         ?? '',
        'address'         => $_POST['address']         ?? '',
        'restaurant_name' => $_POST['restaurant_name'] ?? '',
        'name_position'   => $_POST['name_position']   ?? 'center',
        'open_time'       => $_POST['open_time']       ?? '09:00 AM - 11:00 PM',
        'open_days'       => $_POST['open_days']       ?? 'Thứ 2 - Chủ Nhật',
    ];

    // ✅ FIX 5: Prepare 1 lần ngoài loop, execute nhiều lần
    $stmt = $db->prepare(
        "INSERT INTO settings (key_name, key_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)"
    );
    foreach ($fields as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    // ✅ FIX 2: Validate upload logo - ext + MIME + size
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
        $target_file = '../../public/assets/img/' . $file_name;
        if (move_uploaded_file($tmp_path, $target_file)) {
            $stmt->execute(['logo_url', '../../public/assets/img/' . $file_name]);
            // ✅ FIX 6: Version chống cache browser
            $stmt->execute(['logo_ver', (string)time()]);
        } else {
            $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục.'];
            header('Location: settings.php'); exit;
        }
    }

    // ✅ FIX 4: Flash session + redirect HTTP thay vì alert() JS
    $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => 'Cập nhật cấu hình thành công!'];
    header('Location: settings.php'); exit;
}

// Lấy dữ liệu hiện tại
$stmt = $db->prepare("SELECT * FROM settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['key_value'];
}

// ✅ FIX 3: Escape logo_url + ?v= chống cache browser
$logo_src = '';
if (!empty($settings['logo_url'])) {
    $ver      = $settings['logo_ver'] ?? '1';
    $logo_src = '../../' . htmlspecialchars($settings['logo_url']) . '?v=' . $ver;
}
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
<?php
include '../public/admin_layout_header.php';
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// 1. XỬ LÝ CẬP NHẬT DỮ LIỆU
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $fields = [
        'hotline' => $_POST['hotline'] ?? '',
        'address' => $_POST['address'] ?? '',
        'restaurant_name' => $_POST['restaurant_name'] ?? '',
        'name_position' => $_POST['name_position'] ?? 'center',
        'open_time' => $_POST['open_time'] ?? '09:00 AM - 11:00 PM', // Thêm thời gian
        'open_days' => $_POST['open_days'] ?? 'Thứ 2 - Chủ Nhật'       // Thêm ngày làm việc
    ];

    foreach ($fields as $key => $val) {
        $stmt = $db->prepare("INSERT INTO settings (key_name, key_value) VALUES (:key, :val) 
                             ON DUPLICATE KEY UPDATE key_value = :val");
        $stmt->execute([':key' => $key, ':val' => $val]);
    }

    // Xử lý Upload Logo (Giữ nguyên logic của bạn)
    if (!empty($_FILES['logo']['name'])) {
        $target_dir = "../public/assets/img/";
        $file_name = "logo.png"; 
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo_url = "public/assets/img/" . $file_name;
            $stmt = $db->prepare("INSERT INTO settings (key_name, key_value) VALUES ('logo_url', :val) 
                                 ON DUPLICATE KEY UPDATE key_value = :val");
            $stmt->execute([':val' => $logo_url]);
        }
    }
    echo "<script>alert('Cập nhật cấu hình thành công!'); window.location.href='settings.php';</script>";
}

// 2. LẤY DỮ LIỆU HIỆN TẠI ĐỂ ĐỔ VÀO FORM
$stmt = $db->prepare("SELECT * FROM settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['key_value'];
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
                                <option value="left" <?= ($settings['name_position'] ?? '') == 'left' ? 'selected' : '' ?>>Trái</option>
                                <option value="center" <?= ($settings['name_position'] ?? '') == 'center' ? 'selected' : '' ?>>Giữa</option>
                                <option value="right" <?= ($settings['name_position'] ?? '') == 'right' ? 'selected' : '' ?>>Phải</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Giờ mở cửa</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                <input type="text" name="open_time" class="form-control" 
                                       value="<?= htmlspecialchars($settings['open_time'] ?? '09:00 AM - 11:00 PM') ?>" placeholder="Ví dụ: 09:00 AM - 10:00 PM">
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
                            <?php if(!empty($settings['logo_url'])): ?>
                                <img src="../<?= $settings['logo_url'] ?>" style="max-height: 60px; background: #333; padding: 5px;">
                            <?php endif; ?>
                        </div>
                        <input type="file" name="logo" class="form-control">
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

</body>
</html>
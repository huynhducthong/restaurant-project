<?php
include '../public/admin_layout_header.php';
require_once '../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $ft[$row['setting_key']] = $row['setting_value']; }
$links = $db->query("SELECT * FROM footer_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary"><i class="fas fa-palette me-2"></i>Nâng cấp Footer</h3>
        <a href="../index.php" target="_blank" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-eye me-2"></i>Xem trang chủ</a>
    </div>

    <form action="save_footer_settings.php" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-7 mb-4">
                <div class="card shadow-sm border-0 p-4 h-100" style="border-radius: 15px;">
                    <h5 class="fw-bold text-warning border-bottom pb-2 mb-3">Thương hiệu & Media</h5>
                    <div class="mb-3">
                        <label class="small fw-bold">Tên nhà hàng</label>
                        <input type="text" name="restaurant_name" class="form-control" value="<?= htmlspecialchars($ft['restaurant_name']) ?>">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small fw-bold">Logo Footer</label>
                            <input type="file" name="footer_logo" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Ảnh nền (Parallax)</label>
                            <input type="file" name="footer_bg_image" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Mô tả Footer</label>
                        <textarea name="footer_description" class="form-control" rows="3"><?= $ft['footer_description'] ?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-0 p-4 h-100" style="border-radius: 15px;">
                    <h5 class="fw-bold text-info border-bottom pb-2 mb-3">Màu sắc & Liên hệ</h5>
                    <div class="row mb-3">
                        <div class="col-6"><label class="small fw-bold">Màu nền</label><input type="color" name="footer_bg_color" class="form-control form-control-color w-100" value="<?= $ft['footer_bg_color'] ?>"></div>
                        <div class="col-6"><label class="small fw-bold">Màu chữ</label><input type="color" name="footer_text_color" class="form-control form-control-color w-100" value="<?= $ft['footer_text_color'] ?>"></div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Địa chỉ</label>
                        <input type="text" name="address" class="form-control" value="<?= $ft['address'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Hotline</label>
                        <input type="text" name="phone" class="form-control" value="<?= $ft['phone'] ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 15px;">
            <h5 class="fw-bold text-danger border-bottom pb-2 mb-3">Tính năng & Bản đồ</h5>
            <div class="row">
                <div class="col-md-8">
                    <label class="small fw-bold">Mã nhúng Iframe Google Maps</label>
                    <textarea name="google_map_iframe" class="form-control" rows="4"><?= $ft['google_map_iframe'] ?></textarea>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="show_map" value="1" <?= $ft['show_map']=='1'?'checked':'' ?>><label class="fw-bold">Hiện Bản đồ</label></div>
                    <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="show_newsletter" value="1" <?= $ft['show_newsletter']=='1'?'checked':'' ?>><label class="fw-bold">Hiện Newsletter</label></div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-warning px-5 py-2 fw-bold text-white shadow-sm" style="background: #cda45e; border: none; border-radius: 30px;">
            <i class="fas fa-save me-2"></i>LƯU CẤU HÌNH FOOTER
        </button>
    </form>
</div>
<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}
include '../public/admin_layout_header.php';
require_once '../config/database.php';
$db = (new Database())->getConnection();

// Lấy dữ liệu hiện tại
$stmt = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ft[$row['setting_key']] = $row['setting_value'];
}
$links = $db->query("SELECT * FROM footer_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --gold: #c49b5c;
        --dark: #1a1814;
        --card-bg: #ffffff;
        --text: #333;
        --border: #e0d6c8;
    }
    .footer-admin { font-family: 'Poppins', sans-serif; }
    .footer-admin .card { border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 24px; }
    .footer-admin .card-header { background: #fff; border-bottom: 1px solid var(--border); font-weight: 600; color: var(--dark); }
    .footer-admin .section-title { font-size: 1.1rem; font-weight: 700; color: var(--gold); }
    .footer-admin .btn-save { background: var(--gold); color: #fff; border: none; padding: 12px 30px; border-radius: 30px; font-weight: 600; }
    .footer-admin .btn-save:hover { background: #b07d45; }
    .footer-admin .form-control:focus, .footer-admin .form-select:focus { border-color: var(--gold); box-shadow: 0 0 0 0.2rem rgba(196,155,92,0.25); }
    .footer-admin .btn-outline-gold { border: 1px solid var(--gold); color: var(--gold); }
    .footer-admin .btn-outline-gold:hover { background: var(--gold); color: #fff; }
    .live-preview-wrapper { position: sticky; top: 20px; }
    .live-preview { background: #0c0b09; color: #fff; border-radius: 12px; padding: 30px; min-height: 300px; }
    .live-preview h4 { color: var(--gold); }
    .live-preview .mock-links a { display: inline-block; margin: 4px; padding: 4px 12px; border: 1px solid var(--gold); border-radius: 20px; font-size: 12px; color: #fff; text-decoration: none; }
    .link-table th, .link-table td { vertical-align: middle; }
</style>

<div class="content-area footer-admin">
    <h3 class="mb-4"><i class="fas fa-paint-brush me-2"></i>Thiết kế Footer <span class="text-muted small">(thay đổi sẽ hiển thị bên phải ngay lập tức)</span></h3>

    <div class="row">
        <!-- FORM CHỈNH SỬA -->
        <div class="col-lg-7">
            <form action="save_footer_settings.php" method="POST" enctype="multipart/form-data" id="footerForm">
                <?php include '../config/csrf.php';
                echo csrf_field(); ?>

                <!-- Thương hiệu -->
                <div class="card p-4">
                    <h5 class="section-title mb-3"><i class="fas fa-store-alt"></i> Thương hiệu</h5>
                    <input type="text" name="restaurant_name" class="form-control mb-2" placeholder="Tên nhà hàng" value="<?= htmlspecialchars($ft['restaurant_name'] ?? '') ?>">
                    <textarea name="footer_description" class="form-control mb-2" rows="2" placeholder="Mô tả ngắn"><?= htmlspecialchars($ft['footer_description'] ?? '') ?></textarea>
                    <div class="row">
                        <div class="col-md-6"><input type="file" name="footer_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg"></div>
                        <div class="col-md-6"><input type="file" name="footer_bg_image" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
                    </div>
                </div>

                <!-- Màu sắc & Liên hệ -->
                <div class="card p-4">
                    <h5 class="section-title mb-3"><i class="fas fa-palette"></i> Giao diện & Liên hệ</h5>
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
                    <h5 class="section-title mb-3"><i class="fas fa-share-alt"></i> Mạng xã hội</h5>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_social" value="1" <?= ($ft['show_social'] ?? '0') == '1' ? 'checked' : '' ?>><label>Hiển thị</label></div>
                    <div class="row">
                        <div class="col-md-3"><input type="url" name="facebook_url" class="form-control" placeholder="Facebook" value="<?= htmlspecialchars($ft['facebook_url'] ?? '') ?>"></div>
                        <div class="col-md-3"><input type="url" name="instagram_url" class="form-control" placeholder="Instagram" value="<?= htmlspecialchars($ft['instagram_url'] ?? '') ?>"></div>
                        <div class="col-md-3"><input type="url" name="tiktok_url" class="form-control" placeholder="TikTok" value="<?= htmlspecialchars($ft['tiktok_url'] ?? '') ?>"></div>
                        <div class="col-md-3"><input type="url" name="zalo_url" class="form-control" placeholder="Zalo" value="<?= htmlspecialchars($ft['zalo_url'] ?? '') ?>"></div>
                    </div>
                </div>

                <!-- Bản đồ & Newsletter -->
                <div class="card p-4">
                    <h5 class="section-title mb-3"><i class="fas fa-map-marked-alt"></i> Bản đồ & Newsletter</h5>
                    <textarea name="google_map_iframe" class="form-control mb-2" rows="3" placeholder="Mã nhúng Google Maps"><?= htmlspecialchars($ft['google_map_iframe'] ?? '') ?></textarea>
                    <div class="d-flex gap-4">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="show_map" value="1" <?= ($ft['show_map'] ?? '0') == '1' ? 'checked' : '' ?>><label>Hiện bản đồ</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="show_newsletter" value="1" <?= ($ft['show_newsletter'] ?? '0') == '1' ? 'checked' : '' ?>><label>Hiện newsletter</label></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-save w-100 mt-3"><i class="fas fa-save me-2"></i>Lưu cấu hình</button>
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
                    <thead><tr><th>Tiêu đề</th><th>URL</th><th>Thứ tự</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($links as $l): ?>
                            <tr data-id="<?= $l['id'] ?>">
                                <td><?= htmlspecialchars($l['title']) ?></td>
                                <td><?= htmlspecialchars($l['url']) ?></td>
                                <td><input type="number" class="form-control form-control-sm" value="<?= $l['priority'] ?>" onchange="updatePriority(<?= $l['id'] ?>, this.value)" style="width:80px"></td>
                                <td><button class="btn btn-sm btn-outline-danger" onclick="deleteLink(<?= $l['id'] ?>)"><i class="fas fa-trash"></i></button></td>
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
                    <div id="previewLogo" class="mb-2"></div>
                    <h4 id="previewName"><?= htmlspecialchars($ft['restaurant_name'] ?? 'Restaurantly') ?></h4>
                    <p id="previewDesc"><?= nl2br(htmlspecialchars($ft['footer_description'] ?? '')) ?></p>
                    <div class="mock-links mb-2" id="previewLinks"></div>
                    <div class="mb-2"><i class="fas fa-map-marker-alt"></i> <span id="previewAddr"><?= htmlspecialchars($ft['address'] ?? '') ?></span></div>
                    <div><i class="fas fa-phone"></i> <span id="previewPhone"><?= htmlspecialchars($ft['phone'] ?? '') ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Cập nhật preview mỗi khi người dùng gõ
function updatePreview() {
    const bg = $('[name="footer_bg_color"]').val();
    const textColor = $('[name="footer_text_color"]').val();
    $('#previewFooter').css({ backgroundColor: bg, color: textColor });
    $('#previewName').text($('[name="restaurant_name"]').val() || 'Restaurantly');
    $('#previewDesc').html(($('[name="footer_description"]').val() || '').replace(/\n/g,'<br>'));
    $('#previewAddr').text($('[name="address"]').val() || '');
    $('#previewPhone').text($('[name="phone"]').val() || '');
}

$('input, textarea').on('input', updatePreview);
updatePreview();

// AJAX quản lý links (giữ nguyên)
function addLink() {
    const title = $('#linkTitle').val().trim();
    const url = $('#linkUrl').val().trim();
    const p = $('#linkPriority').val() || 0;
    if (!title || !url) return alert('Nhập đầy đủ');
    $.post('ajax_footer_links.php', { action: 'add', title, url, priority: p }, function(r) {
        if (r.status === 'success') location.reload();
        else alert(r.message);
    }, 'json');
}
function deleteLink(id) { if (confirm('Xóa?')) $.post('ajax_footer_links.php', { action: 'delete', id }, () => location.reload()); }
function updatePriority(id, v) { $.post('ajax_footer_links.php', { action: 'update', id, priority: v }); }
</script>
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
    .live-preview { background: #0c0b09; color: #fff; border-radius: 12px; padding: 30px; min-height: 300px; font-family: 'Cormorant Garamond', serif; }
    .live-preview h4 { color: #fff; font-size: 18px; margin-bottom: 15px; font-family: 'Inter', sans-serif; text-transform: uppercase; letter-spacing: 1px; font-weight: 500;}
    .live-preview p, .live-preview span { font-size: 13px; color: #ccc; line-height: 1.6; }
    .live-preview .social-icons i { margin-right: 10px; font-size: 16px; color: #fff; }
    .live-preview .mock-links a { display: block; margin-bottom: 8px; font-size: 13px; color: #ccc; text-decoration: none; }
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
                    <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="show_social" value="1" <?= ($ft['show_social'] ?? '0') == '1' ? 'checked' : '' ?>><label class="form-check-label">Hiển thị liên kết mạng xã hội</label></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                            <input type="text" name="facebook_url" class="form-control" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($ft['facebook_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted"><i class="fab fa-instagram text-danger"></i> Instagram</label>
                            <input type="text" name="instagram_url" class="form-control" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($ft['instagram_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted"><i class="fab fa-tiktok text-dark"></i> TikTok</label>
                            <input type="text" name="tiktok_url" class="form-control" placeholder="https://tiktok.com/..." value="<?= htmlspecialchars($ft['tiktok_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted"><i class="fas fa-comment-dots text-info"></i> Zalo</label>
                            <input type="text" name="zalo_url" class="form-control" placeholder="https://zalo.me/..." value="<?= htmlspecialchars($ft['zalo_url'] ?? '') ?>">
                        </div>
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
                    <thead><tr><th>Tiêu đề</th><th>URL</th><th>Thứ tự</th><th>Thao tác</th></tr></thead>
                    <tbody>
                        <?php foreach ($links as $l): ?>
                            <tr data-id="<?= $l['id'] ?>">
                                <td><input type="text" class="form-control form-control-sm" id="title_<?= $l['id'] ?>" value="<?= htmlspecialchars($l['title']) ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" id="url_<?= $l['id'] ?>" value="<?= htmlspecialchars($l['url']) ?>"></td>
                                <td><input type="number" class="form-control form-control-sm" value="<?= $l['priority'] ?>" onchange="updatePriority(<?= $l['id'] ?>, this.value)" style="width:80px"></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editLink(<?= $l['id'] ?>)"><i class="fas fa-save"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLink(<?= $l['id'] ?>)"><i class="fas fa-trash"></i></button>
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
                            
                            <div class="mb-1"><i class="fas fa-map-marker-alt" style="width: 15px;"></i> <span id="previewAddr"><?= htmlspecialchars($ft['address'] ?? '') ?></span></div>
                            <div class="mb-1"><i class="fas fa-phone" style="width: 15px;"></i> <span id="previewPhone"><?= htmlspecialchars($ft['phone'] ?? '') ?></span></div>
                            <div class="mb-3"><i class="fas fa-envelope" style="width: 15px;"></i> <span id="previewEmail"><?= htmlspecialchars($ft['email'] ?? '') ?></span></div>
                            
                            <div class="social-icons" id="previewSocials" style="<?= ($ft['show_social'] ?? '0') == '1' ? '' : 'display:none;' ?>">
                                <i class="fab fa-facebook-f" id="icon-fb" style="<?= empty($ft['facebook_url']) ? 'display:none;' : '' ?>"></i>
                                <i class="fab fa-instagram" id="icon-ig" style="<?= empty($ft['instagram_url']) ? 'display:none;' : '' ?>"></i>
                                <i class="fab fa-tiktok" id="icon-tt" style="<?= empty($ft['tiktok_url']) ? 'display:none;' : '' ?>"></i>
                                <i class="fas fa-comment-dots" id="icon-zl" style="<?= empty($ft['zalo_url']) ? 'display:none;' : '' ?>"></i>
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
                                <?= htmlspecialchars($ft['opening_hours'] ?? "Thứ 2: Nghỉ định kỳ\nThứ 3 - Thứ 6: 10:00 AM - 10:00 PM") ?>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Cập nhật preview mỗi khi người dùng gõ
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
    
    // Xử lý ẩn/hiện social icons
    if($('[name="show_social"]').is(':checked')) {
        $('#previewSocials').show();
        $('[name="facebook_url"]').val() ? $('#icon-fb').show() : $('#icon-fb').hide();
        $('[name="instagram_url"]').val() ? $('#icon-ig').show() : $('#icon-ig').hide();
        $('[name="tiktok_url"]').val() ? $('#icon-tt').show() : $('#icon-tt').hide();
        $('[name="zalo_url"]').val() ? $('#icon-zl').show() : $('#icon-zl').hide();
    } else {
        $('#previewSocials').hide();
    }
    
    // Xử lý ẩn/hiện map
    if($('[name="show_map"]').is(':checked')) {
        $('#previewMapBox').show();
    } else {
        $('#previewMapBox').hide();
    }
}

$('input, textarea').on('input change', updatePreview);
updatePreview();

// AJAX quản lý links (giữ nguyên)
function addLink() {
    const title = $('#linkTitle').val().trim();
    const url = $('#linkUrl').val().trim();
    const p = $('#linkPriority').val() || 0;
    if (!title || !url) return alert('Nhập đầy đủ');
    $.post('ajax/ajax_footer_links.php', { action: 'add', title, url, priority: p }, function(r) {
        if (r.status === 'success') location.reload();
        else alert(r.message);
    }, 'json');
}
function deleteLink(id) { if (confirm('Xóa?')) $.post('ajax/ajax_footer_links.php', { action: 'delete', id }, () => location.reload()); }
function updatePriority(id, v) { $.post('ajax/ajax_footer_links.php', { action: 'update', id, priority: v }); }
function editLink(id) {
    const title = $('#title_'+id).val().trim();
    const url = $('#url_'+id).val().trim();
    if (!title || !url) return alert('Nhập đầy đủ Tiêu đề và URL');
    $.post('ajax/ajax_footer_links.php', { action: 'edit', id: id, title: title, url: url }, function(r) {
        if (r.status === 'success') {
            alert('Cập nhật thành công!');
            location.reload();
        } else {
            alert(r.message);
        }
    }, 'json');
}
</script>
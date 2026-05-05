<?php
// =============================================================
// File: admin/controllers/BannerController.php
// Thay thế: manage_banners.php
// MIGRATION: ALTER TABLE banners ADD COLUMN IF NOT EXISTS
//            is_active TINYINT(1) NOT NULL DEFAULT 1;
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// ============================================================
// AJAX: Toggle bật / tắt banner
// ============================================================
if (isset($_POST['toggle_active'])) {
    header('Content-Type: application/json');
    $bid = (int)$_POST['banner_id'];
    $db->prepare("UPDATE banners SET is_active = NOT is_active WHERE id = ?")->execute([$bid]);
    $s = $db->prepare("SELECT is_active FROM banners WHERE id = ?");
    $s->execute([$bid]);
    echo json_encode(['status' => 'success', 'is_active' => (int)$s->fetchColumn()]);
    exit;
}

// ============================================================
// AJAX: Cập nhật thứ tự sau khi drag & drop
// ============================================================
if (isset($_POST['update_order'])) {
    header('Content-Type: application/json');
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) { echo json_encode(['status'=>'error']); exit; }
    $stmt = $db->prepare("UPDATE banners SET display_order = ? WHERE id = ?");
    foreach ($ids as $order => $id) {
        $stmt->execute([$order + 1, (int)$id]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// ============================================================
// XÓA banner — POST thay vì GET
// ============================================================
$flash = $_SESSION['banner_flash'] ?? null;
unset($_SESSION['banner_flash']);

if (isset($_POST['delete_banner_id'])) {
    $del_id = (int)$_POST['delete_banner_id'];
    $img_s  = $db->prepare("SELECT image_url FROM banners WHERE id = ?");
    $img_s->execute([$del_id]);
    $old_img = $img_s->fetchColumn();

    try {
        $db->prepare("DELETE FROM banners WHERE id = ?")->execute([$del_id]);
        if ($old_img) {
            $path = __DIR__ . '/../../public/assets/img/hero/' . $old_img;
            if (file_exists($path)) @unlink($path);
        }
        $_SESSION['banner_flash'] = ['type' => 'success', 'msg' => 'Đã xóa banner thành công!'];
    } catch (Exception $e) {
        $_SESSION['banner_flash'] = ['type' => 'error', 'msg' => 'Lỗi khi xóa: ' . $e->getMessage()];
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ============================================================
// LẤY BANNER ĐỂ SỬA
// ============================================================
$edit_data = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM banners WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $edit_data = $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ============================================================
// THÊM / CẬP NHẬT BANNER
// ============================================================
if (isset($_POST['btn_save'])) {
    $id = !empty($_POST['banner_id']) ? (int)$_POST['banner_id'] : null;

    $data = [
        'title'           => trim($_POST['title']           ?? ''),
        'description'     => trim($_POST['description']     ?? ''),
        'display_order'   => max(1, (int)($_POST['display_order'] ?? 1)),
        'text_align'      => in_array($_POST['text_align'] ?? '', ['left','center','right'])
                             ? $_POST['text_align'] : 'center',
        'text_color'      => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['text_color'] ?? '')
                             ? $_POST['text_color'] : '#ffffff',
        'font_family'     => trim($_POST['font_family']     ?? "'Poppins', sans-serif"),
        'font_style'      => in_array($_POST['font_style'] ?? '', ['normal','bold','italic'])
                             ? $_POST['font_style'] : 'normal',
        'title_font_size' => (int)($_POST['title_font_size'] ?? 48),
        'desc_color'      => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['desc_color'] ?? '')
                             ? $_POST['desc_color'] : '#eeeeee',
        'desc_font_family'=> trim($_POST['desc_font_family']  ?? "'Poppins', sans-serif"),
        'desc_font_style' => in_array($_POST['desc_font_style'] ?? '', ['normal','bold','italic'])
                             ? $_POST['desc_font_style'] : 'normal',
        'desc_font_size'  => (int)($_POST['desc_font_size'] ?? 24),
    ];

    $image_name = $_POST['old_image'] ?? '';

    // ✅ Validate upload ảnh
    if (!empty($_FILES['banner_image']['name'])) {
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
        $ext      = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        $tmp_path = $_FILES['banner_image']['tmp_name'];
        $size     = $_FILES['banner_image']['size'];
        $upload_err = '';

        if (!in_array($ext, $allowed_ext)) {
            $upload_err = 'Ảnh chỉ chấp nhận: JPG, PNG, WEBP.';
        } elseif ($size > 5 * 1024 * 1024) {
            $upload_err = 'Ảnh quá lớn. Tối đa 5MB.';
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp_path);
            finfo_close($finfo);
            if (!in_array($mime, $allowed_mime)) $upload_err = 'File không phải ảnh hợp lệ.';
        }

        if ($upload_err) {
            $_SESSION['banner_flash'] = ['type' => 'error', 'msg' => $upload_err];
            header('Location: ' . $_SERVER['PHP_SELF'] . ($id ? "?edit=$id" : '')); exit;
        }

        $target_dir  = __DIR__ . '/../../public/assets/img/hero/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

        $new_name = bin2hex(random_bytes(10)) . '.' . $ext;
        if (move_uploaded_file($tmp_path, $target_dir . $new_name)) {
            if ($image_name && file_exists($target_dir . $image_name)) @unlink($target_dir . $image_name);
            $image_name = $new_name;
        } else {
            $_SESSION['banner_flash'] = ['type' => 'error', 'msg' => 'Không thể upload ảnh.'];
            header('Location: ' . $_SERVER['PHP_SELF'] . ($id ? "?edit=$id" : '')); exit;
        }
    }

    $data['image_url'] = $image_name;

    try {
        if ($id) {
            $db->prepare(
                "UPDATE banners SET image_url=?,title=?,description=?,display_order=?,text_align=?,
                 text_color=?,font_family=?,font_style=?,title_font_size=?,
                 desc_color=?,desc_font_family=?,desc_font_style=?,desc_font_size=? WHERE id=?"
            )->execute([
                $data['image_url'], $data['title'], $data['description'],
                $data['display_order'], $data['text_align'],
                $data['text_color'], $data['font_family'], $data['font_style'], $data['title_font_size'],
                $data['desc_color'], $data['desc_font_family'], $data['desc_font_style'], $data['desc_font_size'],
                $id
            ]);
            $_SESSION['banner_flash'] = ['type' => 'success', 'msg' => 'Cập nhật banner thành công!'];
        } else {
            $db->prepare(
                "INSERT INTO banners (image_url,title,description,display_order,text_align,
                 text_color,font_family,font_style,title_font_size,
                 desc_color,desc_font_family,desc_font_style,desc_font_size,is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)"
            )->execute([
                $data['image_url'], $data['title'], $data['description'],
                $data['display_order'], $data['text_align'],
                $data['text_color'], $data['font_family'], $data['font_style'], $data['title_font_size'],
                $data['desc_color'], $data['desc_font_family'], $data['desc_font_style'], $data['desc_font_size'],
            ]);
            $_SESSION['banner_flash'] = ['type' => 'success', 'msg' => 'Thêm banner mới thành công!'];
        }
        $edit_data = null;
    } catch (Exception $e) {
        $_SESSION['banner_flash'] = ['type' => 'error', 'msg' => htmlspecialchars($e->getMessage())];
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ============================================================
// DỮ LIỆU HIỂN THỊ
// ============================================================
$banners = $db->query(
    "SELECT * FROM banners ORDER BY display_order ASC, id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$fonts = [
    "'Poppins', sans-serif"       => "Poppins (Hiện đại)",
    "'Playfair Display', serif"   => "Playfair (Cổ điển sang trọng)",
    "'Montserrat', sans-serif"    => "Montserrat (Trẻ trung)",
    "'Lora', serif"               => "Lora (Thanh lịch)",
    "'Dancing Script', cursive"   => "Dancing Script (Bay bướm)",
    "'Pacifico', cursive"         => "Pacifico (Nghệ thuật)",
    "'Great Vibes', cursive"      => "Great Vibes (Thư pháp sang)",
    "'Oswald', sans-serif"        => "Oswald (Gọn gàng, mạnh mẽ)",
    "'Roboto', sans-serif"        => "Roboto (Cơ bản, rõ nét)",
    "'Caveat', cursive"           => "Caveat (Viết tay mộc mạc)",
];
$font_sizes = [12,14,16,18,20,22,24,26,28,36,48,56,64,72];

include '../../public/admin_layout_header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Dancing+Script:wght@400;700&family=Great+Vibes&family=Lora:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;700&family=Oswald:wght@400;700&family=Pacifico&family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">

<style>
.banner-row { cursor: grab; transition: background .15s, opacity .2s; }
.banner-row:active { cursor: grabbing; }
.banner-row.sortable-ghost { opacity: .4; background: #f0f6ff !important; }
.banner-row.sortable-drag  { box-shadow: 0 4px 20px rgba(0,0,0,.15); }
.drag-handle { cursor: grab; color: #bbb; font-size: 18px; padding: 0 8px; }
.drag-handle:hover { color: #666; }
.banner-thumb { width: 110px; height: 65px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
.inactive-banner { opacity: .45; }
</style>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">Quản lý Banner (Slide Show)</h2>
        <a href="BannerController.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-plus me-1"></i>Thêm mới
        </a>
    </div>

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm mb-4 border-0">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ===== CỘT TRÁI: Form ===== -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="m-0 text-primary fw-bold">
                        <?= $edit_data ? 'Cập nhật Banner #' . $edit_data['id'] : 'Thêm Banner Mới' ?>
                    </h5>
                    <?php if ($edit_data): ?>
                    <a href="BannerController.php" class="btn btn-sm btn-outline-secondary">✕ Hủy sửa</a>
                    <?php endif; ?>
                </div>
                <div class="card-body" style="max-height:800px;overflow-y:auto">
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST"
                          enctype="multipart/form-data">
                        <input type="hidden" name="banner_id"  value="<?= (int)($edit_data['id'] ?? 0) ?: '' ?>">
                        <input type="hidden" name="old_image"  value="<?= htmlspecialchars($edit_data['image_url'] ?? '') ?>">

                        <!-- Ảnh & thứ tự -->
                        <h6 class="fw-bold text-success border-bottom pb-1 mb-3">
                            <i class="bi bi-image me-1"></i>Hình ảnh & Vị trí
                        </h6>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label small">
                                    Ảnh Banner <?= $edit_data ? '<span class="text-muted">(bỏ qua nếu giữ ảnh cũ)</span>' : '<span class="text-danger">*</span>' ?>
                                </label>
                                <input type="file" name="banner_image" id="input-img"
                                       class="form-control form-control-sm"
                                       accept=".jpg,.jpeg,.png,.webp"
                                       <?= $edit_data ? '' : 'required' ?>>
                                <div class="form-text">JPG, PNG, WEBP — tối đa 5MB</div>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Thứ tự</label>
                                <input type="number" name="display_order"
                                       class="form-control form-control-sm"
                                       value="<?= (int)($edit_data['display_order'] ?? count($banners) + 1) ?>"
                                       min="1">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Căn lề khối chữ</label>
                                <select name="text_align" id="input-align" class="form-select form-select-sm">
                                    <?php foreach (['center'=>'Giữa','left'=>'Trái','right'=>'Phải'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($edit_data['text_align'] ?? 'center') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Tiêu đề -->
                        <h6 class="fw-bold text-success border-bottom pb-1 mb-3 mt-3">
                            <i class="bi bi-type-h1 me-1"></i>Cài đặt Tiêu đề
                        </h6>
                        <div class="mb-2">
                            <input type="text" name="title" id="input-title"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>"
                                   placeholder="Nhập tiêu đề...">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-3">
                                <label class="form-label small">Màu chữ</label>
                                <input type="color" name="text_color" id="input-color"
                                       class="form-control form-control-color form-control-sm w-100"
                                       value="<?= htmlspecialchars($edit_data['text_color'] ?? '#ffffff') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Font</label>
                                <select name="font_family" id="input-font" class="form-select form-select-sm">
                                    <?php foreach ($fonts as $v => $l): ?>
                                    <option value="<?= htmlspecialchars($v) ?>"
                                        <?= ($edit_data['font_family'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-2">
                                <label class="form-label small">Cỡ</label>
                                <select name="title_font_size" id="input-title-size" class="form-select form-select-sm">
                                    <?php foreach ($font_sizes as $sz): ?>
                                    <option value="<?= $sz ?>" <?= ($edit_data['title_font_size'] ?? 48) == $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small">Kiểu</label>
                                <select name="font_style" id="input-style" class="form-select form-select-sm">
                                    <?php foreach (['normal'=>'Thường','bold'=>'Đậm','italic'=>'Nghiêng'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($edit_data['font_style'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Mô tả -->
                        <h6 class="fw-bold text-success border-bottom pb-1 mb-3 mt-3">
                            <i class="bi bi-card-text me-1"></i>Cài đặt Mô tả
                        </h6>
                        <div class="mb-2">
                            <textarea name="description" id="input-desc"
                                      class="form-control form-control-sm" rows="2"
                                      placeholder="Nhập mô tả..."><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
                        </div>
                        <div class="row g-2 mb-4">
                            <div class="col-3">
                                <label class="form-label small">Màu chữ</label>
                                <input type="color" name="desc_color" id="input-desc-color"
                                       class="form-control form-control-color form-control-sm w-100"
                                       value="<?= htmlspecialchars($edit_data['desc_color'] ?? '#eeeeee') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Font</label>
                                <select name="desc_font_family" id="input-desc-font" class="form-select form-select-sm">
                                    <?php foreach ($fonts as $v => $l): ?>
                                    <option value="<?= htmlspecialchars($v) ?>"
                                        <?= ($edit_data['desc_font_family'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-2">
                                <label class="form-label small">Cỡ</label>
                                <select name="desc_font_size" id="input-desc-size" class="form-select form-select-sm">
                                    <?php foreach ($font_sizes as $sz): ?>
                                    <option value="<?= $sz ?>" <?= ($edit_data['desc_font_size'] ?? 24) == $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small">Kiểu</label>
                                <select name="desc_font_style" id="input-desc-style" class="form-select form-select-sm">
                                    <?php foreach (['normal'=>'Thường','bold'=>'Đậm','italic'=>'Nghiêng'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($edit_data['desc_font_style'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="btn_save" class="btn btn-primary px-4 fw-bold shadow-sm">
                                <i class="bi bi-save me-1"></i>
                                <?= $edit_data ? 'Lưu Cập Nhật' : 'Thêm Banner Mới' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== CỘT PHẢI: Preview ===== -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0" style="position:sticky;top:20px">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="m-0 text-danger fw-bold">
                        <i class="bi bi-display me-1"></i>Xem trước trực tiếp
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="preview-box"
                         class="d-flex flex-column justify-content-center"
                         style="width:100%;height:420px;background-color:#222;
                                background-image:url('../../public/assets/img/hero/<?= htmlspecialchars($edit_data['image_url'] ?? '') ?>');
                                background-size:cover;background-position:center;
                                position:relative;transition:all .3s">
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,.5)"></div>
                        <div class="container position-relative px-4" id="preview-content"
                             style="text-align:<?= htmlspecialchars($edit_data['text_align'] ?? 'center') ?>;z-index:2">
                            <h2 id="preview-title" style="
                                color:<?= htmlspecialchars($edit_data['text_color'] ?? '#ffffff') ?>;
                                font-family:<?= htmlspecialchars($edit_data['font_family'] ?? "'Poppins', sans-serif") ?>;
                                font-size:<?= (int)($edit_data['title_font_size'] ?? 48) ?>px;
                                font-weight:<?= ($edit_data['font_style'] ?? '') === 'bold' ? 'bold' : 'normal' ?>;
                                font-style:<?= ($edit_data['font_style'] ?? '') === 'italic' ? 'italic' : 'normal' ?>;
                                text-shadow:2px 2px 5px rgba(0,0,0,.7);margin-bottom:15px;line-height:1.2">
                                <?= htmlspecialchars($edit_data['title'] ?? 'Tiêu đề Banner') ?: 'Tiêu đề Banner' ?>
                            </h2>
                            <p id="preview-desc" style="
                                color:<?= htmlspecialchars($edit_data['desc_color'] ?? '#eeeeee') ?>;
                                font-family:<?= htmlspecialchars($edit_data['desc_font_family'] ?? "'Poppins', sans-serif") ?>;
                                font-size:<?= (int)($edit_data['desc_font_size'] ?? 24) ?>px;
                                font-weight:<?= ($edit_data['desc_font_style'] ?? '') === 'bold' ? 'bold' : 'normal' ?>;
                                font-style:<?= ($edit_data['desc_font_style'] ?? '') === 'italic' ? 'italic' : 'normal' ?>;
                                text-shadow:1px 1px 4px rgba(0,0,0,.7)">
                                <?= htmlspecialchars($edit_data['description'] ?? 'Mô tả ngắn gọn sẽ hiển thị ở đây.') ?: 'Mô tả ngắn gọn sẽ hiển thị ở đây.' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DANH SÁCH BANNER — Drag & Drop ===== -->
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <h5 class="m-0 fw-bold">Danh sách Banner</h5>
            <span class="small text-muted">
                <i class="fas fa-grip-vertical me-1"></i>Kéo thả để sắp xếp thứ tự
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40"></th>
                        <th width="50" class="text-center">STT</th>
                        <th width="130">Ảnh</th>
                        <th>Thông tin hiển thị</th>
                        <th width="110" class="text-center">Bật/Tắt</th>
                        <th width="120" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="sortable-banners">
                    <?php if (empty($banners)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có banner nào.</td></tr>
                    <?php else: ?>
                    <?php foreach ($banners as $b):
                        $active = (int)($b['is_active'] ?? 1);
                    ?>
                    <tr class="banner-row <?= !$active ? 'inactive-banner' : '' ?>"
                        data-id="<?= $b['id'] ?>">
                        <td><span class="drag-handle">⠿</span></td>
                        <td class="text-center fw-bold text-primary" id="order-<?= $b['id'] ?>">
                            <?= (int)$b['display_order'] ?>
                        </td>
                        <td>
                            <img src="../../public/assets/img/hero/<?= htmlspecialchars($b['image_url']) ?>"
                                 class="banner-thumb"
                                 onerror="this.src='../../public/assets/img/hero/default.jpg'"
                                 alt="<?= htmlspecialchars($b['title']) ?>">
                        </td>
                        <td>
                            <div style="font-family:<?= htmlspecialchars($b['font_family']) ?>;
                                        font-size:1.1rem;color:#333;
                                        font-weight:<?= $b['font_style']==='bold' ? 'bold' : 'normal' ?>;
                                        font-style:<?= $b['font_style']==='italic' ? 'italic' : 'normal' ?>">
                                <?= htmlspecialchars($b['title']) ?>
                                <span class="badge bg-secondary ms-1" style="font-size:10px">
                                    <?= (int)$b['title_font_size'] ?>px
                                </span>
                            </div>
                            <div class="mt-1 text-secondary fst-italic small"
                                 style="font-family:<?= htmlspecialchars($b['desc_font_family']) ?>">
                                <?= htmlspecialchars($b['description']) ?>
                                <span class="badge bg-light text-dark ms-1" style="font-size:10px">
                                    <?= (int)$b['desc_font_size'] ?>px
                                </span>
                            </div>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm <?= $active ? 'btn-success' : 'btn-secondary' ?> btn-toggle-banner"
                                    data-id="<?= $b['id'] ?>"
                                    title="<?= $active ? 'Đang hiển thị — click để ẩn' : 'Đang ẩn — click để hiện' ?>">
                                <i class="fas <?= $active ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                <?= $active ? 'Hiện' : 'Ẩn' ?>
                            </button>
                        </td>
                        <td class="text-center">
                            <a href="BannerController.php?edit=<?= $b['id'] ?>"
                               class="btn btn-outline-primary btn-sm mb-1 w-100">
                                <i class="bi bi-pencil-square"></i> Sửa
                            </a>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm w-100 btn-delete-banner"
                                    data-id="<?= $b['id'] ?>"
                                    data-title="<?= htmlspecialchars($b['title']) ?>">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="modalDeleteBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px">
            <div class="modal-header bg-danger text-white border-0" style="border-radius:14px 14px 0 0">
                <h6 class="modal-title fw-bold"><i class="fas fa-trash me-2"></i>Xác nhận xóa</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="mb-1 text-muted small">Xóa banner:</p>
                <p class="fw-bold" id="delete-banner-title"></p>
                <small class="text-danger">Ảnh sẽ bị xóa vĩnh viễn.</small>
            </div>
            <div class="modal-footer border-0 pb-4 px-4 gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="flex-fill">
                    <input type="hidden" name="delete_banner_id" id="delete-banner-id">
                    <button type="submit" class="btn btn-danger w-100 fw-bold">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// ---- Preview live ----
function val(id) { var el = document.getElementById(id); return el ? el.value : ''; }

var pTitle   = document.getElementById('preview-title');
var pDesc    = document.getElementById('preview-desc');
var pContent = document.getElementById('preview-content');
var pBox     = document.getElementById('preview-box');

function updatePreview() {
    pTitle.innerText = val('input-title') || 'Tiêu đề Banner';
    pDesc.innerText  = val('input-desc')  || 'Mô tả ngắn gọn sẽ hiển thị ở đây.';
    pContent.style.textAlign  = val('input-align');
    pTitle.style.color        = val('input-color');
    pTitle.style.fontFamily   = val('input-font');
    pTitle.style.fontSize     = val('input-title-size') + 'px';
    pTitle.style.fontWeight   = val('input-style') === 'bold'   ? 'bold'   : 'normal';
    pTitle.style.fontStyle    = val('input-style') === 'italic' ? 'italic' : 'normal';
    pDesc.style.color         = val('input-desc-color');
    pDesc.style.fontFamily    = val('input-desc-font');
    pDesc.style.fontSize      = val('input-desc-size') + 'px';
    pDesc.style.fontWeight    = val('input-desc-style') === 'bold'   ? 'bold'   : 'normal';
    pDesc.style.fontStyle     = val('input-desc-style') === 'italic' ? 'italic' : 'normal';
}

['input-title','input-desc','input-align','input-color','input-font',
 'input-style','input-title-size','input-desc-color','input-desc-font',
 'input-desc-style','input-desc-size'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) { el.addEventListener('input', updatePreview); el.addEventListener('change', updatePreview); }
});

// Preview ảnh
var imgInput = document.getElementById('input-img');
if (imgInput) {
    imgInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                pBox.style.backgroundImage = "url('" + e.target.result + "')";
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// ---- Modal xóa ----
document.querySelectorAll('.btn-delete-banner').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('delete-banner-title').textContent = this.dataset.title;
        document.getElementById('delete-banner-id').value          = this.dataset.id;
        new bootstrap.Modal(document.getElementById('modalDeleteBanner')).show();
    });
});

// ---- Toggle bật/tắt ----
document.querySelectorAll('.btn-toggle-banner').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var id  = this.dataset.id;
        var row = this.closest('tr');
        var self = this;
        fetch('<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'toggle_active=1&banner_id=' + id
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status !== 'success') return;
            var active = data.is_active;
            row.classList.toggle('inactive-banner', !active);
            self.className = 'btn btn-sm ' + (active ? 'btn-success' : 'btn-secondary') + ' btn-toggle-banner';
            self.title  = active ? 'Đang hiển thị — click để ẩn' : 'Đang ẩn — click để hiện';
            self.innerHTML = '<i class="fas ' + (active ? 'fa-eye' : 'fa-eye-slash') + '"></i> '
                           + (active ? 'Hiện' : 'Ẩn');
        });
    });
});

// ---- Drag & Drop sắp xếp (SortableJS) ----
var sortable = Sortable.create(document.getElementById('sortable-banners'), {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag',
    onEnd: function () {
        var ids = [];
        document.querySelectorAll('#sortable-banners .banner-row').forEach(function (row, i) {
            ids.push(row.dataset.id);
            var orderEl = document.getElementById('order-' + row.dataset.id);
            if (orderEl) orderEl.textContent = i + 1;
        });
        fetch('<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'update_order=1&' + ids.map(function (id, i) {
                return 'ids[' + i + ']=' + id;
            }).join('&')
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.status === 'success') {
                // Hiện toast nhỏ xác nhận
                var t = document.createElement('div');
                t.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;background:#198754;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.2)';
                t.textContent = '✓ Đã lưu thứ tự';
                document.body.appendChild(t);
                setTimeout(function () { t.remove(); }, 2000);
            }
        });
    }
});
</script>
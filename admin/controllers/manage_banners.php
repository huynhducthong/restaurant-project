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

try {
    $cols = $db->query("DESCRIBE banners")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('button_text', $cols)) { $db->exec("ALTER TABLE banners ADD COLUMN button_text VARCHAR(255) DEFAULT NULL"); }
    if (!in_array('button_link', $cols)) { $db->exec("ALTER TABLE banners ADD COLUMN button_link VARCHAR(255) DEFAULT NULL"); }
    if (!in_array('button_color', $cols)) { $db->exec("ALTER TABLE banners ADD COLUMN button_color VARCHAR(20) DEFAULT '#cda45e'"); }
    if (!in_array('start_date', $cols)) { $db->exec("ALTER TABLE banners ADD COLUMN start_date DATETIME DEFAULT NULL"); }
    if (!in_array('end_date', $cols)) { $db->exec("ALTER TABLE banners ADD COLUMN end_date DATETIME DEFAULT NULL"); }
} catch (Exception $e) {}

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
        'button_text'     => trim($_POST['button_text'] ?? ''),
        'button_link'     => trim($_POST['button_link'] ?? ''),
        'button_color'    => $_POST['button_color'] ?? '#cda45e',
        'start_date'      => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        'end_date'        => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
    ];

    if ($data['start_date'] && $data['end_date']) {
        if (strtotime($data['end_date']) <= strtotime($data['start_date'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Lỗi: Thời gian kết thúc phải diễn ra SAU thời gian bắt đầu!'];
            header('Location: ' . $_SERVER['PHP_SELF'] . ($id ? "?edit=$id" : ''));
            exit;
        }
    }

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
                 desc_color=?,desc_font_family=?,desc_font_style=?,desc_font_size=?,
                 button_text=?, button_link=?, button_color=?, start_date=?, end_date=? WHERE id=?"
            )->execute([
                $data['image_url'], $data['title'], $data['description'],
                $data['display_order'], $data['text_align'],
                $data['text_color'], $data['font_family'], $data['font_style'], $data['title_font_size'],
                $data['desc_color'], $data['desc_font_family'], $data['desc_font_style'], $data['desc_font_size'],
                $data['button_text'], $data['button_link'], $data['button_color'], $data['start_date'], $data['end_date'],
                $id
            ]);
            $_SESSION['banner_flash'] = ['type' => 'success', 'msg' => 'Cập nhật banner thành công!'];
        } else {
            $db->prepare(
                "INSERT INTO banners (image_url,title,description,display_order,text_align,
                 text_color,font_family,font_style,title_font_size,
                 desc_color,desc_font_family,desc_font_style,desc_font_size,is_active,
                 button_text, button_link, button_color, start_date, end_date)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?,?,?,?)"
            )->execute([
                $data['image_url'], $data['title'], $data['description'],
                $data['display_order'], $data['text_align'],
                $data['text_color'], $data['font_family'], $data['font_style'], $data['title_font_size'],
                $data['desc_color'], $data['desc_font_family'], $data['desc_font_style'], $data['desc_font_size'],
                $data['button_text'], $data['button_link'], $data['button_color'], $data['start_date'], $data['end_date']
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
/* Premium UI Aesthetics */
body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
.premium-card { border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 2px 8px rgba(0,0,0,0.02); background: #ffffff; }
.premium-card:hover { box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
.premium-header { border-bottom: 1px solid rgba(0,0,0,0.05); padding: 20px 24px; }
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
.form-control, .form-select { border-radius: 10px; border: 1px solid #dee2e6; padding: 0.6rem 1rem; transition: all 0.2s; background: #fdfdfd; }
.form-control:focus, .form-select:focus { border-color: #cda45e; box-shadow: 0 0 0 0.25rem rgba(205, 164, 94, 0.25); background: #fff; }
.form-control-color { padding: 0.375rem; height: 42px; cursor: pointer; }
.btn-premium { border-radius: 10px; padding: 10px 24px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s; }
.btn-primary-premium { background: linear-gradient(45deg, #cda45e, #d9b87b); border: none; color: white; }
.btn-primary-premium:hover { background: linear-gradient(45deg, #b58d4a, #cda45e); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(205, 164, 94, 0.4); }

/* Switch Toggle (iOS style) */
.switch { position: relative; display: inline-block; width: 46px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
input:checked + .slider { background-color: #28a745; }
input:focus + .slider { box-shadow: 0 0 1px #28a745; }
input:checked + .slider:before { transform: translateX(22px); }

/* Table & List */
.table-hover tbody tr:hover { background-color: rgba(205, 164, 94, 0.05); }
.banner-row { transition: all 0.3s ease; }
.banner-row.inactive-banner { opacity: 0.6; filter: grayscale(50%); }
.drag-handle { cursor: grab; color: #adb5bd; padding: 10px; transition: color 0.2s; }
.drag-handle:hover { color: #cda45e; }
.banner-thumb { width: 120px; height: 70px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h2 class="fw-bold mb-1" style="color: #2c3e50;">Quản lý Banner (Slide Show)</h2>
            <p class="text-muted mb-0">Thiết kế và cấu hình trải nghiệm hiển thị đầu tiên cho khách hàng</p>
        </div>
        <a href="manage_banners.php" class="btn btn-premium btn-primary-premium shadow-sm">
            <i class="fas fa-plus me-2"></i>Thêm Banner Mới
        </a>
    </div>

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm mb-4" style="border-radius: 12px; border: none; border-left: 5px solid <?= $flash['type'] === 'success' ? '#28a745' : '#dc3545' ?>;">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ===== CỘT TRÁI: Form (Card-based UI) ===== -->
        <div class="col-lg-7">
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="banner_id" value="<?= (int)($edit_data['id'] ?? 0) ?: '' ?>">
                <input type="hidden" name="old_image" value="<?= htmlspecialchars($edit_data['image_url'] ?? '') ?>">

                <!-- Card 1: Trạng thái sửa -->
                <?php if ($edit_data): ?>
                <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center" style="border-radius: 12px; border: none;">
                    <div><i class="fas fa-pen-fancy me-2"></i>Đang cập nhật <strong>Banner #<?= $edit_data['id'] ?></strong></div>
                    <a href="manage_banners.php" class="btn btn-sm btn-outline-info rounded-pill px-3">Hủy sửa</a>
                </div>
                <?php endif; ?>

                <!-- Card 2: Hình ảnh & Bố cục -->
                <div class="card premium-card mb-4">
                    <div class="premium-header bg-transparent d-flex align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-image me-2"></i>1. Hình ảnh & Bố cục</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label">Tải lên Ảnh Nền <?= $edit_data ? '<span class="text-muted text-lowercase fw-normal">(Bỏ qua nếu giữ nguyên)</span>' : '<span class="text-danger">*</span>' ?></label>
                                <input type="file" name="banner_image" id="input-img" class="form-control" accept=".jpg,.jpeg,.png,.webp" <?= $edit_data ? '' : 'required' ?>>
                                <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i>Định dạng JPG, PNG, WEBP. Tối đa 5MB. Kích thước khuyên dùng 1920x1080.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Thứ tự ưu tiên</label>
                                <input type="number" name="display_order" class="form-control" value="<?= (int)($edit_data['display_order'] ?? count($banners) + 1) ?>" min="1">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Căn lề Nội dung (Text Align)</label>
                                <div class="d-flex gap-3">
                                    <?php foreach (['left'=>'Trái', 'center'=>'Giữa', 'right'=>'Phải'] as $v=>$l): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="text_align" id="align_<?= $v ?>" value="<?= $v ?>" <?= ($edit_data['text_align'] ?? 'center') === $v ? 'checked' : '' ?> onchange="updatePreview()">
                                        <label class="form-check-label" for="align_<?= $v ?>"><i class="fas fa-align-<?= $v ?> me-1"></i><?= $l ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                    <input type="hidden" id="input-align" value="<?= $edit_data['text_align'] ?? 'center' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Nội dung Tiêu đề & Mô tả -->
                <div class="card premium-card mb-4">
                    <div class="premium-header bg-transparent d-flex align-items-center">
                        <h6 class="m-0 fw-bold text-success"><i class="bi bi-type me-2"></i>2. Nội dung Text</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Tiêu đề -->
                            <div class="col-12">
                                <label class="form-label text-success"><i class="fas fa-heading me-2"></i>Tiêu đề chính</label>
                                <input type="text" name="title" id="input-title" class="form-control form-control-lg mb-3" value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" placeholder="Ví dụ: Chào mừng đến với nhà hàng...">
                                <div class="row g-2">
                                    <div class="col-md-4"><select name="font_family" id="input-font" class="form-select"><?php foreach ($fonts as $v => $l): ?><option value="<?= htmlspecialchars($v) ?>" <?= ($edit_data['font_family'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-3"><select name="title_font_size" id="input-title-size" class="form-select"><?php foreach ($font_sizes as $sz): ?><option value="<?= $sz ?>" <?= ($edit_data['title_font_size'] ?? 48) == $sz ? 'selected' : '' ?>>Cỡ <?= $sz ?>px</option><?php endforeach; ?></select></div>
                                    <div class="col-md-3"><select name="font_style" id="input-style" class="form-select"><?php foreach (['normal'=>'Thường','bold'=>'Đậm','italic'=>'Nghiêng'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($edit_data['font_style'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-2"><input type="color" name="text_color" id="input-color" class="form-control form-control-color w-100" value="<?= htmlspecialchars($edit_data['text_color'] ?? '#ffffff') ?>" title="Màu Tiêu đề"></div>
                                </div>
                            </div>
                            <hr class="text-muted opacity-25 my-4">
                            <!-- Mô tả -->
                            <div class="col-12">
                                <label class="form-label text-success"><i class="fas fa-paragraph me-2"></i>Mô tả ngắn</label>
                                <textarea name="description" id="input-desc" class="form-control mb-3" rows="2" placeholder="Nhập một vài dòng giới thiệu ngắn gọn..."><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
                                <div class="row g-2">
                                    <div class="col-md-4"><select name="desc_font_family" id="input-desc-font" class="form-select"><?php foreach ($fonts as $v => $l): ?><option value="<?= htmlspecialchars($v) ?>" <?= ($edit_data['desc_font_family'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-3"><select name="desc_font_size" id="input-desc-size" class="form-select"><?php foreach ($font_sizes as $sz): ?><option value="<?= $sz ?>" <?= ($edit_data['desc_font_size'] ?? 24) == $sz ? 'selected' : '' ?>>Cỡ <?= $sz ?>px</option><?php endforeach; ?></select></div>
                                    <div class="col-md-3"><select name="desc_font_style" id="input-desc-style" class="form-select"><?php foreach (['normal'=>'Thường','bold'=>'Đậm','italic'=>'Nghiêng'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($edit_data['desc_font_style'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-2"><input type="color" name="desc_color" id="input-desc-color" class="form-control form-control-color w-100" value="<?= htmlspecialchars($edit_data['desc_color'] ?? '#eeeeee') ?>" title="Màu Mô tả"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: CTA & Schedule -->
                <div class="row g-4 mb-4">
                    <!-- Nút Hành Động -->
                    <div class="col-md-6">
                        <div class="card premium-card h-100">
                            <div class="premium-header bg-transparent d-flex align-items-center pb-2 border-0">
                                <h6 class="m-0 fw-bold text-warning-emphasis"><i class="bi bi-hand-index-thumb me-2"></i>Nút Tương Tác (CTA)</h6>
                            </div>
                            <div class="card-body px-4 pt-0">
                                <div class="mb-3">
                                    <label class="form-label small">Chữ trên nút (Bỏ trống để ẩn)</label>
                                    <input type="text" name="button_text" id="input-btn-text" class="form-control" value="<?= htmlspecialchars($edit_data['button_text'] ?? '') ?>" placeholder="VD: Đặt Bàn Ngay">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Đường dẫn đích (Link URL / #ID)</label>
                                    <input type="text" name="button_link" class="form-control" value="<?= htmlspecialchars($edit_data['button_link'] ?? '') ?>" placeholder="VD: #book-a-table">
                                </div>
                                <div>
                                    <label class="form-label small">Màu nền nút bấm</label>
                                    <input type="color" name="button_color" id="input-btn-color" class="form-control form-control-color w-100" value="<?= htmlspecialchars($edit_data['button_color'] ?? '#cda45e') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Lập Lịch -->
                    <div class="col-md-6">
                        <div class="card premium-card h-100">
                            <div class="premium-header bg-transparent d-flex align-items-center pb-2 border-0">
                                <h6 class="m-0 fw-bold text-info-emphasis"><i class="bi bi-clock-history me-2"></i>Lập Lịch Hiển Thị</h6>
                            </div>
                            <div class="card-body px-4 pt-0">
                                <div class="alert alert-secondary py-2 small mb-3 border-0"><i class="fas fa-info-circle me-1"></i>Bỏ trống nếu muốn chạy vô thời hạn.</div>
                                <div class="mb-3">
                                    <label class="form-label small text-info"><i class="fas fa-play me-1"></i>Bắt đầu từ</label>
                                    <input type="datetime-local" name="start_date" class="form-control" value="<?= !empty($edit_data['start_date']) ? date('Y-m-d\TH:i', strtotime($edit_data['start_date'])) : '' ?>">
                                </div>
                                <div>
                                    <label class="form-label small text-danger"><i class="fas fa-stop me-1"></i>Kết thúc lúc</label>
                                    <input type="datetime-local" name="end_date" class="form-control" value="<?= !empty($edit_data['end_date']) ? date('Y-m-d\TH:i', strtotime($edit_data['end_date'])) : '' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Area -->
                <div class="card premium-card mb-5 bg-white">
                    <div class="card-body p-4 text-end">
                        <button type="submit" name="btn_save" class="btn btn-premium btn-primary-premium btn-lg w-100">
                            <i class="fas fa-save me-2"></i><?= $edit_data ? 'Hoàn tất Cập nhật Banner' : 'Tạo mới Banner' ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ===== CỘT PHẢI: Sticky Live Preview ===== -->
        <div class="col-lg-5">
            <div style="position: sticky; top: 30px; z-index: 10;">
                <div class="card premium-card border-0 overflow-hidden" style="box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                    <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold"><i class="fas fa-desktop me-2"></i>Live Preview</h6>
                        <span class="badge bg-secondary rounded-pill px-3">Mô phỏng Trang chủ</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="preview-box" class="d-flex flex-column justify-content-center"
                             style="width:100%; height:450px; background-color:#111;
                                    background-image:url('../../public/assets/img/hero/<?= htmlspecialchars($edit_data['image_url'] ?? 'default.jpg') ?>');
                                    background-size:cover; background-position:center; position:relative; transition:all .4s ease;">
                            <div style="position:absolute; inset:0; background:rgba(0,0,0,.55);"></div>
                            <div class="container position-relative px-4" id="preview-content"
                                 style="text-align:<?= htmlspecialchars($edit_data['text_align'] ?? 'center') ?>; z-index:2; transition: all 0.3s ease;">
                                <h2 id="preview-title" style="
                                    color:<?= htmlspecialchars($edit_data['text_color'] ?? '#ffffff') ?>;
                                    font-family:<?= htmlspecialchars($edit_data['font_family'] ?? "'Poppins', sans-serif") ?>;
                                    font-size:<?= (int)($edit_data['title_font_size'] ?? 48) ?>px;
                                    font-weight:<?= ($edit_data['font_style'] ?? '') === 'bold' ? 'bold' : 'normal' ?>;
                                    font-style:<?= ($edit_data['font_style'] ?? '') === 'italic' ? 'italic' : 'normal' ?>;
                                    text-shadow:2px 2px 8px rgba(0,0,0,.8); margin-bottom:15px; line-height:1.2; transition: all 0.2s;">
                                    <?= htmlspecialchars($edit_data['title'] ?? 'Tiêu đề Banner') ?: 'Tiêu đề Banner' ?>
                                </h2>
                                <p id="preview-desc" style="
                                    color:<?= htmlspecialchars($edit_data['desc_color'] ?? '#eeeeee') ?>;
                                    font-family:<?= htmlspecialchars($edit_data['desc_font_family'] ?? "'Poppins', sans-serif") ?>;
                                    font-size:<?= (int)($edit_data['desc_font_size'] ?? 24) ?>px;
                                    font-weight:<?= ($edit_data['desc_font_style'] ?? '') === 'bold' ? 'bold' : 'normal' ?>;
                                    font-style:<?= ($edit_data['desc_font_style'] ?? '') === 'italic' ? 'italic' : 'normal' ?>;
                                    text-shadow:1px 1px 5px rgba(0,0,0,.8); transition: all 0.2s;">
                                    <?= htmlspecialchars($edit_data['description'] ?? 'Mô tả ngắn gọn sẽ hiển thị ở đây.') ?: 'Mô tả ngắn gọn sẽ hiển thị ở đây.' ?>
                                </p>
                                <a id="preview-btn" href="#" class="btn" style="
                                    display: <?= !empty($edit_data['button_text']) ? 'inline-block' : 'none' ?>;
                                    background-color: <?= htmlspecialchars($edit_data['button_color'] ?? '#cda45e') ?>;
                                    color: #fff; padding: 12px 32px; border-radius: 50px; text-transform: uppercase;
                                    font-weight: 600; font-size: 14px; font-family: 'Poppins', sans-serif; letter-spacing: 1px;
                                    border: none; margin-top: 20px; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                                    <?= htmlspecialchars($edit_data['button_text'] ?? '') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Mini hint -->
                <div class="text-center mt-3 text-muted small">
                    <i class="fas fa-magic me-1"></i>Giao diện tự động cập nhật khi bạn thao tác nhập liệu
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DANH SÁCH BANNER ===== -->
    <div class="card premium-card mt-5">
        <div class="premium-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="m-0 fw-bold" style="color: #2c3e50;"><i class="fas fa-list-ul me-2 text-primary"></i>Danh sách Banner đang chạy</h4>
            <span class="badge bg-light text-dark rounded-pill px-3 py-2 shadow-sm"><i class="fas fa-grip-vertical me-2"></i>Kéo thả hàng để thay đổi thứ tự ưu tiên hiển thị</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                    <thead class="table-light text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                        <tr>
                            <th width="50" class="text-center"></th>
                            <th width="80" class="text-center">Vị trí</th>
                            <th width="150">Giao diện (Ảnh)</th>
                            <th>Chi tiết cấu hình hiển thị</th>
                            <th width="120" class="text-center">Trạng thái</th>
                            <th width="120" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-banners">
                        <?php if (empty($banners)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-images fs-1 mb-3 opacity-25"></i><br>Chưa có banner nào. Hãy thêm banner đầu tiên!</td></tr>
                        <?php else: ?>
                        <?php foreach ($banners as $b):
                            $active = (int)($b['is_active'] ?? 1);
                        ?>
                        <tr class="banner-row <?= !$active ? 'inactive-banner' : '' ?>" data-id="<?= $b['id'] ?>">
                            <td class="text-center"><span class="drag-handle fs-4">⠿</span></td>
                            <td class="text-center">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width:36px;height:36px;font-weight:bold;" id="order-<?= $b['id'] ?>">
                                    <?= (int)$b['display_order'] ?>
                                </div>
                            </td>
                            <td>
                                <div class="position-relative">
                                    <img src="../../public/assets/img/hero/<?= htmlspecialchars($b['image_url']) ?>" class="banner-thumb" onerror="this.src='../../public/assets/img/hero/default.jpg'" alt="<?= htmlspecialchars($b['title']) ?>">
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="mb-1">
                                    <span style="font-family:<?= htmlspecialchars($b['font_family']) ?>; font-size:1.15rem; color:#2c3e50; font-weight:<?= $b['font_style']==='bold' ? 'bold' : 'normal' ?>; font-style:<?= $b['font_style']==='italic' ? 'italic' : 'normal' ?>;">
                                        <?= htmlspecialchars($b['title']) ?>
                                    </span>
                                    <?php if(!empty($b['button_text'])): ?>
                                        <span class="badge ms-2" style="background-color: <?= htmlspecialchars($b['button_color']) ?>; font-weight:normal; letter-spacing:0.5px;">[CTA] <?= htmlspecialchars($b['button_text']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small mb-2 text-truncate" style="max-width: 400px; font-family:<?= htmlspecialchars($b['desc_font_family']) ?>;">
                                    <?= htmlspecialchars($b['description']) ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php 
                                    if (empty($b['start_date']) && empty($b['end_date'])) {
                                        echo '<span class="badge bg-light text-secondary border px-2 py-1"><i class="fas fa-infinity me-1"></i>Vô thời hạn</span>';
                                    } else {
                                        $sd = !empty($b['start_date']) ? date('d/m H:i', strtotime($b['start_date'])) : '...';
                                        $ed = !empty($b['end_date'])   ? date('d/m H:i', strtotime($b['end_date']))   : '...';
                                        echo '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="far fa-calendar-alt me-1"></i>' . $sd . ' &rarr; ' . $ed . '</span>';
                                    }
                                    ?>
                                    <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-align-<?= htmlspecialchars($b['text_align']) ?> me-1"></i>Căn <?= htmlspecialchars($b['text_align']) === 'left'?'Trái':($b['text_align']==='right'?'Phải':'Giữa') ?></span>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <label class="switch mb-0" title="<?= $active ? 'Click để Ẩn' : 'Click để Hiện' ?>">
                                    <input type="checkbox" class="toggle-active-btn" data-id="<?= $b['id'] ?>" <?= $active ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="small mt-1 text-<?= $active ? 'success' : 'secondary' ?> fw-bold toggle-text-<?= $b['id'] ?>"><?= $active ? 'Đang bật' : 'Đã tắt' ?></div>
                            </td>
                            <td class="text-center align-middle">
                                <a href="manage_banners.php?edit=<?= $b['id'] ?>" class="btn btn-light btn-sm text-primary shadow-sm border mb-2 w-100 fw-bold">
                                    <i class="fas fa-pen me-1"></i>Sửa
                                </a>
                                <button type="button" class="btn btn-light btn-sm text-danger shadow-sm border w-100 fw-bold btn-delete-banner" data-id="<?= $b['id'] ?>" data-title="<?= htmlspecialchars($b['title']) ?>">
                                    <i class="fas fa-trash-alt me-1"></i>Xóa
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
</div>

<!-- Modal xác nhận xóa (Premium) -->
<div class="modal fade" id="modalDeleteBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius:20px; overflow:hidden;">
            <div class="modal-body text-center p-5">
                <div class="mb-4 text-danger">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem;"></i>
                </div>
                <h5 class="fw-bold mb-3">Xác nhận xóa Banner</h5>
                <p class="text-muted mb-2">Bạn có chắc chắn muốn xóa:</p>
                <p class="fw-bold fs-5 text-dark" id="delete-banner-title"></p>
                <div class="alert alert-danger py-2 mt-4 mb-0 small rounded-3">Hành động này sẽ xóa vĩnh viễn hình ảnh khỏi máy chủ!</div>
            </div>
            <div class="modal-footer border-0 bg-light p-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="m-0">
                    <input type="hidden" name="delete_banner_id" id="delete-banner-id">
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">Xóa Vĩnh Viễn</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// ---- Premium Live Preview ----
function val(id) { var el = document.getElementById(id); return el ? el.value : ''; }

var pTitle   = document.getElementById('preview-title');
var pDesc    = document.getElementById('preview-desc');
var pContent = document.getElementById('preview-content');
var pBox     = document.getElementById('preview-box');

function updatePreview() {
    pTitle.innerText = val('input-title') || 'Tiêu đề Banner';
    pDesc.innerText  = val('input-desc')  || 'Mô tả ngắn gọn sẽ hiển thị ở đây.';
    
    // Xử lý radio button align
    var alignVal = 'center';
    var alignRadios = document.getElementsByName('text_align');
    for (var i = 0; i < alignRadios.length; i++) {
        if (alignRadios[i].checked) { alignVal = alignRadios[i].value; break; }
    }
    pContent.style.textAlign = alignVal;

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

    var btnText = val('input-btn-text');
    var btnColor = val('input-btn-color');
    var pBtn = document.getElementById('preview-btn');
    if (pBtn) {
        pBtn.style.display = btnText ? 'inline-block' : 'none';
        pBtn.innerText = btnText;
        pBtn.style.backgroundColor = btnColor;
    }
}

['input-title','input-desc','input-color','input-font',
 'input-style','input-title-size','input-desc-color','input-desc-font',
 'input-desc-style','input-desc-size','input-btn-text','input-btn-color'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) { el.addEventListener('input', updatePreview); el.addEventListener('change', updatePreview); }
});

// Image preview
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

// ---- Premium Modal Delete ----
document.querySelectorAll('.btn-delete-banner').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('delete-banner-title').textContent = this.dataset.title;
        document.getElementById('delete-banner-id').value          = this.dataset.id;
        new bootstrap.Modal(document.getElementById('modalDeleteBanner')).show();
    });
});

// ---- Premium Switch Toggle ----
document.querySelectorAll('.toggle-active-btn').forEach(function (input) {
    input.addEventListener('change', function () {
        var id  = this.dataset.id;
        var row = this.closest('tr');
        var textEl = document.querySelector('.toggle-text-' + id);
        
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
            input.checked = active;
            if (textEl) {
                textEl.textContent = active ? 'Đang bật' : 'Đã tắt';
                textEl.className = 'small mt-1 fw-bold toggle-text-' + id + (active ? ' text-success' : ' text-secondary');
            }
        });
    });
});

// ---- Sortable Drag & Drop ----
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
            body: 'update_order=1&' + ids.map(function (id, i) { return 'ids[' + i + ']=' + id; }).join('&')
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.status === 'success') {
                var t = document.createElement('div');
                t.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;background:rgba(40,167,69,0.9);backdrop-filter:blur(5px);color:#fff;padding:12px 24px;border-radius:50px;font-weight:bold;box-shadow:0 10px 20px rgba(40,167,69,0.2);animation:fadeInDown 0.3s ease;';
                t.innerHTML = '<i class="fas fa-check-circle me-2"></i>Thứ tự hiển thị đã được cập nhật';
                document.body.appendChild(t);
                setTimeout(function () { t.style.opacity = '0'; t.style.transform = 'translateY(-20px)'; t.style.transition = 'all 0.3s ease'; setTimeout(function(){t.remove();},300); }, 2500);
            }
        });
    }
});
</script>
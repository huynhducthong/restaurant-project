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
    "'Cormorant Garamond', serif" => "Cormorant Garamond (Tiêu chuẩn - Cổ điển)",
    "'Source Sans 3', sans-serif" => "Source Sans 3 (Văn bản - Hiện đại)",
];
$font_sizes = [12,14,16,18,20,22,24,26,28,36,48,56,64,72];

include '../../public/admin_layout_header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-gold: #cda45e;
        --soft-bg: #fdfdfd;
        --border-color: #e9ecef;
    }
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--soft-bg); color: #444; }

    /* Layout & Cards */
    .card-minimal { border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); background: #fff; margin-bottom: 1.5rem; }
    .card-header-clean { background: transparent; border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; font-weight: 700; color: #2c3e50; }
    
    /* Inputs & Form */
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 0.6rem 0.8rem; font-size: 0.95rem; background: #fff; }
    .form-control:focus { border-color: var(--primary-gold); box-shadow: 0 0 0 0.2rem rgba(205, 164, 94, 0.1); }
    .form-label { font-weight: 600; color: #6c757d; font-size: 0.75rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Buttons */
    .btn-minimal { border-radius: 8px; padding: 0.6rem 1.5rem; font-weight: 600; transition: 0.3s; }
    .btn-gold { background: var(--primary-gold); color: white; border: none; }
    .btn-gold:hover { background: #b89252; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(205, 164, 94, 0.2); }
    .text-gold { color: var(--primary-gold) !important; }

    /* Live Preview */
    .preview-sticky { position: sticky; top: 1.5rem; z-index: 10; }
    .preview-frame { border-radius: 15px; overflow: hidden; border: 1px solid #dee2e6; box-shadow: 0 20px 50px rgba(0,0,0,0.1); position: relative; }
    .preview-label { position: absolute; top: 1rem; right: 1rem; background: rgba(0,0,0,0.4); color: #fff; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.7rem; z-index: 5; font-weight: 600; }

    /* Badge & Controls */
    .status-toggle { width: 44px; height: 22px; position: relative; display: inline-block; }
    .status-toggle input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e2e8f0; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #2ecc71; }
    input:checked + .slider:before { transform: translateX(22px); }

    /* Table Minimal */
    .table-clean thead th { background: #f8fafc; color: #64748b; font-size: 0.7rem; text-transform: uppercase; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
    .table-clean tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .banner-thumb { width: 90px; height: 55px; object-fit: cover; border-radius: 6px; }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Cấu hình Banner</h3>
            <p class="text-muted small mb-0">Quản lý và thiết kế ảnh bìa trang chủ</p>
        </div>
        <a href="manage_banners.php" class="btn btn-minimal btn-gold">
            <i class="fas fa-plus me-2"></i>Thêm mới
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

                <!-- Card 1: Hình ảnh & Căn lề -->
                <div class="card-minimal mb-2">
                    <div class="card-header-clean py-1 px-3 small text-uppercase fw-bold text-primary">
                        <i class="fas fa-image me-1"></i> Hình ảnh & Bố cục
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label mb-1 small text-muted">Tải lên Ảnh Nền (JPG, PNG, WEBP)</label>
                                <input type="file" name="banner_image" id="input-img" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" <?= $edit_data ? '' : 'required' ?>>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1 small text-muted">Thứ tự</label>
                                <input type="number" name="display_order" class="form-control form-control-sm" value="<?= (int)($edit_data['display_order'] ?? count($banners) + 1) ?>" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 small text-muted">Căn lề chữ</label>
                                <div class="d-flex gap-3 align-items-center h-100 pb-1">
                                    <?php foreach (['left'=>'Trái', 'center'=>'Giữa', 'right'=>'Phải'] as $v=>$l): ?>
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="text_align" id="align_<?= $v ?>" value="<?= $v ?>" <?= ($edit_data['text_align'] ?? 'center') === $v ? 'checked' : '' ?> onchange="updatePreview()">
                                        <label class="form-check-label small" style="cursor:pointer;" for="align_<?= $v ?>"><?= $l ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Nội dung văn bản -->
                <div class="card-minimal mb-2">
                    <div class="card-header-clean py-1 px-3 small text-uppercase fw-bold text-success">
                        <i class="fas fa-font me-1"></i> Nội dung & Định dạng
                    </div>
                    <div class="card-body p-2">
                        <div class="mb-2">
                            <label class="form-label mb-1 small text-muted">Tiêu đề chính</label>
                            <input type="text" name="title" id="input-title" class="form-control form-control-sm fw-bold mb-1" value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="fas fa-text-height"></i></span>
                                <select name="font_family" id="input-font" class="form-select" style="flex: 0 0 auto; width: 180px;"><?php foreach ($fonts as $v => $l): ?><option value="<?= htmlspecialchars($v) ?>" <?= ($edit_data['font_family'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                                <select name="title_font_size" id="input-title-size" class="form-select" style="flex: 0 0 auto; width: 85px;"><?php foreach ($font_sizes as $sz): ?><option value="<?= $sz ?>" <?= ($edit_data['title_font_size'] ?? 48) == $sz ? 'selected' : '' ?>><?= $sz ?>px</option><?php endforeach; ?></select>
                                <select name="font_style" id="input-style" class="form-select" style="flex: 0 0 auto; width: 110px;"><?php foreach (['normal'=>'Thường','bold'=>'Đậm','italic'=>'Nghiêng'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($edit_data['font_style'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                                <input type="color" name="text_color" id="input-color" class="form-control form-control-color p-0 border-0" style="flex: 0 0 auto; width: 45px; height: auto;" value="<?= htmlspecialchars($edit_data['text_color'] ?? '#ffffff') ?>">
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label mb-1 small text-muted">Mô tả ngắn</label>
                            <textarea name="description" id="input-desc" class="form-control form-control-sm mb-1" rows="2"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <select name="desc_font_family" id="input-desc-font" class="form-select" style="flex: 0 0 auto; width: 180px;"><?php foreach ($fonts as $v => $l): ?><option value="<?= htmlspecialchars($v) ?>" <?= ($edit_data['desc_font_family'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                                <select name="desc_font_size" id="input-desc-size" class="form-select" style="flex: 0 0 auto; width: 85px;"><?php foreach ($font_sizes as $sz): ?><option value="<?= $sz ?>" <?= ($edit_data['desc_font_size'] ?? 24) == $sz ? 'selected' : '' ?>><?= $sz ?>px</option><?php endforeach; ?></select>
                                <select name="desc_font_style" id="input-desc-style" class="form-select" style="flex: 0 0 auto; width: 110px;"><?php foreach (['normal'=>'Thường','bold'=>'Đậm','italic'=>'Nghiêng'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($edit_data['desc_font_style'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                                <input type="color" name="desc_color" id="input-desc-color" class="form-control form-control-color p-0 border-0" style="flex: 0 0 auto; width: 45px; height: auto;" value="<?= htmlspecialchars($edit_data['desc_color'] ?? '#eeeeee') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Tùy chọn hiển thị & CTA -->
                <div class="card-minimal mb-3">
                    <div class="card-header-clean py-1 px-3 small text-uppercase fw-bold text-warning">
                        <i class="fas fa-link me-1"></i> Tùy chọn hiển thị & CTA
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-3">
                                <label class="form-label mb-1 small text-muted">Tên nút (CTA)</label>
                                <input type="text" name="button_text" id="input-btn-text" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_data['button_text'] ?? '') ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label mb-1 small text-muted">Màu</label>
                                <input type="color" name="button_color" id="input-btn-color" class="form-control form-control-color form-control-sm p-0 border-0 w-100" value="<?= htmlspecialchars($edit_data['button_color'] ?? '#cda45e') ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label mb-1 small text-muted">Đường dẫn (Link)</label>
                                <input type="text" name="button_link" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_data['button_link'] ?? '') ?>" placeholder="VD: menu.php">
                            </div>
                        </div>
                        <hr class="my-2" style="opacity:0.1">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label mb-1 small text-muted">Ngày bắt đầu</label>
                                <input type="datetime-local" name="start_date" class="form-control form-control-sm" value="<?= !empty($edit_data['start_date']) ? date('Y-m-d\TH:i', strtotime($edit_data['start_date'])) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1 small text-muted">Ngày kết thúc</label>
                                <input type="datetime-local" name="end_date" class="form-control form-control-sm" value="<?= !empty($edit_data['end_date']) ? date('Y-m-d\TH:i', strtotime($edit_data['end_date'])) : '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="btn_save" class="btn btn-minimal btn-gold w-100 py-3 mb-5">
                    <i class="fas fa-save me-2"></i><?= $edit_data ? 'CẬP NHẬT BANNER' : 'LƯU BANNER MỚI' ?>
                </button>
            </form>
        </div>

        <!-- ===== CỘT PHẢI: Sticky Live Preview ===== -->
        <div class="col-lg-5">
            <div class="preview-sticky">
                <div class="preview-frame">
                    <div class="preview-label">Live Preview</div>
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
    <div class="card-minimal mt-5">
        <div class="card-header-clean d-flex justify-content-between align-items-center">
            <span>Danh sách Banner</span>
            <small class="text-muted fw-normal"><i class="fas fa-info-circle me-1"></i>Kéo thả để sắp xếp</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-clean align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="40"></th>
                            <th width="120">Ảnh</th>
                            <th>Nội dung hiển thị</th>
                            <th width="100" class="text-center">Bật/Tắt</th>
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
                            <td class="text-center"><span class="drag-handle">⠿</span></td>
                            <td>
                                <img src="../../public/assets/img/hero/<?= htmlspecialchars($b['image_url']) ?>" class="banner-thumb border" onerror="this.src='../../public/assets/img/hero/default.jpg'">
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    <span id="order-<?= $b['id'] ?>" class="d-none"><?= $b['display_order'] ?></span>
                                    <?= htmlspecialchars($b['title']) ?>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 300px;"><?= htmlspecialchars($b['description']) ?></div>
                                <div class="mt-1">
                                    <span class="badge bg-light text-secondary border small">Căn <?= $b['text_align'] ?></span>
                                    <?php if($b['button_text']): ?>
                                        <span class="badge border small" style="background: rgba(205, 164, 94, 0.1); color: var(--primary-gold);">
                                            <i class="fas fa-link me-1"></i>Nút: <?= htmlspecialchars($b['button_text']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <label class="status-toggle">
                                    <input type="checkbox" class="toggle-active-btn" data-id="<?= $b['id'] ?>" <?= $active ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <a href="manage_banners.php?edit=<?= $b['id'] ?>" class="btn btn-sm btn-white border px-3"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-white border px-3 text-danger btn-delete-banner" data-id="<?= $b['id'] ?>" data-title="<?= htmlspecialchars($b['title']) ?>"><i class="fas fa-trash"></i></button>
                                </div>
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

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="modalDeleteBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 card-minimal p-3">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger"><i class="fas fa-trash-alt fa-3x"></i></div>
                <h5 class="fw-bold">Xác nhận xóa?</h5>
                <p class="text-muted mb-0">Bạn có chắc muốn xóa vĩnh viễn banner này?</p>
                <p class="fw-bold mt-2" id="delete-banner-title"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <button type="button" class="btn btn-light px-4 border" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="m-0">
                    <input type="hidden" name="delete_banner_id" id="delete-banner-id">
                    <button type="submit" class="btn btn-danger px-4">Đồng ý xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>


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
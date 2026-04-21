<?php
include '../public/admin_layout_header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$message = "";

// --- 1. LẤY THÔNG TIN ĐỂ SỬA ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt_edit = $db->prepare("SELECT * FROM banners WHERE id = :id");
    $stmt_edit->execute([':id' => $id]);
    $edit_data = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}

// --- 2. XỬ LÝ XÓA BANNER ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt_get = $db->prepare("SELECT image_url FROM banners WHERE id = :id");
        $stmt_get->execute([':id' => $id]);
        $old_image = $stmt_get->fetchColumn();
        
        if ($old_image && file_exists("../public/assets/img/hero/" . $old_image)) {
            unlink("../public/assets/img/hero/" . $old_image);
        }

        $stmt_del = $db->prepare("DELETE FROM banners WHERE id = :id");
        $stmt_del->execute([':id' => $id]);
        $message = "<div class='alert alert-success shadow-sm'>Đã xóa banner thành công!</div>";
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger shadow-sm'>Lỗi khi xóa: " . $e->getMessage() . "</div>";
    }
}

// --- 3. XỬ LÝ THÊM HOẶC CẬP NHẬT ---
if (isset($_POST['btn_save'])) {
    $id = $_POST['banner_id'] ?? null; 
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? ''; 
    $display_order = $_POST['display_order'] ?? 1;
    $text_align = $_POST['text_align'] ?? 'center';

    // Cài đặt Tiêu đề
    $text_color = $_POST['text_color'] ?? '#ffffff';
    $font_family = $_POST['font_family'] ?? "'Poppins', sans-serif";
    $font_style = $_POST['font_style'] ?? 'normal';
    $title_font_size = $_POST['title_font_size'] ?? 48; // Kích thước chữ Tiêu đề

    // Cài đặt Mô tả
    $desc_color = $_POST['desc_color'] ?? '#eeeeee';
    $desc_font_family = $_POST['desc_font_family'] ?? "'Poppins', sans-serif";
    $desc_font_style = $_POST['desc_font_style'] ?? 'normal';
    $desc_font_size = $_POST['desc_font_size'] ?? 24; // Kích thước chữ Mô tả

    $image_name = $_POST['old_image'] ?? '';

    // Xử lý upload ảnh
    if (!empty($_FILES['banner_image']['name'])) {
        $target_dir = "../public/assets/img/hero/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        $new_file_name = time() . '_' . basename($_FILES['banner_image']['name']);
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_dir . $new_file_name)) {
            if (!empty($image_name) && file_exists($target_dir . $image_name)) {
                unlink($target_dir . $image_name);
            }
            $image_name = $new_file_name;
        }
    }

    try {
        if ($id) {
            $sql = "UPDATE banners SET 
                        image_url=:img, title=:title, description=:desc, display_order=:ord, text_align=:align,
                        text_color=:color, font_family=:font, font_style=:style, title_font_size=:t_size,
                        desc_color=:d_color, desc_font_family=:d_font, desc_font_style=:d_style, desc_font_size=:d_size
                    WHERE id=:id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':img' => $image_name, ':title' => $title, ':desc' => $description, ':ord' => $display_order, ':align' => $text_align,
                ':color' => $text_color, ':font' => $font_family, ':style' => $font_style, ':t_size' => $title_font_size,
                ':d_color' => $desc_color, ':d_font' => $desc_font_family, ':d_style' => $desc_font_style, ':d_size' => $desc_font_size,
                ':id' => $id
            ]);
            $message = "<div class='alert alert-success shadow-sm'>Cập nhật banner thành công!</div>";
        } else {
            $sql = "INSERT INTO banners 
                        (image_url, title, description, display_order, text_align, text_color, font_family, font_style, title_font_size, desc_color, desc_font_family, desc_font_style, desc_font_size) 
                    VALUES 
                        (:img, :title, :desc, :ord, :align, :color, :font, :style, :t_size, :d_color, :d_font, :d_style, :d_size)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':img' => $image_name, ':title' => $title, ':desc' => $description, ':ord' => $display_order, ':align' => $text_align,
                ':color' => $text_color, ':font' => $font_family, ':style' => $font_style, ':t_size' => $title_font_size,
                ':d_color' => $desc_color, ':d_font' => $desc_font_family, ':d_style' => $desc_font_style, ':d_size' => $desc_font_size
            ]);
            $message = "<div class='alert alert-success shadow-sm'>Thêm banner mới thành công!</div>";
        }
        $edit_data = null; 
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger shadow-sm'>Lỗi: " . $e->getMessage() . "</div>";
    }
}

// --- 4. LẤY DANH SÁCH BANNER ---
$banners = [];
try {
    $stmt_list = $db->prepare("SELECT * FROM banners ORDER BY display_order ASC");
    $stmt_list->execute();
    $banners = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Danh sách Font chữ & Kích thước
$fonts = [
    "'Poppins', sans-serif" => "Poppins (Hiện đại)", 
    "'Playfair Display', serif" => "Playfair (Cổ điển sang trọng)", 
    "'Montserrat', sans-serif" => "Montserrat (Trẻ trung)",
    "'Lora', serif" => "Lora (Thanh lịch, dễ đọc)",
    "'Dancing Script', cursive" => "Dancing Script (Bay bướm)",
    "'Pacifico', cursive" => "Pacifico (Nghệ thuật, vui vẻ)",
    "'Great Vibes', cursive" => "Great Vibes (Thư pháp cực sang)",
    "'Oswald', sans-serif" => "Oswald (Gọn gàng, mạnh mẽ)",
    "'Roboto', sans-serif" => "Roboto (Cơ bản, rõ nét)",
    "'Caveat', cursive" => "Caveat (Viết tay mộc mạc)"
];
$font_sizes = [12, 14, 16, 18, 20, 22, 24, 26, 28, 36, 48, 56, 64, 72];
?>

<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Dancing+Script:wght@400;700&family=Great+Vibes&family=Lora:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;700&family=Oswald:wght@400;700&family=Pacifico&family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">

<div class="container-fluid py-4 bg-light min-vh-100">
    <h2 class="mb-4 text-dark border-bottom pb-2" style="font-weight: 700;">Quản lý Banner (Slide Show)</h2>
    <?= $message ?>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm bg-white border-0">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="m-0 text-primary fw-bold"><?= $edit_data ? 'Cập nhật Banner' : 'Thêm Banner Mới' ?></h5>
                    <?php if($edit_data): ?>
                        <a href="manage_banners.php" class="btn btn-sm btn-outline-secondary">Hủy Sửa</a>
                    <?php endif; ?>
                </div>
                <div class="card-body" style="max-height: 800px; overflow-y: auto;">
                    <form action="manage_banners.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="banner_id" value="<?= $edit_data['id'] ?? '' ?>">
                        <input type="hidden" name="old_image" value="<?= $edit_data['image_url'] ?? '' ?>">

                        <h6 class="fw-bold text-success border-bottom pb-1"><i class="bi bi-image"></i> Hình ảnh & Vị trí</h6>
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label small">Ảnh Banner <?= $edit_data ? '(Bỏ qua nếu giữ ảnh cũ)' : '' ?></label>
                                <input type="file" name="banner_image" id="input-img" class="form-control form-control-sm" <?= $edit_data ? '' : 'required' ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Thứ tự hiển thị</label>
                                <input type="number" name="display_order" class="form-control form-control-sm" value="<?= $edit_data['display_order'] ?? '1' ?>">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label small">Căn lề toàn khối</label>
                                <select name="text_align" id="input-align" class="form-select form-select-sm">
                                    <option value="center" <?= ($edit_data['text_align'] ?? '') == 'center' ? 'selected' : '' ?>>Giữa</option>
                                    <option value="left" <?= ($edit_data['text_align'] ?? '') == 'left' ? 'selected' : '' ?>>Trái</option>
                                    <option value="right" <?= ($edit_data['text_align'] ?? '') == 'right' ? 'selected' : '' ?>>Phải</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="fw-bold text-success border-bottom pb-1 mt-4"><i class="bi bi-type-h1"></i> Cài đặt Tiêu đề</h6>
                        <div class="row mb-3">
                            <div class="col-12 mb-2">
                                <input type="text" name="title" id="input-title" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" placeholder="Nhập tiêu đề...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Màu chữ</label>
                                <input type="color" name="text_color" id="input-color" class="form-control form-control-color w-100 form-control-sm" value="<?= $edit_data['text_color'] ?? '#ffffff' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Font chữ</label>
                                <select name="font_family" id="input-font" class="form-select form-select-sm">
                                    <?php foreach($fonts as $val => $name): ?>
                                        <option value="<?= $val ?>" <?= ($edit_data['font_family'] ?? '') == $val ? 'selected' : '' ?>><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Cỡ chữ</label>
                                <select name="title_font_size" id="input-title-size" class="form-select form-select-sm">
                                    <?php foreach($font_sizes as $sz): ?>
                                        <option value="<?= $sz ?>" <?= ($edit_data['title_font_size'] ?? 48) == $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Kiểu</label>
                                <select name="font_style" id="input-style" class="form-select form-select-sm">
                                    <option value="normal" <?= ($edit_data['font_style'] ?? '') == 'normal' ? 'selected' : '' ?>>Thường</option>
                                    <option value="bold" <?= ($edit_data['font_style'] ?? '') == 'bold' ? 'selected' : '' ?>>In đậm</option>
                                    <option value="italic" <?= ($edit_data['font_style'] ?? '') == 'italic' ? 'selected' : '' ?>>Nghiêng</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="fw-bold text-success border-bottom pb-1 mt-4"><i class="bi bi-card-text"></i> Cài đặt Mô tả</h6>
                        <div class="row mb-3">
                            <div class="col-12 mb-2">
                                <textarea name="description" id="input-desc" class="form-control form-control-sm" rows="2" placeholder="Nhập mô tả..."><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Màu chữ</label>
                                <input type="color" name="desc_color" id="input-desc-color" class="form-control form-control-color w-100 form-control-sm" value="<?= $edit_data['desc_color'] ?? '#eeeeee' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Font chữ</label>
                                <select name="desc_font_family" id="input-desc-font" class="form-select form-select-sm">
                                    <?php foreach($fonts as $val => $name): ?>
                                        <option value="<?= $val ?>" <?= ($edit_data['desc_font_family'] ?? '') == $val ? 'selected' : '' ?>><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Cỡ chữ</label>
                                <select name="desc_font_size" id="input-desc-size" class="form-select form-select-sm">
                                    <?php foreach($font_sizes as $sz): ?>
                                        <option value="<?= $sz ?>" <?= ($edit_data['desc_font_size'] ?? 24) == $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Kiểu</label>
                                <select name="desc_font_style" id="input-desc-style" class="form-select form-select-sm">
                                    <option value="normal" <?= ($edit_data['desc_font_style'] ?? '') == 'normal' ? 'selected' : '' ?>>Thường</option>
                                    <option value="bold" <?= ($edit_data['desc_font_style'] ?? '') == 'bold' ? 'selected' : '' ?>>In đậm</option>
                                    <option value="italic" <?= ($edit_data['desc_font_style'] ?? '') == 'italic' ? 'selected' : '' ?>>Nghiêng</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" name="btn_save" class="btn btn-primary px-4 fw-bold shadow-sm">
                                <i class="bi bi-save me-1"></i> <?= $edit_data ? 'Lưu Cập Nhật' : 'Thêm Banner Mới' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm bg-white border-0 h-100" style="position: sticky; top: 20px;">
                <div class="card-header bg-white border-bottom">
                    <h5 class="m-0 text-danger fw-bold"><i class="bi bi-display"></i> Xem trước trực tiếp</h5>
                </div>
                <div class="card-body p-0">
                    <div id="preview-box" class="d-flex flex-column justify-content-center" 
                         style="width: 100%; height: 500px; background-color: #222; background-image: url('../public/assets/img/hero/<?= $edit_data['image_url'] ?? '' ?>'); background-size: cover; background-position: center; position: relative; transition: all 0.3s;">
                        
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5);"></div>
                        
                        <div class="container position-relative px-4" id="preview-content" style="text-align: <?= $edit_data['text_align'] ?? 'center' ?>; z-index: 2;">
                            
                            <h2 id="preview-title" style="
                                color: <?= $edit_data['text_color'] ?? '#ffffff' ?>; 
                                font-family: <?= $edit_data['font_family'] ?? "'Poppins', sans-serif" ?>; 
                                font-size: <?= $edit_data['title_font_size'] ?? 48 ?>px; 
                                font-weight: <?= ($edit_data['font_style'] ?? '') == 'bold' ? 'bold' : 'normal' ?>; 
                                font-style: <?= ($edit_data['font_style'] ?? '') == 'italic' ? 'italic' : 'normal' ?>; 
                                text-shadow: 2px 2px 5px rgba(0,0,0,0.7); 
                                margin-bottom: 15px; 
                                line-height: 1.2;">
                                <?= !empty($edit_data['title']) ? $edit_data['title'] : 'Tiêu đề Banner' ?>
                            </h2>
                            
                            <p id="preview-desc" style="
                                color: <?= $edit_data['desc_color'] ?? '#eeeeee' ?>; 
                                font-family: <?= $edit_data['desc_font_family'] ?? "'Poppins', sans-serif" ?>; 
                                font-size: <?= $edit_data['desc_font_size'] ?? 24 ?>px; 
                                font-weight: <?= ($edit_data['desc_font_style'] ?? '') == 'bold' ? 'bold' : 'normal' ?>; 
                                font-style: <?= ($edit_data['desc_font_style'] ?? '') == 'italic' ? 'italic' : 'normal' ?>; 
                                text-shadow: 1px 1px 4px rgba(0,0,0,0.7);">
                                <?= !empty($edit_data['description']) ? $edit_data['description'] : 'Mô tả ngắn gọn về nhà hàng của bạn sẽ hiển thị ở đây.' ?>
                            </p>
                            
                            <div class="mt-4">
                                <span style="display:inline-block; border: 2px solid #cda45e; color:#fff; padding: 10px 25px; border-radius: 50px; font-size: 13px; text-transform: uppercase; font-family:'Poppins';">Thực đơn của chúng tôi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="m-0 fw-bold">Danh sách Banner hiện tại</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="80" class="text-center">Thứ tự</th>
                        <th width="200">Ảnh</th>
                        <th>Thông tin hiển thị</th>
                        <th width="120" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($banners)): ?>
                        <?php foreach($banners as $b): ?>
                        <tr>
                            <td class="text-center fw-bold text-primary fs-5"><?= htmlspecialchars($b['display_order']) ?></td>
                            <td>
                                <img src="../public/assets/img/hero/<?= htmlspecialchars($b['image_url']) ?>" 
                                     class="img-thumbnail" style="max-height: 100px;">
                            </td>
                            <td>
                                <div style="font-family: <?= $b['font_family'] ?>; color: #333; font-size: 1.2rem; font-weight: <?= ($b['font_style'] == 'bold') ? 'bold' : 'normal' ?>;">
                                    <?= htmlspecialchars($b['title']) ?> <span class="badge bg-secondary fs-6" style="color:<?= $b['text_color'] ?> !important;">Size: <?= $b['title_font_size'] ?>px</span>
                                </div>
                                <div class="mt-1 text-secondary fst-italic" style="font-family: <?= $b['desc_font_family'] ?>;">
                                    <?= htmlspecialchars($b['description']) ?> <span class="badge bg-light text-dark fs-6">Size: <?= $b['desc_font_size'] ?>px</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="?edit=<?= $b['id'] ?>" class="btn btn-outline-primary btn-sm mb-1 w-100">
                                    <i class="bi bi-pencil-square"></i> Sửa
                                </a>
                                <a href="?delete=<?= $b['id'] ?>" class="btn btn-outline-danger btn-sm w-100" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa banner này không?')">
                                    <i class="bi bi-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">Chưa có banner nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const val = id => document.getElementById(id).value;
    
    const pTitle = document.getElementById('preview-title');
    const pDesc = document.getElementById('preview-desc');
    const pContent = document.getElementById('preview-content');

    function updatePreview() {
        // Cập nhật text
        pTitle.innerText = val('input-title') || 'Tiêu đề Banner';
        pDesc.innerText = val('input-desc') || 'Mô tả ngắn gọn sẽ hiển thị ở đây.';
        
        // Căn lề chung
        pContent.style.textAlign = val('input-align');

        // Styles Tiêu đề
        pTitle.style.color = val('input-color');
        pTitle.style.fontFamily = val('input-font');
        pTitle.style.fontSize = val('input-title-size') + 'px';
        pTitle.style.fontWeight = (val('input-style') === 'bold') ? 'bold' : 'normal';
        pTitle.style.fontStyle = (val('input-style') === 'italic') ? 'italic' : 'normal';

        // Styles Mô tả
        pDesc.style.color = val('input-desc-color');
        pDesc.style.fontFamily = val('input-desc-font');
        pDesc.style.fontSize = val('input-desc-size') + 'px';
        pDesc.style.fontWeight = (val('input-desc-style') === 'bold') ? 'bold' : 'normal';
        pDesc.style.fontStyle = (val('input-desc-style') === 'italic') ? 'italic' : 'normal';
    }

    // Lắng nghe sự kiện cho các input/select
    const inputIds = [
        'input-title', 'input-desc', 'input-align', 
        'input-color', 'input-font', 'input-style', 'input-title-size',
        'input-desc-color', 'input-desc-font', 'input-desc-style', 'input-desc-size'
    ];
    
    inputIds.forEach(id => {
        let el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        }
    });

    // Xử lý đổi ảnh nền ngay lập tức
    document.getElementById('input-img').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = e => document.getElementById('preview-box').style.backgroundImage = `url('${e.target.result}')`;
            reader.readAsDataURL(this.files[0]);
        }
    });
</script>
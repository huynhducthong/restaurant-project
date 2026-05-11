<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php'; // BỔ SUNG: Import thư viện chống CSRF

$db = (new Database())->getConnection();
$message = "";

// ============================================================
// 2. XỬ LÝ XÓA
// ============================================================
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt_img = $db->prepare("SELECT image FROM chefs WHERE id = ?");
    $stmt_img->execute([$del_id]);
    $old_img = $stmt_img->fetchColumn();
    if ($old_img && file_exists("../public/assets/img/chefs/" . $old_img)) {
        unlink("../public/assets/img/chefs/" . $old_img);
    }
    $db->prepare("DELETE FROM chefs WHERE id = ?")->execute([$del_id]);
    $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Đã xóa hồ sơ đầu bếp thành công!</div>";
}

// ============================================================
// 3. XỬ LÝ BẬT/TẮT HIỂN THỊ
// ============================================================
if (isset($_GET['toggle']) && isset($_GET['field'])) {
    $tog_id = (int) $_GET['toggle'];
    $tog_field = in_array($_GET['field'], ['is_active', 'is_featured']) ? $_GET['field'] : 'is_active';
    $db->prepare("UPDATE chefs SET $tog_field = 1 - $tog_field WHERE id = ?")->execute([$tog_id]);
    header("Location: manage_chefs.php");
    exit;
}

// ============================================================
// 4. LẤY DỮ LIỆU ĐỂ SỬA
// ============================================================
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt_e = $db->prepare("SELECT * FROM chefs WHERE id = ?");
    $stmt_e->execute([$edit_id]);
    $edit_data = $stmt_e->fetch(PDO::FETCH_ASSOC);
}

// ============================================================
// 5. XỬ LÝ LƯU (THÊM / SỬA)
// ============================================================
if (isset($_POST['btn_save'])) {
    // BỔ SUNG: Kiểm tra CSRF Token
    if (!verify_csrf()) {
        $message = "<div class='alert alert-danger'><i class='fas fa-shield-alt me-2'></i>Lỗi bảo mật (CSRF): Yêu cầu không hợp lệ! Vui lòng tải lại trang.</div>";
    } else {
        $chef_id = !empty($_POST['chef_id']) ? (int) $_POST['chef_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $experience = (int) ($_POST['experience'] ?? 0);
        $specialty = trim($_POST['specialty'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $quote = trim($_POST['quote'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $sort_order = (int) ($_POST['sort_order'] ?? 0);
        $image_name = trim($_POST['old_image'] ?? '');

        // Upload ảnh
        if (!empty($_FILES['chef_image']['name'])) {
            $target_dir = "../public/assets/img/chefs/";
            if (!is_dir($target_dir))
                mkdir($target_dir, 0777, true);
            $new_file = time() . '_' . basename($_FILES['chef_image']['name']);
            if (move_uploaded_file($_FILES['chef_image']['tmp_name'], $target_dir . $new_file)) {
                if ($image_name && file_exists($target_dir . $image_name))
                    unlink($target_dir . $image_name);
                $image_name = $new_file;
            }
        }

        try {
            if ($chef_id) {
                $sql = "UPDATE chefs SET name=:n, position=:p, image=:img, experience=:exp,
                            specialty=:sp, description=:desc, quote=:q, facebook=:fb, instagram=:ig,
                            email=:em, is_active=:ia, is_featured=:if2, sort_order=:so WHERE id=:id";
                $params = [
                    ':n' => $name,
                    ':p' => $position,
                    ':img' => $image_name,
                    ':exp' => $experience,
                    ':sp' => $specialty,
                    ':desc' => $description,
                    ':q' => $quote,
                    ':fb' => $facebook,
                    ':ig' => $instagram,
                    ':em' => $email,
                    ':ia' => $is_active,
                    ':if2' => $is_featured,
                    ':so' => $sort_order,
                    ':id' => $chef_id
                ];
                $db->prepare($sql)->execute($params);
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Cập nhật hồ sơ đầu bếp thành công!</div>";
            } else {
                $sql = "INSERT INTO chefs (name,position,image,experience,specialty,description,quote,
                            facebook,instagram,email,is_active,is_featured,sort_order)
                        VALUES (:n,:p,:img,:exp,:sp,:desc,:q,:fb,:ig,:em,:ia,:if2,:so)";
                $params = [
                    ':n' => $name,
                    ':p' => $position,
                    ':img' => $image_name,
                    ':exp' => $experience,
                    ':sp' => $specialty,
                    ':desc' => $description,
                    ':q' => $quote,
                    ':fb' => $facebook,
                    ':ig' => $instagram,
                    ':em' => $email,
                    ':ia' => $is_active,
                    ':if2' => $is_featured,
                    ':so' => $sort_order
                ];
                $db->prepare($sql)->execute($params);
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Thêm đầu bếp mới thành công!</div>";
            }
            $edit_data = null;
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i>Lỗi: " . $e->getMessage() . "</div>";
        }
    }
}

// ============================================================
// 6. LẤY DANH SÁCH HIỂN THỊ
// ============================================================
$chefs = $db->query("SELECT * FROM chefs ORDER BY is_featured DESC, sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .chef-card-thumb {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #cda45e;
    }

    .chef-placeholder {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2c3e50, #cda45e);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.4rem;
    }

    .badge-featured {
        background: #cda45e;
        color: #fff;
    }

    .table th {
        background: #2c3e50;
        color: #ecf0f1;
    }

    .section-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
        padding: 28px;
    }

    .form-label {
        font-weight: 600;
        color: #2c3e50;
    }

    .btn-gold {
        background: #cda45e;
        border-color: #cda45e;
        color: #fff;
    }

    .btn-gold:hover {
        background: #b8903e;
        color: #fff;
    }
</style>

<div class="main-content" style="padding-top:10px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-0"><i class="fas fa-user-tie me-2" style="color:#cda45e"></i>Quản lý Đội ngũ Đầu bếp</h3>
            <small class="text-muted">Thêm, chỉnh sửa và quản lý hồ sơ đầu bếp hiển thị trên website</small>
        </div>
        <?php if ($edit_data): ?>
            <a href="manage_chefs.php" class="btn btn-secondary"><i class="fas fa-plus me-1"></i>Thêm mới</a>
        <?php endif; ?>
    </div>

    <?= $message ?>

    <div class="row g-4">
        <div class="col-xl-5 col-lg-5">
            <div class="section-card h-100">
                <h5 class="mb-4" style="color:#2c3e50; border-bottom:2px solid #cda45e; padding-bottom:10px;">
                    <i class="fas fa-<?= $edit_data ? 'edit' : 'user-plus' ?> me-2"></i>
                    <?= $edit_data ? 'Chỉnh sửa hồ sơ' : 'Thêm đầu bếp mới' ?>
                </h5>
                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <input type="hidden" name="chef_id" value="<?= $edit_data['id'] ?? '' ?>">
                    <input type="hidden" name="old_image" value="<?= $edit_data['image'] ?? '' ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="VD: Nguyễn Văn An"
                                value="<?= htmlspecialchars($edit_data['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Chức vụ <span class="text-danger">*</span></label>
                            <select name="position" class="form-select">
                                <?php
                                $positions = ['Bếp trưởng', 'Bếp phó', 'Bếp chính', 'Đầu bếp', 'Bếp bánh', 'Bếp phụ', 'Phụ bếp'];
                                $cur_pos = $edit_data['position'] ?? 'Đầu bếp';
                                foreach ($positions as $pos):
                                    $sel = ($cur_pos === $pos) ? 'selected' : '';
                                    ?>
                                    <option value="<?= $pos ?>" <?= $sel ?>><?= $pos ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ảnh đại diện</label>
                        <?php if (!empty($edit_data['image'])): ?>
                            <div class="mb-2 d-flex align-items-center gap-2">
                                <img src="../public/assets/img/chefs/<?= htmlspecialchars($edit_data['image']) ?>"
                                    class="chef-card-thumb" alt="Avatar">
                                <small class="text-success">Đã có ảnh. Tải lên ảnh mới để thay thế.</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="chef_image" class="form-control" accept="image/*">
                        <small class="text-muted">Nên dùng ảnh vuông, kích thước tối thiểu 300×300px.</small>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label">Kinh nghiệm (năm)</label>
                            <input type="number" name="experience" class="form-control" min="0" max="60"
                                value="<?= $edit_data['experience'] ?? 0 ?>">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Chuyên môn ẩm thực</label>
                            <input type="text" name="specialty" class="form-control"
                                placeholder="VD: Ẩm thực Nhật, Âu, Việt..."
                                value="<?= htmlspecialchars($edit_data['specialty'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả chi tiết</label>
                        <textarea name="description" class="form-control" rows="3"
                            placeholder="Giới thiệu hành trình, thành tích của đầu bếp..."><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-quote-left me-1 text-warning"></i>Câu nói nổi
                            bật</label>
                        <input type="text" name="quote" class="form-control"
                            placeholder="VD: Nấu ăn là ngôn ngữ của tình yêu..."
                            value="<?= htmlspecialchars($edit_data['quote'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-share-alt me-1"></i>Mạng xã hội & Liên hệ</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-primary text-white"><i
                                    class="fab fa-facebook-f"></i></span>
                            <input type="text" name="facebook" class="form-control"
                                placeholder="Link Facebook hoặc username"
                                value="<?= htmlspecialchars($edit_data['facebook'] ?? '') ?>">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text" style="background:#e1306c;color:#fff"><i
                                    class="fab fa-instagram"></i></span>
                            <input type="text" name="instagram" class="form-control"
                                placeholder="Link Instagram hoặc @username"
                                value="<?= htmlspecialchars($edit_data['instagram'] ?? '') ?>">
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-secondary text-white"><i
                                    class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="Email liên hệ"
                                value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <label class="form-label">Thứ tự</label>
                            <input type="number" name="sort_order" class="form-control" min="0"
                                value="<?= $edit_data['sort_order'] ?? 0 ?>">
                        </div>
                        <div class="col-8 d-flex align-items-end gap-3 pb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="chk_active" name="is_active"
                                    <?= (!isset($edit_data) || !empty($edit_data['is_active'])) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="chk_active">Hiển thị</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="chk_feat" name="is_featured"
                                    <?= !empty($edit_data['is_featured']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="chk_feat">Ghim nổi bật</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="btn_save" class="btn btn-gold flex-grow-1">
                            <i class="fas fa-save me-2"></i><?= $edit_data ? 'Lưu thay đổi' : 'Thêm đầu bếp' ?>
                        </button>
                        <?php if ($edit_data): ?>
                            <a href="manage_chefs.php" class="btn btn-outline-secondary">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-7 col-lg-7">
            <div class="section-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0"
                        style="color:#2c3e50; border-bottom:2px solid #cda45e; padding-bottom:10px; width:100%">
                        <i class="fas fa-list me-2"></i>Danh sách đầu bếp
                        <span class="badge bg-secondary ms-2"><?= count($chefs) ?></span>
                    </h5>
                </div>

                <?php if (empty($chefs)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-user-slash fa-3x mb-3 d-block"></i>
                        Chưa có hồ sơ đầu bếp nào. Hãy thêm ngay!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.9rem">
                            <thead>
                                <tr>
                                    <th>Đầu bếp</th>
                                    <th>Chức vụ</th>
                                    <th class="text-center">KN</th>
                                    <th class="text-center">HT</th>
                                    <th class="text-center">NB</th>
                                    <th class="text-center">TT</th>
                                    <th class="text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chefs as $chef): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($chef['image'])): ?>
                                                    <img src="../public/assets/img/chefs/<?= htmlspecialchars($chef['image']) ?>"
                                                        class="chef-card-thumb" alt="<?= htmlspecialchars($chef['name']) ?>">
                                                <?php else: ?>
                                                    <div class="chef-placeholder">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($chef['name']) ?></div>
                                                    <?php if (!empty($chef['specialty'])): ?>
                                                        <small
                                                            class="text-muted"><?= htmlspecialchars($chef['specialty']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $pos_colors = [
                                                'Bếp trưởng' => 'danger',
                                                'Bếp phó' => 'warning',
                                                'Bếp chính' => 'info',
                                            ];
                                            $bc = $pos_colors[$chef['position']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $bc ?>"><?= htmlspecialchars($chef['position']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($chef['experience'] > 0): ?>
                                                <span class="badge bg-light text-dark border"><?= $chef['experience'] ?>n</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="manage_chefs.php?toggle=<?= $chef['id'] ?>&field=is_active"
                                                title="<?= $chef['is_active'] ? 'Đang hiện – Click để ẩn' : 'Đang ẩn – Click để hiện' ?>">
                                                <i class="fas fa-<?= $chef['is_active'] ? 'eye' : 'eye-slash' ?> fa-lg"
                                                    style="color:<?= $chef['is_active'] ? '#28a745' : '#aaa' ?>"></i>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <a href="manage_chefs.php?toggle=<?= $chef['id'] ?>&field=is_featured"
                                                title="<?= $chef['is_featured'] ? 'Đang ghim – Click để bỏ ghim' : 'Click để ghim nổi bật' ?>">
                                                <i class="fas fa-star fa-lg"
                                                    style="color:<?= $chef['is_featured'] ? '#cda45e' : '#ddd' ?>"></i>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">#<?= $chef['sort_order'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="manage_chefs.php?edit=<?= $chef['id'] ?>"
                                                class="btn btn-sm btn-outline-primary me-1" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_chefs.php?delete=<?= $chef['id'] ?>"
                                                class="btn btn-sm btn-outline-danger" title="Xóa"
                                                onclick="return confirm('Bạn có chắc muốn xóa đầu bếp này không?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section-card mt-4"
                style="background: linear-gradient(135deg,#f8f9fa,#fff8ee); border-left: 4px solid #cda45e;">
                <h6 class="mb-3" style="color:#cda45e"><i class="fas fa-lightbulb me-2"></i>Hướng dẫn nhanh</h6>
                <div class="row g-2" style="font-size:.85rem; color:#555">
                    <div class="col-6">
                        <i class="fas fa-eye text-success me-1"></i> Click biểu tượng mắt để bật/tắt hiển thị
                    </div>
                    <div class="col-6">
                        <i class="fas fa-star me-1" style="color:#cda45e"></i> Click ngôi sao để ghim đầu bếp nổi bật
                    </div>
                    <div class="col-6">
                        <i class="fas fa-sort-numeric-up text-info me-1"></i> Thứ tự nhỏ hơn sẽ xuất hiện trước
                    </div>
                    <div class="col-6">
                        <i class="fas fa-crown text-warning me-1"></i> Đầu bếp được ghim luôn xuất hiện đầu trang
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
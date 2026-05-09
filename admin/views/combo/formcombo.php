<?php
// =============================================================
// File: admin/views/combo/formcombo.php
// Biến từ ComboController: $mode('add'/'edit'), $old, $errors,
//   $success, $all_foods, $selected_foods,
//   $combo (edit)
// =============================================================
$mode       = $mode ?? 'add';
$is_edit    = ($mode === 'edit');
$form_title = $is_edit ? 'Chỉnh sửa Combo' : 'Thêm Combo Mới';

// ✅ FIX LỖI NHÂN ĐÔI COMBO: Lấy trực tiếp ID từ mảng $combo
$combo_id = $is_edit ? ($combo['id'] ?? 0) : 0;

$form_action = $is_edit
    ? 'ComboController.php?action=edit&id=' . $combo_id
    : 'ComboController.php?action=add';
?>
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">
<style>
.food-list-box{border:1px solid #dee2e6;padding:15px;max-height:420px;overflow-y:auto;border-radius:10px;background:#fdfdfd}
.food-check-item{padding:8px 10px;border-bottom:1px solid #f0f0f0;border-radius:6px;transition:.15s}
.food-check-item:hover{background:#f8f8f8}
.food-check-item:last-child{border-bottom:none}
.img-preview-wrap{position:relative;display:inline-block}
.img-preview-combo{max-height:150px;border-radius:10px;border:2px solid #dee2e6;object-fit:cover;display:block;margin-bottom:8px}
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Breadcrumb + Switcher -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <a href="ComboController.php?action=list" class="text-decoration-none text-muted">
                        <i class="fas fa-boxes me-1"></i>Danh sách Combo
                    </a>
                    <span>/</span>
                    <span class="text-dark fw-bold"><?= htmlspecialchars($form_title) ?></span>
                    <?php if ($is_edit && !empty($combo_id)): ?>
                    <span class="badge bg-secondary ms-1" style="font-size:10px">#<?= $combo_id ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($is_edit && !empty($all_combos_list)): ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">Chuyển sang:</span>
                    <select class="form-select form-select-sm"
                            style="max-width:220px"
                            onchange="if(this.value) window.location='ComboController.php?action=edit&id='+this.value">
                        <option value="">-- Chọn combo khác --</option>
                        <?php foreach ($all_combos_list as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"
                            <?= $c['id'] == $combo_id ? 'selected' : '' ?>>
                            #<?= $c['id'] ?> — <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <!-- Alert -->
            <?php if (!empty($success)): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 mb-4">
                <i class="fas fa-check-circle fa-lg text-success"></i>
                <div>
                    <div class="fw-bold"><?= $is_edit ? 'Cập nhật combo thành công!' : 'Thêm combo thành công!' ?></div>
                    <div class="small">
                        <a href="ComboController.php?action=list" class="alert-link">Quay lại danh sách</a>
                        <?php if (!$is_edit): ?>&nbsp;hoặc&nbsp;<a href="ComboController.php?action=add" class="alert-link">Thêm combo khác</a><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-2"></i>Vui lòng kiểm tra lại:</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?><li class="small"><?= $e ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card shadow-lg border-0" style="border-radius:16px;overflow:hidden;">
                <div class="card-header bg-dark py-3 px-4">
                    <h4 class="mb-0 text-white fw-bold">
                        <i class="fas fa-<?= $is_edit ? 'edit' : 'plus-circle' ?> me-2 text-warning"></i>
                        <?= htmlspecialchars($form_title) ?>
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data"
                          action="<?= $form_action ?>" id="form-combo">
                        
                        <!-- ✅ FIX LỖI NHÂN ĐÔI COMBO: Input hidden truyền đúng $combo_id -->
                        <?php if ($is_edit): ?>
                        <input type="hidden" name="combo_id" value="<?= $combo_id ?>">
                        <?php endif; ?>

                        <div class="row g-4">
                            <!-- Cột trái: thông tin + ảnh -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">
                                        Tên Combo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" class="form-control bg-light border-0 py-2"
                                           value="<?= htmlspecialchars($old['name']) ?>"
                                           placeholder="Ví dụ: Combo Gia Đình 4 người" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">
                                        Giá Combo (VNĐ) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="price" class="form-control bg-light border-0 py-2"
                                               value="<?= htmlspecialchars($old['price']) ?>"
                                               min="0" step="1000" required placeholder="0">
                                        <span class="input-group-text bg-light border-0 text-muted small">đ</span>
                                    </div>
                                    <div class="form-text small" id="price-display"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">Mô tả</label>
                                    <textarea name="description" class="form-control bg-light border-0" rows="3"
                                              placeholder="Ghi chú về combo..."><?= htmlspecialchars($old['description']) ?></textarea>
                                </div>

                                <!-- Ảnh -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">
                                        Hình ảnh Combo
                                        <?php if (!$is_edit): ?><span class="text-muted small fw-normal">(tùy chọn)</span><?php endif; ?>
                                    </label>

                                    <?php if ($is_edit && !empty($combo['image'])): ?>
                                    <div class="img-preview-wrap mb-2">
                                        <img id="imgCurrent"
                                             src="../../public/assets/img/combos/<?= htmlspecialchars($combo['image']) ?>"
                                             class="img-preview-combo"
                                             onerror="this.src='../../public/assets/img/combos/default-combo.jpg'"
                                             alt="Ảnh hiện tại">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="delete_image" id="delImg" value="1">
                                            <label class="form-check-label text-danger small" for="delImg">
                                                Xóa ảnh hiện tại
                                            </label>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <input type="file" name="image" id="imgInput"
                                           class="form-control bg-light border-0"
                                           accept=".jpg,.jpeg,.png,.webp"
                                           onchange="previewComboImg(this)">
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">JPG, PNG, WEBP — tối đa 5MB.
                                            <?= $is_edit ? 'Để trống nếu không đổi ảnh.' : '' ?>
                                        </small>
                                        <small id="img-err" class="text-danger fw-bold"></small>
                                    </div>
                                    <img id="imgNewPreview"
                                         style="max-height:120px;border-radius:8px;border:2px solid #cda45e;margin-top:8px;display:none;object-fit:contain"
                                         src="#" alt="Preview mới">
                                </div>
                            </div>

                            <!-- Cột phải: chọn món -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">
                                    Chọn món ăn trong Combo
                                </label>
                                <div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
                                    <input type="text" id="food-search" class="form-control form-control-sm"
                                           placeholder="🔍 Tìm món..." style="max-width:200px"
                                           oninput="filterFoodList()">
                                    <span id="selected-count" class="badge bg-primary"></span>
                                </div>
                                <div class="food-list-box" id="food-list-box">
                                    <?php foreach ($all_foods as $food):
                                        $checked = in_array($food['id'], $selected_foods) ? 'checked' : '';
                                    ?>
                                    <div class="food-check-item" data-name="<?= strtolower(htmlspecialchars($food['name'])) ?>">
                                        <div class="form-check">
                                            <input class="form-check-input food-cb" type="checkbox"
                                                   name="food_ids[]"
                                                   value="<?= (int)$food['id'] ?>"
                                                   id="f<?= $food['id'] ?>"
                                                   <?= $checked ?> onchange="updateCount()">
                                            <label class="form-check-label w-100" for="f<?= $food['id'] ?>">
                                                <span class="fw-bold"><?= htmlspecialchars($food['name']) ?></span>
                                                <small class="text-muted float-end"><?= number_format($food['price']) ?>đ</small>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($all_foods)): ?>
                                    <div class="text-muted small text-center py-3 fst-italic">Chưa có món ăn nào đang hiển thị.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll(true)">Chọn tất cả</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="selectAll(false)">Bỏ chọn</button>
                                </div>
                            </div>
                        </div>

                        <!-- Nút submit -->
                        <div class="d-flex gap-2 mt-4 pt-3 border-top justify-content-end">
                            <a href="ComboController.php?action=list"
                               class="btn btn-light px-4 rounded-pill fw-bold text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm"
                                    id="btn-submit">
                                <i class="fas fa-save me-2"></i>
                                <?= $is_edit ? 'LƯU THAY ĐỔI' : 'TẠO COMBO' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewComboImg(input) {
    var errEl   = document.getElementById('img-err');
    var preview = document.getElementById('imgNewPreview');
    errEl.textContent = '';
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var allowed = ['image/jpeg','image/png','image/webp'];
    if (!allowed.includes(file.type)) { errEl.textContent = 'Định dạng không hợp lệ (JPG/PNG/WEBP)'; return; }
    if (file.size > 5 * 1024 * 1024) { errEl.textContent = 'File quá lớn, tối đa 5MB'; return; }
    var reader = new FileReader();
    reader.onload = function (e) { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(file);
}

function filterFoodList() {
    var q = document.getElementById('food-search').value.toLowerCase();
    document.querySelectorAll('.food-check-item').forEach(function (item) {
        item.style.display = item.dataset.name.includes(q) ? '' : 'none';
    });
}

function updateCount() {
    var n = document.querySelectorAll('.food-cb:checked').length;
    var el = document.getElementById('selected-count');
    el.textContent = n > 0 ? n + ' món đã chọn' : '';
}

function selectAll(check) {
    document.querySelectorAll('.food-cb').forEach(function (cb) {
        var item = cb.closest('.food-check-item');
        if (item.style.display !== 'none') cb.checked = check;
    });
    updateCount();
}

document.getElementById('form-combo').addEventListener('submit', function () {
    var btn = document.getElementById('btn-submit');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...'; }
});

// Init count
updateCount();
</script>
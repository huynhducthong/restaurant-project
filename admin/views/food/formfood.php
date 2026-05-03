<?php
// Gọi chung layout header
include __DIR__ . '/../../../public/admin_layout_header.php';

$mode       = $mode       ?? 'add';
$is_edit    = ($mode === 'edit');
$form_title = $is_edit ? 'Chỉnh sửa món ăn' : 'Thêm món ăn mới';
$back_url   = 'FoodController.php?action=list';
$form_action = $is_edit
    ? 'FoodController.php?action=edit&id=' . ($id ?? 0)
    : 'FoodController.php?action=add';
?>
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
.drop-zone{border:2px dashed #cda45e;border-radius:14px;padding:20px;text-align:center;cursor:pointer;transition:.2s;background:#fffdf8;position:relative}
.drop-zone:hover,.drop-zone.dragover{background:#fef6e4;border-color:#a07840}
.drop-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.img-preview{width:100%;max-height:200px;object-fit:contain;border-radius:10px;display:none;margin-top:10px}
.img-preview.show{display:block}
.recipe-row{background:#f8f9fa;border-radius:10px;padding:10px 12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.recipe-row select,.recipe-row input{flex:1;min-width:120px;font-size:13px}
</style>

<div class="content-wrapper p-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <!-- Breadcrumb -->
            <div class="d-flex align-items-center gap-2 mb-4 text-muted small">
                <a href="FoodController.php?action=list" class="text-decoration-none text-muted">
                    <i class="fas fa-utensils me-1"></i>Thực đơn
                </a>
                <span>/</span>
                <span class="text-dark fw-bold"><?= htmlspecialchars($form_title) ?></span>
                <?php if ($is_edit && !empty($food)): ?>
                <span class="badge bg-secondary ms-1" style="font-size:10px">#<?= $id ?></span>
                <?php endif; ?>
            </div>

            <!-- Alert thành công -->
            <?php if (!empty($success)): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 mb-4">
                <i class="fas fa-check-circle fa-lg text-success"></i>
                <div>
                    <div class="fw-bold">
                        <?= $is_edit ? 'Cập nhật thành công!' : 'Thêm món ăn thành công!' ?>
                    </div>
                    <div class="small">
                        <a href="FoodController.php?action=list" class="alert-link">Quay lại danh sách</a>
                        <?php if (!$is_edit): ?>
                        &nbsp;hoặc&nbsp;
                        <a href="FoodController.php?action=add" class="alert-link">Thêm món khác</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alert lỗi -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-2"></i>Vui lòng kiểm tra lại:</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?><li class="small"><?= $e ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
                <div class="card-header bg-dark py-3 px-4 text-center">
                    <h4 class="mb-0 text-white" style="font-family:'Playfair Display',serif;">
                        <i class="fas fa-<?= $is_edit ? 'edit' : 'plus-circle' ?> me-2 text-warning"></i>
                        <?= htmlspecialchars($form_title) ?>
                    </h4>
                </div>

                <div class="card-body p-4 bg-white">
                    <form method="POST" enctype="multipart/form-data" id="form-food" novalidate
                          action="<?= $form_action ?>">
                        <?php if ($is_edit): ?>
                        <input type="hidden" name="food_id" value="<?= $id ?>">
                        <?php endif; ?>

                        <!-- Thông tin cơ bản -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                            <i class="fas fa-info-circle me-1 text-warning"></i>Thông tin món ăn
                        </p>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">
                                Tên món ăn <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control bg-light border-0 py-2"
                                   value="<?= htmlspecialchars($old['name']) ?>"
                                   maxlength="150" required placeholder="Ví dụ: Bít tết bò sốt tiêu">
                            <div class="form-text text-end small" id="name-count"></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">
                                    Danh mục <span class="text-danger">*</span>
                                </label>
                                <select name="category_id" class="form-select bg-light border-0 py-2" required>
                                    <option value="">-- Chọn loại --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>"
                                        <?= ($old['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">
                                    Giá bán (VNĐ) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="price" class="form-control bg-light border-0 py-2"
                                           value="<?= htmlspecialchars($old['price']) ?>"
                                           min="0" step="1000" required placeholder="0">
                                    <span class="input-group-text bg-light border-0 text-muted small">đ</span>
                                </div>
                                <div class="form-text small" id="price-display"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Mô tả chi tiết</label>
                            <textarea name="description" class="form-control bg-light border-0" rows="3"
                                      placeholder="Hương vị, thành phần chính..."><?= htmlspecialchars($old['description']) ?></textarea>
                        </div>

                        <!-- Ảnh -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                            <i class="fas fa-image me-1 text-warning"></i>Ảnh món ăn
                            <?php if (!$is_edit): ?><span class="text-danger">*</span><?php endif; ?>
                        </p>

                        <?php if ($is_edit && !empty($food['image'])): ?>
                        <div class="mb-2">
                            <div class="small text-muted mb-1">Ảnh hiện tại:</div>
                            <img src="../../public/assets/img/menu/<?= htmlspecialchars($food['image']) ?>"
                                 id="imgCurrent"
                                 style="max-height:140px;border-radius:10px;border:2px solid #f0f0f0;object-fit:cover"
                                 onerror="this.src='../../public/assets/img/menu/default.jpg'"
                                 alt="Ảnh hiện tại">
                        </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <div class="drop-zone" id="dropZone">
                                <input type="file" name="image" id="imageInput"
                                       accept=".jpg,.jpeg,.png,.webp,.gif"
                                       <?= !$is_edit ? 'required' : '' ?>>
                                <div id="drop-placeholder">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-warning mb-2"></i>
                                    <div class="fw-bold text-muted small">
                                        <?= $is_edit ? 'Thay ảnh mới (kéo thả hoặc click)' : 'Kéo thả ảnh vào đây' ?>
                                    </div>
                                    <div class="text-muted" style="font-size:12px">JPG, PNG, WEBP, GIF — tối đa 5MB
                                        <?= $is_edit ? ' · Để trống nếu không đổi ảnh' : '' ?>
                                    </div>
                                </div>
                                <img id="imgPreview" class="img-preview" src="#" alt="Preview">
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small id="file-info" class="text-muted"></small>
                                <small id="file-error" class="text-danger fw-bold"></small>
                            </div>
                        </div>

                        <!-- Định mức nguyên liệu -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                            <i class="fas fa-balance-scale me-1 text-warning"></i>Định mức nguyên liệu
                            <span class="badge bg-light text-muted border ms-1" style="font-size:10px;font-weight:400">Tùy chọn</span>
                        </p>

                        <?php if (empty($ingredients)): ?>
                        <div class="alert alert-light border small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Chưa có nguyên liệu nào trong kho.
                            <a href="../manage_inventory.php" class="alert-link">Thêm nguyên liệu</a>
                        </div>
                        <?php else: ?>
                        <div id="recipe-list" class="d-flex flex-column gap-2 mb-3">
                            <?php if ($is_edit && !empty($current_recipes)):
                                foreach ($current_recipes as $rcp): ?>
                            <div class="recipe-row">
                                <select name="ingredients[]" class="form-select form-select-sm bg-light border-0 ing-select">
                                    <option value="">-- Nguyên liệu --</option>
                                    <?php foreach ($ingredients as $ing): ?>
                                    <option value="<?= $ing['id'] ?>"
                                            data-unit="<?= htmlspecialchars($ing['unit_name']) ?>"
                                            <?= $ing['id'] == $rcp['ingredient_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ing['item_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantities[]"
                                       class="form-control form-control-sm bg-light border-0"
                                       value="<?= (float)$rcp['quantity_required'] ?>"
                                       min="0.01" step="0.01" placeholder="Số lượng" style="max-width:110px">
                                <select name="units[]" class="form-select form-select-sm bg-light border-0" style="max-width:110px">
                                    <option value="">-- Đơn vị --</option>
                                    <?php foreach ($all_units as $u): ?>
                                    <option value="<?= htmlspecialchars($u) ?>"
                                            <?= $u === $rcp['unit'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-recipe px-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" id="btn-add-recipe"
                                class="btn btn-sm btn-outline-warning rounded-pill px-3">
                            <i class="fas fa-plus me-1"></i>Thêm nguyên liệu
                        </button>
                        <?php endif; ?>

                        <!-- Nút submit -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-warning py-3 rounded-pill fw-bold text-white shadow-sm"
                                    style="background:#cda45e;border:none;" id="btn-submit">
                                <i class="fas fa-save me-2"></i>
                                <?= $is_edit ? 'LƯU THAY ĐỔI' : 'LƯU MÓN ĂN' ?>
                            </button>
                            <a href="<?= $back_url ?>" class="btn btn-light py-2 rounded-pill text-muted fw-bold border-0">
                                <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.allIngredients = <?= json_encode($ingredients) ?>;
window.allUnits       = <?= json_encode($all_units) ?>;
var IS_EDIT           = <?= $is_edit ? 'true' : 'false' ?>;
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var MAX_BYTE  = 5 * 1024 * 1024;
    var ALLOWED   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    var input     = document.getElementById('imageInput');
    var preview   = document.getElementById('imgPreview');
    var dropZone  = document.getElementById('dropZone');
    var fileInfo  = document.getElementById('file-info');
    var fileErr   = document.getElementById('file-error');

    function handleFile(file) {
        fileErr.textContent = '';
        if (!ALLOWED.includes(file.type)) { fileErr.textContent = 'Định dạng không hợp lệ'; return; }
        if (file.size > MAX_BYTE) { fileErr.textContent = 'File quá lớn, tối đa 5MB'; return; }
        fileInfo.textContent = file.name + ' — ' + (file.size / 1024 / 1024).toFixed(2) + ' MB';
        var reader = new FileReader();
        reader.onload = function (e) { preview.src = e.target.result; preview.classList.add('show'); };
        reader.readAsDataURL(file);
    }

    if (input) {
        input.addEventListener('change', function () { if (this.files[0]) handleFile(this.files[0]); });
        dropZone.addEventListener('dragover', function (e) { e.preventDefault(); this.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault(); this.classList.remove('dragover');
            var file = e.dataTransfer.files[0];
            if (file) { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; handleFile(file); }
        });
    }

    var nameInput = document.querySelector('input[name=name]');
    var nameCount = document.getElementById('name-count');
    if (nameInput && nameCount) {
        function updateCount() {
            var len = nameInput.value.length;
            nameCount.textContent = len + '/150';
            nameCount.style.color = len > 130 ? '#dc3545' : '#6c757d';
        }
        nameInput.addEventListener('input', updateCount); updateCount();
    }

    var priceInput   = document.querySelector('input[name=price]');
    var priceDisplay = document.getElementById('price-display');
    if (priceInput && priceDisplay) {
        priceInput.addEventListener('input', function () {
            var v = parseInt(this.value);
            priceDisplay.textContent = (!isNaN(v) && v > 0) ? '≈ ' + v.toLocaleString('vi-VN') + ' đồng' : '';
            priceDisplay.style.color = '#198754';
        });
        priceInput.dispatchEvent(new Event('input'));
    }

    // Định mức nguyên liệu
    var recipeList   = document.getElementById('recipe-list');
    var btnAddRecipe = document.getElementById('btn-add-recipe');

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function buildIngOpts(selId) {
        return (window.allIngredients || []).map(function (i) {
            return '<option value="' + i.id + '" data-unit="' + escHtml(i.unit_name) + '"' +
                (parseInt(selId) === i.id ? ' selected' : '') + '>' + escHtml(i.item_name) + '</option>';
        }).join('');
    }
    function buildUnitOpts(selUnit) {
        return (window.allUnits || []).map(function (u) {
            return '<option value="' + escHtml(u) + '"' + (u === selUnit ? ' selected' : '') + '>' + escHtml(u) + '</option>';
        }).join('');
    }
    function addRecipeRow(ingId, qty, unit) {
        var row = document.createElement('div');
        row.className = 'recipe-row';
        row.innerHTML =
            '<select name="ingredients[]" class="form-select form-select-sm bg-light border-0 ing-select">' +
            '<option value="">-- Nguyên liệu --</option>' + buildIngOpts(ingId) + '</select>' +
            '<input type="number" name="quantities[]" class="form-control form-control-sm bg-light border-0"' +
            ' placeholder="Số lượng" min="0.01" step="0.01" value="' + (qty || '') + '" style="max-width:110px">' +
            '<select name="units[]" class="form-select form-select-sm bg-light border-0" style="max-width:110px">' +
            '<option value="">-- Đơn vị --</option>' + buildUnitOpts(unit) + '</select>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-recipe px-2"><i class="fas fa-times"></i></button>';
        row.querySelector('.ing-select').addEventListener('change', function () {
            var dUnit = this.options[this.selectedIndex].dataset.unit || '';
            var uSel  = row.querySelector('select[name="units[]"]');
            for (var i = 0; i < uSel.options.length; i++) {
                if (uSel.options[i].value === dUnit) { uSel.selectedIndex = i; break; }
            }
        });
        row.querySelector('.btn-remove-recipe').addEventListener('click', function () { row.remove(); });
        if (recipeList) recipeList.appendChild(row);
    }

    // Gắn sự kiện cho row đã có (edit mode)
    if (recipeList) {
        recipeList.querySelectorAll('.recipe-row').forEach(function (row) {
            var ingSelect = row.querySelector('.ing-select');
            if (ingSelect) ingSelect.addEventListener('change', function () {
                var dUnit = this.options[this.selectedIndex].dataset.unit || '';
                var uSel  = row.querySelector('select[name="units[]"]');
                for (var i = 0; i < uSel.options.length; i++) {
                    if (uSel.options[i].value === dUnit) { uSel.selectedIndex = i; break; }
                }
            });
            var removeBtn = row.querySelector('.btn-remove-recipe');
            if (removeBtn) removeBtn.addEventListener('click', function () { row.remove(); });
        });
    }

    if (btnAddRecipe) btnAddRecipe.addEventListener('click', function () { addRecipeRow('', '', ''); });

    document.getElementById('form-food').addEventListener('submit', function (e) {
        if (fileErr && fileErr.textContent !== '') { e.preventDefault(); return; }
        var btn = document.getElementById('btn-submit');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...'; }
    });
})();
</script>
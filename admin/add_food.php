<?php
session_start();

// ✅ FIX: Xác thực session admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();

// --- Dữ liệu dùng chung ---
$categories  = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_units   = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// --- Giá trị form giữ lại khi lỗi (sticky form) ---
$old = [
    'name'        => '',
    'category_id' => '',
    'price'       => '',
    'description' => '',
];

$errors  = [];
$success = false;

// ============================================================
// XỬ LÝ POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lấy & trim dữ liệu
    $old['name']        = trim($_POST['name']        ?? '');
    $old['category_id'] = trim($_POST['category_id'] ?? '');
    $old['price']       = trim($_POST['price']       ?? '');
    $old['description'] = trim($_POST['description'] ?? '');

    // --- Validate cơ bản ---
    if ($old['name'] === '') {
        $errors[] = 'Tên món ăn không được để trống.';
    } elseif (mb_strlen($old['name']) > 150) {
        $errors[] = 'Tên món ăn tối đa 150 ký tự.';
    }

    if ($old['category_id'] === '') {
        $errors[] = 'Vui lòng chọn danh mục.';
    }

    $price_val = (float)$old['price'];
    if ($old['price'] === '' || $price_val < 0) {
        $errors[] = 'Giá bán phải là số không âm.';
    }

    // --- ✅ FIX: Validate ảnh upload ---
    $file_name = '';
    $upload_ok = false;

    if (empty($_FILES['image']['name'])) {
        $errors[] = 'Vui lòng chọn ảnh cho món ăn.';
    } else {
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $max_size     = 5 * 1024 * 1024; // 5 MB

        $orig_name = $_FILES['image']['name'];
        $tmp_path  = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];
        $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        // Kiểm tra extension
        if (!in_array($ext, $allowed_ext)) {
            $errors[] = 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận: JPG, PNG, WEBP, GIF.';
        }
        // Kiểm tra MIME type thực tế (không tin vào extension)
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp_path);
            finfo_close($finfo);
            if (!in_array($mime, $allowed_mime)) {
                $errors[] = 'File không phải ảnh hợp lệ. Vui lòng chọn lại.';
            } else {
                $upload_ok = true;
            }
        } else {
            $upload_ok = true; // finfo không có, bỏ qua kiểm tra MIME
        }

        // Kiểm tra dung lượng
        if ($file_size > $max_size) {
            $errors[] = 'Ảnh quá lớn. Dung lượng tối đa là 5MB.';
            $upload_ok = false;
        }

        // Tạo tên file ngẫu nhiên (chống path traversal & ghi đè)
        if ($upload_ok) {
            $file_name = bin2hex(random_bytes(12)) . '.' . $ext;
        }
    }

    // --- Lưu dữ liệu nếu không có lỗi ---
    if (empty($errors)) {
        $target = "../public/assets/img/menu/" . $file_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $errors[] = 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục <code>assets/img/menu/</code>.';
        } else {
            $db->beginTransaction();
            try {
                // Insert món ăn
                $db->prepare(
                    "INSERT INTO foods (name, category_id, price, description, image, is_active)
                     VALUES (?, ?, ?, ?, ?, 1)"
                )->execute([
                    $old['name'],
                    (int)$old['category_id'],
                    $price_val,
                    $old['description'],
                    $file_name,
                ]);
                $new_id = (int)$db->lastInsertId();

                // Lưu định mức nguyên liệu nếu có
                if (!empty($_POST['ingredients'])) {
                    $r_stmt = $db->prepare(
                        "INSERT INTO food_recipes (food_id, ingredient_id, quantity_required, unit)
                         VALUES (?, ?, ?, ?)"
                    );
                    foreach ($_POST['ingredients'] as $idx => $ing_id) {
                        if (empty($ing_id)) continue;
                        $qty  = (float)($_POST['quantities'][$idx] ?? 0);
                        $unit = trim($_POST['units'][$idx] ?? '');
                        if ($qty > 0 && $unit !== '') {
                            $r_stmt->execute([$new_id, (int)$ing_id, $qty, $unit]);
                        }
                    }
                }

                $db->commit();
                $success = true;

                // Reset form
                $old = ['name' => '', 'category_id' => '', 'price' => '', 'description' => ''];

            } catch (Exception $e) {
                $db->rollBack();
                // Xóa ảnh đã upload nếu DB lỗi
                if (file_exists($target)) @unlink($target);
                $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">

<style>
/* Drop zone ảnh */
.drop-zone {
    border: 2px dashed #cda45e;
    border-radius: 14px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: .2s;
    background: #fffdf8;
    position: relative;
}
.drop-zone:hover, .drop-zone.dragover {
    background: #fef6e4;
    border-color: #a07840;
}
.drop-zone input[type=file] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
.img-preview {
    width: 100%;
    max-height: 200px;
    object-fit: contain;
    border-radius: 10px;
    display: none;
    margin-top: 10px;
}
.img-preview.show { display: block; }

/* Định mức rows */
.recipe-row {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}
.recipe-row select, .recipe-row input {
    flex: 1;
    min-width: 120px;
    font-size: 13px;
}

/* Progress bar upload */
.upload-progress { display: none; }
</style>

<div class="content-wrapper p-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <!-- Header breadcrumb -->
            <div class="d-flex align-items-center gap-2 mb-4 text-muted small">
                <a href="manage_foods.php" class="text-decoration-none text-muted">
                    <i class="fas fa-utensils me-1"></i>Thực đơn
                </a>
                <span>/</span>
                <span class="text-dark fw-bold">Thêm món mới</span>
            </div>

            <!-- Alert thành công -->
            <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 mb-4">
                <i class="fas fa-check-circle fa-lg text-success"></i>
                <div>
                    <div class="fw-bold">Thêm món ăn thành công!</div>
                    <div class="small">
                        <a href="manage_foods.php" class="alert-link">Quay lại danh sách</a>
                        &nbsp;hoặc&nbsp;
                        <a href="add_food.php" class="alert-link">Thêm món khác</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alert lỗi -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-2"></i>Vui lòng kiểm tra lại:</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                    <li class="small"><?= $e ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
                <div class="card-header bg-dark py-3 px-4 text-center">
                    <h4 class="mb-0 text-white" style="font-family:'Playfair Display',serif;">
                        <i class="fas fa-plus-circle me-2 text-warning"></i>Thêm món ăn mới
                    </h4>
                </div>

                <div class="card-body p-4 bg-white">
                    <form method="POST" enctype="multipart/form-data" id="form-add-food" novalidate>

                        <!-- ===== THÔNG TIN CƠ BẢN ===== -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                            <i class="fas fa-info-circle me-1 text-warning"></i>Thông tin món ăn
                        </p>

                        <!-- Tên món -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">
                                Tên món ăn <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name"
                                   class="form-control bg-light border-0 py-2"
                                   placeholder="Ví dụ: Bít tết bò sốt tiêu"
                                   value="<?= htmlspecialchars($old['name']) ?>"
                                   maxlength="150" required>
                            <div class="form-text text-end" id="name-count" style="font-size:11px"></div>
                        </div>

                        <!-- Danh mục + Giá -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">
                                    Danh mục <span class="text-danger">*</span>
                                </label>
                                <select name="category_id" class="form-select bg-light border-0 py-2" required>
                                    <option value="">-- Chọn loại --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>"
                                        <?= $old['category_id'] == $cat['id'] ? 'selected' : '' ?>>
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
                                    <input type="number" name="price"
                                           class="form-control bg-light border-0 py-2"
                                           placeholder="0" min="0" step="1000"
                                           value="<?= htmlspecialchars($old['price']) ?>" required>
                                    <span class="input-group-text bg-light border-0 text-muted small">đ</span>
                                </div>
                                <div class="form-text small" id="price-display"></div>
                            </div>
                        </div>

                        <!-- Mô tả -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Mô tả chi tiết</label>
                            <textarea name="description" class="form-control bg-light border-0" rows="3"
                                      placeholder="Hương vị, thành phần chính..."><?= htmlspecialchars($old['description']) ?></textarea>
                        </div>

                        <!-- ===== ẢNH MÓN ĂN ===== -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                            <i class="fas fa-image me-1 text-warning"></i>Ảnh món ăn
                        </p>

                        <div class="mb-4">
                            <div class="drop-zone" id="dropZone">
                                <input type="file" name="image" id="imageInput"
                                       accept=".jpg,.jpeg,.png,.webp,.gif" required>
                                <div id="drop-placeholder">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-warning mb-2"></i>
                                    <div class="fw-bold text-muted small">Kéo thả ảnh vào đây</div>
                                    <div class="text-muted" style="font-size:12px">hoặc click để chọn file</div>
                                    <div class="text-muted mt-1" style="font-size:11px">JPG, PNG, WEBP, GIF — tối đa 5MB</div>
                                </div>
                                <img id="imgPreview" class="img-preview" src="#" alt="Preview">
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small id="file-info" class="text-muted"></small>
                                <small id="file-error" class="text-danger fw-bold"></small>
                            </div>
                        </div>

                        <!-- ===== ĐỊNH MỨC NGUYÊN LIỆU ===== -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                            <i class="fas fa-balance-scale me-1 text-warning"></i>Định mức nguyên liệu
                            <span class="badge bg-light text-muted border ms-1" style="font-size:10px;font-weight:400;">Tùy chọn</span>
                        </p>

                        <?php if (empty($ingredients)): ?>
                        <div class="alert alert-light border small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Chưa có nguyên liệu nào trong kho.
                            <a href="manage_inventory.php" class="alert-link">Thêm nguyên liệu</a> trước.
                        </div>
                        <?php else: ?>
                        <div id="recipe-list" class="d-flex flex-column gap-2 mb-3"></div>

                        <button type="button" id="btn-add-recipe"
                                class="btn btn-sm btn-outline-warning rounded-pill px-3">
                            <i class="fas fa-plus me-1"></i>Thêm nguyên liệu
                        </button>

                        <div class="mt-2 p-2 rounded-3 bg-light small text-muted">
                            <i class="fas fa-lightbulb me-1 text-warning"></i>
                            Thiết lập định mức giúp hệ thống tự động trừ kho mỗi khi có đơn hàng.
                        </div>
                        <?php endif; ?>

                        <!-- ===== NÚT SUBMIT ===== -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-warning py-3 rounded-pill fw-bold text-white shadow-sm"
                                    style="background:#cda45e;border:none;" id="btn-submit">
                                <i class="fas fa-save me-2"></i>LƯU MÓN ĂN
                            </button>
                            <a href="manage_foods.php"
                               class="btn btn-light py-2 rounded-pill text-muted fw-bold border-0">
                                <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Data cho JS -->
<script>
window.allIngredients = <?= json_encode($ingredients) ?>;
window.allUnits       = <?= json_encode($all_units) ?>;
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {

    /* ========== PREVIEW ẢNH + VALIDATE CLIENT-SIDE ========== */
    const MAX_MB   = 5;
    const MAX_BYTE = MAX_MB * 1024 * 1024;
    const ALLOWED  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    const input    = document.getElementById('imageInput');
    const preview  = document.getElementById('imgPreview');
    const dropZone = document.getElementById('dropZone');
    const placeholder = document.getElementById('drop-placeholder');
    const fileInfo = document.getElementById('file-info');
    const fileErr  = document.getElementById('file-error');

    function handleFile(file) {
        fileErr.textContent = '';
        if (!ALLOWED.includes(file.type)) {
            fileErr.textContent = 'Định dạng không hợp lệ (JPG/PNG/WEBP/GIF)';
            return;
        }
        if (file.size > MAX_BYTE) {
            fileErr.textContent = 'File quá lớn. Tối đa ' + MAX_MB + 'MB.';
            return;
        }
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.textContent = file.name + ' — ' + sizeMB + ' MB';

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.add('show');
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    input.addEventListener('change', function () {
        if (this.files[0]) handleFile(this.files[0]);
    });

    // Drag & drop
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', function () {
        this.classList.remove('dragover');
    });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) {
            // Gán file vào input
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            handleFile(file);
        }
    });

    /* ========== ĐẾM KÝ TỰ TÊN MÓN ========== */
    const nameInput  = document.querySelector('input[name=name]');
    const nameCount  = document.getElementById('name-count');
    function updateCount() {
        const len = nameInput.value.length;
        nameCount.textContent = len + '/150';
        nameCount.style.color = len > 130 ? '#dc3545' : '#6c757d';
    }
    nameInput.addEventListener('input', updateCount);
    updateCount();

    /* ========== FORMAT GIÁ ========== */
    const priceInput   = document.querySelector('input[name=price]');
    const priceDisplay = document.getElementById('price-display');
    priceInput.addEventListener('input', function () {
        const v = parseInt(this.value);
        if (!isNaN(v) && v > 0) {
            priceDisplay.textContent = '≈ ' + v.toLocaleString('vi-VN') + ' đồng';
            priceDisplay.style.color = '#198754';
        } else {
            priceDisplay.textContent = '';
        }
    });
    if (priceInput.value) priceInput.dispatchEvent(new Event('input'));

    /* ========== ĐỊNH MỨC NGUYÊN LIỆU ========== */
    const recipeList   = document.getElementById('recipe-list');
    const btnAddRecipe = document.getElementById('btn-add-recipe');
    if (!recipeList || !btnAddRecipe) return;

    const ingredients = window.allIngredients || [];
    const units       = window.allUnits || [];

    function buildIngOpts(selectedId) {
        return ingredients.map(function (i) {
            const sel = (selectedId && parseInt(selectedId) === i.id) ? 'selected' : '';
            return '<option value="' + i.id + '" data-unit="' + escHtml(i.unit_name) + '" ' + sel + '>'
                 + escHtml(i.item_name) + '</option>';
        }).join('');
    }

    function buildUnitOpts(selectedUnit) {
        return units.map(function (u) {
            const sel = (u === selectedUnit) ? 'selected' : '';
            return '<option value="' + escHtml(u) + '" ' + sel + '>' + escHtml(u) + '</option>';
        }).join('');
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function addRecipeRow(ingId, qty, unit) {
        const row = document.createElement('div');
        row.className = 'recipe-row';
        row.innerHTML =
            '<select name="ingredients[]" class="form-select form-select-sm bg-light border-0 ing-select">'
          + '<option value="">-- Nguyên liệu --</option>'
          + buildIngOpts(ingId)
          + '</select>'
          + '<input type="number" name="quantities[]" class="form-control form-control-sm bg-light border-0"'
          + ' placeholder="Số lượng" min="0.01" step="0.01" value="' + (qty || '') + '" style="max-width:110px">'
          + '<select name="units[]" class="form-select form-select-sm bg-light border-0" style="max-width:110px">'
          + '<option value="">-- Đơn vị --</option>'
          + buildUnitOpts(unit)
          + '</select>'
          + '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-recipe px-2">'
          + '<i class="fas fa-times"></i></button>';

        // Tự điền đơn vị mặc định khi chọn nguyên liệu
        row.querySelector('.ing-select').addEventListener('change', function () {
            const opt  = this.options[this.selectedIndex];
            const dUnit = opt.dataset.unit || '';
            const uSel  = row.querySelector('select[name="units[]"]');
            for (let i = 0; i < uSel.options.length; i++) {
                if (uSel.options[i].value === dUnit) {
                    uSel.selectedIndex = i;
                    break;
                }
            }
        });

        row.querySelector('.btn-remove-recipe').addEventListener('click', function () {
            row.remove();
        });

        recipeList.appendChild(row);
    }

    btnAddRecipe.addEventListener('click', function () {
        addRecipeRow('', '', '');
    });

    /* ========== VALIDATE SUBMIT ========== */
    document.getElementById('form-add-food').addEventListener('submit', function (e) {
        if (fileErr.textContent !== '') {
            e.preventDefault();
            fileErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        // Disable nút tránh double submit
        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
    });

})();
</script>
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

// ✅ FIX: Lấy $id từ GET hoặc POST (tránh undefined khi form submit)
$id = (int)($_GET['id'] ?? $_POST['food_id'] ?? 0);
if ($id <= 0) {
    header('Location: manage_foods.php?error=notfound'); exit;
}

// Load dữ liệu món ăn
$stmt = $db->prepare("SELECT * FROM foods WHERE id = ?");
$stmt->execute([$id]);
$food = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ FIX: Redirect thay vì die()
if (!$food) {
    header('Location: manage_foods.php?error=notfound'); exit;
}

// Load định mức nguyên liệu hiện tại
$recipe_stmt = $db->prepare(
    "SELECT r.*, i.item_name FROM food_recipes r
     JOIN inventory i ON r.ingredient_id = i.id
     WHERE r.food_id = ? ORDER BY i.item_name"
);
$recipe_stmt->execute([$id]);
$current_recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- State ---
$errors  = [];
$success = false;

// Giá trị mặc định = dữ liệu hiện có
$old = [
    'name'        => $food['name'],
    'category_id' => $food['category_id'],
    'price'       => $food['price'],
    'description' => $food['description'],
];

// ============================================================
// XỬ LÝ POST — CẬP NHẬT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lấy & trim dữ liệu
    $old['name']        = trim($_POST['name']        ?? '');
    $old['category_id'] = trim($_POST['category_id'] ?? '');
    $old['price']       = trim($_POST['price']       ?? '');
    $old['description'] = trim($_POST['description'] ?? '');

    // --- Validate ---
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
    $new_image = ''; // tên file mới (rỗng = giữ ảnh cũ)
    $do_replace_image = false;

    if (!empty($_FILES['image']['name'])) {
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $max_size     = 5 * 1024 * 1024;

        $orig_name = $_FILES['image']['name'];
        $tmp_path  = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];
        $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $errors[] = 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận: JPG, PNG, WEBP, GIF.';
        } elseif ($file_size > $max_size) {
            $errors[] = 'Ảnh quá lớn. Tối đa 5MB.';
        } else {
            // Validate MIME thực
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $tmp_path);
                finfo_close($finfo);
                if (!in_array($mime, $allowed_mime)) {
                    $errors[] = 'File không phải ảnh hợp lệ. Vui lòng chọn lại.';
                } else {
                    $do_replace_image = true;
                }
            } else {
                $do_replace_image = true;
            }
        }

        if ($do_replace_image) {
            // ✅ Tên file ngẫu nhiên chống ghi đè & path traversal
            $new_image = bin2hex(random_bytes(12)) . '.' . $ext;
        }
    }

    // --- Lưu nếu không lỗi ---
    if (empty($errors)) {
        $final_image = $food['image']; // mặc định giữ ảnh cũ

        if ($do_replace_image) {
            $target = "../public/assets/img/menu/" . $new_image;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $errors[] = 'Không thể tải ảnh lên. Kiểm tra quyền ghi thư mục <code>assets/img/menu/</code>.';
            } else {
                // ✅ FIX: Xóa ảnh cũ sau khi upload thành công
                $old_path = "../public/assets/img/menu/" . $food['image'];
                if ($food['image'] && file_exists($old_path)) {
                    @unlink($old_path);
                }
                $final_image = $new_image;
            }
        }

        if (empty($errors)) {
            $db->beginTransaction();
            try {
                // Cập nhật thông tin món ăn
                $db->prepare(
                    "UPDATE foods SET name=?, category_id=?, price=?, description=?, image=? WHERE id=?"
                )->execute([
                    $old['name'],
                    (int)$old['category_id'],
                    $price_val,
                    $old['description'],
                    $final_image,
                    $id,
                ]);

                // Cập nhật định mức: xóa hết cũ rồi insert lại
                $db->prepare("DELETE FROM food_recipes WHERE food_id = ?")->execute([$id]);

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
                            $r_stmt->execute([$id, (int)$ing_id, $qty, $unit]);
                        }
                    }
                }

                $db->commit();
                $success = true;

                // Reload lại dữ liệu sau khi lưu
                $stmt->execute([$id]);
                $food = $stmt->fetch(PDO::FETCH_ASSOC);

                $recipe_stmt->execute([$id]);
                $current_recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);

                $old = [
                    'name'        => $food['name'],
                    'category_id' => $food['category_id'],
                    'price'       => $food['price'],
                    'description' => $food['description'],
                ];

            } catch (Exception $e) {
                $db->rollBack();
                // Rollback: xóa ảnh mới nếu đã upload
                if ($do_replace_image && isset($target) && file_exists($target)) {
                    @unlink($target);
                }
                $errors[] = 'Lỗi hệ thống: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">

<style>
.img-current {
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid #f0f0f0;
    transition: .2s;
}
.drop-zone {
    border: 2px dashed #cda45e;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
    cursor: pointer;
    transition: .2s;
    background: #fffdf8;
    position: relative;
}
.drop-zone:hover, .drop-zone.dragover { background: #fef6e4; border-color: #a07840; }
.drop-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0;
    cursor: pointer; width: 100%; height: 100%;
}
.img-new-preview {
    width: 100%;
    max-height: 160px;
    object-fit: contain;
    border-radius: 10px;
    display: none;
    margin-top: 8px;
    border: 2px solid #cda45e;
}
.img-new-preview.show { display: block; }
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
    min-width: 110px;
    font-size: 13px;
}
.section-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: #999;
    letter-spacing: .05em;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 14px;
}
</style>

<div class="content-wrapper p-4">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">

            <!-- Breadcrumb -->
            <div class="d-flex align-items-center gap-2 mb-4 text-muted small">
                <a href="manage_foods.php" class="text-decoration-none text-muted">
                    <i class="fas fa-utensils me-1"></i>Thực đơn
                </a>
                <span>/</span>
                <span class="text-dark fw-bold">Sửa: <?= htmlspecialchars($food['name']) ?></span>
            </div>

            <!-- Alert thành công -->
            <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 mb-4" id="alert-success">
                <i class="fas fa-check-circle fa-lg text-success"></i>
                <div>
                    <div class="fw-bold">Cập nhật thành công!</div>
                    <div class="small">
                        <a href="manage_foods.php" class="alert-link">Quay lại danh sách</a>
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
                <div class="card-header bg-dark py-3 px-4 d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 text-white" style="font-family:'Playfair Display',serif;">
                        <i class="fas fa-edit me-2 text-warning"></i>Chỉnh sửa món ăn
                    </h4>
                    <span class="badge bg-secondary opacity-75">#<?= $id ?></span>
                </div>

                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data" id="form-edit" novalidate>
                        <input type="hidden" name="food_id" value="<?= $id ?>">

                        <div class="row g-4">

                            <!-- ===== CỘT TRÁI: Thông tin cơ bản ===== -->
                            <div class="col-md-7">

                                <div class="section-title">
                                    <i class="fas fa-info-circle me-1 text-warning"></i>Thông tin món ăn
                                </div>

                                <!-- Tên món -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">
                                        Tên món ăn <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name"
                                           class="form-control bg-light border-0 py-2"
                                           value="<?= htmlspecialchars($old['name']) ?>"
                                           maxlength="150" required>
                                    <div class="form-text text-end small" id="name-count"></div>
                                </div>

                                <!-- Danh mục + Giá -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
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
                                    <div class="col-6">
                                        <label class="form-label fw-bold small text-muted">
                                            Giá bán <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="price"
                                                   class="form-control bg-light border-0 py-2"
                                                   value="<?= htmlspecialchars($old['price']) ?>"
                                                   min="0" step="1000" required>
                                            <span class="input-group-text bg-light border-0 text-muted small">đ</span>
                                        </div>
                                        <div class="form-text small" id="price-display"></div>
                                    </div>
                                </div>

                                <!-- Mô tả -->
                                <div class="mb-0">
                                    <label class="form-label fw-bold small text-muted">Mô tả món ăn</label>
                                    <textarea name="description"
                                              class="form-control bg-light border-0" rows="4"
                                              placeholder="Hương vị, thành phần chính..."><?= htmlspecialchars($old['description']) ?></textarea>
                                </div>
                            </div>

                            <!-- ===== CỘT PHẢI: Ảnh ===== -->
                            <div class="col-md-5">

                                <div class="section-title">
                                    <i class="fas fa-image me-1 text-warning"></i>Ảnh món ăn
                                </div>

                                <!-- Ảnh hiện tại -->
                                <div class="mb-2">
                                    <div class="small text-muted mb-1">Ảnh hiện tại:</div>
                                    <img id="imgCurrent"
                                         src="../public/assets/img/menu/<?= htmlspecialchars($food['image']) ?>"
                                         class="img-current"
                                         onerror="this.src='../public/assets/img/menu/default.jpg'"
                                         alt="<?= htmlspecialchars($food['name']) ?>">
                                </div>

                                <!-- Drag & Drop zone -->
                                <div class="drop-zone" id="dropZone">
                                    <input type="file" id="imageInput" name="image"
                                           accept=".jpg,.jpeg,.png,.webp,.gif">
                                    <i class="fas fa-cloud-upload-alt text-warning mb-1" style="font-size:20px"></i>
                                    <div class="small text-muted fw-bold">Thay ảnh mới</div>
                                    <div class="text-muted" style="font-size:11px">Kéo thả hoặc click chọn file</div>
                                    <div class="text-muted" style="font-size:10px">JPG, PNG, WEBP, GIF — max 5MB</div>
                                    <img id="imgNewPreview" class="img-new-preview" src="#" alt="Preview mới">
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small id="file-info" class="text-muted"></small>
                                    <small id="file-error" class="text-danger fw-bold"></small>
                                </div>

                            </div>
                        </div>

                        <!-- ===== ĐỊNH MỨC NGUYÊN LIỆU (inline editable) ===== -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="section-title mb-3">
                                <i class="fas fa-balance-scale me-1 text-warning"></i>Định mức nguyên liệu
                                <span class="text-muted fw-normal"
                                      style="font-size:10px;text-transform:none;letter-spacing:0;">
                                    — Lưu form để cập nhật định mức
                                </span>
                            </div>

                            <?php if (empty($ingredients)): ?>
                            <div class="alert alert-light border small text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Chưa có nguyên liệu nào trong kho.
                                <a href="manage_inventory.php" class="alert-link">Thêm nguyên liệu</a>
                            </div>
                            <?php else: ?>

                            <div id="recipe-list" class="d-flex flex-column gap-2 mb-3">
                                <?php foreach ($current_recipes as $rcp): ?>
                                <!-- Row định mức hiện có (pre-filled) -->
                                <div class="recipe-row" data-existing="1">
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
                                    <input type="number"
                                           name="quantities[]"
                                           class="form-control form-control-sm bg-light border-0"
                                           value="<?= (float)$rcp['quantity_required'] ?>"
                                           min="0.01" step="0.01"
                                           placeholder="Số lượng"
                                           style="max-width:110px">
                                    <select name="units[]"
                                            class="form-select form-select-sm bg-light border-0"
                                            style="max-width:110px">
                                        <option value="">-- Đơn vị --</option>
                                        <?php foreach ($all_units as $u): ?>
                                        <option value="<?= htmlspecialchars($u) ?>"
                                                <?= $u === $rcp['unit'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-remove-recipe px-2">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" id="btn-add-recipe"
                                    class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                <i class="fas fa-plus me-1"></i>Thêm nguyên liệu
                            </button>

                            <?php endif; ?>
                        </div>

                        <!-- ===== NÚT ACTION ===== -->
                        <div class="d-flex gap-2 mt-4 pt-3 border-top justify-content-end">
                            <a href="manage_foods.php"
                               class="btn btn-light px-4 rounded-pill fw-bold text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Hủy bỏ
                            </a>
                            <button type="submit" id="btn-submit"
                                    class="btn btn-warning px-5 rounded-pill fw-bold text-white shadow-sm"
                                    style="background:#cda45e;border:none;">
                                <i class="fas fa-save me-2"></i>LƯU THAY ĐỔI
                            </button>
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

    /* ===== PREVIEW ẢNH MỚI + VALIDATE ===== */
    const MAX_BYTE = 5 * 1024 * 1024;
    const ALLOWED  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    const input    = document.getElementById('imageInput');
    const dropZone = document.getElementById('dropZone');
    const preview  = document.getElementById('imgNewPreview');
    const fileInfo = document.getElementById('file-info');
    const fileErr  = document.getElementById('file-error');

    function handleFile(file) {
        fileErr.textContent = '';
        if (!ALLOWED.includes(file.type)) {
            fileErr.textContent = 'Định dạng không hợp lệ (JPG/PNG/WEBP/GIF)';
            return;
        }
        if (file.size > MAX_BYTE) {
            fileErr.textContent = 'File quá lớn, tối đa 5MB';
            return;
        }
        fileInfo.textContent = file.name + ' — ' + (file.size / 1024 / 1024).toFixed(2) + ' MB';
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.add('show');
        };
        reader.readAsDataURL(file);
    }

    input.addEventListener('change', function () {
        if (this.files[0]) handleFile(this.files[0]);
    });

    dropZone.addEventListener('dragover',  function (e) { e.preventDefault(); this.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', function ()  { this.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        handleFile(file);
    });

    /* ===== ĐẾM KÝ TỰ TÊN ===== */
    const nameInput = document.querySelector('input[name=name]');
    const nameCount = document.getElementById('name-count');
    function updateCount() {
        const len = nameInput.value.length;
        nameCount.textContent = len + '/150';
        nameCount.style.color = len > 130 ? '#dc3545' : '#6c757d';
    }
    nameInput.addEventListener('input', updateCount);
    updateCount();

    /* ===== FORMAT GIÁ ===== */
    const priceInput   = document.querySelector('input[name=price]');
    const priceDisplay = document.getElementById('price-display');
    priceInput.addEventListener('input', function () {
        const v = parseInt(this.value);
        priceDisplay.textContent = (!isNaN(v) && v > 0)
            ? '≈ ' + v.toLocaleString('vi-VN') + ' đồng'
            : '';
        priceDisplay.style.color = '#198754';
    });
    priceInput.dispatchEvent(new Event('input'));

    /* ===== ĐỊNH MỨC NGUYÊN LIỆU ===== */
    const recipeList   = document.getElementById('recipe-list');
    const btnAddRecipe = document.getElementById('btn-add-recipe');
    if (!recipeList || !btnAddRecipe) return;

    const ingredients = window.allIngredients || [];
    const units       = window.allUnits || [];

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildIngOpts(selId) {
        return ingredients.map(function (i) {
            return '<option value="' + i.id + '" data-unit="' + escHtml(i.unit_name) + '"'
                + (parseInt(selId) === i.id ? ' selected' : '') + '>'
                + escHtml(i.item_name) + '</option>';
        }).join('');
    }

    function buildUnitOpts(selUnit) {
        return units.map(function (u) {
            return '<option value="' + escHtml(u) + '"' + (u === selUnit ? ' selected' : '') + '>'
                + escHtml(u) + '</option>';
        }).join('');
    }

    function addRecipeRow(ingId, qty, unit) {
        const row = document.createElement('div');
        row.className = 'recipe-row';
        row.innerHTML =
            '<select name="ingredients[]" class="form-select form-select-sm bg-light border-0 ing-select">'
          + '<option value="">-- Nguyên liệu --</option>'
          + buildIngOpts(ingId)
          + '</select>'
          + '<input type="number" name="quantities[]"'
          + ' class="form-control form-control-sm bg-light border-0"'
          + ' placeholder="Số lượng" min="0.01" step="0.01"'
          + ' value="' + (qty || '') + '" style="max-width:110px">'
          + '<select name="units[]" class="form-select form-select-sm bg-light border-0" style="max-width:110px">'
          + '<option value="">-- Đơn vị --</option>'
          + buildUnitOpts(unit)
          + '</select>'
          + '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-recipe px-2">'
          + '<i class="fas fa-times"></i></button>';

        // Auto-fill đơn vị khi chọn nguyên liệu
        row.querySelector('.ing-select').addEventListener('change', function () {
            const dUnit = this.options[this.selectedIndex].dataset.unit || '';
            const uSel  = row.querySelector('select[name="units[]"]');
            for (let i = 0; i < uSel.options.length; i++) {
                if (uSel.options[i].value === dUnit) { uSel.selectedIndex = i; break; }
            }
        });

        row.querySelector('.btn-remove-recipe').addEventListener('click', function () {
            row.remove();
        });

        recipeList.appendChild(row);
    }

    // Gắn sự kiện cho các row định mức sẵn có từ PHP
    document.querySelectorAll('.recipe-row').forEach(function (row) {
        // Auto-fill đơn vị
        row.querySelector('.ing-select').addEventListener('change', function () {
            const dUnit = this.options[this.selectedIndex].dataset.unit || '';
            const uSel  = row.querySelector('select[name="units[]"]');
            for (let i = 0; i < uSel.options.length; i++) {
                if (uSel.options[i].value === dUnit) { uSel.selectedIndex = i; break; }
            }
        });
        // Nút xóa row
        row.querySelector('.btn-remove-recipe').addEventListener('click', function () {
            row.remove();
        });
    });

    btnAddRecipe.addEventListener('click', function () {
        addRecipeRow('', '', '');
    });

    /* ===== CHỐNG DOUBLE SUBMIT ===== */
    document.getElementById('form-edit').addEventListener('submit', function (e) {
        if (fileErr.textContent !== '') {
            e.preventDefault();
            fileErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
    });

    /* ===== TỰ ẨN ALERT SUCCESS ===== */
    const alertSuccess = document.getElementById('alert-success');
    if (alertSuccess) setTimeout(function () { alertSuccess.style.opacity = 0; }, 3500);

})();
</script>
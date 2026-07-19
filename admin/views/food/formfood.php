<?php
// Gọi chung layout header
include __DIR__ . '/../../../public/admin_layout_header.php';

$action     = $action ?? 'add';
$is_edit    = ($action === 'edit');
$form_title = $is_edit ? 'Chỉnh sửa món ăn' : 'Thêm món ăn mới';
$back_url   = 'FoodController.php?action=list';
$form_action = $is_edit
    ? 'FoodController.php?action=edit&id=' . ($id ?? 0)
    : 'FoodController.php?action=add';
?>
<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">



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
                    <h4 class="mb-0 text-white" style="font-family:'Cormorant Garamond', serif;">
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

                        
                        <!-- Nav Tabs -->
                        <ul class="nav nav-pills mb-4 gap-2" id="foodFormTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active rounded-pill fw-bold px-4" id="basic-tab" data-bs-toggle="pill" data-bs-target="#basic" type="button" role="tab" style="font-size:14px;"><i class="fas fa-info-circle me-1"></i> Cơ bản</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill fw-bold px-4" id="fine-dining-tab" data-bs-toggle="pill" data-bs-target="#fine-dining" type="button" role="tab" style="font-size:14px;"><i class="fas fa-glass-cheers me-1"></i> Trải nghiệm</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill fw-bold px-4" id="recipe-tab" data-bs-toggle="pill" data-bs-target="#recipe" type="button" role="tab" style="font-size:14px;"><i class="fas fa-balance-scale me-1"></i> Thành phần</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="foodFormTabsContent">
                            <!-- TAB: CƠ BẢN -->
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
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
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Chủ đề (Tùy chọn)</label>
                                <select name="theme_id" class="form-select bg-light border-0 py-2">
                                    <option value="">-- Không thuộc chủ đề nào --</option>
                                    <?php foreach ($all_themes as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= (($old['theme_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">
                                    Giá bán (VNĐ) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="price" class="form-control bg-light border-0 py-2"
                                           value="<?= isset($old['price']) && $old['price'] !== '' ? (float)$old['price'] : '' ?>"
                                           min="0" step="1000" required placeholder="0">
                                    <span class="input-group-text bg-light border-0 text-muted small">đ</span>
                                </div>
                                <div class="form-text small" id="price-display"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Giới hạn Toppings tối đa</label>
                                <div class="input-group">
                                    <input type="number" name="max_toppings" class="form-control bg-light border-0 py-2"
                                           value="<?= htmlspecialchars($old['max_toppings'] ?? '4') ?>"
                                           min="0" step="1" required placeholder="4">
                                    <span class="input-group-text bg-light border-0 text-muted small">toppings</span>
                                </div>
                                <div class="form-text small">Đặt 0 nếu không giới hạn số lượng topping được chọn.</div>
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

                                                    </div>
                            <!-- TAB: TRẢI NGHIỆM -->
                            <div class="tab-pane fade" id="fine-dining" role="tabpanel">
<div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Chất gây dị ứng (FDA Standard) <span class="badge bg-light text-muted border ms-1" style="font-size:10px;font-weight:400">Tùy chọn</span></label>
                            <div class="d-flex flex-wrap gap-2 p-3 bg-light rounded" style="border: 1px solid #f0f0f0;">
                                <?php 
                                $algopts = ['Sữa', 'Trứng', 'Đậu phộng', 'Đậu nành', 'Lúa mì / Gluten', 'Cá', 'Hải sản có vỏ', 'Hải sản thân mềm', 'Mè / Vừng', 'Mù tạt', 'Quả hạch', 'Sulphites', 'Đậu Lupin'];
                                $current_algs = array_map('trim', explode(',', $old['allergens'] ?? ''));
                                foreach($algopts as $alg): ?>
                                <label class="d-flex align-items-center gap-2 m-0" style="cursor:pointer; font-size:13px; width:30%; font-weight:500;">
                                    <input type="checkbox" name="allergens[]" value="<?= $alg ?>" <?= in_array($alg, $current_algs) ? 'checked' : '' ?> style="accent-color:#d64545; width:16px; height:16px;"> <?= $alg ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text small mt-2">Đánh dấu nếu món ăn chứa các thành phần dị ứng tiêu chuẩn.</div>
                        </div>

                        <!-- Trải nghiệm Fine Dining (Wine Pairing & Chef Note) -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2 mt-4">
                            <i class="fas fa-glass-cheers me-1 text-warning"></i>Trải nghiệm Fine Dining
                        </p>

                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted">Gợi ý Rượu vang (Wine Pairing)</label>
                                <select name="wine_pairing_id" class="form-select bg-light border-0 py-2">
                                    <option value="">-- Không chọn --</option>
                                    <?php if(!empty($drinks)): foreach ($drinks as $drink): ?>
                                    <option value="<?= htmlspecialchars($drink['id']) ?>"
                                        <?= (($old['wine_pairing_id'] ?? '') == $drink['id']) ? 'selected' : '' ?>>
                                        🍷 <?= htmlspecialchars($drink['name']) ?>
                                    </option>
                                    <?php endforeach; endif; ?>
                                </select>
                                <div class="form-text small">Chọn 1 loại đồ uống để đề xuất dùng kèm.</div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-muted">Ghi chú của Bếp trưởng (Chef Note)</label>
                                <textarea name="chef_note" class="form-control bg-light border-0" rows="2"
                                          placeholder="Ví dụ: Món ăn sẽ ngon nhất khi thưởng thức cùng vang đỏ Cabernet Sauvignon..."><?= htmlspecialchars($old['chef_note'] ?? '') ?></textarea>
                            </div>
                            
                                                        <div class="col-12 mt-4">
                                <h6 class="fw-bold text-dark border-bottom pb-2"><i class="fas fa-route text-warning me-2"></i>Hành Trình Món Ăn (6 Giai Đoạn)</h6>
                            </div>
                            <?php
                            // Cố gắng giải mã JSON, nếu lỗi hoặc không phải JSON thì trả về mảng rỗng
                            $fj = json_decode($old['food_journey'] ?? '{}', true);
                            if (!is_array($fj)) $fj = [];
                            ?>
                            <div class="col-md-6 mt-2">
                                <label class="form-label fw-bold small text-muted">1. Nguồn gốc</label>
                                <textarea name="fj_origin" class="form-control bg-light border-0 mb-2" rows="2"><?= htmlspecialchars($fj['origin'] ?? '') ?></textarea>
                                <input type="file" name="fj_img_origin" class="form-control form-control-sm bg-light border-0 mb-2" accept="image/*">
                                <?php if (!empty($fj['origin_img'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="../../public/assets/img/journey/<?= htmlspecialchars($fj['origin_img']) ?>" alt="img" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc;">
                                        <small class="text-muted">Đã tải ảnh lên</small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-2 mt-2 bg-white rounded border border-warning border-opacity-25">
                                    <label class="form-label fw-bold small text-warning"><i class="fas fa-certificate me-1"></i>Chứng nhận nguồn gốc</label>
                                    <input type="file" name="fj_img_certificate" class="form-control form-control-sm bg-light border-0 mb-2" accept="image/*">
                                    <?php if (!empty($fj['certificate_img'])): ?>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <img src="../../public/assets/img/journey/<?= htmlspecialchars($fj['certificate_img']) ?>" alt="img" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc;">
                                            <small class="text-muted">Đã có ảnh chứng nhận</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label fw-bold small text-muted">2. Tuyển chọn</label>
                                <textarea name="fj_selection" class="form-control bg-light border-0 mb-2" rows="2"><?= htmlspecialchars($fj['selection'] ?? '') ?></textarea>
                                <input type="file" name="fj_img_selection" class="form-control form-control-sm bg-light border-0 mb-2" accept="image/*">
                                <?php if (!empty($fj['selection_img'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="../../public/assets/img/journey/<?= htmlspecialchars($fj['selection_img']) ?>" alt="img" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc;">
                                        <small class="text-muted">Đã tải ảnh lên</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label fw-bold small text-muted">3. Bảo quản</label>
                                <textarea name="fj_storage" class="form-control bg-light border-0 mb-2" rows="2"><?= htmlspecialchars($fj['storage'] ?? '') ?></textarea>
                                <input type="file" name="fj_img_storage" class="form-control form-control-sm bg-light border-0 mb-2" accept="image/*">
                                <?php if (!empty($fj['storage_img'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="../../public/assets/img/journey/<?= htmlspecialchars($fj['storage_img']) ?>" alt="img" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc;">
                                        <small class="text-muted">Đã tải ảnh lên</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label fw-bold small text-muted">4. Sơ chế</label>
                                <textarea name="fj_prep" class="form-control bg-light border-0 mb-2" rows="2"><?= htmlspecialchars($fj['prep'] ?? '') ?></textarea>
                                <input type="file" name="fj_img_prep" class="form-control form-control-sm bg-light border-0 mb-2" accept="image/*">
                                <?php if (!empty($fj['prep_img'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="../../public/assets/img/journey/<?= htmlspecialchars($fj['prep_img']) ?>" alt="img" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc;">
                                        <small class="text-muted">Đã tải ảnh lên</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label fw-bold small text-muted">5. Chế biến</label>
                                <textarea name="fj_cooking_art" class="form-control bg-light border-0 mb-2" rows="2"><?= htmlspecialchars($fj['cooking_art'] ?? '') ?></textarea>
                                <input type="file" name="fj_img_cooking_art" class="form-control form-control-sm bg-light border-0 mb-2" accept="image/*">
                                <?php if (!empty($fj['cooking_art_img'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="../../public/assets/img/journey/<?= htmlspecialchars($fj['cooking_art_img']) ?>" alt="img" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc;">
                                        <small class="text-muted">Đã tải ảnh lên</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label fw-bold small text-muted">6. Trình bày</label>
                                <textarea name="fj_presentation" class="form-control bg-light border-0 mb-2" rows="2"><?= htmlspecialchars($fj['presentation'] ?? '') ?></textarea>
                                <input type="file" name="fj_img_presentation" class="form-control form-control-sm bg-light border-0 mb-2" accept="image/*">
                                <?php if (!empty($fj['presentation_img'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="../../public/assets/img/journey/<?= htmlspecialchars($fj['presentation_img']) ?>" alt="img" class="rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc;">
                                        <small class="text-muted">Đã tải ảnh lên</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 mt-3">
                                <div class="form-check form-switch p-0 d-flex align-items-center gap-2">
                                    <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" id="is_chef_recommended" name="is_chef_recommended" value="1" <?= (!empty($old['is_chef_recommended'])) ? 'checked' : '' ?> style="width:40px;height:20px;cursor:pointer;">
                                    <label class="form-check-label fw-bold text-dark" for="is_chef_recommended" style="cursor:pointer; padding-left:10px;">
                                        ⭐ Đánh dấu là "Gợi ý từ Bếp trưởng"
                                    </label>
                                </div>
                                <div class="form-text small" style="margin-left: 50px;">Bật tùy chọn này để hiển thị món ăn lên phần nổi bật trên Menu.</div>
                            </div>
                        </div>

                                                    </div>
                            <!-- TAB: THÀNH PHẦN -->
                            <div class="tab-pane fade" id="recipe" role="tabpanel">
<!-- Định mức nguyên liệu -->
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                            <i class="fas fa-balance-scale me-1 text-warning"></i>Định mức nguyên liệu
                            <span class="badge bg-light text-muted border ms-1" style="font-size:10px;font-weight:400">Tùy chọn</span>
                        </p>

                        <?php if (empty($ingredients)): ?>
                        <div class="alert alert-light border small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Chưa có nguyên liệu nào trong kho.
                            <a href="InventoryController.php" class="alert-link">Thêm nguyên liệu</a>
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

                        <!-- Yêu cầu đặc biệt (Độ chín) -->
                        <?php if (!empty($special_requests)): ?>
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2 mt-4">
                            <i class="fas fa-star me-1 text-danger"></i>Yêu cầu đặc biệt (Special Requests)
                            <span class="badge bg-light text-muted border ms-1" style="font-size:10px;font-weight:400">Tùy chọn</span>
                        </p>
                        <div class="mb-4">
                            <div class="row g-3">
                                <div class="col-12 mb-2">
                                    <div class="row g-2">
                                        <?php foreach ($special_requests as $sr): 
                                            $is_checked = !empty($current_toppings) && in_array($sr['id'], $current_toppings);
                                        ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="topping-card d-flex align-items-center gap-2" data-topping-id="<?= $sr['id'] ?>" style="border-left: 3px solid #dc3545;">
                                                    <div class="form-check m-0 d-flex align-items-center">
                                                        <input class="form-check-input topping-checkbox" type="checkbox" name="toppings[]" 
                                                               value="<?= $sr['id'] ?>" id="special_req_<?= $sr['id'] ?>"
                                                               <?= $is_checked ? 'checked' : '' ?> style="cursor: pointer; width: 1.1rem; height: 1.1rem; border-color: #dc3545; accent-color: #dc3545;">
                                                    </div>
                                                    <label class="form-check-label flex-grow-1" for="special_req_<?= $sr['id'] ?>" style="cursor: pointer; font-size: 12.5px; user-select: none;">
                                                        <strong class="text-dark d-block" style="line-height: 1.2;"><?= htmlspecialchars($sr['name']) ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Định mức nguyên liệu và Toppings -->
                        <?php 
                        $grouped_toppings = [];
                        if (!empty($toppings)) {
                            foreach ($toppings as $t) {
                                $group = $t['topping_group'] ?: 'Khác';
                                $grouped_toppings[$group][] = $t;
                            }
                        }
                        if (!empty($grouped_toppings)):
                        ?>
                        <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2 mt-4">
                            <i class="fas fa-plus-circle me-1 text-warning"></i>Danh sách Toppings cho món ăn
                            <span class="badge bg-light text-muted border ms-1" style="font-size:10px;font-weight:400">Tùy chọn</span>
                        </p>
                        <div class="mb-4">
                            <div class="row g-3">
                                <?php foreach ($grouped_toppings as $group => $items): ?>
                                    <div class="col-12 mb-2">
                                        <h6 class="fw-bold text-dark border-bottom pb-1" style="font-size: 13px; color: #cda45e !important;">
                                            <i class="fas fa-tags me-1"></i><?= htmlspecialchars($group) ?>
                                        </h6>
                                        <div class="row g-2">
                                            <?php foreach ($items as $item): 
                                                $is_checked = !empty($current_toppings) && in_array($item['id'], $current_toppings);
                                            ?>
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="topping-card d-flex align-items-center gap-2" data-topping-id="<?= $item['id'] ?>">
                                                        <div class="form-check m-0 d-flex align-items-center">
                                                            <input class="form-check-input topping-checkbox" type="checkbox" name="toppings[]" 
                                                                   value="<?= $item['id'] ?>" id="topping_<?= $item['id'] ?>"
                                                                   <?= $is_checked ? 'checked' : '' ?> style="cursor: pointer; width: 1.1rem; height: 1.1rem; border-color: #cda45e;">
                                                        </div>
                                                        <label class="form-check-label flex-grow-1" for="topping_<?= $item['id'] ?>" style="cursor: pointer; font-size: 12.5px; user-select: none;">
                                                            <strong class="text-dark d-block" style="line-height: 1.2;"><?= htmlspecialchars($item['name']) ?></strong>
                                                            <span class="text-muted" style="font-size: 11px;">+<?= number_format($item['price']) ?>đ</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                                                    </div>
                        </div> <!-- end tab-content -->
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

    // Toggle active style and selection on topping cards
    document.querySelectorAll('.topping-checkbox').forEach(function(cb) {
        var card = cb.closest('.topping-card');
        if (cb.checked) {
            card.classList.add('active-checked');
        }
        cb.addEventListener('change', function() {
            if (this.checked) {
                card.classList.add('active-checked');
            } else {
                card.classList.remove('active-checked');
            }
        });
        card.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL') {
                cb.checked = !cb.checked;
                cb.dispatchEvent(new Event('change'));
            }
        });
    });

    document.getElementById('form-food').addEventListener('submit', function (e) {
        if (fileErr && fileErr.textContent !== '') { e.preventDefault(); return; }
        var btn = document.getElementById('btn-submit');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...'; }
    });
})();
</script>
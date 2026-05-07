<?php
// Gọi chung một layout header từ thư mục public
include __DIR__ . '/../../../public/admin_layout_header.php';
?>

<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<style>
.sort-link { font-size: 12px; color: var(--bs-secondary); text-decoration: none; }
.sort-link.active { color: #212529; font-weight: 600; }
.sort-link:hover { color: #212529; }
.food-img { width:60px;height:60px;object-fit:cover;border-radius:10px;border:1px solid #eee; transition:.2s; }
.food-img:hover { transform:scale(1.08); box-shadow:0 4px 12px rgba(0,0,0,.15); }
tr.inactive-row { opacity: .55; }
tr.inactive-row td:first-child::after { content:''; }
.profit-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; }
.profit-high { background:#d1fae5;color:#065f46; }
.profit-mid  { background:#fef9c3;color:#713f12; }
.profit-low  { background:#fee2e2;color:#991b1b; }
</style>

<div class="content-wrapper p-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold m-0"><i class="fas fa-utensils me-2 text-warning"></i>Quản lý thực đơn & Định mức</h3>
        <div class="d-flex gap-2">
            <a href="FoodController.php?show_hidden=1<?= $show_hidden?'':'' ?>"
               class="btn btn-sm <?= $show_hidden ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                <i class="fas fa-eye-slash me-1"></i>
                Món ẩn <?php if($hidden_count > 0): ?><span class="badge bg-danger ms-1"><?= $hidden_count ?></span><?php endif; ?>
            </a>
            <a href="FoodController.php?action=add" class="btn btn-primary shadow-sm rounded-pill px-4">
                <i class="fas fa-plus me-1"></i>Thêm món mới
            </a>
        </div>
    </div>

    <!-- Thông báo -->
    <?php if (!empty($delete_error)): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-3">
        <i class="fas fa-exclamation-circle"></i>
        <div><?= $delete_error ?></div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success border-0 shadow-sm mb-3">
        <i class="fas fa-check-circle me-2"></i>Xóa món ăn thành công.
    </div>
    <?php endif; ?>

    <!-- Bộ lọc + Tìm kiếm -->
    <div class="card border-0 shadow-sm mb-3 p-3" style="border-radius:12px;">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">

            <!-- Filter danh mục -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="FoodController.php<?= $show_hidden ? '?show_hidden=1' : '' ?>"
                   class="btn btn-sm <?= $filter=='all' ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-3">
                    Tất cả <span class="badge bg-secondary ms-1"><?= $total ?></span>
                </a>
                <div class="vr mx-1"></div>
                <?php foreach($cats as $cat):
                    $cat_count_s = $db->prepare("SELECT COUNT(*) FROM foods f LEFT JOIN categories c ON f.category_id=c.id WHERE c.name=? AND f.is_active=1");
                    $cat_count_s->execute([$cat['name']]);
                    $cat_count = $cat_count_s->fetchColumn();
                ?>
                <a href="FoodController.php?filter=<?= urlencode($cat['name']) ?><?= $show_hidden?'&show_hidden=1':'' ?>"
                   class="btn btn-sm <?= $filter==$cat['name'] ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-3">
                    <?= htmlspecialchars($cat['name']) ?>
                    <span class="badge bg-secondary ms-1"><?= $cat_count ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Tìm kiếm -->
            <form method="GET" class="d-flex gap-2" style="min-width:240px">
                <?php if($filter !== 'all') echo "<input type='hidden' name='filter' value='" . htmlspecialchars($filter) . "'>"; ?>
                <?php if($show_hidden) echo "<input type='hidden' name='show_hidden' value='1'>"; ?>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="🔍 Tìm tên món..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-sm btn-dark px-3">Tìm</button>
                <?php if($search): ?>
                <a href="FoodController.php<?= $filter!=='all'?"?filter=".urlencode($filter):'' ?>" class="btn btn-sm btn-outline-secondary">✕</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Sort + kết quả -->
        <div class="d-flex align-items-center gap-3 mt-2 pt-2 border-top flex-wrap">
            <small class="text-muted">
                Hiển thị <b><?= count($foods) ?></b> / <?= $total ?> món
                <?php if ($search): ?> — kết quả cho "<b><?= htmlspecialchars($search) ?></b>"<?php endif; ?>
            </small>
            <div class="d-flex gap-2 align-items-center ms-auto flex-wrap">
                <small class="text-muted">Sắp xếp:</small>
                <?php
                $sorts = [
                    'newest'     => 'Mới nhất',
                    'oldest'     => 'Cũ nhất',
                    'name_asc'   => 'Tên A→Z',
                    'name_desc'  => 'Tên Z→A',
                    'price_asc'  => 'Giá tăng',
                    'price_desc' => 'Giá giảm',
                ];
                foreach($sorts as $key => $lbl):
                    $url = 'FoodController.php?' . http_build_query(array_filter([
                        'filter' => $filter !== 'all' ? $filter : null,
                        'q'      => $search ?: null,
                        'sort'   => $key,
                        'show_hidden' => $show_hidden ? 1 : null,
                    ]));
                ?>
                <a href="<?= $url ?>" class="sort-link <?= $sort===$key ? 'active' : '' ?>">
                    <?= $lbl ?><?= $sort===$key ? ' ▾' : '' ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Bảng danh sách -->
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:15px;">
        <?php if (empty($foods)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-utensils fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">Không tìm thấy món ăn nào<?= $search ? " khớp với \"" . htmlspecialchars($search) . "\"" : '' ?>.</p>
            <?php if($search): ?><a href="FoodController.php" class="btn btn-sm btn-outline-dark mt-2">Xem tất cả</a><?php endif; ?>
        </div>
        <?php else: ?>
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4" style="width:80px">Ảnh</th>
                    <th>Tên món</th>
                    <th>Danh mục</th>
                    <th>Định mức kho</th>
                    <th>Giá bán / Vốn</th>
                    <th class="text-center" style="width:200px">Thao tác</th>
                </tr>
            </thead>
            <tbody style="background:white;">
                <?php foreach($foods as $row):
                    $fid       = (int)$row['id'];
                    $in_combo  = $combo_map[$fid]  ?? 0;
                    $recipes   = $recipe_map[$fid] ?? [];
                    $cost      = $cost_map[$fid]   ?? 0;
                    $is_active = (int)($row['is_active'] ?? 1);
                    $price     = (float)$row['price'];
                    $margin    = ($price > 0 && $cost > 0) ? round(($price - $cost) / $price * 100) : null;
                    $margin_cls = $margin === null ? '' : ($margin >= 60 ? 'profit-high' : ($margin >= 30 ? 'profit-mid' : 'profit-low'));
                ?>
                <tr class="<?= !$is_active ? 'inactive-row' : '' ?>">

                    <!-- Ảnh -->
                    <td class="ps-4">
                        <img src="../../public/assets/img/menu/<?= htmlspecialchars($row['image']) ?>"
                             class="food-img"
                             onerror="this.src='../../public/assets/img/menu/default.jpg'"
                             alt="<?= htmlspecialchars($row['name']) ?>">
                    </td>

                    <!-- Tên -->
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                        <div class="d-flex gap-1 flex-wrap mt-1">
                            <?php if (!$is_active): ?>
                                <span class="badge bg-secondary" style="font-size:9px">ĐÃ ẨN</span>
                            <?php endif; ?>
                            <?php if ($in_combo > 0): ?>
                                <span class="badge bg-danger" style="font-size:9px">ĐANG TRONG COMBO</span>
                            <?php else: ?>
                                <small class="text-muted">Mã: #<?= $fid ?></small>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Danh mục -->
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($row['category_name'] ?? 'Chưa phân loại') ?>
                        </span>
                    </td>

                    <!-- Định mức -->
                    <td style="max-width:230px;">
                        <?php if (empty($recipes)): ?>
                            <small class="text-muted fst-italic">Chưa có định mức</small>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach($recipes as $rcp): ?>
                                <span class="badge bg-warning-subtle border-0 shadow-sm"
                                      style="font-size:10px;color:#856404;">
                                    <?= htmlspecialchars($rcp['item_name']) ?>:
                                    <?= (float)$rcp['quantity_required'] ?><?= htmlspecialchars($rcp['unit']) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Giá bán / Vốn / Lợi nhuận -->
                    <td>
                        <div class="fw-bold text-danger"><?= number_format($price, 0, ',', '.') ?>đ</div>
                        <?php if ($cost > 0): ?>
                        <div class="small text-muted">Vốn: <?= number_format($cost, 0, ',', '.') ?>đ</div>
                        <?php if ($margin !== null): ?>
                        <span class="profit-badge <?= $margin_cls ?>">
                            Lãi <?= $margin ?>%
                        </span>
                        <?php endif; ?>
                        <?php elseif (empty($recipes)): ?>
                        <small class="text-muted">—</small>
                        <?php else: ?>
                        <small class="text-muted">Chưa có giá vốn</small>
                        <?php endif; ?>
                    </td>

                    <!-- Thao tác -->
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                            <button title="Thiết lập định mức"
                                    class="btn btn-sm btn-outline-warning btn-add-recipe"
                                    data-id="<?= $fid ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>">
                                <i class="fas fa-balance-scale"></i>
                            </button>

                            <a href="FoodController.php?action=edit&id=<?= $fid ?>"
                               class="btn btn-sm btn-outline-primary"
                               title="Sửa món">
                                <i class="fas fa-edit"></i>
                            </a>

                            <button class="btn btn-sm <?= $is_active ? 'btn-outline-secondary' : 'btn-secondary' ?> btn-toggle-food"
                                    data-id="<?= $fid ?>"
                                    data-active="<?= $is_active ?>"
                                    title="<?= $is_active ? 'Ẩn món khỏi thực đơn' : 'Hiện lại món' ?>">
                                <i class="fas <?= $is_active ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                            </button>

                            <button class="btn btn-sm btn-outline-danger btn-delete-food"
                                    data-id="<?= $fid ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>"
                                    data-combo="<?= $in_combo ?>"
                                    title="Xóa món">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
            Trang <b><?= $page ?></b> / <?= $total_pages ?>
            &nbsp;·&nbsp; Tổng <b><?= $total ?></b> món
        </small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildUrl(['page' => $page - 1]) ?>">‹ Trước</a>
            </li>
            <?php for($p = 1; $p <= $total_pages; $p++):
                if ($total_pages <= 7 || abs($p - $page) <= 1 || $p === 1 || $p === $total_pages): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= buildUrl(['page' => $p]) ?>"><?= $p ?></a>
                </li>
                <?php elseif (abs($p - $page) === 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildUrl(['page' => $page + 1]) ?>">Sau ›</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<!-- MODAL ĐỊNH MỨC & XÓA MÓN GIỮ NGUYÊN (Lược bớt HTML cho gọn, vẫn y như cũ) -->
<div class="modal fade" id="modalRecipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-balance-scale me-2 text-warning"></i>Định mức: <span id="recipe-food-name" class="text-warning"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-save-recipe">
                <div class="modal-body p-4">
                    <input type="hidden" name="food_id" id="recipe-food-id">
                    <div id="recipe-items-list"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-3 rounded-pill" id="btn-add-ingredient-row">
                        <i class="fas fa-plus me-1"></i>Thêm nguyên liệu khác
                    </button>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 rounded-3 text-white shadow-sm" style="background:#cda45e;border:none;">LƯU TẤT CẢ ĐỊNH MỨC</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header border-0 bg-danger text-white" style="border-radius:16px 16px 0 0;">
                <h6 class="modal-title fw-bold"><i class="fas fa-trash me-2"></i>Xác nhận xóa</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="mb-1">Xóa món ăn:</p>
                <p class="fw-bold" id="confirm-food-name"></p>
                <div id="combo-warning" class="alert alert-warning small py-2 d-none">
                    <i class="fas fa-exclamation-triangle me-1"></i>Món này đang nằm trong <strong>Combo</strong>.
                </div>
                <small class="text-muted">Hành động này không thể hoàn tác.</small>
            </div>
            <div class="modal-footer border-0 pb-4 px-4 gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Hủy bỏ</button>
                <a href="#" id="confirm-delete-link" class="btn btn-danger flex-fill fw-bold">Xóa</a>
            </div>
        </div>
    </div>
</div>

<script>
window.allIngredients = <?= json_encode($ingredients) ?>;
window.allUnits       = <?= json_encode($all_units) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/assets/admin/js/admin.js"></script>

<script>
$(function () {
    $(document).on('click', '.btn-delete-food', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const combo = parseInt($(this).data('combo'));
        $('#confirm-food-name').text(name);
        $('#combo-warning').toggleClass('d-none', combo === 0);
        $('#confirm-delete-link').attr('href', 'FoodController.php?action=delete&id=' + id
            + '<?= $filter !== "all" ? "&filter=" . urlencode($filter) : "" ?>'
            + '<?= $search ? "&q=" . urlencode($search) : "" ?>'
            + '<?= $show_hidden ? "&show_hidden=1" : "" ?>');
        new bootstrap.Modal(document.getElementById('modalConfirmDelete')).show();
    });

    $(document).on('click', '.btn-toggle-food', function () {
        const btn = $(this);
        const id = btn.data('id');
        const row = btn.closest('tr');
        $.post('FoodController.php?action=toggle', { food_id: id }, function (r) {
            if (r.status !== 'success') return;
            const active = r.is_active;
            btn.data('active', active);
            btn.attr('title', active ? 'Ẩn món khỏi thực đơn' : 'Hiện lại món');
            btn.toggleClass('btn-outline-secondary', !!active).toggleClass('btn-secondary', !active);
            btn.find('i').toggleClass('fa-eye-slash', !!active).toggleClass('fa-eye', !active);
            row.toggleClass('inactive-row', !active);
            const hiddenBadge = row.find('.badge.bg-secondary');
            if (!active) { if (!hiddenBadge.length) row.find('.fw-bold.text-dark').after('<span class="badge bg-secondary ms-1" style="font-size:9px">ĐÃ ẨN</span>'); } else { hiddenBadge.remove(); }
        }, 'json');
    });

    setTimeout(() => $('.alert-success').fadeOut(400), 3000);
});
</script>
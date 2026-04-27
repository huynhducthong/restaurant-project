<?php
// =====================================================================
// MIGRATION SQL CẦN CHẠY TRƯỚC:
// ALTER TABLE combos ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;
// ALTER TABLE combos ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
// =====================================================================

session_start();

// ✅ FIX: Xác thực session admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

// ✅ FIX: Include đúng thứ tự (header trước, DB sau)
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();

// ============================================================
// AJAX: Toggle Bật / Tắt combo
// ============================================================
if (isset($_POST['toggle_active'])) {
    header('Content-Type: application/json');
    $cid = (int)$_POST['combo_id'];
    $db->prepare("UPDATE combos SET is_active = NOT is_active WHERE id = ?")->execute([$cid]);
    $s = $db->prepare("SELECT is_active FROM combos WHERE id = ?");
    $s->execute([$cid]);
    echo json_encode(['status' => 'success', 'is_active' => (int)$s->fetchColumn()]);
    exit;
}

// ============================================================
// XÓA COMBO qua POST (không dùng GET link nữa)
// ============================================================
$delete_error = '';
if (isset($_POST['delete_combo_id'])) {
    $del_id = (int)$_POST['delete_combo_id'];

    // Lấy tên ảnh để xóa file
    $img_s = $db->prepare("SELECT image FROM combos WHERE id = ?");
    $img_s->execute([$del_id]);
    $del_img = $img_s->fetchColumn();

    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM combo_items WHERE combo_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM combos WHERE id = ?")         ->execute([$del_id]);
        $db->commit();

        // Xóa file ảnh nếu có
        if ($del_img) {
            $img_path = "../public/assets/img/combos/" . $del_img;
            if (file_exists($img_path)) @unlink($img_path);
        }

        header("Location: list_combos.php?msg=deleted"); exit;
    } catch (Exception $e) {
        $db->rollBack();
        $delete_error = "Lỗi khi xóa: " . htmlspecialchars($e->getMessage());
    }
}

// ============================================================
// FILTER / SEARCH / PHÂN TRANG
// ============================================================
$search      = trim($_GET['q']    ?? '');
$show_hidden = isset($_GET['show_hidden']);
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;

$where_parts = [];
$params      = [];

if ($search !== '') {
    $where_parts[] = "c.name LIKE ?";
    $params[]      = '%' . $search . '%';
}
if (!$show_hidden) {
    $where_parts[] = "c.is_active = 1";
}

$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// Đếm tổng
$cnt_stmt = $db->prepare(
    "SELECT COUNT(*) FROM combos c $where_sql"
);
$cnt_stmt->execute($params);
$total       = (int)$cnt_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// ✅ FIX: Dùng fetchAll() + count() thay rowCount()
// Thêm: food_count, total_food_price để tính % tiết kiệm
$sql = "SELECT c.*,
            GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR '||') as list_foods,
            COUNT(ci.food_id)  as food_count,
            SUM(f.price)       as total_food_price
        FROM combos c
        LEFT JOIN combo_items ci ON c.id = ci.combo_id
        LEFT JOIN foods f        ON ci.food_id = f.id
        $where_sql
        GROUP BY c.id
        ORDER BY c.id DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC); // ✅ fetchAll thay rowCount

// Đếm combo ẩn để hiển thị badge
$hidden_count = (int)$db->query("SELECT COUNT(*) FROM combos WHERE is_active = 0")->fetchColumn();

// Hàm build URL giữ nguyên params
function buildUrl(array $override = []): string {
    $base = array_merge([
        'q'    => $_GET['q']    ?? '',
        'page' => 1,
    ], $override);
    foreach ($base as $k => $v) {
        if ($v === '' || $v === null) unset($base[$k]);
    }
    return 'list_combos.php?' . http_build_query($base);
}
?>

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">

<style>
.combo-img {
    width: 80px; height: 60px;
    object-fit: cover; border-radius: 8px;
    transition: .2s;
}
.combo-img:hover { transform: scale(1.08); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
tr.inactive-row { opacity: .5; }
.saving-badge {
    font-size: 10px; padding: 2px 6px; border-radius: 4px;
    background: #d1fae5; color: #065f46; display: inline-block; margin-top: 3px;
}
.saving-badge.low { background: #fef9c3; color: #713f12; }
</style>

<div class="container-fluid py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold m-0">
            <i class="fas fa-boxes me-2 text-primary"></i>Quản lý Combo Món Ăn
        </h3>
        <div class="d-flex gap-2">
            <a href="list_combos.php<?= $show_hidden ? '' : '?show_hidden=1' ?>"
               class="btn btn-sm <?= $show_hidden ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                <i class="fas fa-eye-slash me-1"></i>
                Combo ẩn
                <?php if ($hidden_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $hidden_count ?></span>
                <?php endif; ?>
            </a>
            <a href="add_combo.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus-circle me-1"></i>Thêm Combo Mới
            </a>
        </div>
    </div>

    <!-- Thông báo -->
    <?php if (!empty($delete_error)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <i class="fas fa-exclamation-circle me-2"></i><?= $delete_error ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success border-0 shadow-sm mb-3" id="alert-success">
        <i class="fas fa-check-circle me-2"></i>Đã xóa combo thành công.
    </div>
    <?php endif; ?>

    <!-- Search + Kết quả -->
    <div class="card border-0 shadow-sm mb-3 p-3" style="border-radius:12px;">
        <div class="d-flex gap-3 align-items-center flex-wrap justify-content-between">
            <form method="GET" class="d-flex gap-2" style="min-width:260px">
                <?php if ($show_hidden) echo "<input type='hidden' name='show_hidden' value='1'>"; ?>
                <input type="text" name="q"
                       class="form-control form-control-sm"
                       placeholder="🔍 Tìm tên combo..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-sm btn-dark px-3">Tìm</button>
                <?php if ($search): ?>
                <a href="list_combos.php<?= $show_hidden ? '?show_hidden=1' : '' ?>"
                   class="btn btn-sm btn-outline-secondary">✕</a>
                <?php endif; ?>
            </form>

            <small class="text-muted">
                <?php if ($search): ?>
                    Kết quả cho "<b><?= htmlspecialchars($search) ?></b>" —
                <?php endif; ?>
                Tổng <b><?= $total ?></b> combo
                <?= $show_hidden ? '(bao gồm đã ẩn)' : '' ?>
            </small>
        </div>
    </div>

    <!-- Bảng combo -->
    <div class="card shadow-sm border-0 overflow-hidden mb-3" style="border-radius:15px;">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4" style="width:50px">ID</th>
                    <th style="width:90px">Ảnh</th>
                    <th>Tên Combo</th>
                    <th style="width:160px">Giá / Tiết kiệm</th>
                    <th>Món trong Combo</th>
                    <th class="text-center" style="width:170px">Thao tác</th>
                </tr>
            </thead>
            <tbody style="background:white;">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-boxes fa-3x mb-3 d-block opacity-25"></i>
                        <?= $search
                            ? 'Không tìm thấy combo nào khớp với "<b>' . htmlspecialchars($search) . '</b>".'
                            : 'Chưa có combo nào.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row):
                    $is_active       = (int)($row['is_active'] ?? 1);
                    $combo_price     = (float)$row['price'];
                    $total_food_price = (float)($row['total_food_price'] ?? 0);
                    $food_count      = (int)$row['food_count'];

                    // Tính % tiết kiệm so với mua lẻ
                    $saving_pct  = null;
                    $saving_amt  = null;
                    if ($total_food_price > 0 && $combo_price < $total_food_price) {
                        $saving_amt = $total_food_price - $combo_price;
                        $saving_pct = round($saving_amt / $total_food_price * 100);
                    }
                ?>
                <tr class="<?= !$is_active ? 'inactive-row' : '' ?>">

                    <!-- ID -->
                    <td class="ps-4">
                        <strong class="text-muted">#<?= $row['id'] ?></strong>
                    </td>

                    <!-- Ảnh -->
                    <td>
                        <?php if (!empty($row['image'])): ?>
                        <img src="../public/assets/img/combos/<?= htmlspecialchars($row['image']) ?>"
                             class="combo-img"
                             onerror="this.onerror=null;this.src='../public/assets/img/combos/default.jpg'"
                             alt="<?= htmlspecialchars($row['name']) ?>">
                        <?php else: ?>
                        <div class="combo-img bg-light text-muted d-flex align-items-center
                                    justify-content-center" style="font-size:10px;">
                            No Img
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Tên combo -->
                    <td>
                        <div class="fw-bold text-primary"><?= htmlspecialchars($row['name']) ?></div>
                        <div class="d-flex gap-1 mt-1 flex-wrap">
                            <?php if (!$is_active): ?>
                            <span class="badge bg-secondary" style="font-size:9px">ĐÃ ẨN</span>
                            <?php endif; ?>
                            <?php if (!empty($row['created_at'])): ?>
                            <small class="text-muted" style="font-size:10px">
                                <i class="far fa-clock me-1"></i>
                                <?= date('d/m/Y', strtotime($row['created_at'])) ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Giá / Tiết kiệm -->
                    <td>
                        <span class="badge bg-success-subtle text-success fs-6 fw-bold">
                            <?= number_format($combo_price) ?>đ
                        </span>
                        <?php if ($saving_pct !== null): ?>
                        <div class="saving-badge <?= $saving_pct < 10 ? 'low' : '' ?>">
                            <i class="fas fa-tag me-1"></i>
                            Tiết kiệm <?= $saving_pct ?>%
                            (<?= number_format($saving_amt) ?>đ)
                        </div>
                        <?php elseif ($total_food_price > 0): ?>
                        <div class="text-muted" style="font-size:10px;margin-top:3px">
                            Giá gốc: <?= number_format($total_food_price) ?>đ
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Món trong combo -->
                    <td>
                        <?php if ($row['list_foods']): ?>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach (explode('||', $row['list_foods']) as $fn): ?>
                            <span class="badge bg-info-subtle text-dark border-0"
                                  style="font-size:11px;">
                                <?= htmlspecialchars($fn) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted mt-1 d-block" style="font-size:10px;">
                            <?= $food_count ?> món
                        </small>
                        <?php else: ?>
                        <span class="text-muted small fst-italic">Chưa có món</span>
                        <?php endif; ?>
                    </td>

                    <!-- Thao tác -->
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center">

                            <!-- Sửa -->
                            <a href="edit_combo.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-warning"
                               title="Chỉnh sửa combo">
                                <i class="fas fa-edit"></i>
                            </a>

                            <!-- Bật / Tắt -->
                            <button type="button"
                                    class="btn btn-sm <?= $is_active ? 'btn-outline-secondary' : 'btn-secondary' ?> btn-toggle-combo"
                                    data-id="<?= $row['id'] ?>"
                                    data-active="<?= $is_active ?>"
                                    title="<?= $is_active ? 'Ẩn combo' : 'Hiện combo' ?>">
                                <i class="fas <?= $is_active ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                            </button>

                            <!-- Xóa (mở modal) -->
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger btn-delete-combo"
                                    data-id="<?= $row['id'] ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>"
                                    data-count="<?= $food_count ?>"
                                    title="Xóa combo">
                                <i class="fas fa-trash"></i>
                            </button>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav class="d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Trang <b><?= $page ?></b> / <?= $total_pages ?>
            &nbsp;·&nbsp; Tổng <b><?= $total ?></b> combo
        </small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildUrl(['page' => $page - 1]) ?>">‹</a>
            </li>
            <?php for ($p = 1; $p <= $total_pages; $p++):
                if ($total_pages <= 7 || abs($p - $page) <= 1 || $p === 1 || $p === $total_pages): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= buildUrl(['page' => $p]) ?>"><?= $p ?></a>
                </li>
                <?php elseif (abs($p - $page) === 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildUrl(['page' => $page + 1]) ?>">›</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<!-- ===================== MODAL XÓA ===================== -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header border-0 bg-danger text-white" style="border-radius:16px 16px 0 0;">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-trash me-2"></i>Xác nhận xóa
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="mb-1 text-muted small">Xóa combo:</p>
                <p class="fw-bold mb-1" id="confirm-combo-name"></p>
                <p class="text-muted small mb-0" id="confirm-combo-count"></p>
                <p class="text-danger small mt-2 mb-0">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Hành động này không thể hoàn tác.
                </p>
            </div>
            <div class="modal-footer border-0 pb-4 px-4 gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">
                    Hủy bỏ
                </button>
                <!-- ✅ FIX: Xóa qua POST thay vì GET link -->
                <form method="POST" class="flex-fill" id="form-delete-combo">
                    <input type="hidden" name="delete_combo_id" id="delete-combo-id">
                    <button type="submit" class="btn btn-danger w-100 fw-bold">
                        Xóa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function () {

    // ---- Mở modal xóa ----
    $(document).on('click', '.btn-delete-combo', function () {
        const id    = $(this).data('id');
        const name  = $(this).data('name');
        const count = parseInt($(this).data('count'));

        $('#confirm-combo-name').text(name);
        $('#confirm-combo-count').text(
            count > 0 ? 'Combo gồm ' + count + ' món, sẽ bị xóa hoàn toàn.' : ''
        );
        $('#delete-combo-id').val(id);
        new bootstrap.Modal(document.getElementById('modalConfirmDelete')).show();
    });

    // ---- Toggle bật / tắt combo ----
    $(document).on('click', '.btn-toggle-combo', function () {
        const btn    = $(this);
        const id     = btn.data('id');
        const row    = btn.closest('tr');

        $.post('list_combos.php', { toggle_active: 1, combo_id: id }, function (r) {
            if (r.status !== 'success') return;

            const active = r.is_active;
            btn.data('active', active);
            btn.attr('title', active ? 'Ẩn combo' : 'Hiện combo');
            btn.toggleClass('btn-outline-secondary', !!active)
               .toggleClass('btn-secondary', !active);
            btn.find('i')
               .toggleClass('fa-eye-slash', !!active)
               .toggleClass('fa-eye', !active);

            row.toggleClass('inactive-row', !active);

            // Cập nhật badge "ĐÃ ẨN"
            const hiddenBadge = row.find('.badge.bg-secondary');
            if (!active) {
                if (!hiddenBadge.length) {
                    row.find('.fw-bold.text-primary')
                       .after('<span class="badge bg-secondary ms-1" style="font-size:9px">ĐÃ ẨN</span>');
                }
            } else {
                hiddenBadge.remove();
            }
        }, 'json');
    });

    // ---- Tự ẩn alert success ----
    setTimeout(function () {
        $('#alert-success').fadeOut(400);
    }, 3000);

});
</script>
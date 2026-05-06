<?php
// =============================================================
// File: admin/controllers/BookController.php
// Route: ?tab=books|orders|stock (mặc định: books)
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

$tab  = $_GET['tab'] ?? 'books';
$flash = $_SESSION['book_flash'] ?? null;
unset($_SESSION['book_flash']);

// ============================================================
// AJAX: Cập nhật trạng thái đơn hàng
// ============================================================
if (isset($_POST['update_order_status'])) {
    header('Content-Type: application/json');
    $oid = (int)$_POST['order_id'];
    $allowed = ['new', 'confirmed', 'shipping', 'done', 'cancelled'];
    $status  = in_array($_POST['status'] ?? '', $allowed) ? $_POST['status'] : null;
    if ($status && $oid > 0) {
        $db->prepare("UPDATE book_orders SET status = ? WHERE id = ?")
            ->execute([$status, $oid]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// ============================================================
// UPLOAD ẢNH SÁCH — helper
// ============================================================
function uploadBookImage(array &$errors): string
{
    if (empty($_FILES['book_image']['name'])) return '';
    $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
    $ext  = strtolower(pathinfo($_FILES['book_image']['name'], PATHINFO_EXTENSION));
    $tmp  = $_FILES['book_image']['tmp_name'];
    $size = $_FILES['book_image']['size'];
    if (!in_array($ext, $allowed_ext)) {
        $errors[] = 'Ảnh chỉ chấp nhận JPG/PNG/WEBP.';
        return '';
    }
    if ($size > 3 * 1024 * 1024) {
        $errors[] = 'Ảnh tối đa 3MB.';
        return '';
    }
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (!in_array($mime, $allowed_mime)) {
            $errors[] = 'File không hợp lệ.';
            return '';
        }
    }
    $dir = __DIR__ . '/../../public/assets/img/books/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . $name)) {
        $errors[] = 'Không thể upload ảnh.';
        return '';
    }
    return $name;
}

// ============================================================
// CRUD SÁCH
// ============================================================
// Xóa sách
if (isset($_POST['delete_book_id'])) {
    $bid = (int)$_POST['delete_book_id'];
    // Kiểm tra có đơn hàng không
    $chk = $db->prepare("SELECT COUNT(*) FROM book_order_items WHERE book_id = ?");
    $chk->execute([$bid]);
    if ((int)$chk->fetchColumn() > 0) {
        $_SESSION['book_flash'] = ['type' => 'error', 'msg' => 'Không thể xóa sách đã có trong đơn hàng. Hãy ẩn sách thay vì xóa.'];
    } else {
        $img_s = $db->prepare("SELECT image FROM books WHERE id = ?");
        $img_s->execute([$bid]);
        $img = $img_s->fetchColumn();
        $db->prepare("DELETE FROM books WHERE id = ?")->execute([$bid]);
        if ($img) {
            $p = __DIR__ . '/../../public/assets/img/books/' . $img;
            if (file_exists($p)) @unlink($p);
        }
        $_SESSION['book_flash'] = ['type' => 'success', 'msg' => 'Đã xóa sách.'];
    }
    header('Location: BookController.php?tab=books');
    exit;
}

// Toggle ẩn/hiện sách
if (isset($_POST['toggle_book_id'])) {
    header('Content-Type: application/json');
    $bid = (int)$_POST['toggle_book_id'];
    $db->prepare("UPDATE books SET is_active = NOT is_active WHERE id = ?")->execute([$bid]);
    $s = $db->prepare("SELECT is_active FROM books WHERE id = ?");
    $s->execute([$bid]);
    echo json_encode(['status' => 'success', 'is_active' => (int)$s->fetchColumn()]);
    exit;
}

// Thêm / Sửa sách
if (isset($_POST['save_book'])) {
    $bid    = !empty($_POST['book_id']) ? (int)$_POST['book_id'] : null;
    $errors = [];
    $data   = [
        'title'       => trim($_POST['title']       ?? ''),
        'author'      => trim($_POST['author']       ?? ''),
        'description' => trim($_POST['description']  ?? ''),
        'price'       => max(0, (float)($_POST['price'] ?? 0)),
        'stock'       => max(0, (int)($_POST['stock']   ?? 0)),
        'category'    => trim($_POST['category']     ?? 'Sách nấu ăn'),
    ];
    if ($data['title'] === '') $errors[] = 'Tên sách không được để trống.';

    $img_name = $_POST['old_image'] ?? '';
    $new_img  = uploadBookImage($errors);
    if ($new_img) {
        // Xóa ảnh cũ
        if ($img_name) {
            $op = __DIR__ . '/../../public/assets/img/books/' . $img_name;
            if (file_exists($op)) @unlink($op);
        }
        $img_name = $new_img;
    }
    $data['image'] = $img_name;

    if (empty($errors)) {
        if ($bid) {
            $db->prepare(
                "UPDATE books SET title=?,author=?,description=?,price=?,stock=?,category=?,image=?,is_active=1 WHERE id=?"
            )->execute([$data['title'], $data['author'], $data['description'], $data['price'], $data['stock'], $data['category'], $data['image'], $bid]);
            $_SESSION['book_flash'] = ['type' => 'success', 'msg' => 'Cập nhật sách thành công!'];
        } else {
            $db->prepare(
                "INSERT INTO books (title,author,description,price,stock,category,image) VALUES (?,?,?,?,?,?,?)"
            )->execute([$data['title'], $data['author'], $data['description'], $data['price'], $data['stock'], $data['category'], $data['image']]);
            $_SESSION['book_flash'] = ['type' => 'success', 'msg' => 'Thêm sách mới thành công!'];
        }
        header('Location: BookController.php?tab=books');
        exit;
    }
    // Nếu lỗi → hiện lại form với error
    $form_errors = $errors;
    $form_data   = $data + ['id' => $bid];
}

// ============================================================
// CẬP NHẬT TỒN KHO NHANH
// ============================================================
if (isset($_POST['update_stock'])) {
    $bid   = (int)$_POST['book_id'];
    $stock = max(0, (int)$_POST['stock']);
    $db->prepare("UPDATE books SET stock = ? WHERE id = ?")->execute([$stock, $bid]);
    $_SESSION['book_flash'] = ['type' => 'success', 'msg' => 'Đã cập nhật tồn kho.'];
    header('Location: BookController.php?tab=stock');
    exit;
}

// ============================================================
// DỮ LIỆU HIỂN THỊ
// ============================================================
$books = $db->query(
    "SELECT * FROM books ORDER BY is_active DESC, id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Đơn hàng + filter
$order_status_filter = $_GET['order_status'] ?? '';
$order_search        = trim($_GET['oq'] ?? '');
$order_where = [];
$order_params = [];
if ($order_status_filter) {
    $order_where[] = "status = ?";
    $order_params[] = $order_status_filter;
}
if ($order_search) {
    $order_where[] = "(customer_name LIKE ? OR phone LIKE ? OR order_code LIKE ?)";
    $order_params = array_merge($order_params, ["%$order_search%", "%$order_search%", "%$order_search%"]);
}
$order_sql = "SELECT * FROM book_orders" . ($order_where ? ' WHERE ' . implode(' AND ', $order_where) : '') . " ORDER BY created_at DESC LIMIT 100";
$order_stmt = $db->prepare($order_sql);
$order_stmt->execute($order_params);
$orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm đơn theo trạng thái
$status_counts = [];
$sc = $db->query("SELECT status, COUNT(*) as cnt FROM book_orders GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sc as $r) $status_counts[$r['status']] = (int)$r['cnt'];

// Book để sửa
$edit_book = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM books WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $edit_book = $s->fetch(PDO::FETCH_ASSOC);
}

$book_cats = $db->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

include '../../public/admin_layout_header.php';
?>

<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<style>
    .status-badge {
        font-size: 11px;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
    }

    .s-new {
        background: #dbeafe;
        color: #1e40af
    }

    .s-confirmed {
        background: #fef9c3;
        color: #713f12
    }

    .s-shipping {
        background: #e0f2fe;
        color: #075985
    }

    .s-done {
        background: #dcfce7;
        color: #166534
    }

    .s-cancelled {
        background: #fee2e2;
        color: #991b1b
    }

    .order-row:hover {
        background: #f9f6f0 !important;
    }

    .book-thumb {
        width: 60px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #eee
    }
</style>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">
            📚 Quản lý Sách
        </h2>
        <a href="../../books.php" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-external-link-alt me-1"></i>Xem trang sách
        </a>
    </div>

    <!-- Flash -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> border-0 shadow-sm mb-4 alert-dismissible fade show">
            <?= htmlspecialchars($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 border-0" style="gap:4px">
        <?php
        $tabs = [
            'books'  => ['📖', 'Sách (' . count($books) . ')'],
            'orders' => ['📦', 'Đơn hàng (' . array_sum($status_counts) . ')'],
            'stock'  => ['📊', 'Tồn kho'],
        ];
        foreach ($tabs as $t => [$icon, $label]):
        ?>
            <li class="nav-item">
                <a class="nav-link <?= $tab === $t ? 'active fw-bold' : '' ?>"
                    href="BookController.php?tab=<?= $t ?>">
                    <?= $icon ?> <?= $label ?>
                    <?php if ($t === 'orders' && ($status_counts['new'] ?? 0) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $status_counts['new'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- ===== TAB: SÁCH ===== -->
    <?php if ($tab === 'books'): ?>
        <div class="row g-4">

            <!-- Form thêm/sửa -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;position:sticky;top:20px">
                    <div class="card-header bg-dark py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 text-white fw-bold">
                            <?= $edit_book ? 'Sửa sách #' . $edit_book['id'] : 'Thêm sách mới' ?>
                        </h5>
                        <?php if ($edit_book): ?>
                            <a href="BookController.php?tab=books" class="btn btn-sm btn-outline-light">✕</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4" style="max-height:80vh;overflow-y:auto">
                        <form method="POST" enctype="multipart/form-data"
                            action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?tab=books">
                            <input type="hidden" name="save_book" value="1">
                            <input type="hidden" name="book_id" value="<?= (int)($edit_book['id'] ?? 0) ?: '' ?>">
                            <input type="hidden" name="old_image" value="<?= htmlspecialchars($edit_book['image'] ?? '') ?>">

                            <?php if (!empty($form_errors)): ?>
                                <div class="alert alert-danger border-0 small mb-3">
                                    <?php foreach ($form_errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tên sách <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($edit_book['title'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tác giả</label>
                                <input type="text" name="author" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($edit_book['author'] ?? '') ?>"
                                    placeholder="Nguyễn Văn A">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Giá (đ) <span class="text-danger">*</span></label>
                                    <input type="number" name="price" class="form-control bg-light border-0"
                                        value="<?= (float)($edit_book['price'] ?? 0) ?>" min="0" step="1000">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Tồn kho</label>
                                    <input type="number" name="stock" class="form-control bg-light border-0"
                                        value="<?= (int)($edit_book['stock'] ?? 0) ?>" min="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Danh mục</label>
                                <input type="text" name="category" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($edit_book['category'] ?? 'Sách nấu ăn') ?>"
                                    list="cat-list" placeholder="Sách nấu ăn">
                                <datalist id="cat-list">
                                    <?php foreach ($book_cats as $c): ?>
                                        <option value="<?= htmlspecialchars($c) ?>">
                                        <?php endforeach; ?>
                                        <option value="Sách nấu ăn">
                                        <option value="Sách ẩm thực">
                                        <option value="Sách dinh dưỡng">
                                </datalist>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Mô tả</label>
                                <textarea name="description" class="form-control bg-light border-0" rows="3"
                                    placeholder="Giới thiệu ngắn về cuốn sách..."><?= htmlspecialchars($edit_book['description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">
                                    Ảnh bìa <?= $edit_book ? '<span class="text-muted fw-normal">(để trống nếu giữ)</span>' : '' ?>
                                </label>
                                <?php if (!empty($edit_book['image'])): ?>
                                    <img src="../../public/assets/img/books/<?= htmlspecialchars($edit_book['image']) ?>"
                                        style="height:80px;border-radius:6px;display:block;margin-bottom:8px;border:1px solid #eee;object-fit:cover"
                                        onerror="this.remove()">
                                <?php endif; ?>
                                <input type="file" name="book_image" class="form-control bg-light border-0"
                                    accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text">JPG/PNG/WEBP — tối đa 3MB</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill">
                                <i class="fas fa-save me-1"></i>
                                <?= $edit_book ? 'Lưu cập nhật' : 'Thêm sách' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách sách -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:14px">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:70px">Ảnh</th>
                                <th>Tên sách</th>
                                <th class="text-center">Giá</th>
                                <th class="text-center">Kho</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center" style="width:120px">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($books)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Chưa có sách nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($books as $b): ?>
                                    <tr class="<?= !$b['is_active'] ? 'opacity-50' : '' ?>">
                                        <td>
                                            <img src="<?= !empty($b['image']) ? '../../public/assets/img/books/' . htmlspecialchars($b['image']) : '../../public/assets/img/books/default-book.jpg' ?>"
                                                class="book-thumb"
                                                onerror="this.src='../../public/assets/img/books/default-book.jpg'"
                                                alt="">
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($b['title']) ?></div>
                                            <?php if ($b['author']): ?>
                                                <div class="small text-muted"><?= htmlspecialchars($b['author']) ?></div>
                                            <?php endif; ?>
                                            <span class="badge bg-light text-dark border" style="font-size:9px">
                                                <?= htmlspecialchars($b['category']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold text-warning">
                                            <?= number_format($b['price'], 0, ',', '.') ?>đ
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold <?= $b['stock'] <= 0 ? 'text-danger' : ($b['stock'] <= 5 ? 'text-warning' : 'text-success') ?>">
                                                <?= $b['stock'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm <?= $b['is_active'] ? 'btn-success' : 'btn-secondary' ?> btn-toggle-book"
                                                data-id="<?= $b['id'] ?>">
                                                <?= $b['is_active'] ? 'Hiện' : 'Ẩn' ?>
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <a href="?tab=books&edit=<?= $b['id'] ?>"
                                                class="btn btn-sm btn-outline-primary mb-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-del-book"
                                                data-id="<?= $b['id'] ?>"
                                                data-title="<?= htmlspecialchars($b['title']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== TAB: ĐƠN HÀNG ===== -->
    <?php elseif ($tab === 'orders'): ?>

        <!-- Filter + Search -->
        <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius:12px">
            <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                <input type="hidden" name="tab" value="orders">
                <input type="text" name="oq" class="form-control form-control-sm"
                    style="max-width:220px" placeholder="🔍 Tên / SĐT / Mã đơn"
                    value="<?= htmlspecialchars($order_search) ?>">
                <select name="order_status" class="form-select form-select-sm" style="max-width:180px">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach (['new' => 'Mới', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'done' => 'Hoàn thành', 'cancelled' => 'Đã hủy'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $order_status_filter === $v ? 'selected' : '' ?>>
                            <?= $l ?> (<?= $status_counts[$v] ?? 0 ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-primary px-4">Lọc</button>
                <a href="?tab=orders" class="btn btn-sm btn-outline-secondary">Reset</a>
            </form>
        </div>

        <!-- Stat badges -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <?php
            $st_info = ['new' => ['Mới', 'danger'], 'confirmed' => ['Đã xác nhận', 'warning'], 'shipping' => ['Đang giao', 'info'], 'done' => ['Hoàn thành', 'success'], 'cancelled' => ['Đã hủy', 'secondary']];
            foreach ($st_info as $sv => [$sl, $sc]):
            ?>
                <span class="badge bg-<?= $sc ?> rounded-pill">
                    <?= $sl ?>: <?= $status_counts[$sv] ?? 0 ?>
                </span>
            <?php endforeach; ?>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:14px">
            <table class="table table-hover align-middle mb-0" style="font-size:13px">
                <thead class="table-dark">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Giao hàng</th>
                        <th>Tổng tiền</th>
                        <th>Ngày đặt</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Chưa có đơn hàng nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o):
                            $sc_map = ['new' => 's-new', 'confirmed' => 's-confirmed', 'shipping' => 's-shipping', 'done' => 's-done', 'cancelled' => 's-cancelled'];
                            $sl_map = ['new' => 'Mới', 'confirmed' => 'Xác nhận', 'shipping' => 'Đang giao', 'done' => 'Hoàn thành', 'cancelled' => 'Đã hủy'];
                        ?>
                            <tr class="order-row">
                                <td><strong class="text-primary"><?= htmlspecialchars($o['order_code']) ?></strong></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($o['customer_name']) ?></div>
                                    <div class="text-muted"><?= htmlspecialchars($o['phone']) ?></div>
                                </td>
                                <td>
                                    <?= $o['delivery_method'] === 'ship'
                                        ? '<span class="badge bg-info text-dark">🚚 Ship</span>'
                                        : '<span class="badge bg-secondary">🏠 Lấy tại quán</span>' ?>
                                </td>
                                <td class="fw-bold text-warning">
                                    <?= number_format($o['total_amount'], 0, ',', '.') ?>đ
                                </td>
                                <td class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
                                </td>
                                <td class="text-center">
                                    <select class="form-select form-select-sm order-status-select"
                                        data-id="<?= $o['id'] ?>"
                                        style="min-width:130px;font-size:11px">
                                        <?php foreach (['new' => 'Mới', 'confirmed' => 'Xác nhận', 'shipping' => 'Đang giao', 'done' => 'Hoàn thành', 'cancelled' => 'Đã hủy'] as $v => $l): ?>
                                            <option value="<?= $v ?>" <?= $o['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-dark btn-view-order"
                                        data-id="<?= $o['id'] ?>">
                                        Chi tiết
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ===== TAB: TỒN KHO ===== -->
    <?php elseif ($tab === 'stock'): ?>

        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:14px">
            <div class="card-header bg-white py-3 px-4">
                <h5 class="m-0 fw-bold">Quản lý tồn kho sách</h5>
            </div>
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tên sách</th>
                        <th class="text-center">Tồn kho hiện tại</th>
                        <th class="text-center">Đã bán</th>
                        <th style="width:200px">Cập nhật nhanh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $b):
                        $sold_s = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM book_order_items WHERE book_id = ?");
                        $sold_s->execute([$b['id']]);
                        $sold = (int)$sold_s->fetchColumn();
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($b['title']) ?></strong>
                                <div class="small text-muted"><?= htmlspecialchars($b['category']) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold fs-5 <?= $b['stock'] <= 0 ? 'text-danger' : ($b['stock'] <= 5 ? 'text-warning' : 'text-success') ?>">
                                    <?= $b['stock'] ?>
                                </span>
                                <?php if ($b['stock'] <= 5 && $b['stock'] > 0): ?>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:9px">Sắp hết</span>
                                <?php elseif ($b['stock'] <= 0): ?>
                                    <span class="badge bg-danger ms-1" style="font-size:9px">Hết hàng</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-primary fw-bold"><?= $sold ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="update_stock" value="1">
                                    <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                                    <input type="number" name="stock" class="form-control form-control-sm"
                                        value="<?= $b['stock'] ?>" min="0" style="width:80px">
                                    <button type="submit" class="btn btn-sm btn-primary px-3">Lưu</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<!-- Modal: Chi tiết đơn hàng -->
<div class="modal fade" id="modalOrderDetail" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
            <div class="modal-header border-0 bg-dark text-white" style="border-radius:16px 16px 0 0">
                <h5 class="modal-title fw-bold">Chi tiết đơn hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="order-detail-body">
                <div class="text-center py-4 text-muted">Đang tải...</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Xóa sách -->
<div class="modal fade" id="modalDeleteBook" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px">
            <div class="modal-header bg-danger text-white border-0" style="border-radius:14px 14px 0 0">
                <h6 class="modal-title fw-bold">Xác nhận xóa</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fw-bold" id="del-book-title"></p>
                <small class="text-danger">Hành động không thể hoàn tác.</small>
            </div>
            <div class="modal-footer border-0 pb-4 gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" class="flex-fill">
                    <input type="hidden" name="delete_book_id" id="del-book-id">
                    <button type="submit" class="btn btn-danger w-100 fw-bold">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
    // ---- Xóa sách ----
    document.querySelectorAll('.btn-del-book').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('del-book-title').textContent = this.dataset.title;
            document.getElementById('del-book-id').value = this.dataset.id;
            new bootstrap.Modal(document.getElementById('modalDeleteBook')).show();
        });
    });

    // ---- Toggle hiện/ẩn sách ----
    document.querySelectorAll('.btn-toggle-book').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var self = this;
            $.post(location.pathname, {
                toggle_book_id: id
            }, function(r) {
                if (r.status !== 'success') return;
                var active = r.is_active;
                self.className = 'btn btn-sm ' + (active ? 'btn-success' : 'btn-secondary') + ' btn-toggle-book';
                self.textContent = active ? 'Hiện' : 'Ẩn';
                self.closest('tr').classList.toggle('opacity-50', !active);
            }, 'json');
        });
    });

    // ---- Cập nhật trạng thái đơn hàng ----
    document.querySelectorAll('.order-status-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var id = this.dataset.id;
            var status = this.value;
            $.post(location.pathname + '?tab=orders', {
                update_order_status: 1,
                order_id: id,
                status: status
            }, function(r) {
                if (r.status === 'success') {
                    // Flash nhỏ
                    var t = document.createElement('div');
                    t.style.cssText = 'position:fixed;bottom:16px;right:16px;z-index:9999;background:#198754;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.2)';
                    t.textContent = '✓ Đã cập nhật';
                    document.body.appendChild(t);
                    setTimeout(function() {
                        t.remove();
                    }, 2000);
                }
            }, 'json');
        });
    });

    // ---- Chi tiết đơn hàng ----
    <?php
    // Chuẩn bị data đơn hàng cho JS
    $orders_detail = [];
    foreach ($orders as $o) {
        $oi_s = $db->prepare("SELECT * FROM book_order_items WHERE order_id = ?");
        $oi_s->execute([$o['id']]);
        $o['items'] = $oi_s->fetchAll(PDO::FETCH_ASSOC);
        $orders_detail[$o['id']] = $o;
    }
    ?>
    var ordersData = <?= json_encode($orders_detail) ?>;

    document.querySelectorAll('.btn-view-order').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var o = ordersData[id];
            if (!o) return;
            var delivery = o.delivery_method === 'ship' ?
                '🚚 Giao tận nơi — ' + (o.address || '—') :
                '🏠 Đến lấy tại quán';
            var items_html = '';
            (o.items || []).forEach(function(item) {
                items_html += '<tr><td>' + escHtml(item.book_title) + '</td><td class="text-center">' + item.quantity + '</td><td class="text-end">' + parseInt(item.price * item.quantity).toLocaleString('vi-VN') + 'đ</td></tr>';
            });
            document.getElementById('order-detail-body').innerHTML =
                '<div class="row g-3 mb-3">' +
                '<div class="col-md-6"><div class="small text-muted">Mã đơn</div><div class="fw-bold text-primary">' + escHtml(o.order_code) + '</div></div>' +
                '<div class="col-md-6"><div class="small text-muted">Ngày đặt</div><div>' + o.created_at + '</div></div>' +
                '<div class="col-md-6"><div class="small text-muted">Khách hàng</div><div class="fw-bold">' + escHtml(o.customer_name) + '</div><div>' + escHtml(o.phone) + '</div></div>' +
                '<div class="col-md-6"><div class="small text-muted">Giao hàng</div><div>' + escHtml(delivery) + '</div></div>' +
                (o.note ? '<div class="col-12"><div class="small text-muted">Ghi chú</div><div class="fst-italic">' + escHtml(o.note) + '</div></div>' : '') +
                '</div>' +
                '<table class="table table-sm"><thead class="table-light"><tr><th>Sách</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead><tbody>' +
                items_html +
                '</tbody><tfoot><tr><th colspan="2">Tổng cộng</th><th class="text-end text-warning fw-bold">' + parseInt(o.total_amount).toLocaleString('vi-VN') + 'đ</th></tr></tfoot></table>';
            new bootstrap.Modal(document.getElementById('modalOrderDetail')).show();
        });
    });

    function escHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
</script>
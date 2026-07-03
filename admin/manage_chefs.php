<?php
ob_start();
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/csrf.php';

$db = (new Database())->getConnection();

// Automated DB upgrades
try {
    $db->exec("ALTER TABLE chefs ADD COLUMN IF NOT EXISTS awards TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE chefs ADD COLUMN IF NOT EXISTS signature_dishes VARCHAR(255) DEFAULT NULL");
    $db->exec("ALTER TABLE chefs ADD COLUMN IF NOT EXISTS service_fee INT DEFAULT 250000");
    
    // Create chef_reviews table if it does not exist
    $db->exec("CREATE TABLE IF NOT EXISTS chef_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chef_id INT NOT NULL,
        user_id INT NULL,
        author_name VARCHAR(100) DEFAULT 'Khách ẩn danh',
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $db->exec("ALTER TABLE chef_reviews ADD COLUMN IF NOT EXISTS author_name VARCHAR(100) DEFAULT 'Khách ẩn danh'");
} catch (Exception $e) {
    // Ignore database upgrade errors
}

$message_success = '';
$message_error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $message_error = "Lỗi bảo mật (CSRF): Yêu cầu không hợp lệ! Vui lòng tải lại trang.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create' || $action === 'edit') {
            $id = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $experience = (int)($_POST['experience'] ?? 0);
            $specialty = trim($_POST['specialty'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $quote = trim($_POST['quote'] ?? '');
            $facebook = trim($_POST['facebook'] ?? '');
            $instagram = trim($_POST['instagram'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $sort_order = (int)($_POST['sort_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $awards = trim($_POST['awards'] ?? '');
            $sig_dishes_arr = $_POST['signature_dishes'] ?? [];
            $signature_technique = trim($_POST['signature_technique'] ?? '');
            $signature_technique_specs = trim($_POST['signature_technique_specs'] ?? '');
            $signature_technique_process = trim($_POST['signature_technique_process'] ?? '');
            $signature_technique_quote = trim($_POST['signature_technique_quote'] ?? '');
            $signature_technique_difficulty = trim($_POST['signature_technique_difficulty'] ?? '');
            $signature_technique_final_result = trim($_POST['signature_technique_final_result'] ?? '');
            $signature_dishes = !empty($sig_dishes_arr) ? implode(',', array_map('intval', $sig_dishes_arr)) : null;
            $service_fee = (int)($_POST['service_fee'] ?? 0);

            if (empty($name)) {
                $message_error = "Vui lòng nhập họ tên đầu bếp.";
            } else {
                try {
                    $message_success_extra = '';

                    // Xử lý upload ảnh đại diện
                    $image_name = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $target_dir = "../public/assets/img/chefs/";
                            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                            $image_name = time() . '_' . uniqid() . '.' . $ext;
                            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);
                        }
                    }

                    if ($action === 'create') {
                        $stmt = $db->prepare("INSERT INTO chefs (name, position, image, experience, specialty, description, quote, facebook, instagram, email, is_active, is_featured, sort_order, awards, signature_dishes, service_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt->execute([$name, $position, $image_name, $experience, $specialty, $description, $quote, $facebook, $instagram, $email, $is_active, $is_featured, $sort_order, $awards, $signature_dishes, $service_fee])) {
                            // TỰ ĐỘNG ĐỒNG BỘ SANG BẢNG NHÂN SỰ
                            try {
                                $sync_stmt = $db->prepare("INSERT INTO employees (full_name, email, position, salary, status) VALUES (?, ?, ?, 0, 'working')");
                                $sync_stmt->execute([$name, $email, $position]);
                                $message_success = "Đã thêm đầu bếp và đồng bộ tự động sang Quản lý Nhân sự." . ($message_success_extra ? " " . $message_success_extra : "");
                            } catch (Exception $sync_e) {
                                $message_success = "Đã thêm đầu bếp (Nhưng lỗi đồng bộ Nhân sự: " . $sync_e->getMessage() . ")." . ($message_success_extra ? " " . $message_success_extra : "");
                            }
                        } else {
                            $message_error = "Có lỗi xảy ra khi thêm đầu bếp: " . implode(" - ", $stmt->errorInfo());
                        }
                    } else {
                        if ($image_name) {
                            $stmt_old = $db->prepare("SELECT image FROM chefs WHERE id=?");
                            $stmt_old->execute([$id]);
                            $old_row = $stmt_old->fetch(PDO::FETCH_ASSOC);
                            if ($old_row && !empty($old_row['image']) && file_exists("../public/assets/img/chefs/" . $old_row['image'])) {
                                @unlink("../public/assets/img/chefs/" . $old_row['image']);
                            }
                            $stmt = $db->prepare("UPDATE chefs SET name=?, position=?, image=?, experience=?, specialty=?, description=?, quote=?, facebook=?, instagram=?, email=?, is_active=?, is_featured=?, sort_order=?, awards=?, signature_dishes=?, service_fee=? WHERE id=?");
                            $success = $stmt->execute([$name, $position, $image_name, $experience, $specialty, $description, $quote, $facebook, $instagram, $email, $is_active, $is_featured, $sort_order, $awards, $signature_dishes, $service_fee, $id]);
                        } else {
                            $stmt = $db->prepare("UPDATE chefs SET name=?, position=?, experience=?, specialty=?, description=?, quote=?, facebook=?, instagram=?, email=?, is_active=?, is_featured=?, sort_order=?, awards=?, signature_dishes=?, service_fee=? WHERE id=?");
                            $success = $stmt->execute([$name, $position, $experience, $specialty, $description, $quote, $facebook, $instagram, $email, $is_active, $is_featured, $sort_order, $awards, $signature_dishes, $service_fee, $id]);
                        }
                        if ($success) {
                            $message_success = "Cập nhật thông tin đầu bếp thành công." . ($message_success_extra ? " " . $message_success_extra : "");
                        } else {
                            $message_error = "Có lỗi xảy ra khi cập nhật: " . implode(" - ", $stmt->errorInfo());
                        }
                    }
                } catch (PDOException $e) {
                    $message_error = "Lỗi Database: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt_old = $db->prepare("SELECT image FROM chefs WHERE id=?");
                $stmt_old->execute([$id]);
                $old_row = $stmt_old->fetch(PDO::FETCH_ASSOC);
                if ($old_row && !empty($old_row['image']) && file_exists("../public/assets/img/chefs/" . $old_row['image'])) {
                    @unlink("../public/assets/img/chefs/" . $old_row['image']);
                }
                $stmt = $db->prepare("DELETE FROM chefs WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message_success = "Đã xóa đầu bếp thành công.";
                } else {
                    $message_error = "Lỗi khi xóa đầu bếp.";
                }
            }
        }
    }
}

// Filter & Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR position LIKE ? OR specialty LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 3, $search_param);
}

$where_clause = implode(" AND ", $where);

// Count records
$stmt_count = $db->prepare("SELECT COUNT(*) FROM chefs WHERE $where_clause");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$sql = "SELECT * FROM chefs WHERE $where_clause ORDER BY sort_order ASC, id DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active foods for signature dishes selection
$foods = $db->query("SELECT id, name FROM foods WHERE status = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    .table-hover tbody tr:hover { background-color: #f8fafc; }
    .badge-status { font-weight: 500; padding: 0.4em 0.8em; }
    .avatar-placeholder { width: 45px; height: 45px; background-color: #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b; }
    .card-custom { border-radius: 10px; }
    
    /* Ensure the admin section ignores the frontend body style completely to maintain white background */
    body { background: #f4f6f9 !important; color: #333 !important; }

    /* Sticky Tab Bar inside Modal Dialog */
    #chefModalTab {
        position: sticky;
        top: -24px;
        background: #ffffff;
        z-index: 1030;
        padding-top: 15px;
        padding-bottom: 10px;
        margin-top: -24px;
        border-bottom: 1px solid #dee2e6;
    }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="fas fa-user-tie me-2 text-primary"></i> Quản lý Đầu Bếp</h4>
        <button class="btn btn-primary shadow-sm" onclick="openModal('create')">
            <i class="fas fa-plus me-2"></i> Thêm Đầu Bếp
        </button>
    </div>

    <?php if ($message_success): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($message_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($message_error): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($message_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="card card-custom p-3 mb-4 shadow-sm border-0">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm tên, chức vụ, chuyên môn..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-secondary">Tìm kiếm</button>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <a href="manage_chefs.php" class="btn btn-light"><i class="fas fa-sync-alt"></i> Làm mới</a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="card card-custom p-0 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-dark">
                <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                    <tr>
                        <th class="ps-4">Thứ tự</th>
                        <th>Đầu bếp</th>
                        <th>Chuyên môn / Kinh nghiệm</th>
                        <th>Nổi bật</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($chefs) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-user-slash fa-3x mb-3 text-light"></i>
                                <h5>Chưa có dữ liệu đầu bếp</h5>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($chefs as $chef): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-secondary"><?= $chef['sort_order'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($chef['image']): ?>
                                        <img src="/restaurant-project/public/assets/img/chefs/<?= htmlspecialchars($chef['image']) ?>" class="shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border-radius: 10px;" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?= strtoupper(mb_substr($chef['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($chef['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($chef['position']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-danger fw-bold">
                                <?= number_format($chef['service_fee'] ?? 0, 0, ',', '.') ?> đ
                            </td>
                            <td>
                                <div class="small fw-medium text-dark"><?= htmlspecialchars($chef['specialty'] ?: 'Chưa cập nhật') ?></div>
                                <div class="small text-muted"><?= $chef['experience'] ?> năm kinh nghiệm</div>
                            </td>
                            <td>
                                <?php if ($chef['is_featured']): ?>
                                    <span class="badge bg-warning text-dark badge-status"><i class="fas fa-star text-warning"></i> Nổi bật</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($chef['is_active']): ?>
                                    <span class="badge bg-success badge-status">Đang hiển thị</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary badge-status">Đã ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="manage_chef_reviews.php?chef_id=<?= $chef['id'] ?>" class="btn btn-sm btn-outline-warning rounded-circle me-1" title="Xem nhận xét">
                                    <i class="fas fa-comment-dots"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Thư viện ảnh"
                                    onclick='openModal("edit", <?= htmlspecialchars(json_encode($chef, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)) ?>, "gallery-tab")'>
                                    <i class="fas fa-images"></i>
                                </button>

                                <button class="btn btn-sm btn-outline-info rounded-circle me-1" title="Chỉnh sửa thông tin"
                                    onclick='openModal("edit", <?= htmlspecialchars(json_encode($chef, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)) ?>, "info-tab")'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đầu bếp này? Dữ liệu không thể khôi phục.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $chef['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white p-3 border-top d-flex justify-content-end">
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="chefModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background:#fff;">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-plus me-2"></i> Thêm Đầu Bếp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-dark">
                <!-- Tab Nav -->
                <ul class="nav nav-tabs mb-4" id="chefModalTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab" aria-controls="tab-info" aria-selected="true">
                            <i class="fas fa-info-circle me-1"></i> Thông tin cơ bản
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="gallery-tab" data-bs-toggle="tab" data-bs-target="#tab-gallery" type="button" role="tab" aria-controls="tab-gallery" aria-selected="false">
                            <i class="fas fa-images me-1"></i> Thư viện ảnh (Gallery)
                        </button>
                    </li>

                </ul>

                <div class="tab-content" id="chefModalTabContent">
                    <!-- Tab 1: Info -->
                    <div class="tab-pane fade show active" id="tab-info" role="tabpanel" aria-labelledby="info-tab">
                        <form method="POST" id="chefForm" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" id="formAction" value="create">
                            <input type="hidden" name="id" id="chefId">
                            
                            <div class="row g-3">
                                <div class="col-md-12 text-center mb-3">
                                    <label class="form-label fw-bold d-block text-dark">Ảnh đại diện (Avatar)</label>
                                    <img id="previewImage" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; display: none; margin: 0 auto 10px auto; border: 3px solid #cda45e;">
                                    <input type="file" class="form-control form-control-sm mx-auto" style="max-width: 300px;" name="image" id="chefImage" accept="image/*" onchange="previewFile(this)">
                                    <small class="text-muted">Định dạng hỗ trợ: JPG, PNG, GIF</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Họ và Tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" id="chefName" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Chức vụ <span class="text-danger">*</span></label>
                                    <select name="position" id="chefPosition" class="form-select" required>
                                        <option value="Bếp trưởng">Bếp trưởng</option>
                                        <option value="Bếp phó">Bếp phó</option>
                                        <option value="Bếp chính">Bếp chính</option>
                                        <option value="Đầu bếp">Đầu bếp</option>
                                        <option value="Phụ bếp">Phụ bếp</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Kinh nghiệm (năm)</label>
                                    <input type="number" class="form-control" name="experience" id="chefExperience" min="0" value="0">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold text-dark">Chuyên môn</label>
                                    <input type="text" class="form-control" name="specialty" id="chefSpecialty" placeholder="VD: Ẩm thực Âu, Á, Fusion...">
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Mô tả chi tiết</label>
                                    <textarea class="form-control" name="description" id="chefDescription" rows="3"></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Câu nói nổi bật</label>
                                    <input type="text" class="form-control" name="quote" id="chefQuote" placeholder="VD: Nấu ăn là một nghệ thuật...">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                                    <input type="text" class="form-control" name="facebook" id="chefFacebook" placeholder="Link Facebook">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark"><i class="fab fa-instagram text-danger"></i> Instagram</label>
                                    <input type="text" class="form-control" name="instagram" id="chefInstagram" placeholder="Link Instagram">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark"><i class="fas fa-envelope text-success"></i> Email liên hệ</label>
                                    <input type="email" class="form-control" name="email" id="chefEmail" placeholder="Email">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Thứ tự hiển thị</label>
                                    <input type="number" class="form-control" name="sort_order" id="chefSortOrder" value="0">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Phí dịch vụ (VNĐ) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control text-danger fw-bold" name="service_fee" id="chefServiceFee" value="250000" min="0" step="1000" required>
                                </div>
                                
                                <div class="col-md-4 d-flex align-items-center gap-4 mt-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="chefIsActive" checked value="1">
                                        <label class="form-check-label fw-bold text-dark" for="chefIsActive">Hiển thị trên web</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_featured" id="chefIsFeatured" value="1">
                                        <label class="form-check-label fw-bold text-dark" for="chefIsFeatured">Ghim nổi bật</label>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold text-primary border-bottom pb-2"><i class="fas fa-fire me-2"></i>Tuyệt Kỹ Chế Biến (Signature Technique)</h6>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Timeline Quy trình (Mỗi bước 1 dòng hoặc bắt đầu bằng số)</label>
                                    <textarea class="form-control" name="signature_technique_process" id="chefSigProcess" rows="4" placeholder="1. Xử lý ikejime ngay khi cá còn sống...&#10;2. Làm sạch hoàn toàn máu và nội tạng..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Thành quả cuối cùng</label>
                                    <textarea class="form-control" name="signature_technique_final_result" id="chefSigFinal" rows="2" placeholder="Thịt mềm tan trong miệng, hương vị bùng nổ..."></textarea>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold text-primary border-bottom pb-2"><i class="fas fa-award me-2"></i>Giải thưởng & Món đặc trưng</h6>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Giải thưởng & Chuyên môn (Awards & Expertise)</label>
                                    <textarea class="form-control" name="awards" id="chefAwards" rows="3" placeholder="Định dạng: Tên giải thưởng | Chi tiết giải thưởng | Class icon Bootstrap (Tự chọn)&#10;Mỗi giải thưởng viết trên 1 dòng. Ví dụ:&#10;Sao Michelin | Đạt năm 2022 | trophy&#10;Le Cordon Bleu | Tốt nghiệp xuất sắc | mortarboard"></textarea>
                                    <small class="text-muted">Nhập mỗi giải thưởng trên 1 dòng, phân tách tên và chi tiết bằng ký tự gạch đứng (|). Icon tùy chọn có thể là: trophy, award, mortarboard, star, etc.</small>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Các món ăn đặc trưng (Signature Dishes)</label>
                                    <div class="border p-3 rounded" style="max-height: 200px; overflow-y: auto; background: #fff;">
                                        <div class="row g-2">
                                            <?php foreach ($foods as $food): ?>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input signature-dish-cb" type="checkbox" name="signature_dishes[]" value="<?= $food['id'] ?>" id="sd_<?= $food['id'] ?>">
                                                        <label class="form-check-label text-dark" for="sd_<?= $food['id'] ?>">
                                                            <?= htmlspecialchars($food['name']) ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Tích chọn các món ăn tiêu biểu do chính tay đầu bếp này chế biến để hiển thị trong chi tiết.</small>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <div class="text-end">
                                <button type="button" class="btn btn-light me-2 text-dark" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary" id="btnSubmit">Lưu Thông Tin</button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Gallery -->
                    <div class="tab-pane fade" id="tab-gallery" role="tabpanel" aria-labelledby="gallery-tab">
                        <div id="gallery-placeholder" class="alert alert-warning m-2">
                            <i class="fas fa-exclamation-triangle me-2"></i> Vui lòng lưu thông tin cơ bản của đầu bếp trước khi quản lý Thư viện ảnh (Gallery).
                        </div>
                        <div id="gallery-manager-content" style="display: none;">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Tải lên ảnh mới (Tối đa 6 ảnh tổng cộng)</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="galleryUploadInput" accept="image/*" multiple>
                                        <button class="btn btn-primary" type="button" onclick="uploadGalleryImages()"><i class="fas fa-upload me-1"></i> Tải lên</button>
                                    </div>
                                    <small class="text-muted">Hỗ trợ JPG, PNG, WEBP, GIF. Tối đa 5MB/ảnh. Chọn nhiều ảnh bằng cách giữ Ctrl/Shift.</small>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold m-0"><i class="fas fa-images me-1 text-primary"></i> Danh sách ảnh hiện có (<span id="galleryCountText">0</span>/6 ảnh)</h6>
                                <button type="button" class="btn btn-sm btn-success" onclick="saveGallerySort()"><i class="fas fa-save me-1"></i> Cập nhật thứ tự</button>
                            </div>
                            <div id="galleryThumbnailsList" class="d-flex flex-wrap gap-3">
                                <!-- Load động bằng JS -->
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let myModal;
    let currentChefId = null;
    const csrfToken = '<?= csrf_token() ?>';

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val !== null && val !== undefined ? val : '';
    };

    function openModal(mode, data = null, activeTabId = 'info-tab') {
        if (!myModal) {
            myModal = new bootstrap.Modal(document.getElementById('chefModal'));
        }
        
        // Reset tabs to Basic Info tab on open
        const firstTab = document.getElementById('info-tab');
        if (firstTab) firstTab.click();

        document.getElementById('formAction').value = mode;
        document.getElementById('chefForm').reset();
        document.getElementById('previewImage').style.display = 'none';
        document.querySelectorAll('.signature-dish-cb').forEach(cb => cb.checked = false);

        if (mode === 'create') {
            currentChefId = null;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i> Thêm Đầu Bếp Mới';
            document.getElementById('btnSubmit').innerText = 'Thêm Đầu Bếp';
            document.getElementById('chefId').value = '';
            
            // Hide managers and show placeholders
            document.getElementById('gallery-placeholder').style.display = 'block';
            document.getElementById('gallery-manager-content').style.display = 'none';

            // Default switches
            document.getElementById('chefIsActive').checked = false;
            document.getElementById('chefIsFeatured').checked = false;
            setVal('chefServiceFee', 250000);
        } else {
            currentChefId = data.id;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i> Cập nhật Thông tin Đầu Bếp';
            document.getElementById('btnSubmit').innerText = 'Cập Nhật';
            
            // Show managers and hide placeholders
            document.getElementById('gallery-placeholder').style.display = 'none';
            document.getElementById('gallery-manager-content').style.display = 'block';

            document.getElementById('chefId').value = data.id;
            setVal('chefName', data.name);
            
            // Set position select
            let posSelect = document.getElementById('chefPosition');
            if (posSelect) {
                let found = false;
                for(let i=0; i<posSelect.options.length; i++) {
                    if(posSelect.options[i].value === data.position) {
                        posSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if(!found && data.position) {
                    let opt = document.createElement('option');
                    opt.value = data.position;
                    opt.innerHTML = data.position;
                    posSelect.appendChild(opt);
                    posSelect.value = data.position;
                }
            }

            setVal('chefExperience', data.experience);
            setVal('chefSpecialty', data.specialty);
            setVal('chefDescription', data.description);
            setVal('chefQuote', data.quote);
            setVal('chefFacebook', data.facebook);
            setVal('chefInstagram', data.instagram);
            setVal('chefEmail', data.email);
            setVal('chefSortOrder', data.sort_order);
            setVal('chefServiceFee', data.service_fee || 0);
            
            document.getElementById('chefIsActive').checked = data.is_active == 1;
            document.getElementById('chefIsFeatured').checked = data.is_featured == 1;
            
            setVal('chefAwards', data.awards);
            setVal('chefSigProcess', data.signature_technique_process);
            setVal('chefSigFinal', data.signature_technique_final_result);

            if (data.signature_dishes) {
                let dishes = data.signature_dishes.split(',');
                dishes.forEach(id => {
                    let cb = document.getElementById('sd_' + id.trim());
                    if (cb) cb.checked = true;
                });
            }

            let preview = document.getElementById('previewImage');
            if(data.image) {
                preview.src = '/restaurant-project/public/assets/img/chefs/' + data.image;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }

            // Load Gallery via AJAX
            fetchChefGallery();
        }
        
        myModal.show();

        // Switch to the requested tab
        const targetTab = document.getElementById(activeTabId);
        if (targetTab) {
            setTimeout(() => {
                targetTab.click();
            }, 100);
        }
    }

    function previewFile(input) {
        var file = input.files[0];
        if(file){
            var reader = new FileReader();
            reader.onload = function(){
                var preview = document.getElementById('previewImage');
                preview.src = reader.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    }

    // ===== GALLERY AJAX =====
    function fetchChefGallery() {
        if (!currentChefId) return;
        const container = document.getElementById('galleryThumbnailsList');
        container.innerHTML = '<div class="text-muted w-100 py-3 text-center"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải ảnh...</div>';
        
        fetch('ajax/ajax_chef_gallery.php?action=fetch&chef_id=' + currentChefId + '&csrf_token=' + csrfToken)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    renderGallery(res.data);
                } else {
                    container.innerHTML = '<div class="alert alert-danger w-100">' + res.message + '</div>';
                }
            })
            .catch(err => {
                container.innerHTML = '<div class="alert alert-danger w-100">Lỗi tải dữ liệu.</div>';
            });
    }

    function renderGallery(images) {
        const container = document.getElementById('galleryThumbnailsList');
        container.innerHTML = '';
        
        document.getElementById('galleryCountText').textContent = images.length;
        
        if (images.length === 0) {
            container.innerHTML = '<div class="text-muted py-4 text-center w-100"><i class="fas fa-images fa-2x mb-2 d-block opacity-50"></i>Chưa có ảnh nào trong thư viện (Tối đa 6 ảnh)</div>';
            return;
        }
        
        images.forEach(img => {
            const card = document.createElement('div');
            card.className = 'card shadow-sm border p-2 position-relative d-flex flex-column align-items-center bg-light';
            card.style.width = '160px';
            card.innerHTML = `
                <div class="position-relative w-100" style="height: 100px; overflow: hidden; border-radius: 4px;">
                    <img src="/restaurant-project/public/assets/img/chefs/gallery/${img.image}" style="width: 100%; height: 100%; object-fit: cover;" alt="Gallery">
                </div>
                <div class="d-flex justify-content-between align-items-center w-100 mt-2 gap-1">
                    <input type="number" class="form-control form-control-sm text-center sort-order-input px-1" data-id="${img.id}" value="${img.sort_order}" title="Thứ tự hiển thị" style="width: 60px;">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteGalleryImage(${img.id})" title="Xóa ảnh này"><i class="fas fa-trash-alt"></i></button>
                </div>
                <div class="w-100 mt-1">
                    <label class="btn btn-sm btn-outline-secondary w-100 py-1" style="font-size: 11px; cursor: pointer;">
                        <i class="fas fa-sync-alt me-1"></i>Thay thế
                        <input type="file" style="display:none;" onchange="replaceGalleryImage(${img.id}, this)" accept="image/*">
                    </label>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function uploadGalleryImages() {
        const input = document.getElementById('galleryUploadInput');
        if (input.files.length === 0) {
            alert('Vui lòng chọn ít nhất 1 ảnh để tải lên.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('chef_id', currentChefId);
        formData.append('csrf_token', csrfToken);
        for (let i = 0; i < input.files.length; i++) {
            formData.append('image[]', input.files[i]);
        }

        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';

        fetch('ajax/ajax_chef_gallery.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (res.success) {
                input.value = '';
                fetchChefGallery();
                if(res.errors && res.errors.length > 0){
                    alert('Upload thành công nhưng có một số file lỗi:\n' + res.errors.join('\n'));
                }
            } else {
                alert(res.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('Đã xảy ra lỗi khi tải ảnh lên.');
        });
    }

    function deleteGalleryImage(id) {
        if (!confirm('Bạn có chắc muốn xóa ảnh này khỏi thư viện?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('csrf_token', csrfToken);

        fetch('ajax/ajax_chef_gallery.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                fetchChefGallery();
            } else {
                alert(res.message);
            }
        });
    }

    function replaceGalleryImage(id, inputElement) {
        const file = inputElement.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'replace');
        formData.append('id', id);
        formData.append('image', file);
        formData.append('csrf_token', csrfToken);

        fetch('ajax/ajax_chef_gallery.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                fetchChefGallery();
            } else {
                alert(res.message);
            }
        });
    }

    function saveGallerySort() {
        const inputs = document.querySelectorAll('.sort-order-input');
        const orders = [];
        inputs.forEach(input => {
            orders.push({
                id: input.getAttribute('data-id'),
                sort_order: input.value
            });
        });

        if (orders.length === 0) return;

        const formData = new FormData();
        formData.append('action', 'sort');
        formData.append('orders', JSON.stringify(orders));
        formData.append('csrf_token', csrfToken);

        fetch('ajax/ajax_chef_gallery.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            alert(res.message);
            if (res.success) {
                fetchChefGallery();
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>

</div> <!-- Đóng content-area -->
</div> <!-- Đóng main-wrapper -->
</body>
</html>
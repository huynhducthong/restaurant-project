<?php
ob_start();
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/csrf.php';

$db = (new Database())->getConnection();

$message_success = '';
$message_error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $message_error = "Lỗi bảo mật (CSRF): Yêu cầu không hợp lệ! Vui lòng tải lại trang.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'toggle_status') {
            $id = (int)($_POST['id'] ?? 0);
            $current_status = $_POST['status'] ?? '';
            $new_status = ($current_status === 'approved') ? 'rejected' : 'approved';
            
            if ($id) {
                $stmt = $db->prepare("UPDATE chef_reviews SET status = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $id])) {
                    $message_success = "Cập nhật trạng thái nhận xét thành công.";
                } else {
                    $message_error = "Không thể cập nhật trạng thái nhận xét.";
                }
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $author_name = trim($_POST['author_name'] ?? '');
            $rating = (int)($_POST['rating'] ?? 5);
            $comment = trim($_POST['comment'] ?? '');
            
            if (empty($comment)) {
                $message_error = "Nội dung nhận xét không được để trống.";
            } elseif ($rating < 1 || $rating > 5) {
                $message_error = "Đánh giá sao phải từ 1 đến 5.";
            } elseif ($id) {
                if (empty($author_name)) {
                    $author_name = 'Khách ẩn danh';
                }
                $stmt = $db->prepare("UPDATE chef_reviews SET author_name = ?, rating = ?, comment = ? WHERE id = ?");
                if ($stmt->execute([$author_name, $rating, $comment, $id])) {
                    $message_success = "Cập nhật nhận xét thành công.";
                } else {
                    $message_error = "Không thể cập nhật nhận xét.";
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $db->prepare("DELETE FROM chef_reviews WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message_success = "Đã xóa nhận xét thành công.";
                } else {
                    $message_error = "Lỗi khi xóa nhận xét.";
                }
            }
        }
    }
}

// Filters & Pagination
$chef_filter = isset($_GET['chef_id']) ? (int)$_GET['chef_id'] : 0;
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($chef_filter > 0) {
    $where[] = "r.chef_id = ?";
    $params[] = $chef_filter;
}
if ($status_filter !== '') {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
}
if ($search !== '') {
    $where[] = "(r.comment LIKE ? OR r.author_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(" AND ", $where);

// Count total records
$stmt_count = $db->prepare("SELECT COUNT(*) FROM chef_reviews r WHERE $where_clause");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch reviews
$sql = "SELECT r.*, c.name as chef_name, c.image as chef_image 
        FROM chef_reviews r 
        LEFT JOIN chefs c ON r.chef_id = c.id 
        WHERE $where_clause 
        ORDER BY r.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all chefs for filter dropdown
$chefs = $db->query("SELECT id, name FROM chefs ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .table-hover tbody tr:hover { background-color: #f8fafc; }
    .badge-status { font-weight: 500; padding: 0.4em 0.8em; }
    .card-custom { border-radius: 10px; }
    body { background: #f4f6f9 !important; color: #333 !important; }
    .star-yellow { color: #ffc107; }
    .star-grey { color: #e2e8f0; }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="fas fa-comments me-2 text-primary"></i> Quản lý Đánh giá Đầu bếp</h4>
        <a href="manage_chefs.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Quay lại Đầu Bếp
        </a>
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

    <!-- Toolbar Filters -->
    <div class="card card-custom p-3 mb-4 shadow-sm border-0">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Lọc theo Đầu bếp</label>
                <select name="chef_id" class="form-select">
                    <option value="0">Tất cả đầu bếp</option>
                    <?php foreach ($chefs as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $chef_filter == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Trạng thái hiển thị</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Đang hiển thị</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Đang ẩn</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Tìm kiếm nội dung / Tác giả</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Từ khóa..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2 mt-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                <a href="manage_chef_reviews.php" class="btn btn-light" title="Làm mới"><i class="fas fa-sync-alt"></i></a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="card card-custom p-0 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-dark">
                <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                    <tr>
                        <th class="ps-4" style="width: 15%;">Đầu bếp</th>
                        <th style="width: 15%;">Người gửi</th>
                        <th style="width: 12%;">Đánh giá</th>
                        <th>Nội dung bình luận</th>
                        <th style="width: 12%;">Ngày gửi</th>
                        <th style="width: 12%;">Trạng thái</th>
                        <th class="text-end pe-4" style="width: 15%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reviews) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-comment-slash fa-3x mb-3 text-light"></i>
                                <h5>Không tìm thấy đánh giá nào</h5>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($rev['chef_name'] ?? 'Đã xóa') ?></div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><?= htmlspecialchars($rev['author_name']) ?></div>
                            </td>
                            <td>
                                <div>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $rev['rating'] ? 'star-yellow' : 'star-grey' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-wrap text-muted small" style="max-width: 400px; word-break: break-word;">
                                    <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?></div>
                            </td>
                            <td>
                                <?php if ($rev['status'] === 'approved'): ?>
                                    <span class="badge bg-success badge-status">Đang hiển thị</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary badge-status">Đã ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <!-- Toggle show/hide button -->
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $rev['status'] ?>">
                                    <?php if ($rev['status'] === 'approved'): ?>
                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-circle me-1" title="Ẩn nhận xét">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-circle me-1" title="Hiển thị nhận xét">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>

                                <!-- Edit button -->
                                <button class="btn btn-sm btn-outline-info rounded-circle me-1" title="Chỉnh sửa"
                                    onclick='openEditModal(<?= htmlspecialchars(json_encode($rev, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Delete button -->
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhận xét này vĩnh viễn?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $rev['id'] ?>">
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
                        <a class="page-link" href="?page=<?= $page - 1 ?>&chef_id=<?= $chef_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&chef_id=<?= $chef_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&chef_id=<?= $chef_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Sau</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Chỉnh sửa Đánh giá -->
<div class="modal fade" id="editReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background:#fff;">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Chỉnh sửa nhận xét</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-dark">
                <form method="POST" id="editReviewForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editReviewId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Đầu bếp</label>
                        <input type="text" class="form-control bg-light" id="editReviewChefName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Họ tên người gửi</label>
                        <input type="text" class="form-control" name="author_name" id="editReviewAuthor" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Số sao đánh giá</label>
                        <select name="rating" id="editReviewRating" class="form-select" required>
                            <option value="5">5 sao</option>
                            <option value="4">4 sao</option>
                            <option value="3">3 sao</option>
                            <option value="2">2 sao</option>
                            <option value="1">1 sao</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nội dung bình luận</label>
                        <textarea class="form-control" name="comment" id="editReviewComment" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Phản hồi từ Đầu bếp (Chef Response)</label>
                        <textarea class="form-control" name="chef_response" id="editChefResponse" rows="4" placeholder="Nhập lời cảm ơn hoặc phản hồi của Bếp trưởng tại đây..."></textarea>
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-light me-2 text-dark" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let editModal;

    function openEditModal(data) {
        if (!editModal) {
            editModal = new bootstrap.Modal(document.getElementById('editReviewModal'));
        }
        
        document.getElementById('editReviewId').value = data.id;
        document.getElementById('editReviewChefName').value = data.chef_name;
        document.getElementById('editReviewAuthor').value = data.author_name;
        document.getElementById('editReviewRating').value = data.rating;
        document.getElementById('editReviewComment').value = data.comment;
        document.getElementById('editChefResponse').value = data.chef_response || '';
        
        editModal.show();
    }
</script>

</div> <!-- Đóng content-area -->
</div> <!-- Đóng main-wrapper -->
</body>
</html>

<?php
ob_start();
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = (new Database())->getConnection();

// 1. Cập nhật bảng (Thêm is_starred và status replied)
try {
    $db->exec("ALTER TABLE contacts ADD COLUMN is_starred TINYINT(1) DEFAULT 0");
} catch (\Exception $e) {}
try {
    $db->exec("ALTER TABLE contacts MODIFY COLUMN status ENUM('new','read','replied') DEFAULT 'new'");
} catch (\Exception $e) {}

$message_success = '';
$message_error = '';

// 2. Xử lý Bulk Actions
if (isset($_POST['bulk_action']) && isset($_POST['contact_ids']) && is_array($_POST['contact_ids'])) {
    $action = $_POST['bulk_action'];
    $ids = array_map('intval', $_POST['contact_ids']);
    $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
    
    if ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM contacts WHERE id IN ($ids_placeholder)");
        $stmt->execute($ids);
        $message_success = "Đã xóa " . count($ids) . " liên hệ.";
    } elseif ($action === 'mark_read') {
        $stmt = $db->prepare("UPDATE contacts SET status = 'read' WHERE status = 'new' AND id IN ($ids_placeholder)");
        $stmt->execute($ids);
        $message_success = "Đã đánh dấu " . count($ids) . " liên hệ là đã đọc.";
    } elseif ($action === 'star') {
        $stmt = $db->prepare("UPDATE contacts SET is_starred = 1 WHERE id IN ($ids_placeholder)");
        $stmt->execute($ids);
        $message_success = "Đã đánh dấu sao " . count($ids) . " liên hệ.";
    }
}

// 3. Xử lý Trả lời Email
if (isset($_POST['reply_contact_id']) && isset($_POST['reply_message'])) {
    $id = (int)$_POST['reply_contact_id'];
    $reply_msg = $_POST['reply_message'];
    
    $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST']       ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME']   ?? '';
            $mail->Password   = $_ENV['MAIL_PASSWORD']   ?? '';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
            $mail->Port       = $_ENV['MAIL_PORT']       ?? 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($_ENV['MAIL_USERNAME'] ?? 'contact@restaurant.com', 'Restaurantly Admin');
            $mail->addAddress($contact['email'], $contact['name']);

            $mail->isHTML(true);
            $mail->Subject = "Phản hồi: " . ($contact['subject'] ?: 'Liên hệ của bạn');
            $mail->Body    = nl2br(htmlspecialchars($reply_msg)) . "<br><br><hr><small>Đây là email phản hồi tự động từ hệ thống Restaurantly. Vui lòng không trả lời lại email này.</small>";

            $mail->send();
            
            $db->prepare("UPDATE contacts SET status = 'replied' WHERE id = ?")->execute([$id]);
            $message_success = "Đã gửi email phản hồi thành công đến " . htmlspecialchars($contact['email']);
        } catch (\Exception $e) {
            $message_error = "Không thể gửi email. Vui lòng kiểm tra lại cấu hình SMTP. Lỗi: {$mail->ErrorInfo}";
        }
    }
}

// 4. Xử lý Hành động Đơn lẻ (GET)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];
    if ($action === 'read') {
        $db->prepare("UPDATE contacts SET status = 'read' WHERE id = ?")->execute([$id]);
        header("Location: manage_contacts.php?msg=read"); exit;
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM contacts WHERE id = ?")->execute([$id]);
        header("Location: manage_contacts.php?msg=deleted"); exit;
    } elseif ($action === 'toggle_star') {
        $db->prepare("UPDATE contacts SET is_starred = NOT is_starred WHERE id = ?")->execute([$id]);
        header("Location: manage_contacts.php?msg=starred"); exit;
    }
}

// Messages GET redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'read') $message_success = "Đã đánh dấu là đã đọc.";
    if ($_GET['msg'] === 'deleted') $message_success = "Đã xóa liên hệ.";
    if ($_GET['msg'] === 'starred') $message_success = "Đã cập nhật trạng thái quan trọng.";
}

// 5. Lọc, Tìm kiếm, Phân trang
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($filter_status) {
    if ($filter_status === 'starred') {
        $where[] = "is_starred = 1";
    } else {
        $where[] = "status = ?";
        $params[] = $filter_status;
    }
}

$where_clause = implode(" AND ", $where);

$stmt_count = $db->prepare("SELECT COUNT(*) FROM contacts WHERE $where_clause");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM contacts WHERE $where_clause ORDER BY is_starred DESC, created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .table-hover tbody tr:hover { background-color: #f8fafc; }
    .star-icon { color: #ccc; cursor: pointer; text-decoration: none; font-size: 1.1rem; }
    .star-icon.active { color: #f59e0b; }
    .badge-status { font-weight: 500; font-size: 0.8rem; padding: 0.35em 0.6em; }
    .tr-unread { font-weight: 600; background-color: #f1f5f9; }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="fas fa-envelope-open-text me-2 text-primary"></i> Quản lý Liên hệ</h4>
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

    <!-- Toolbar: Search, Filter -->
    <div class="card card-custom p-3 mb-4 shadow-sm border-0">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm tên, email, chủ đề..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="new" <?= $filter_status == 'new' ? 'selected' : '' ?>>Chưa đọc (Mới)</option>
                    <option value="read" <?= $filter_status == 'read' ? 'selected' : '' ?>>Đã đọc</option>
                    <option value="replied" <?= $filter_status == 'replied' ? 'selected' : '' ?>>Đã phản hồi</option>
                    <option value="starred" <?= $filter_status == 'starred' ? 'selected' : '' ?>>Quan trọng (Ghim)</option>
                </select>
            </div>
            <div class="col-md-4 text-end">
                <a href="manage_contacts.php" class="btn btn-outline-secondary"><i class="fas fa-sync-alt"></i> Làm mới</a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <form method="POST" id="contactsForm">
        <div class="card card-custom p-0 shadow-sm border-0">
            <!-- Bulk Actions -->
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <select name="bulk_action" class="form-select form-select-sm" style="width: auto; min-width: 220px;" required>
                        <option value="">-- Hành động hàng loạt --</option>
                        <option value="mark_read">Đánh dấu đã đọc</option>
                        <option value="star">Đánh dấu sao</option>
                        <option value="delete">Xóa các mục chọn</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Thực hiện hành động này?');">Áp dụng</button>
                </div>
                <div class="text-muted small">Hiển thị <strong><?= count($contacts) ?></strong> / <?= $total_records ?> liên hệ</div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase;">
                        <tr>
                            <th width="40" class="text-center"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                            <th width="40" class="text-center"><i class="fas fa-star"></i></th>
                            <th>Khách hàng</th>
                            <th>Chủ đề</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contacts) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                    <h5>Không tìm thấy liên hệ nào</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($contacts as $c): ?>
                            <tr class="<?= $c['status'] === 'new' ? 'tr-unread' : '' ?>">
                                <td class="text-center">
                                    <input type="checkbox" name="contact_ids[]" value="<?= $c['id'] ?>" class="form-check-input check-item">
                                </td>
                                <td class="text-center">
                                    <a href="?action=toggle_star&id=<?= $c['id'] ?>" class="star-icon <?= $c['is_starred'] ? 'active' : '' ?>" title="Đánh dấu quan trọng">
                                        <i class="<?= $c['is_starred'] ? 'fas' : 'far' ?> fa-star"></i>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($c['email']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-1 text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($c['subject']) ?>">
                                        <?= htmlspecialchars($c['subject'] ?: '(Không có tiêu đề)') ?>
                                    </div>
                                    <div class="text-secondary small text-truncate" style="max-width: 250px; font-size: 0.8rem;">
                                        <?= htmlspecialchars($c['message']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'new'): ?>
                                        <span class="badge bg-danger badge-status">Mới</span>
                                    <?php elseif ($c['status'] === 'replied'): ?>
                                        <span class="badge bg-success badge-status"><i class="fas fa-reply"></i> Đã phản hồi</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary badge-status">Đã đọc</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d/m/Y', strtotime($c['created_at'])) ?><br>
                                    <?= date('H:i', strtotime($c['created_at'])) ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle me-1" 
                                            title="Xem chi tiết" 
                                            onclick="viewContact(<?= htmlspecialchars(json_encode([
                                                'id' => $c['id'],
                                                'name' => $c['name'],
                                                'email' => $c['email'],
                                                'subject' => $c['subject'],
                                                'message' => $c['message'],
                                                'time' => date('d/m/Y H:i', strtotime($c['created_at'])),
                                                'status' => $c['status']
                                            ])) ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($c['status'] === 'new'): ?>
                                        <a href="?action=read&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-success rounded-circle me-1" title="Đánh dấu đã đọc">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $filter_status ?>">Trước</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $filter_status ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $filter_status ?>">Sau</a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Modal Xem Chi Tiết & Phản Hồi -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="fas fa-envelope-open me-2"></i> Chi tiết liên hệ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <p class="text-muted mb-1 small">Từ khách hàng</p>
                        <h6 class="fw-bold mb-0" id="modal-name">Tên khách</h6>
                        <a href="#" id="modal-email-link" class="text-primary small" style="text-decoration: none;" id="modal-email">email@domain.com</a>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <p class="text-muted mb-1 small">Thời gian gửi</p>
                        <h6 class="mb-0" id="modal-time">10/10/2026 10:00</h6>
                        <span class="badge bg-secondary mt-1" id="modal-status">Trạng thái</span>
                    </div>
                </div>
                
                <div class="bg-light p-3 rounded mb-4 border">
                    <h6 class="fw-bold border-bottom pb-2 mb-2" id="modal-subject">Chủ đề: </h6>
                    <div style="white-space: pre-wrap; font-size: 0.95rem; color: #333;" id="modal-message">Nội dung...</div>
                </div>

                <hr class="my-4">
                
                <!-- Form Phản Hồi -->
                <h6 class="fw-bold text-success mb-3"><i class="fas fa-reply me-2"></i> Soạn phản hồi qua Email</h6>
                <form method="POST" id="replyForm" onsubmit="return confirm('Gửi email phản hồi cho khách hàng này?');">
                    <input type="hidden" name="reply_contact_id" id="reply_contact_id">
                    <div class="mb-3">
                        <textarea name="reply_message" class="form-control" rows="5" placeholder="Viết nội dung phản hồi của bạn vào đây..." required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-2"></i> Gửi Phản Hồi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('checkAll').addEventListener('change', function() {
    let checkboxes = document.querySelectorAll('.check-item');
    for (let checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});

function viewContact(data) {
    document.getElementById('modal-name').innerText = data.name;
    document.getElementById('modal-email-link').innerText = data.email;
    document.getElementById('modal-email-link').href = 'mailto:' + data.email;
    document.getElementById('modal-time').innerText = data.time;
    document.getElementById('modal-subject').innerText = data.subject || '(Không có chủ đề)';
    document.getElementById('modal-message').innerText = data.message;
    document.getElementById('reply_contact_id').value = data.id;
    
    let statusBadge = document.getElementById('modal-status');
    if(data.status === 'new') {
        statusBadge.className = 'badge bg-danger';
        statusBadge.innerText = 'Mới';
    } else if(data.status === 'replied') {
        statusBadge.className = 'badge bg-success';
        statusBadge.innerText = 'Đã phản hồi';
    } else {
        statusBadge.className = 'badge bg-secondary';
        statusBadge.innerText = 'Đã đọc';
    }

    var myModal = new bootstrap.Modal(document.getElementById('contactModal'));
    myModal.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>
<?php
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = (new Database())->getConnection();

// 2. Khởi tạo/Cập nhật bảng contacts
try {
    // Đảm bảo các cột và enum tồn tại
    $db->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS is_starred TINYINT(1) DEFAULT 0");
    $db->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS admin_note TEXT");
    $db->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS reply_content TEXT");
    $db->exec("ALTER TABLE contacts MODIFY COLUMN status ENUM('new','read','replied') DEFAULT 'new'");
} catch (\Exception $e) {
    //
}

// 3. Xử lý AJAX (Mark read, Save note)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($_POST['ajax_action'] === 'mark_read') {
        $db->prepare("UPDATE contacts SET status = 'read' WHERE id = ? AND status = 'new'")->execute([$id]);
        echo json_encode(['success' => true]); exit;
    }
    if ($_POST['ajax_action'] === 'save_note') {
        $db->prepare("UPDATE contacts SET admin_note = ? WHERE id = ?")->execute([$_POST['note'] ?? '', $id]);
        echo json_encode(['success' => true]); exit;
    }
}

// 4. Xử lý POST (Bulk Actions, Single Actions, Reply)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    // Bulk Actions
    if (isset($_POST['bulk_action']) && isset($_POST['contact_ids'])) {
        $action = $_POST['bulk_action'];
        $ids = array_map('intval', $_POST['contact_ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'delete') {
            $db->prepare("DELETE FROM contacts WHERE id IN ($placeholders)")->execute($ids);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã xóa ' . count($ids) . ' liên hệ.'];
        } elseif ($action === 'mark_read') {
            $db->prepare("UPDATE contacts SET status = 'read' WHERE id IN ($placeholders)")->execute($ids);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã đánh dấu đã đọc.'];
        } elseif ($action === 'star') {
            $db->prepare("UPDATE contacts SET is_starred = 1 WHERE id IN ($placeholders)")->execute($ids);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã gắn sao các mục.'];
        }
    }
    // Single Actions (Delete, Toggle Star)
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($_POST['action'] === 'delete') {
            $db->prepare("DELETE FROM contacts WHERE id = ?")->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã xóa liên hệ.'];
        } elseif ($_POST['action'] === 'toggle_star') {
            $db->prepare("UPDATE contacts SET is_starred = NOT is_starred WHERE id = ?")->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã cập nhật trạng thái quan trọng.'];
        }
    }
    // Reply Email
    if (isset($_POST['reply_contact_id']) && isset($_POST['reply_message'])) {
        // CSRF Check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Yêu cầu không hợp lệ (CSRF).'];
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }
        $id = (int)$_POST['reply_contact_id'];
        $reply_msg = $_POST['reply_message'];
        $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($c) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USERNAME'] ?? '';
                $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
                $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom($_ENV['MAIL_USERNAME'] ?? 'contact@restaurant.com', 'Restaurantly Admin');
                $mail->addAddress($c['email'], $c['name']);
                $mail->isHTML(true);
                $mail->Subject = "Phản hồi: " . ($c['subject'] ?: 'Liên hệ');
                $mail->Body    = nl2br(htmlspecialchars($reply_msg)) . "<br><br><hr><small>Phản hồi từ hệ thống Admin.</small>";
                $mail->send();
                $db->prepare("UPDATE contacts SET status = 'replied', reply_content = ? WHERE id = ?")->execute([$reply_msg, $id]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Gửi phản hồi thành công!'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Lỗi gửi mail: ' . $mail->ErrorInfo];
            }
        }
    }

    // Save Settings
    if (isset($_POST['save_templates'])) {
        $templates = [
            'contact_tpl_1' => $_POST['tpl_1'],
            'contact_tpl_2' => $_POST['tpl_2'],
            'contact_tpl_3' => $_POST['tpl_3']
        ];
        foreach ($templates as $key => $val) {
            $db->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)")->execute([$key, $val]);
        }
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã lưu mẫu phản hồi.'];
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }

    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// 5. Thống kê (1 Query)
$stats = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
    SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred_count
FROM contacts")->fetch(PDO::FETCH_ASSOC);

// 6. Lọc & Tìm kiếm
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$where = ["1=1"]; $params = [];
if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ?)";
    $p = "%$search%"; $params = array_merge($params, [$p, $p, $p]);
}
if ($filter_status) {
    if ($filter_status === 'starred') { $where[] = "is_starred = 1"; }
    else { $where[] = "status = ?"; $params[] = $filter_status; }
}
$where_clause = implode(" AND ", $where);

// 7. Xuất CSV (Handle before any HTML)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $db->prepare("SELECT * FROM contacts WHERE $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=contacts_export_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['ID', 'Tên', 'Email', 'Tiêu đề', 'Trạng thái', 'Ngày gửi']);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$r['id'], $r['name'], $r['email'], $r['subject'], $r['status'], $r['created_at']]);
    }
    fclose($out); exit;
}

// 8. Phân trang & Query chính
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$stmt_count = $db->prepare("SELECT COUNT(*) FROM contacts WHERE $where_clause");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM contacts WHERE $where_clause ORDER BY is_starred DESC, created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include '../public/admin_layout_header.php';

// Fetch Templates
$tpls = [];
$res = $db->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('contact_tpl_1', 'contact_tpl_2', 'contact_tpl_3')")->fetchAll(PDO::FETCH_KEY_PAIR);
$tpls[1] = $res['contact_tpl_1'] ?? 'Cảm ơn bạn đã liên hệ với Restaurantly. Chúng tôi đã nhận được thông tin và sẽ phản hồi sớm nhất.';
$tpls[2] = $res['contact_tpl_2'] ?? 'Chào bạn, bộ phận hỗ trợ đã tiếp nhận yêu cầu của bạn. Chúng tôi sẽ phản hồi chi tiết trong vòng 24h tới.';
$tpls[3] = $res['contact_tpl_3'] ?? 'Chào bạn, để được hỗ trợ nhanh nhất vui lòng liên hệ hotline nhà hàng. Trân trọng!';
?>


<style>
    .table-hover tbody tr:hover { background-color: #f8fafc; }
    .star-icon { color: #ccc; cursor: pointer; text-decoration: none; font-size: 1.1rem; }
    .star-icon.active { color: #f59e0b; }
    .badge-status { font-weight: 500; font-size: 0.8rem; padding: 0.35em 0.6em; }
    .tr-unread { font-weight: 600; background-color: #f1f5f9; }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h4 class="fw-bold m-0"><i class="fas fa-envelope-open-text me-2 text-primary"></i> Quản lý Liên hệ</h4>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3"><i class="fas fa-inbox text-primary"></i></div>
                    <div><h6 class="text-muted mb-0 small">Tổng liên hệ</h6><h4 class="fw-bold mb-0"><?= $stats['total'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="<?= $stats['new_count'] > 0 ? 'bg-danger' : 'bg-secondary' ?> bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="fas fa-envelope-open <?= $stats['new_count'] > 0 ? 'text-danger' : 'text-secondary' ?>"></i>
                    </div>
                    <div><h6 class="text-muted mb-0 small">Mới chưa đọc</h6><h4 class="fw-bold mb-0 <?= $stats['new_count'] > 0 ? 'text-danger' : '' ?>"><?= $stats['new_count'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3"><i class="fas fa-reply text-success"></i></div>
                    <div><h6 class="text-muted mb-0 small">Đã phản hồi</h6><h4 class="fw-bold mb-0"><?= $stats['replied_count'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3"><i class="fas fa-star text-warning"></i></div>
                    <div><h6 class="text-muted mb-0 small">Gắn sao</h6><h4 class="fw-bold mb-0"><?= $stats['starred_count'] ?></h4></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i> 
            <?= htmlspecialchars($flash['msg']) ?>
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
                <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="fas fa-cog"></i> Mẫu phản hồi</button>
                <a href="?export=csv&search=<?= urlencode($search) ?>&status=<?= $filter_status ?>" class="btn btn-success me-2"><i class="fas fa-file-csv"></i> Xuất CSV</a>
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
                            <tr class="<?= $c['status'] === 'new' ? 'tr-unread' : '' ?>" id="row-<?= $c['id'] ?>">
                                <td class="text-center">
                                    <input type="checkbox" name="contact_ids[]" value="<?= $c['id'] ?>" class="form-check-input check-item">
                                </td>
                                <td class="text-center">
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_star">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="star-icon <?= $c['is_starred'] ? 'active' : '' ?> border-0 bg-transparent p-0" title="Đánh dấu quan trọng">
                                            <i class="<?= $c['is_starred'] ? 'fas' : 'far' ?> fa-star"></i>
                                        </button>
                                    </form>
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
                                <td id="status-badge-<?= $c['id'] ?>">
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
                                            onclick='viewContact(<?= json_encode([
                                                "id" => $c["id"],
                                                "name" => $c["name"],
                                                "email" => $c["email"],
                                                "subject" => $c["subject"],
                                                "message" => $c["message"],
                                                "time" => date("d/m/Y H:i", strtotime($c["created_at"])),
                                                "status" => $c["status"],
                                                "admin_note" => $c["admin_note"] ?? "",
                                                "reply_content" => $c["reply_content"] ?? ""
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" 
                                            title="Xóa" 
                                            onclick="confirmDelete(<?= $c['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

                <!-- Lịch sử phản hồi -->
                <div id="reply-history-box" class="mb-4 d-none">
                    <label class="form-label fw-bold text-success small"><i class="fas fa-history me-1"></i> Nội dung đã phản hồi</label>
                    <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded small text-dark" id="modal-reply-history"></div>
                </div>

                <!-- Ghi chú nội bộ -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small"><i class="fas fa-edit me-1"></i> Ghi chú nội bộ (Chỉ admin thấy)</label>
                    <div class="input-group shadow-sm">
                        <textarea id="admin_note_input" class="form-control" rows="2" placeholder="Nhập ghi chú hoặc nhắc nhở tại đây..."></textarea>
                        <button class="btn btn-primary" type="button" id="btnSaveNote" onclick="saveAdminNote()">
                            <i class="fas fa-save me-1"></i> Lưu
                        </button>
                    </div>
                </div>

                <hr class="my-4">
                
                <!-- Form Phản Hồi -->
                <h6 class="fw-bold text-success mb-3"><i class="fas fa-reply me-2"></i> Soạn phản hồi qua Email</h6>
                
                <!-- Mẫu phản hồi nhanh -->
                <div class="mb-2 d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 small quick-reply" data-text="<?= htmlspecialchars($tpls[1]) ?>">Mẫu 1</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 small quick-reply" data-text="<?= htmlspecialchars($tpls[2]) ?>">Mẫu 2</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 small quick-reply" data-text="<?= htmlspecialchars($tpls[3]) ?>">Mẫu 3</button>
                </div>

                <form method="POST" id="replyForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="reply_contact_id" id="reply_contact_id">
                    <div class="mb-3">
                        <textarea name="reply_message" id="reply_message" class="form-control" rows="4" placeholder="Viết nội dung phản hồi của bạn vào đây..." required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-light border me-2" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-success px-4" onclick="return confirm('Gửi email phản hồi cho khách hàng này?');"><i class="fas fa-paper-plane me-2"></i> Gửi Phản Hồi</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận Xóa -->
...
            </div>
        </div>
    </div>
</div>

<!-- Modal Cài đặt Mẫu phản hồi -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Cài đặt mẫu phản hồi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mẫu phản hồi 1</label>
                        <textarea name="tpl_1" class="form-control" rows="3"><?= htmlspecialchars($tpls[1]) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mẫu phản hồi 2</label>
                        <textarea name="tpl_2" class="form-control" rows="3"><?= htmlspecialchars($tpls[2]) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mẫu phản hồi 3</label>
                        <textarea name="tpl_3" class="form-control" rows="3"><?= htmlspecialchars($tpls[3]) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="save_templates" class="btn btn-primary btn-sm">Lưu cài đặt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Chuyển sang var/forEach theo chuẩn cũ của project
document.getElementById('checkAll').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.check-item');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = this.checked;
    }.bind(this));
});

// Quick Reply Logic
document.querySelectorAll('.quick-reply').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var text = this.getAttribute('data-text');
        document.getElementById('reply_message').value = text;
    });
});

function viewContact(data) {
    document.getElementById('modal-name').innerText = data.name;
    document.getElementById('modal-email-link').innerText = data.email;
    document.getElementById('modal-email-link').href = 'mailto:' + data.email;
    document.getElementById('modal-time').innerText = data.time;
    document.getElementById('modal-subject').innerText = data.subject || '(Không có chủ đề)';
    document.getElementById('modal-message').innerText = data.message;
    document.getElementById('reply_contact_id').value = data.id;
    document.getElementById('admin_note_input').value = data.admin_note || '';
    
    // Hiển thị lịch sử phản hồi nếu có
    var historyBox = document.getElementById('reply-history-box');
    if (data.reply_content) {
        historyBox.classList.remove('d-none');
        document.getElementById('modal-reply-history').innerText = data.reply_content;
    } else {
        historyBox.classList.add('d-none');
    }
    
    var statusBadge = document.getElementById('modal-status');
    if(data.status === 'new') {
        statusBadge.className = 'badge bg-danger'; statusBadge.innerText = 'Mới';
        
        // AJAX: Tự động đánh dấu đã đọc
        var formData = new FormData();
        formData.append('ajax_action', 'mark_read');
        formData.append('id', data.id);
        fetch(window.location.href, { method: 'POST', body: formData })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if(res.success) {
                // Cập nhật UI ngay lập tức
                var badge = document.getElementById('status-badge-' + data.id);
                if(badge) badge.innerHTML = '<span class="badge bg-secondary badge-status">Đã đọc</span>';
                var row = document.getElementById('row-' + data.id);
                if(row) row.classList.remove('tr-unread');
            }
        });
    } else if(data.status === 'replied') {
        statusBadge.className = 'badge bg-success'; statusBadge.innerText = 'Đã phản hồi';
    } else {
        statusBadge.className = 'badge bg-secondary'; statusBadge.innerText = 'Đã đọc';
    }

    var myModal = new bootstrap.Modal(document.getElementById('contactModal'));
    myModal.show();
}

function saveAdminNote() {
    var id = document.getElementById('reply_contact_id').value;
    var note = document.getElementById('admin_note_input').value;
    var btn = document.getElementById('btnSaveNote');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    var formData = new FormData();
    formData.append('ajax_action', 'save_note');
    formData.append('id', id);
    formData.append('note', note);
    
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Đã lưu';
        setTimeout(function() { btn.innerHTML = '<i class="fas fa-save me-1"></i> Lưu'; }, 2000);
    });
}

function confirmDelete(id) {
    document.getElementById('delete_id_input').value = id;
    var delModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    delModal.show();
}
</script>




</div>
</body>
</html>

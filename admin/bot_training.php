<?php
include '../public/admin_layout_header.php';
require_once '../config/database.php';

if (!isset($db)) {
    $db = (new Database())->getConnection();
}

$message = '';

// Xử lý Xóa
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM bot_responses WHERE id = ?")->execute([$id]);
    $message = '<div class="alert alert-success">Đã xóa kịch bản Bot thành công.</div>';
}

// Xử lý Thêm / Sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keywords = trim($_POST['keywords']);
    $answer = trim($_POST['answer']);
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Cập nhật
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE bot_responses SET keywords = ?, answer = ? WHERE id = ?");
        $stmt->execute([$keywords, $answer, $id]);
        $message = '<div class="alert alert-success">Đã cập nhật kịch bản Bot thành công.</div>';
    } else {
        // Thêm mới
        $stmt = $db->prepare("INSERT INTO bot_responses (keywords, answer) VALUES (?, ?)");
        $stmt->execute([$keywords, $answer]);
        $message = '<div class="alert alert-success">Đã thêm kịch bản Bot thành công.</div>';
    }
}

// Lấy danh sách kịch bản
$stmt = $db->query("SELECT * FROM bot_responses ORDER BY id DESC");
$bot_scripts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-area">
    <div class="container-fluid pt-4 px-4">
        <?= $message ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-primary"><i class="fas fa-robot me-2"></i>Quản lý Kịch bản Bot (Bot Training)</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#botModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Thêm Kịch Bản Mới
            </button>
        </div>

        <div class="bg-white rounded p-4 shadow-sm">
            <div class="alert alert-info mb-4" style="font-size: 14px;">
                <i class="fas fa-info-circle me-2"></i> <strong>Hướng dẫn:</strong> Nhập các từ khóa liên quan đến câu hỏi của khách hàng, cách nhau bằng dấu phẩy (Ví dụ: <code>thực đơn,menu,món ăn</code>). Khi khách hàng chat và có chứa 1 trong các từ khóa này, Bot sẽ tự động trả lời bằng nội dung tương ứng.
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" width="5%">ID</th>
                            <th scope="col" width="35%">Từ khóa (Keywords)</th>
                            <th scope="col" width="45%">Câu trả lời của Bot</th>
                            <th scope="col" width="15%" class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bot_scripts as $script): ?>
                        <tr>
                            <td><?= $script['id'] ?></td>
                            <td>
                                <?php 
                                    $kws = explode(',', $script['keywords']);
                                    foreach($kws as $kw) {
                                        echo '<span class="badge bg-secondary me-1 mb-1" style="font-weight:normal;">'.htmlspecialchars(trim($kw)).'</span>';
                                    }
                                ?>
                            </td>
                            <td style="white-space: pre-wrap; font-size: 14px;"><?= htmlspecialchars($script['answer']) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editBotScript(<?= $script['id'] ?>, '<?= htmlspecialchars($script['keywords'], ENT_QUOTES) ?>', '<?= htmlspecialchars(str_replace(["\r", "\n"], ['\r', '\n'], $script['answer']), ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?= $script['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa kịch bản này?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($bot_scripts) === 0): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Chưa có kịch bản Bot nào.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="botModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="modalTitle">Thêm Kịch Bản Bot Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="bot_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Từ khóa (Keywords) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="keywords" id="bot_keywords" required placeholder="Ví dụ: thực đơn, menu, báo giá">
                        <div class="form-text">Các từ khóa cách nhau bằng dấu phẩy (,). Không phân biệt hoa/thường.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Câu trả lời tự động <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="answer" id="bot_answer" rows="5" required placeholder="Nhập câu trả lời của Bot..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu Kịch Bản</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Thêm Kịch Bản Bot Mới';
    document.getElementById('bot_id').value = '';
    document.getElementById('bot_keywords').value = '';
    document.getElementById('bot_answer').value = '';
}

function editBotScript(id, keywords, answer) {
    document.getElementById('modalTitle').innerText = 'Chỉnh sửa Kịch Bản Bot';
    document.getElementById('bot_id').value = id;
    document.getElementById('bot_keywords').value = keywords;
    document.getElementById('bot_answer').value = answer.replace(/\\n/g, '\n').replace(/\\r/g, '\r');
    
    var myModal = new bootstrap.Modal(document.getElementById('botModal'));
    myModal.show();
}
</script>

<?php include '../public/admin_layout_footer.php'; ?>

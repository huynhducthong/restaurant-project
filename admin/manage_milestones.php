<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../config/database.php';
$db = (new Database())->getConnection();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff', 'waiter', 'chef', 'cashier', 1, 2])) {
    header("Location: /restaurant-project/public/login.php?error=access_denied");
    exit();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $type = $_POST['type'];
            $threshold = (int)$_POST['threshold'];
            $title = trim($_POST['reward_title']);
            $desc = trim($_POST['reward_desc']);
            $discount_percent = (int)$_POST['discount_percent'];
            
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO milestones (type, threshold, reward_title, reward_desc, discount_percent) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$type, $threshold, $title, $desc, $discount_percent]);
                $_SESSION['success_message'] = "Thêm cột mốc thành công!";
            } else {
                $id = (int)$_POST['milestone_id'];
                $stmt = $db->prepare("UPDATE milestones SET type=?, threshold=?, reward_title=?, reward_desc=?, discount_percent=? WHERE id=?");
                $stmt->execute([$type, $threshold, $title, $desc, $discount_percent, $id]);
                $_SESSION['success_message'] = "Cập nhật cột mốc thành công!";
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['milestone_id'];
            $db->prepare("DELETE FROM milestones WHERE id=?")->execute([$id]);
            $_SESSION['success_message'] = "Xóa cột mốc thành công!";
        }
        header("Location: manage_milestones.php");
        exit;
    }
}

// Fetch milestones
$stmt = $db->query("SELECT * FROM milestones ORDER BY type, threshold ASC");
$milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../public/admin_layout_header.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Quản lý Đặc quyền Cột mốc (Milestones)</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item active">Đặc quyền Cột mốc</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title d-flex justify-content-between align-items-center">
                            Danh sách Cột mốc
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="fas fa-plus-circle"></i> Thêm Mốc Mới
                            </button>
                        </h5>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Hệ thống <strong>Milestone Rewards</strong> thay thế cho việc tích điểm truyền thống. Khách hàng đạt số lần dùng bữa (hoặc mức chi tiêu) sẽ nhận được món quà bất ngờ tại nhà hàng.
                        </div>

                        <table class="table table-hover mt-3">
                            <thead>
                                <tr>
                                    <th>Loại mốc</th>
                                    <th>Điều kiện (Số lần / Số tiền)</th>
                                    <th>Quà tặng (Reward)</th>
                                    <th>Tự động giảm giá</th>
                                    <th>Mô tả / Ghi chú</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($milestones as $m): ?>
                                <tr>
                                    <td>
                                        <?php if($m['type'] == 'visit'): ?>
                                            <span class="badge bg-primary">Số lần đến</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Chi tiêu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= $m['type'] == 'spend' ? number_format($m['threshold'], 0, ',', '.') . ' VNĐ' : $m['threshold'] . ' lần' ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($m['reward_title']) ?></td>
                                    <td>
                                        <?php if ($m['discount_percent'] > 0): ?>
                                            <span class="badge bg-danger">Giảm <?= $m['discount_percent'] ?>%</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0%</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($m['reward_desc']) ?></small></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                            data-id="<?= $m['id'] ?>"
                                            data-type="<?= $m['type'] ?>"
                                            data-threshold="<?= $m['threshold'] ?>"
                                            data-title="<?= htmlspecialchars($m['reward_title']) ?>"
                                            data-discount="<?= $m['discount_percent'] ?>"
                                            data-desc="<?= htmlspecialchars($m['reward_desc']) ?>"
                                            onclick="editMilestone(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mốc này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($milestones)): ?>
                                    <tr><td colspan="5" class="text-center">Chưa có cột mốc nào được tạo.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Form -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Thêm Cột Mốc Mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="milestone_id" id="milestone_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Loại mốc</label>
                            <select name="type" id="mType" class="form-select" required>
                                <option value="visit">Số lần đến dùng bữa (Visits)</option>
                                <option value="spend">Tổng chi tiêu (Spend)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mốc đạt thưởng (Số lần hoặc Số tiền VNĐ)</label>
                            <input type="number" name="threshold" id="mThreshold" class="form-control" required placeholder="Ví dụ: 3 (lần) hoặc 50000000 (VND)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tên Quà Tặng (Hiển thị cho khách)</label>
                            <input type="text" name="reward_title" id="mTitle" class="form-control" required placeholder="VD: Ly Champagne Chào Mừng">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phần trăm giảm giá tự động (%)</label>
                            <input type="number" name="discount_percent" id="mDiscount" class="form-control" value="0" min="0" max="100" placeholder="VD: 10">
                            <small class="text-muted d-block mt-1">Sẽ tự động giảm % này vào tổng hóa đơn khi khách đặt bàn lần sau (0 = Không giảm).</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả chi tiết</label>
                            <textarea name="reward_desc" id="mDesc" class="form-control" rows="3" placeholder="Ghi chú chi tiết về món quà..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu Lại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function editMilestone(btn) {
    document.getElementById('modalTitle').innerText = 'Chỉnh Sửa Cột Mốc';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('milestone_id').value = btn.getAttribute('data-id');
    document.getElementById('mType').value = btn.getAttribute('data-type');
    document.getElementById('mThreshold').value = btn.getAttribute('data-threshold');
    document.getElementById('mTitle').value = btn.getAttribute('data-title');
    document.getElementById('mDiscount').value = btn.getAttribute('data-discount');
    document.getElementById('mDesc').value = btn.getAttribute('data-desc');
    
    var modalEl = document.getElementById('addModal');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

document.getElementById('addModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerText = 'Thêm Cột Mốc Mới';
    document.getElementById('formAction').value = 'add';
    document.getElementById('milestone_id').value = '';
    document.getElementById('mThreshold').value = '';
    document.getElementById('mTitle').value = '';
    document.getElementById('mDiscount').value = '0';
    document.getElementById('mDesc').value = '';
});
</script>

<?php include '../public/admin_layout_footer.php'; ?>

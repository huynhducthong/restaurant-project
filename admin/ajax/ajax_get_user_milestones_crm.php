<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff', 'waiter', 'cashier', 1, 2])) {
    die('<div class="alert alert-danger m-3">Không có quyền truy cập</div>');
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();
$user_id = (int)($_GET['user_id'] ?? 0);

if (!$user_id) {
    die('<div class="text-danger p-3">User ID không hợp lệ.</div>');
}

// Lấy thông tin visits
$stmt = $db->prepare("SELECT visit_count FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$visits = (int)$stmt->fetchColumn();

// Lấy mốc tiếp theo
$stmt_next = $db->prepare("SELECT threshold, reward_title FROM milestones WHERE threshold > ? ORDER BY threshold ASC LIMIT 1");
$stmt_next->execute([$visits]);
$next_ms = $stmt_next->fetch(PDO::FETCH_ASSOC);

// Lấy quà chưa nhận
$stmt_um = $db->prepare("
    SELECT m.reward_title, m.reward_desc, um.id as um_id 
    FROM user_milestones um 
    JOIN milestones m ON um.milestone_id = m.id 
    WHERE um.user_id = ? AND um.is_redeemed = 0
");
$stmt_um->execute([$user_id]);
$unredeemed = $stmt_um->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="p-3">
    <?php if ($next_ms): ?>
        <?php 
            $remaining = $next_ms['threshold'] - $visits;
            
            $stmt_prev = $db->prepare("SELECT threshold FROM milestones WHERE threshold <= ? ORDER BY threshold DESC LIMIT 1");
            $stmt_prev->execute([$visits]);
            $prev_thresh = (int)$stmt_prev->fetchColumn();
            
            $total_step = $next_ms['threshold'] - $prev_thresh;
            $current_step = $visits - $prev_thresh;
            $percent = $total_step > 0 ? round(($current_step / $total_step) * 100) : 0;
        ?>
        <div class="mb-3">
            <div class="d-flex justify-content-between mb-1 small fw-bold">
                <span class="text-secondary">Tiến trình đạt mốc tiếp theo</span>
                <span class="text-primary"><?= $visits ?> / <?= $next_ms['threshold'] ?> Lần</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $percent ?>%;"></div>
            </div>
            <div class="text-end small text-muted mt-1">Còn <b class="text-dark"><?= $remaining ?> lần</b> nữa để nhận <b class="text-danger"><?= htmlspecialchars($next_ms['reward_title']) ?></b></div>
        </div>
    <?php else: ?>
        <div class="alert alert-success py-2 mb-3 text-center">
            <i class="fas fa-crown me-2"></i> Khách hàng đã đạt mốc cao nhất!
        </div>
    <?php endif; ?>

    <?php if (count($unredeemed) > 0): ?>
        <div class="alert alert-warning mb-0 shadow-sm" style="border-left: 4px solid #c8933a;">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-gift me-2" style="color: #c8933a;"></i>Quà tặng chưa nhận:</h6>
            <ul class="mb-0 ps-3 small text-dark">
                <?php foreach ($unredeemed as $u): ?>
                    <li class="mb-1">
                        <strong><?= htmlspecialchars($u['reward_title']) ?></strong>: <?= htmlspecialchars($u['reward_desc']) ?>
                        <button type="button" class="btn btn-sm btn-outline-success ms-2 py-0 px-2 btn-redeem-ms" data-um-id="<?= $u['um_id'] ?>"><i class="fas fa-check"></i> Đã trao quà</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="text-muted small text-center"><i class="fas fa-check-circle text-success me-1"></i> Không có quà tặng đặc quyền nào đang chờ.</div>
    <?php endif; ?>
</div>

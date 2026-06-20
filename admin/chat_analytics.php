<?php
require_once __DIR__ . '/../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// --- KPI THỜI GIAN (Average Response Time) ---
$stmt = $db->query("SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_resp FROM chat_sessions WHERE status IN ('agent_handling', 'closed') AND first_response_at IS NOT NULL");
$avg_resp_seconds = (int)$stmt->fetchColumn();
$avg_resp = $avg_resp_seconds > 60 ? floor($avg_resp_seconds/60) . ' phút ' . ($avg_resp_seconds%60) . ' giây' : $avg_resp_seconds . ' giây';

// --- KHỐI LƯỢNG HÔM NAY ---
$stmt = $db->query("SELECT COUNT(*) FROM chat_sessions WHERE DATE(created_at) = CURDATE()");
$today_total = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM chat_sessions WHERE DATE(created_at) = CURDATE() AND status = 'waiting_agent'");
$today_waiting = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM chat_sessions WHERE DATE(created_at) = CURDATE() AND status = 'closed'");
$today_closed = (int)$stmt->fetchColumn();

// --- HIỆU SUẤT ---
$stmt = $db->query("SELECT COUNT(*) FROM chat_sessions WHERE status = 'closed' AND first_response_at IS NULL"); // Đóng mà không qua agent => Bot giải quyết thành công
$bot_success = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM chat_sessions WHERE status = 'closed'");
$total_closed = (int)$stmt->fetchColumn();

$bot_success_rate = $total_closed > 0 ? round(($bot_success / $total_closed) * 100, 1) : 0;
$agent_rate = 100 - $bot_success_rate;

// --- INSIGHT (Top Keywords) ---
$stmt = $db->query("SELECT keyword_searched, COUNT(*) as count FROM bot_context_logs GROUP BY keyword_searched ORDER BY count DESC LIMIT 10");
$top_keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-area">
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-2">Thời gian phản hồi TB</div>
                <h3 class="fw-bold text-primary m-0"><?= $avg_resp ?></h3>
                <small class="text-muted mt-2"><i class="fas fa-stopwatch"></i> KPI tốc độ CSKH</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-2">Phiên Chat Hôm Nay</div>
                <h3 class="fw-bold m-0"><?= number_format($today_total) ?></h3>
                <div class="mt-2 small">
                    <span class="text-danger fw-bold"><i class="fas fa-clock"></i> <?= $today_waiting ?> đang chờ</span> | 
                    <span class="text-success"><i class="fas fa-check"></i> <?= $today_closed ?> đã đóng</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-2">Tỷ lệ tự động hóa</div>
                <h3 class="fw-bold text-success m-0"><?= $bot_success_rate ?>%</h3>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-success" style="width: <?= $bot_success_rate ?>%"></div>
                    <div class="progress-bar bg-warning" style="width: <?= $agent_rate ?>%"></div>
                </div>
                <small class="text-muted mt-1">Bot tự giải quyết thành công</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-2">Tỷ lệ qua Nhân viên</div>
                <h3 class="fw-bold text-warning m-0"><?= $agent_rate ?>%</h3>
                <small class="text-muted mt-2"><i class="fas fa-user-headset"></i> Cần Agent hỗ trợ</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-4">Top Từ khóa Khách hỏi nhiều nhất</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Từ khóa</th>
                                <th>Lượt hỏi</th>
                                <th>Mức độ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_keywords as $k): 
                                $pct = min(100, $k['count'] * 2); // giả lập pct cho bar
                            ?>
                            <tr>
                                <td class="fw-bold text-primary">"<?= htmlspecialchars($k['keyword_searched']) ?>"</td>
                                <td><?= number_format($k['count']) ?></td>
                                <td style="width: 40%;">
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar bg-info" style="width: <?= $pct ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_keywords)): ?>
                            <tr><td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center d-flex align-items-center justify-content-center" style="min-height:300px; background:linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
                <div>
                    <i class="fas fa-robot fa-4x text-muted mb-3 opacity-25"></i>
                    <h5 class="text-muted">Chatbot Analytics Chart</h5>
                    <p class="small text-muted mb-0">Biểu đồ đang được nâng cấp ở phiên bản tiếp theo.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../public/admin_layout_footer.php'; ?>

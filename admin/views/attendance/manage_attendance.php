<?php
include '../../../public/admin_layout_header.php';
require_once __DIR__ . '/../../../config/database.php';
$db = (new Database())->getConnection();

$date = $_GET['date'] ?? date('Y-m-d');

// Lấy danh sách phân công và trạng thái chấm công
$query = "SELECT sa.*, s.shift_name, s.start_time, s.end_time, e.full_name 
          FROM shift_assignments sa
          JOIN shifts s ON sa.shift_id = s.id
          JOIN employees e ON sa.employee_id = e.id
          WHERE sa.work_date = ?
          ORDER BY s.start_time ASC";
$stmt = $db->prepare($query);
$stmt->execute([$date]);
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách nhân viên CHƯA được phân ca trong ngày này
$query_unassigned = "SELECT id, full_name, position FROM employees 
                     WHERE status = 'working' 
                     AND id NOT IN (SELECT employee_id FROM shift_assignments WHERE work_date = ?)";
$stmt_un = $db->prepare($query_unassigned);
$stmt_un->execute([$date]);
$unassigned = $stmt_un->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-calendar-check me-2 text-primary"></i>Kiểm tra Chấm công Ngày</h3>
        <input type="date" class="form-control w-auto" value="<?= $date ?>"
            onchange="location.href='?date='+this.value">
    </div>
    
    <?php if (count($unassigned) > 0): ?>
        <div class="alert alert-warning shadow-sm border-0 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                <div>
                    <h6 class="mb-1 fw-bold">Có <?= count($unassigned) ?> nhân viên chưa được phân ca làm việc:</h6>
                    <div class="small">
                        <?php 
                        $names = array_map(function($e) { return $e['full_name'] . " (" . ($e['position'] ?: 'N/A') . ")"; }, $unassigned);
                        echo implode(', ', $names);
                        ?>
                    </div>
                    <a href="../../manage_shifts.php?date=<?= $date ?>" class="btn btn-sm btn-dark mt-2">Đi tới Chia lịch ngay</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nhân viên</th>
                    <th>Ca làm</th>
                    <th>Giờ quy định</th>
                    <th>Giờ vào/ra</th>
                    <th>Trạng thái</th>
                    <th>Duyệt công</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendances as $row): ?>
                    <tr>
                        <td><strong>
                                <?= htmlspecialchars($row['full_name']) ?>
                            </strong></td>
                        <td>
                            <?= htmlspecialchars($row['shift_name']) ?>
                        </td>
                        <td><small>
                                <?= $row['start_time'] ?> -
                                <?= $row['end_time'] ?>
                            </small></td>
                        <td>
                            <span class="text-success">
                                <?= $row['check_in'] ? date('H:i', strtotime($row['check_in'])) : '--:--' ?>
                            </span> /
                            <span class="text-danger">
                                <?= $row['check_out'] ? date('H:i', strtotime($row['check_out'])) : '--:--' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'scheduled'): ?>
                                <span class="badge bg-secondary">Chưa đến</span>
                            <?php elseif ($row['status'] == 'present'): ?>
                                <span class="badge bg-success">Có mặt</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Vắng mặt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'present' && $row['approval_status'] == 'pending'): ?>
                                <button class="btn btn-sm btn-outline-success"
                                    onclick="approve(<?= $row['id'] ?>, 'approve')">Duyệt</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="approve(<?= $row['id'] ?>, 'reject')">Từ
                                    chối</button>
                            <?php else: ?>
                                <span class="text-muted">
                                    <?= ucfirst($row['approval_status']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-light" onclick="editTime(<?= $row['id'] ?>, '<?= $row['check_in'] ?? '' ?>', '<?= $row['check_out'] ?? '' ?>')" title="Sửa giờ chấm công thủ công"><i
                                    class="fas fa-pencil-alt"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- SCRIPTS DEPS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function approve(id, action) {
        if (!confirm('Xác nhận thao tác này?')) return;
        $.post('../../ajax/ajax_approve_attendance.php', { assignment_id: id, action: action }, function (res) {
            if (res.status === 'success') location.reload();
            else alert(res.message);
        }, 'json');
    }
</script>

<!-- Modal Sửa giờ thủ công -->
<div class="modal fade" id="modalEditTime" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form id="formEditTime">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Sửa giờ chấm công thủ công</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="assignment_id" id="edit_assignment_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Giờ Check-in thực tế</label>
                        <input type="datetime-local" class="form-control" name="check_in" id="edit_check_in" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Giờ Check-out thực tế</label>
                        <input type="datetime-local" class="form-control" name="check_out" id="edit_check_out">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Bỏ trống nếu nhân viên chưa kết thúc ca.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">Cập nhật & Duyệt luôn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTime(id, checkIn, checkOut) {
    $('#edit_assignment_id').val(id);
    
    // Convert SQL datetime 'YYYY-MM-DD HH:MM:SS' to 'YYYY-MM-DDTHH:MM' for input type="datetime-local"
    if (checkIn && checkIn !== 'null' && checkIn !== '') {
        let formattedCheckIn = checkIn.replace(' ', 'T').substring(0, 16);
        $('#edit_check_in').val(formattedCheckIn);
    } else {
        // If not checked in, prefill with today's date + current time
        let now = new Date();
        let tzoffset = now.getTimezoneOffset() * 60000; 
        let localISOTime = (new Date(Date.now() - tzoffset)).toISOString().slice(0, 16);
        $('#edit_check_in').val(localISOTime);
    }

    if (checkOut && checkOut !== 'null' && checkOut !== '') {
        let formattedCheckOut = checkOut.replace(' ', 'T').substring(0, 16);
        $('#edit_check_out').val(formattedCheckOut);
    } else {
        $('#edit_check_out').val('');
    }

    const modal = new bootstrap.Modal(document.getElementById('modalEditTime'));
    modal.show();
}

$('#formEditTime').submit(function(e) {
    e.preventDefault();
    $.post('../../ajax/ajax_edit_attendance.php', $(this).serialize(), function(res) {
        if(res.status === 'success') {
            location.reload();
        } else {
            alert(res.message);
        }
    }, 'json');
});
</script>

</div> <!-- Closing content-area -->
</div> <!-- Closing main-wrapper -->
</body>
</html>
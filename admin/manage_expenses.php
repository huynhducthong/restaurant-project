<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $category = $_POST['category'] ?? '';
        $amount = str_replace(',', '', $_POST['amount'] ?? '0');
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $note = $_POST['note'] ?? '';

        if (empty($category) || empty($amount) || empty($expense_date)) {
            $message = "Vui lòng nhập đầy đủ thông tin bắt buộc.";
            $message_type = "danger";
        } else {
            if ($action === 'add') {
                $sql = "INSERT INTO restaurant_expenses (category, amount, expense_date, note) VALUES (:category, :amount, :expense_date, :note)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':category' => $category,
                    ':amount' => $amount,
                    ':expense_date' => $expense_date,
                    ':note' => $note
                ]);
                $message = "Thêm chi phí thành công.";
                $message_type = "success";
            } else {
                $id = $_POST['id'] ?? 0;
                $sql = "UPDATE restaurant_expenses SET category = :category, amount = :amount, expense_date = :expense_date, note = :note WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':category' => $category,
                    ':amount' => $amount,
                    ':expense_date' => $expense_date,
                    ':note' => $note,
                    ':id' => $id
                ]);
                $message = "Cập nhật chi phí thành công.";
                $message_type = "success";
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $sql = "DELETE FROM restaurant_expenses WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $message = "Đã xóa chi phí.";
        $message_type = "success";
    }
}

// Fetch data
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$sql = "SELECT * FROM restaurant_expenses WHERE MONTH(expense_date) = :month AND YEAR(expense_date) = :year ORDER BY expense_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':month' => $month, ':year' => $year]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_expense = 0;
foreach ($expenses as $e) {
    $total_expense += $e['amount'];
}

$pageTitle = "Quản Lý Chi Phí";
include '../public/admin_layout_header.php';
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-file-invoice-dollar me-2"></i>Quản Lý Chi Phí</h2>
            <p class="text-muted">Theo dõi và quản lý các khoản chi phí vận hành nhà hàng.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Thêm Chi Phí
            </button>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tháng</label>
                <select name="month" class="form-select">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $month == $i ? 'selected' : '' ?>>
                            Tháng <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Năm</label>
                <select name="year" class="form-select">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-filter me-2"></i>Lọc Dữ Liệu</button>
            </div>
            <div class="col-md-3 text-end">
                <h4 class="mb-0 text-danger">Tổng chi: <?= number_format($total_expense) ?> đ</h4>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Ngày</th>
                        <th>Hạng Mục</th>
                        <th>Số Tiền</th>
                        <th>Ghi Chú</th>
                        <th class="text-end pe-4">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($expenses) > 0): ?>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td class="ps-4"><?= date('d/m/Y', strtotime($e['expense_date'])) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($e['category']) ?></span>
                                </td>
                                <td class="fw-bold text-danger"><?= number_format($e['amount']) ?> đ</td>
                                <td><?= htmlspecialchars($e['note']) ?></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick='editExpense(<?= json_encode($e) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Không có dữ liệu chi phí cho tháng này.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title" id="modalTitle">Thêm Chi Phí Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="expenseId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Hạng mục chi phí <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select" required>
                            <option value="">-- Chọn hạng mục --</option>
                            <option value="Điện nước">Điện nước (Điện, nước, internet, rác)</option>
                            <option value="Lương">Lương (Lương NV, thưởng, phụ cấp)</option>
                            <option value="Mặt bằng">Mặt bằng (Thuê nhà, kho bãi)</option>
                            <option value="Bảo trì">Bảo trì (Sửa chữa, bảo dưỡng)</option>
                            <option value="Marketing">Marketing (Quảng cáo, in ấn)</option>
                            <option value="Nguyên liệu ngoài">Nguyên liệu phát sinh ngoài</option>
                            <option value="Khác">Chi phí khác</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số tiền (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control" required min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ngày chi <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" id="expense_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ghi chú chi tiết</label>
                        <textarea name="note" id="note" class="form-control" rows="3" placeholder="Ví dụ: Tiền điện tháng 10..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Lưu Chi Phí</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Thêm Chi Phí Mới';
    document.getElementById('formAction').value = 'add';
    document.getElementById('expenseId').value = '';
    document.getElementById('category').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('expense_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('note').value = '';
}

function editExpense(data) {
    document.getElementById('modalTitle').innerText = 'Cập Nhật Chi Phí';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('expenseId').value = data.id;
    document.getElementById('category').value = data.category;
    document.getElementById('amount').value = data.amount;
    document.getElementById('expense_date').value = data.expense_date;
    document.getElementById('note').value = data.note;
    
    var myModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    myModal.show();
}
</script>

<?php include '../public/admin_layout_footer.php'; ?>

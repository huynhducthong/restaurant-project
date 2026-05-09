<?php
// admin/views/po/po_list.php
include __DIR__ . '/../../../public/admin_layout_header.php';
?>
<style>
    :root {
        --primary-color: #2c3e50;
        --accent-color: #3498db;
        --success-color: #27ae60;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --light-bg: #f8f9fa;
        --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    body { font-family: 'Inter', 'Segoe UI', Roboto, sans-serif; background-color: #f4f7f6; }
    
    /* Sidebar & Navigation */
    .sidebar-card { border: none; border-radius: 12px; box-shadow: var(--card-shadow); }
    .btn-menu { 
        text-align: left; border-radius: 8px; transition: all 0.2s; 
        border: 1px solid transparent; font-size: 0.85rem; padding: 10px 15px;
        text-decoration: none; color: #555; display: block;
    }
    .btn-menu:hover { transform: translateX(5px); background-color: rgba(52, 152, 219, 0.1); color: var(--accent-color); }
    .btn-menu.active { background-color: var(--primary-color); color: white; }

    /* Cards & Containers */
    .main-card { border: none; border-radius: 15px; box-shadow: var(--card-shadow); background: white; }

    /* Tables */
    .table thead th { 
        background-color: #f8f9fa; color: #7f8c8d; font-weight: 600; 
        text-transform: uppercase; font-size: 0.75rem; border-top: none;
        padding: 15px;
    }
    .table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="row g-4">
        <!-- ================= CỘT MENU TRÁI (ĐỒNG BỘ VỚI KHO) ================= -->
        <div class="col-lg-3">
            <div class="card sidebar-card p-3 mb-4">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Chức năng chính</h6>
                <a href="InventoryController.php?tab=all" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-boxes me-2"></i> TẤT CẢ KHO
                </a>
                <a href="InventoryController.php?tab=transfers" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i> CHUYỂN KHO
                </a>
                <a href="InventoryController.php?tab=distribution" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-th me-2"></i> BÁO CÁO PHÂN BỔ
                </a>
                <hr class="my-3 opacity-10">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Mở rộng</h6>
                <a href="POController.php" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold active">
                    <i class="fas fa-file-invoice-dollar me-2"></i> ĐẶT HÀNG (PO)
                </a>
                <a href="InventoryController.php?tab=suppliers" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-truck me-2"></i> NHÀ CUNG CẤP
                </a>
                <a href="InventoryController.php?tab=chart" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-chart-bar me-2"></i> BIỂU ĐỒ KHO
                </a>
            </div>
        </div>

        <!-- ================= CỘT NỘI DUNG CHÍNH (PO LIST) ================= -->
        <div class="col-lg-9">
            <div class="main-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-uppercase m-0"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Phiếu Đặt Hàng (PO)</h3>
                    <button class="btn btn-primary shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCreatePO">
                        <i class="fas fa-plus me-2"></i>TẠO PHIẾU NHẬP
                    </button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4">
                        <i class="fas fa-check-circle me-2"></i><?= ($_GET['msg'] === 'success') ? 'Nhận hàng và nhập kho thành công!' : 'Tạo Phiếu đặt hàng (PO) thành công!' ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Mã PO / Ngày tạo</th>
                                <th>Nhà cung cấp</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pos as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-primary">#<?= htmlspecialchars($p['po_code']) ?></div>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></small>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($p['supplier_name']) ?></td>
                                <td class="text-end text-danger fw-bold fs-6"><?= number_format($p['total_amount']) ?> đ</td>
                                <td>
                                    <?php if($p['status'] == 'pending'): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Chờ nhận</span>
                                    <?php elseif($p['status'] == 'completed'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Đã nhập kho</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border fw-bold me-1" onclick="viewPO(<?= $p['id'] ?>, '<?= htmlspecialchars($p['po_code']) ?>')">Xem</button>
                                    <?php if($p['status'] == 'pending'): ?>
                                        <a href="POController.php?action=receive&id=<?= $p['id'] ?>" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Xác nhận hàng đã về và tự động cộng vào Kho Tổng?')">
                                            Nhận hàng
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pos)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">Chưa có phiếu đặt hàng nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= CÁC MODAL ================= -->
<div class="modal fade" id="modalCreatePO" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" method="POST" action="POController.php">
            <input type="hidden" name="create_po" value="1">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">TẠO PHIẾU ĐẶT HÀNG MỚI</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold mb-1 small text-muted">NHÀ CUNG CẤP <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select shadow-sm" required>
                            <option value="">-- Chọn Nhà Cung Cấp --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table table-bordered mb-0" id="poTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th width="150">Số Lượng</th>
                                <th width="200">Đơn Giá (đ)</th>
                                <th width="200">Thành Tiền (đ)</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="poBody">
                            <tr>
                                <td>
                                    <select name="item_id[]" class="form-select item-select" required>
                                        <option value="">- Chọn NL -</option>
                                        <?php foreach($ingredients as $i): ?>
                                            <option value="<?= $i['id'] ?>" data-price="<?= $i['cost_price'] ?>"><?= $i['item_name'] ?> (<?= $i['unit_name'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="qty[]" class="form-control qty-input" step="0.01" min="0.01" required></td>
                                <td><input type="number" name="price[]" class="form-control price-input" required></td>
                                <td><input type="text" class="form-control row-total" readonly value="0"></td>
                                <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove border-0"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-white">
                                <td colspan="3" class="text-end fw-bold align-middle">TỔNG CỘNG:</td>
                                <td colspan="2"><input type="text" id="poGrandTotal" class="form-control fw-bold text-danger border-0 fs-5" readonly value="0"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-3 fw-bold" id="btnAddRow"><i class="fas fa-plus me-1"></i>Thêm dòng hàng</button>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-5 shadow">HOÀN TẤT & LƯU PHIẾU</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalViewPO" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title fw-bold text-primary">CHI TIẾT PHIẾU: <span id="view-po-code"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nguyên Liệu</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody id="view-po-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $(document).on('change', '.item-select', function() {
        let price = $(this).find(':selected').data('price') || 0;
        $(this).closest('tr').find('.price-input').val(price);
        calcTotal();
    });
    $(document).on('input', '.qty-input, .price-input', function() { calcTotal(); });
    $('#btnAddRow').click(function() {
        let newRow = $('#poBody tr:first').clone();
        newRow.find('input').val('');
        newRow.find('.row-total').val('0');
        $('#poBody').append(newRow);
    });
    $(document).on('click', '.btn-remove', function() {
        if ($('#poBody tr').length > 1) { $(this).closest('tr').remove(); calcTotal(); }
    });
    function calcTotal() {
        let grandTotal = 0;
        $('#poBody tr').each(function() {
            let qty = parseFloat($(this).find('.qty-input').val()) || 0;
            let price = parseFloat($(this).find('.price-input').val()) || 0;
            let total = qty * price;
            $(this).find('.row-total').val(total.toLocaleString('en-US'));
            grandTotal += total;
        });
        $('#poGrandTotal').val(grandTotal.toLocaleString('en-US') + ' đ');
    }
});
window.viewPO = function(id, code) {
    $('#view-po-code').text(code);
    $('#view-po-body').html('<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>');
    new bootstrap.Modal(document.getElementById('modalViewPO')).show();
    $.post('POController.php', { action: 'get_details', po_id: id }, function(res) {
        if(res.status === 'success') {
            let html = '';
            let grandTotal = 0;
            res.data.forEach(item => {
                let qty = parseFloat(item.expected_qty || item.quantity || 0);
                let price = parseFloat(item.expected_price || item.price || 0);
                let total = qty * price;
                grandTotal += total;
                html += `<tr><td><div class="fw-bold">${item.item_name}</div></td><td class="text-center"><strong>${qty}</strong> <small class="text-muted">${item.unit_name}</small></td><td class="text-end">${price.toLocaleString('en-US')} đ</td><td class="text-end fw-bold text-danger">${total.toLocaleString('en-US')} đ</td></tr>`;
            });
            html += `<tr class="bg-light"><td colspan="3" class="text-end fw-bold py-3">TỔNG CỘNG:</td><td class="text-end fw-bold text-danger py-3 fs-5">${grandTotal.toLocaleString('en-US')} đ</td></tr>`;
            $('#view-po-body').html(html);
        }
    }, 'json');
};
</script>
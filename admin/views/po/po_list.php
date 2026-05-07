<?php
// admin/views/po/po_list.php
include __DIR__ . '/../../../public/admin_layout_header.php';
?>
<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-uppercase"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Phiếu Đặt Hàng (PO)</h3>
        <button class="btn btn-primary shadow-sm"><i class="fas fa-plus me-2"></i>Tạo Phiếu Nhập</button>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Nhận hàng và nhập kho thành công!</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-primary">
                <tr>
                    <th>Mã PO</th>
                    <th>Ngày tạo</th>
                    <th>Nhà cung cấp</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php foreach ($pos as $p): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($p['po_code']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                    <td><?= htmlspecialchars($p['supplier_name']) ?></td>
                    <td class="text-danger fw-bold"><?= number_format($p['total_amount']) ?> đ</td>
                    <td>
                        <?php if($p['status'] == 'pending'): ?>
                            <span class="badge bg-warning text-dark">Chờ nhận hàng</span>
                        <?php elseif($p['status'] == 'completed'): ?>
                            <span class="badge bg-success">Đã nhập kho</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-light border"><i class="fas fa-eye text-primary"></i> Xem</button>
                        
                        <?php if($p['status'] == 'pending'): ?>
                            <!-- Nút Xác nhận Nhận hàng -->
                            <a href="POController.php?action=receive&id=<?= $p['id'] ?>" 
                               class="btn btn-sm btn-success fw-bold"
                               onclick="return confirm('Bạn xác nhận hàng đã về và tự động cộng vào kho?')">
                                <i class="fas fa-box-open me-1"></i> Nhận hàng
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($pos)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có phiếu đặt hàng nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
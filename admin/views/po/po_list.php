<?php
// admin/views/po/po_list.php
include __DIR__ . '/../../../public/admin_layout_header.php';
?>
<style>
    :root {
        --primary-color: #2C3034;
        --accent-color: #7A2021;
        --success-color: #27ae60;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --light-bg: #fdfdfd;
        --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    
    body { background-color: #f8f9fa; }

    /* Sidebar & Navigation */
    .sidebar-card {
        border: 1px solid #f1f1f1;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        position: relative;
        z-index: 100;
        background: #fff;
    }

    .btn-menu {
        text-align: left;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
        border: 1px solid transparent;
        font-size: 0.85rem;
        padding: 10px 15px;
        margin-bottom: 5px;
        cursor: pointer !important;
        display: flex;
        align-items: center;
        width: 100%;
        background: transparent;
        color: #555;
        text-decoration: none;
    }

    .btn-menu:hover {
        background-color: #f8f9fa !important;
        color: var(--primary-color) !important;
    }

    .btn-menu.active {
        background-color: var(--primary-color) !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(44, 48, 52, 0.15);
    }

    .btn-menu i {
        width: 25px;
        pointer-events: none;
    }

    .stat-card {
        border: 1px solid #f1f1f1;
        border-radius: 12px;
        transition: transform 0.2s;
        background: #fff;
        border-left: 3px solid var(--accent-color);
        box-shadow: var(--card-shadow);
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    /* Clean Tables */
    .main-card { border: 1px solid #f1f1f1; border-radius: 12px; box-shadow: var(--card-shadow); background: #fff; }
    
    .table thead th {
        background-color: transparent;
        color: #95a5a6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 1px;
        border-top: none;
        border-bottom: 1px solid #eaeaea;
        padding: 15px;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px dashed #f1f1f1;
        color: #495057;
    }

    .table-hover tbody tr:hover {
        background-color: #fcfcfc;
    }

    /* Action Buttons in Table */
    .btn-group .btn {
        padding: 6px 10px;
        transition: all 0.2s;
        border-radius: 6px;
    }

    .btn-group .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    /* Ghost Badges */
    .badge-ghost-success { background-color: #f8f9fa; color: #495057; border: 1px solid #e9ecef; padding: 5px 8px; font-weight: 500; border-radius: 6px; font-size: 0.75rem; }
    .badge-ghost-success::before { content: "•"; color: var(--success-color); font-size: 22px; margin-right: 4px; vertical-align: middle; line-height: 0.5; }
    
    .badge-ghost-danger { background-color: #f8f9fa; color: #495057; border: 1px solid #e9ecef; padding: 5px 8px; font-weight: 500; border-radius: 6px; font-size: 0.75rem; }
    .badge-ghost-danger::before { content: "•"; color: var(--danger-color); font-size: 22px; margin-right: 4px; vertical-align: middle; line-height: 0.5; }
    
    .badge-ghost-warning { background-color: #f8f9fa; color: #495057; border: 1px solid #e9ecef; padding: 5px 8px; font-weight: 500; border-radius: 6px; font-size: 0.75rem; }
    .badge-ghost-warning::before { content: "•"; color: var(--warning-color); font-size: 22px; margin-right: 4px; vertical-align: middle; line-height: 0.5; }
    
    .badge-ghost-info { background-color: #f8f9fa; color: #495057; border: 1px solid #e9ecef; padding: 5px 8px; font-weight: 500; border-radius: 6px; font-size: 0.75rem; }
    .badge-ghost-info::before { content: "•"; color: var(--accent-color); font-size: 22px; margin-right: 4px; vertical-align: middle; line-height: 0.5; }
    
    .badge-ghost-secondary { background-color: #f8f9fa; color: #495057; border: 1px solid #e9ecef; padding: 5px 8px; font-weight: 500; border-radius: 6px; font-size: 0.75rem; }
    .badge-ghost-secondary::before { content: "•"; color: #6c757d; font-size: 22px; margin-right: 4px; vertical-align: middle; line-height: 0.5; }
    /* Form Select styling */
    .form-select {
        border: 1px solid #e9ecef;
        box-shadow: none !important;
        font-size: 0.85rem;
        color: #495057;
        border-radius: 6px;
    }
    .form-select:focus {
        border-color: #ced4da;
    }
    
    /* Pagination styling */
    .pagination .page-link {
        color: #495057;
        border-color: #e9ecef;
        box-shadow: none !important;
    }
    .pagination .page-item.active .page-link {
        background-color: #2C3034 !important;
        border-color: #2C3034 !important;
        color: #ffffff !important;
        font-weight: 700;
    }
    .pagination .page-link:hover {
        background-color: #f1f1f1;
    }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="row g-4">
        <!-- ================= CỘT MENU TRÁI (ĐỒNG BỘ VỚI KHO) ================= -->
        <div class="col-lg-3">
            <div class="card sidebar-card mb-4 overflow-hidden">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold m-0 text-secondary text-uppercase small">Thống kê nhanh</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($top_used as $t): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-3 py-2 small">
                            <span class="text-muted"><?= htmlspecialchars($t['item_name']) ?></span>
                            <span class="fw-bold text-dark"><?= (float)$t['total'] ?> <?= $t['unit_name'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card sidebar-card p-3 mb-4">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Chức năng chính</h6>
                <a href="InventoryController.php?tab=all" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-boxes me-2"></i> TẤT CẢ KHO
                </a>
                <a href="InventoryController.php?tab=transfers" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i> CHUYỂN KHO
                    <?php if ($pending_transfers_count > 0): ?>
                        <span class="badge badge-ghost-danger ms-auto"><?= $pending_transfers_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="InventoryController.php?tab=distribution" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-th me-2"></i> BÁO CÁO PHÂN BỔ
                </a>
                <a href="InventoryController.php?tab=history" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-history me-2"></i> LỊCH SỬ GD
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
                <hr class="my-3 opacity-10">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Cài đặt danh mục</h6>
                <button class="btn btn-menu btn-outline-secondary w-100 mb-2 py-2 fw-bold" onclick="window.location.href='InventoryController.php'">
                    <i class="fas fa-tags me-2"></i> DANH MỤC
                </button>
                <button class="btn btn-menu btn-outline-secondary w-100 mb-2 py-2 fw-bold" onclick="window.location.href='InventoryController.php'">
                    <i class="fas fa-ruler me-2"></i> ĐƠN VỊ TÍNH
                </button>
            </div>

            <?php if ($low_stock_count > 0): ?>
                <div class="stat-card p-3 mb-3 border-start-danger" style="border-left: 4px solid var(--danger-color); background: #fff; border-radius: 12px; position: relative;">
                    <div class="small text-danger fw-bold text-uppercase mb-1">Cảnh báo tồn kho</div>
                    <div class="h5 m-0 fw-bold"><?= $low_stock_count ?> món sắp hết</div>
                    <a href="InventoryController.php?tab=reorder" class="stretched-link"></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================= CỘT NỘI DUNG CHÍNH (PO LIST) ================= -->
        <div class="col-lg-9">
            <div class="main-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-uppercase m-0"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Phiếu Đặt Hàng (PO)</h3>
                    <div>
                        <a href="POController.php?export_excel=1" class="btn btn-outline-success shadow-sm fw-bold px-3 me-2">
                            <i class="fas fa-file-excel me-2"></i>XUẤT EXCEL (KÈM HSD)
                        </a>
                        <button class="btn btn-outline-dark shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCreatePO">
                            <i class="fas fa-plus me-2"></i>TẠO PHIẾU NHẬP
                        </button>
                    </div>
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
                                        <span class="badge badge-ghost-warning"><i class="fas fa-clock me-1"></i>Chờ nhận</span>
                                    <?php elseif($p['status'] == 'completed'): ?>
                                        <span class="badge badge-ghost-success"><i class="fas fa-check me-1"></i>Đã nhập kho</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border fw-bold me-1" onclick="viewPO(<?= $p['id'] ?>, '<?= htmlspecialchars($p['po_code']) ?>')">Xem</button>
                                    <?php if($p['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-success fw-bold" onclick="openReceivePO(<?= $p['id'] ?>, '<?= htmlspecialchars($p['po_code']) ?>')">
                                            <i class="fas fa-arrow-down me-1"></i>Nhận hàng
                                        </button>
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

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<div class="modal fade" id="modalCreatePO" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" method="POST" action="POController.php" style="border-radius:20px;overflow:hidden;">
            <input type="hidden" name="create_po" value="1">
            <div class="modal-header bg-dark  py-3 px-4">
                <h5 class="modal-title mb-0" style="font-family:'Cormorant Garamond', serif;">
                    <i class="fas fa-file-invoice-dollar me-2 text-warning"></i> TẠO PHIẾU ĐẶT HÀNG MỚI
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <p class="fw-bold small text-uppercase text-muted mb-3 border-bottom pb-2">
                    <i class="fas fa-info-circle me-1 text-warning"></i>Thông tin nhà cung cấp
                </p>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <select name="supplier_id" id="po-supplier-select" class="form-select bg-light border-0 py-2 shadow-sm" required>
                            <option value="">-- Chọn Nhà Cung Cấp --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>" data-atvstp-expiry="<?= htmlspecialchars($s['atvstp_expiry'] ?? '') ?>"><?= $s['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="atvstp-warning" class="mt-2 text-danger fw-bold small d-none">
                            <i class="fas fa-exclamation-triangle me-1"></i>LƯU Ý: Giấy ATVSTP của nhà cung cấp này đã hết hạn (hoặc chưa cập nhật). Vui lòng yêu cầu cấp lại trước khi nhập hàng!
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <p class="fw-bold small text-uppercase text-muted mb-0">
                        <i class="fas fa-box-open me-1 text-warning"></i>Chi tiết hàng hóa
                    </p>
                    <button type="button" class="btn btn-sm btn-outline-primary border-0 fw-bold" onclick="openQuickAddIng()">
                        <i class="fas fa-plus-circle me-1"></i>Thêm nguyên liệu mới
                    </button>
                </div>
                <div class="card shadow-sm border-0 overflow-hidden mb-3">
                    <table class="table table-bordered mb-0 align-middle" id="poTable">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th width="150">Số Lượng</th>
                                <th width="200">Đơn Giá (VNĐ)</th>
                                <th width="200">Thành Tiền (VNĐ)</th>
                                <th width="50" class="text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="poBody">
                            <tr>
                                <td>
                                    <select name="item_id[]" class="form-select border-0 bg-light item-select" required>
                                        <option value="">- Chọn NL -</option>
                                        <?php foreach($ingredients as $i): ?>
                                            <option value="<?= $i['id'] ?>" data-price="<?= $i['cost_price'] ?>"><?= $i['item_name'] ?> (<?= $i['unit_name'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="qty[]" class="form-control border-0 bg-light qty-input" step="0.01" min="0.01" placeholder="0" required></td>
                                <td><input type="text" name="price[]" class="form-control border-0 bg-light price-input money-input" placeholder="0" required></td>
                                <td><input type="text" class="form-control border-0 bg-light text-danger fw-bold row-total" readonly value="0"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove border-0"><i class="fas fa-times"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="3" class="text-end fw-bold align-middle text-muted">TỔNG CỘNG PHIẾU ĐẶT:</td>
                                <td colspan="2"><input type="text" id="poGrandTotal" class="form-control fw-bold text-danger border-0 fs-5 bg-transparent" readonly value="0"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold" id="btnAddRow">
                        <i class="fas fa-plus me-1"></i>Thêm dòng hàng
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark  rounded-pill px-3 fw-bold shadow-sm" onclick="loadSuggestions()">
                        <i class="fas fa-lightbulb me-1"></i>Gợi ý từ tồn kho thấp
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 bg-white p-4 pt-0">
                <button type="submit" class="btn btn-warning w-100 py-3 rounded-pill fw-bold  shadow-sm" style="background:#cda45e;border:none;">
                    <i class="fas fa-check-circle me-2"></i> HOÀN TẤT & LƯU PHIẾU ĐẶT HÀNG
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalViewPO" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header bg-dark  py-3 px-4">
                <h5 class="modal-title fw-bold" style="font-family:'Cormorant Garamond', serif;"><i class="fas fa-info-circle me-2 text-warning"></i>CHI TIẾT PHIẾU: <span id="view-po-code" class="text-warning"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Nguyên Liệu</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end pe-4">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody id="view-po-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NHẬN HÀNG (GOODS RECEIPT) -->
<div class="modal fade" id="modalReceivePO" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" method="POST" action="POController.php" style="border-radius:20px;overflow:hidden;" enctype="multipart/form-data">
            <input type="hidden" name="receive_po_final" value="1">
            <input type="hidden" name="po_id" id="receive-po-id">
            <div class="modal-header bg-success  py-3 px-4">
                <h5 class="modal-title fw-bold" style="font-family:'Cormorant Garamond', serif;">
                    <i class="fas fa-check-double me-2"></i>NHẬN HÀNG VÀ NHẬP KHO: <span id="receive-po-code" class=""></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-info m-3 py-2 small">
                    <i class="fas fa-info-circle me-1"></i>Vui lòng kiểm tra số lượng và giá thực tế khi nhận hàng. Hàng sẽ được nhập vào <b>Kho Tổng</b>.
                </div>
                <div class="m-3 p-3 bg-light border rounded">
                    <label class="small fw-bold text-danger mb-1"><i class="fas fa-file-invoice me-1"></i>Giấy kiểm dịch lô hàng / CO-CQ (Ảnh/PDF)</label>
                    <input type="file" name="po_batch_cert" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="small text-muted mt-1">Sẽ được lưu vĩnh viễn vào lịch sử nhập kho của lô hàng này.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Nguyên Liệu</th>
                                <th class="text-center" width="100">SL Đặt</th>
                                <th class="text-center" width="140">SL Thực Nhận</th>
                                <th class="text-center" width="150">Giá Nhập (đ)</th>
                                <th class="text-center" width="120">Nhiệt độ (°C)</th>
                                <th class="text-center" width="150">Hạn sử dụng</th>
                            </tr>
                        </thead>
                        <tbody id="receive-po-body">
                            <!-- Dữ liệu load bằng AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="submit" class="btn btn-outline-success px-5 py-2 fw-bold rounded-pill">
                    <i class="fas fa-save me-2"></i>XÁC NHẬN NHẬP KHO
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL THÊM NHANH NGUYÊN LIỆU (QUICK ADD) -->
<div class="modal fade" id="modalQuickAddIng" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary  py-2 px-3">
                <h6 class="modal-title mb-0">Thêm nguyên liệu mới</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="small fw-bold">Tên nguyên liệu</label>
                    <input type="text" id="quick-ing-name" class="form-control form-control-sm" placeholder="VD: Sốt BBQ">
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Đơn vị tính</label>
                    <input type="text" id="quick-ing-unit" class="form-control form-control-sm" placeholder="VD: Chai, Kg...">
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Danh mục</label>
                    <select id="quick-ing-cat" class="form-select form-select-sm">
                        <option value="Gia vị">Gia vị</option>
                        <option value="Thực phẩm">Thực phẩm</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>
                <button type="button" class="btn btn-outline-dark btn-sm w-100 fw-bold" id="btnSaveQuickIng">LƯU & CHỌN</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    $('#po-supplier-select').on('change', function() {
        const option = $(this).find('option:selected');
        const expiry = option.data('atvstp-expiry');
        const warningDiv = $('#atvstp-warning');
        
        if (expiry) {
            const expiryDate = new Date(expiry);
            const today = new Date();
            today.setHours(0,0,0,0);
            
            if (expiryDate < today) {
                warningDiv.removeClass('d-none');
            } else {
                warningDiv.addClass('d-none');
            }
        } else {
            // Nếu không có ngày hết hạn, có thể coi là chưa cấp hoặc bỏ qua
            if ($(this).val() !== "") {
                // Hiện cảnh báo nếu có chọn NCC nhưng không có HSD
                warningDiv.removeClass('d-none');
            } else {
                warningDiv.addClass('d-none');
            }
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('trigger_suggestion') === '1') {
        const myModal = new bootstrap.Modal(document.getElementById('modalCreatePO'));
        myModal.show();
        // Đợi modal hiện xong rồi mới gọi gợi ý
        document.getElementById('modalCreatePO').addEventListener('shown.bs.modal', function () {
            loadSuggestions();
        }, { once: true });
    } else if (urlParams.get('add_item')) {
        const addItemId = urlParams.get('add_item');
        const addQty = urlParams.get('qty');
        const myModal = new bootstrap.Modal(document.getElementById('modalCreatePO'));
        myModal.show();
        document.getElementById('modalCreatePO').addEventListener('shown.bs.modal', function () {
            const firstRow = $('#poBody tr').first();
            firstRow.find('.item-select').val(addItemId).trigger('change');
            if (addQty) {
                firstRow.find('.qty-input').val(addQty).trigger('input');
            }
        }, { once: true });
    }

    // Định dạng tiền tệ khi focus/blur
    $(document).on('focus', '.money-input', function() {
        let val = $(this).val().replace(/,/g, '');
        $(this).attr('type', 'number');
        $(this).val(val);
    });

    $(document).on('blur', '.money-input', function() {
        let val = parseFloat($(this).val()) || 0;
        $(this).attr('type', 'text');
        $(this).val(val.toLocaleString('en-US'));
        calcTotal();
    });

    $(document).on('change', '.item-select', function() {
        let price = $(this).find(':selected').data('price') || 0;
        let priceInput = $(this).closest('tr').find('.price-input');
        priceInput.attr('type', 'text');
        priceInput.val(parseFloat(price).toLocaleString('en-US'));
        calcTotal();
    });

    $(document).on('input', '.qty-input', function() { calcTotal(); });

    $('#btnAddRow').click(function() {
        let newRow = $('#poBody tr:first').clone();
        newRow.find('input').val('');
        newRow.find('input.price-input').attr('type', 'text').val('');
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
            let priceStr = $(this).find('.price-input').val() || '0';
            let price = parseFloat(priceStr.replace(/,/g, '')) || 0;
            let total = qty * price;
            $(this).find('.row-total').val(total.toLocaleString('en-US'));
            grandTotal += total;
        });
        $('#poGrandTotal').val(grandTotal.toLocaleString('en-US') + ' đ');
    }

    $('form[action="POController.php"]').on('submit', function() {
        $(this).find('.money-input').each(function() {
            let val = $(this).val().replace(/,/g, '');
            $(this).attr('type', 'number').val(val);
        });
    });

    window.openQuickAddIng = function() {
        new bootstrap.Modal(document.getElementById('modalQuickAddIng')).show();
    };

    $('#btnSaveQuickIng').click(function() {
        const name = $('#quick-ing-name').val();
        const unit = $('#quick-ing-unit').val();
        const cat  = $('#quick-ing-cat').val();
        if(!name || !unit) return alert('Vui lòng nhập đủ Tên và Đơn vị!');
        $(this).prop('disabled', true).text('Đang lưu...');

        $.post('POController.php', { action: 'quick_add_ingredient', name: name, unit: unit, category: cat }, function(res) {
            $('#btnSaveQuickIng').prop('disabled', false).text('LƯU & CHỌN');
            if(res.status === 'success') {
                const newOpt = `<option value="${res.id}" data-price="0" selected>${name} (${unit})</option>`;
                $('.item-select').append(newOpt);
                bootstrap.Modal.getInstance(document.getElementById('modalQuickAddIng')).hide();
                $('#quick-ing-name, #quick-ing-unit').val('');
                alert('Đã thêm nguyên liệu mới!');
            } else {
                alert('Lỗi: ' + res.message);
            }
        }, 'json');
    });

    window.viewPO = function(id, code) {
        $('#view-po-code').text(code);
        $('#view-po-body').html('<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-warning"></div></td></tr>');
        $('#view-po-cert-btn').remove(); // Xóa nút cũ nếu có
        new bootstrap.Modal(document.getElementById('modalViewPO')).show();
        $.post('POController.php', { action: 'get_details', po_id: id }, function(res) {
            if(res.status === 'success') {
                let html = '', grandTotal = 0;
                res.data.forEach(item => {
                    let qty = parseFloat(item.expected_qty || item.quantity || 0);
                    let price = parseFloat(item.expected_price || item.price || 0);
                    let total = qty * price;
                    grandTotal += total;
                    html += `<tr><td class="ps-4"><div class="fw-bold">${item.item_name}</div></td><td class="text-center"><strong>${qty}</strong> <small class="text-muted">${item.unit_name}</small></td><td class="text-end">${price.toLocaleString('en-US')} đ</td><td class="text-end fw-bold text-danger pe-4">${total.toLocaleString('en-US')} đ</td></tr>`;
                });
                html += `<tr class="bg-light"><td colspan="3" class="text-end fw-bold py-3 text-muted">TỔNG CỘNG:</td><td class="text-end fw-bold text-danger py-3 fs-5 pe-4">${grandTotal.toLocaleString('en-US')} đ</td></tr>`;
                $('#view-po-body').html(html);

                // Nếu có file giấy kiểm dịch, thêm nút vào header
                if (res.batch_cert_file) {
                    const btn = `<a href="../../uploads/po_certs/${res.batch_cert_file}" target="_blank" id="view-po-cert-btn" class="btn btn-sm btn-outline-danger ms-3 fw-bold shadow-sm"><i class="fas fa-file-pdf me-1"></i>Xem Chứng Từ Lô Hàng</a>`;
                    $('#view-po-code').after(btn);
                }
                
                // Nếu có giấy ATVSTP của nhà cung cấp
                if (res.supplier_atvstp) {
                    const atvstpBtn = `<a href="../../uploads/suppliers/${res.supplier_atvstp}" target="_blank" id="view-po-supplier-atvstp" class="btn btn-sm btn-outline-warning ms-2"><i class="fas fa-file-certificate me-1"></i>Xem ATVSTP Nhà Cung Cấp</a>`;
                    $('#view-po-code').after(atvstpBtn);
                }
            }
        }, 'json');
    };

    window.openReceivePO = function(id, code) {
        $('#receive-po-id').val(id);
        $('#receive-po-code').text(code);
        $('#receive-po-body').html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-success"></div></td></tr>');
        new bootstrap.Modal(document.getElementById('modalReceivePO')).show();
        $.post('POController.php', { action: 'get_details', po_id: id }, function(res) {
            if(res.status === 'success') {
                let html = '';
                res.data.forEach(item => {
                    let qty = parseFloat(item.expected_qty || 0);
                    let price = parseFloat(item.expected_price || 0);
                    html += `<tr><td class="ps-4"><div class="fw-bold">${item.item_name}</div><input type="hidden" name="ingredient_id[]" value="${item.ingredient_id}"></td><td class="text-center text-muted">${qty} ${item.unit_name}</td><td><div class="input-group input-group-sm"><input type="number" name="received_qty[]" class="form-control text-center fw-bold" step="0.01" value="${qty}" required><span class="input-group-text">${item.unit_name}</span></div></td><td><input type="text" name="received_price[]" class="form-control form-control-sm text-end money-input" value="${price.toLocaleString('en-US')}" required></td><td><input type="text" name="receiving_temperature[]" class="form-control form-control-sm text-center" placeholder="VD: -18, 4" required></td><td class="pe-4"><input type="date" name="expiry_date[]" class="form-control form-control-sm" required></td></tr>`;
                });
                $('#receive-po-body').html(html);

                $('#receive-po-supplier-atvstp').remove(); // Xóa link cũ nếu có
                if (res.supplier_atvstp) {
                    const atvstpLink = `<a href="../../uploads/suppliers/${res.supplier_atvstp}" target="_blank" id="receive-po-supplier-atvstp" class="btn btn-sm btn-outline-light ms-3"><i class="fas fa-file-certificate me-1"></i>Xem Giấy ATVSTP Nhà Cung Cấp</a>`;
                    $('#receive-po-code').after(atvstpLink);
                }
            }
        }, 'json');
    };

    // ================= GỢI Ý NHẬP HÀNG TỰ ĐỘNG =================
    window.loadSuggestions = function() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang tải...';

        $.post('../controllers/InventoryController.php', { action: 'get_reorder_list' }, function(res) {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if(res.status === 'success' && res.data.length > 0) {
                // Xóa dòng đầu tiên nếu nó trống
                const firstRow = $('#poBody tr').first();
                if(firstRow.find('.item-select').val() === '') {
                    firstRow.remove();
                }

                res.data.forEach(item => {
                    // Kiểm tra xem item đã có trong danh sách chưa
                    let exists = false;
                    $('.item-select').each(function() {
                        if($(this).val() == item.id) exists = true;
                    });
                    if(exists) return;

                    const min = parseFloat(item.min_stock) || 5;
                    const stock = parseFloat(item.total_stock);
                    // Gợi ý: Nhập bù đủ min + 50% dự phòng
                    const suggestQty = Math.ceil((min - stock) + (min * 0.5));
                    
                    const newRow = `
                        <tr>
                            <td>
                                <select name="item_id[]" class="form-select border-0 bg-light item-select" required>
                                    <option value="${item.id}" selected>${item.item_name} (${item.unit_name})</option>
                                </select>
                            </td>
                            <td><input type="number" name="qty[]" class="form-control border-0 bg-light qty-input" step="0.01" min="0.01" value="${suggestQty}" required></td>
                            <td><input type="text" name="price[]" class="form-control border-0 bg-light price-input money-input" value="${parseInt(item.cost_price).toLocaleString('en-US')}" required></td>
                            <td><input type="text" class="form-control border-0 bg-light text-danger fw-bold row-total" readonly value="0"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove border-0"><i class="fas fa-times"></i></button></td>
                        </tr>
                    `;
                    $('#poBody').append(newRow);
                });
                // Cập nhật lại thành tiền cho các dòng mới
                $('#poBody tr').each(function() {
                    const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                    const price = parseInt($(this).find('.price-input').val().replace(/[^0-9]/g, '')) || 0;
                    $(this).find('.row-total').val((qty * price).toLocaleString('en-US'));
                });
                updateGrandTotal();
                alert('✅ Đã tự động thêm ' + res.data.length + ' nguyên liệu cần nhập hàng.');
            } else {
                alert('ℹ️ Hiện tại không có nguyên liệu nào dưới mức tồn tối thiểu.');
            }
        }, 'json').fail(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('❌ Lỗi kết nối máy chủ.');
        });
    };
});
</script>
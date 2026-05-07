<?php
// admin/views/inventory/inventory_view.php
include '../../public/admin_layout_header.php';
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

    body {
        font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
        background-color: #f4f7f6;
    }

    /* Sidebar & Navigation */
    .sidebar-card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        position: relative;
        z-index: 100;
        /* Đảm bảo menu luôn nằm trên cùng */
    }

    .btn-menu {
        text-align: left;
        border-radius: 10px;
        transition: all 0.15s ease-in-out;
        border: 1px solid transparent;
        font-size: 0.9rem;
        padding: 12px 18px;
        /* Tăng diện tích bấm */
        margin-bottom: 5px;
        cursor: pointer !important;
        display: flex;
        align-items: center;
        width: 100%;
        background: #fff;
    }

    .btn-menu:hover {
        background-color: #f0f7ff !important;
        color: var(--accent-color) !important;
        transform: scale(1.02);
    }

    .btn-menu:active {
        transform: scale(0.98);
    }

    /* Hiệu ứng lún xuống khi bấm */
    .btn-menu.active {
        background-color: var(--primary-color) !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(44, 62, 80, 0.3);
    }

    .btn-menu i {
        width: 25px;
        pointer-events: none;
    }

    /* Cards & Containers */
    .main-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        background: white;
    }

    .stat-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s;
        background: #fff;
        border-left: 4px solid var(--accent-color);
        position: relative;
        /* QUAN TRỌNG: Để nhốt stretched-link bên trong */
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    /* Tables */
    .table thead th {
        background-color: #f8f9fa;
        color: #7f8c8d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        border-top: none;
        padding: 15px;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
    }

    .table-hover tbody tr:hover {
        background-color: #fdfdfd;
    }

    /* Action Buttons in Table */
    .btn-group .btn {
        padding: 8px 12px;
        transition: all 0.2s;
    }

    .btn-group .btn:hover {
        transform: scale(1.15);
        z-index: 5;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-group .btn i {
        pointer-events: none;
    }

    /* Filters */
    #filterButtons .btn.active {
        box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
    }

    #filterButtons .btn-outline-danger.active {
        background-color: var(--danger-color) !important;
        color: #fff !important;
    }

    #filterButtons .btn-outline-warning.active {
        background-color: var(--warning-color) !important;
        color: #fff !important;
    }

    #filterButtons .btn-outline-secondary.active {
        background-color: #6c757d !important;
        color: #fff !important;
    }

    /* Inputs */
    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        padding: 10px 15px;
        font-size: 0.9rem;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15);
        border-color: var(--accent-color);
    }

    /* Animation - ĐÃ LOẠI BỎ ĐỂ TĂNG TỐC ĐỘ */
    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .btn-menu i {
        pointer-events: none;
    }

    /* Đảm bảo click vào icon vẫn là click vào nút */
    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
    /* Custom badge color for audit */
    .bg-purple { background-color: #6f42c1 !important; }
</style>

<div class="container-fluid py-4 min-vh-100">
    <div class="row g-4">
        <!-- ================= CỘT MENU TRÁI ================= -->
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
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold active" id="btn-all" onclick="switchTab('all')">
                    <i class="fas fa-boxes me-2"></i> TẤT CẢ KHO
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-transfers" onclick="switchTab('transfers')">
                    <i class="fas fa-exchange-alt me-2"></i> CHUYỂN KHO
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-distribution" onclick="switchTab('distribution')">
                    <i class="fas fa-th me-2"></i> BÁO CÁO PHÂN BỔ
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-history" onclick="switchTab('history')">
                    <i class="fas fa-history me-2"></i> LỊCH SỬ GD
                </button>
                <hr class="my-3 opacity-10">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Mở rộng</h6>
                <a href="POController.php" class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold">
                    <i class="fas fa-file-invoice-dollar me-2"></i> ĐẶT HÀNG (PO)
                </a>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-suppliers" onclick="switchTab('suppliers')">
                    <i class="fas fa-truck me-2"></i> NHÀ CUNG CẤP
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-chart" onclick="switchTab('chart')">
                    <i class="fas fa-chart-bar me-2"></i> BIỂU ĐỒ KHO
                </button>
            </div>

            <?php if ($low_stock_count > 0): ?>
                <div class="stat-card p-3 mb-3 border-start-danger" style="border-left-color: var(--danger-color);">
                    <div class="small text-danger fw-bold text-uppercase mb-1">Cảnh báo tồn kho</div>
                    <div class="h5 m-0 fw-bold"><?= $low_stock_count ?> món sắp hết</div>
                    <a href="#" onclick="switchTab('reorder');return false;" class="stretched-link"></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================= CỘT NỘI DUNG CHÍNH ================= -->
        <div class="col-lg-9 tab-content">
            <?php if ($msg === 'success'): ?>
                <div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-2"></i> Hoàn tất thao tác! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif ($msg === 'error'): ?>
                <div class="alert alert-danger alert-dismissible"><strong>Lỗi!</strong> Vui lòng thử lại. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <!-- 1. TAB TẤT CẢ KHO -->
            <div class="tab-pane active" id="tab-all">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="fw-bold m-0 text-uppercase">Tồn Kho Đa Vị Trí</h4>
                    <div class="d-flex gap-2">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Tìm kiếm..." style="width:200px" oninput="filterTable()">
                        <button class="btn btn-warning fw-bold shadow-sm" onclick="openInventoryModal()">+ Thêm Mới</button>
                    </div>
                </div>

                <div class="mb-2 mt-2 d-flex gap-2 flex-wrap" id="filterButtons">
                    <button class="btn btn-sm btn-outline-secondary px-3 fw-bold active" onclick="filterWarning('all', this)">Tất cả</button>
                    <button class="btn btn-sm btn-outline-danger px-3 fw-bold" onclick="filterWarning('low', this)">
                        <i class="fas fa-arrow-down me-1" style="pointer-events:none"></i>Tồn kho thấp
                        <?php if ($low_stock_count > 0) echo "<span class='badge bg-danger ms-1' style='pointer-events:none'>$low_stock_count</span>"; ?>
                    </button>
                    <button class="btn btn-sm btn-outline-warning px-3 fw-bold" onclick="filterWarning('expiry', this)">
                        <i class="fas fa-clock me-1" style="pointer-events:none"></i>Sắp hết HSD
                        <?php if ($expiry_warn_count > 0) echo "<span class='badge bg-warning text-dark ms-1' style='pointer-events:none'>$expiry_warn_count</span>"; ?>
                    </button>
                </div>

                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table align-middle mb-0 table-hover" id="invTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th width="250">Vị trí lưu trữ (Đa kho)</th>
                                <th>HSD</th>
                                <th>Giá BQGQ</th>
                                <th class="text-end" width="280">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="invBody">
                            <?php foreach ($inv as $i):
                                $total = $i['total_stock'];
                                $min = (float)($i['min_stock'] ?? 0);
                                $exp = $i['expiry_date'] ?? '';

                                // Logic phục vụ cho bộ lọc JS (Chỉ áp dụng cho món đang active)
                                $isLow = ($min > 0 && $total <= $min && $i['is_active'] == 1) ? 1 : 0;
                                $isExpiring = ($exp && $exp <= $warn_date && $i['is_active'] == 1) ? 1 : 0;
                            ?>
                                <tr class="inv-row <?= $i['is_active'] == 0 ? 'opacity-50 bg-light' : '' ?>"
                                    data-name="<?= strtolower(htmlspecialchars($i['item_name'])) ?>"
                                    data-low="<?= $isLow ?>"
                                    data-expiry="<?= $isExpiring ?>"
                                    data-visible="1">
                                    <td>
                                        <strong><?= htmlspecialchars($i['item_name']) ?></strong>
                                        <?php if ($i['is_active'] == 0): ?>
                                            <span class="badge bg-secondary ms-1">Đã ẩn</span>
                                        <?php endif; ?>
                                        <div class="small text-muted"><?= htmlspecialchars($i['category']) ?> | Min: <?= $min ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $has_stock = false;
                                        foreach ($warehouses as $w):
                                            $qty = $i['stocks'][$w['id']] ?? 0;
                                            if ($qty > 0): $has_stock = true;
                                                $badge_color = ($w['type'] == 'main') ? 'bg-primary' : (($w['type'] == 'kitchen') ? 'bg-danger' : 'bg-info text-dark');
                                        ?>
                                                <div class="mb-1"><span class="badge <?= $badge_color ?> me-1"><?= $w['name'] ?></span> <span class="fw-bold"><?= $qty ?></span> <?= $i['unit_name'] ?></div>
                                        <?php endif;
                                        endforeach; ?>

                                        <?php if (!$has_stock): ?>
                                            <span class="badge bg-light text-muted border">Hết hàng</span>
                                        <?php endif; ?>

                                        <div class="mt-1 small text-success fw-bold">Tổng: <?= $total ?> <?= $i['unit_name'] ?></div>
                                    </td>
                                    <td>
                                        <?php if ($exp): ?>
                                            <span class="<?= $isExpiring ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $exp ?></span>
                                            <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="text-success fw-bold small"><?= number_format($i['cost_price']) ?>đ</td>
                                    <td class="text-end">
                                        <div class="btn-group shadow-sm">
                                            <?php if ($i['is_active'] == 1): ?>
                                                <!-- Nút chức năng đầy đủ -->
                                                <button class="btn btn-sm btn-success" title="Nhập hàng" onclick="openImport(<?= $i['id'] ?>, '<?= addslashes($i['item_name']) ?>', '<?= $i['unit_name'] ?>')"><i class="fas fa-arrow-down"></i></button>
                                                <button class="btn btn-sm btn-dark" title="Chuyển kho" onclick="openTransfer(<?= $i['id'] ?>, '<?= addslashes($i['item_name']) ?>', '<?= $i['unit_name'] ?>')"><i class="fas fa-exchange-alt"></i></button>
                                                <button class="btn btn-sm btn-primary" title="Xuất dùng" onclick="openExport(<?= $i['id'] ?>, '<?= addslashes($i['item_name']) ?>', 'export')"><i class="fas fa-arrow-up"></i></button>
                                                <button class="btn btn-sm btn-danger" title="Hủy (Hư hỏng/Hết hạn)" onclick="openExport(<?= $i['id'] ?>, '<?= addslashes($i['item_name']) ?>', 'loss')"><i class="fas fa-times-circle"></i></button>
                                                <!-- Nút Ẩn -->
                                                <a href="InventoryController.php?toggle_id=<?= $i['id'] ?>" class="btn btn-sm btn-secondary" title="Ẩn/Ngưng sử dụng"><i class="fas fa-eye-slash"></i></a>
                                            <?php else: ?>
                                                <!-- Đã Ẩn -> Chỉ hiện nút khôi phục -->
                                                <a href="InventoryController.php?toggle_id=<?= $i['id'] ?>" class="btn btn-sm btn-info text-white" title="Mở lại sử dụng"><i class="fas fa-eye"></i></a>
                                            <?php endif; ?>

                                            <!-- Nút Sửa và Xóa -->
                                            <button class="btn btn-sm btn-light border" onclick='openEdit(<?= json_encode($i) ?>)'><i class="fas fa-edit"></i></button>
                                            <a href="InventoryController.php?delete_id=<?= $i['id'] ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('CẢNH BÁO: Xóa cứng sẽ làm mất lịch sử. Nên dùng tính năng ẨN. Bạn vẫn chắc chắn muốn XÓA?')" title="Xóa vĩnh viễn"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- KHU VỰC PHÂN TRANG (PAGINATION) -->
                <div class="d-flex justify-content-between align-items-center mt-3 p-2 bg-white border-top rounded shadow-sm">
                    <div class="small text-muted fw-bold" id="paginInfo"></div>
                    <div id="paginBtns" class="btn-group btn-group-sm shadow-sm"></div>
                </div>
            </div>

            <!-- 2. TAB ĐỀ XUẤT ĐẶT HÀNG -->
            <div class="tab-pane" id="tab-reorder">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-danger"><i class="fas fa-cart-plus me-2"></i>Cần Đặt Hàng</h4>
                    <a href="POController.php" class="btn btn-danger btn-sm fw-bold"><i class="fas fa-arrow-right me-1"></i>Đến trang PO</a>
                </div>
                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table table-hover mb-0">
                        <thead class="table-danger">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th>Tổng Tồn (Mọi kho)</th>
                                <th>Đề xuất mua</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reorder_list as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['item_name']) ?></strong></td>
                                    <td class="text-danger fw-bold"><?= (float)$r['total_stock'] ?> / <?= (float)$r['min_stock'] ?> <?= $r['unit_name'] ?></td>
                                    <td><span class="badge bg-success fs-6">+ <?= number_format(($r['min_stock'] - $r['total_stock']) + ($r['min_stock'] * 0.5), 1) ?> <?= $r['unit_name'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reorder_list)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-success"><i class="fas fa-check-circle fa-2x mb-2 d-block"></i>Kho hàng đang ở mức an toàn.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. TAB KIỂM KÊ -->
            <div class="tab-pane" id="tab-audit">
                <h4 class="fw-bold mb-3 text-warning"><i class="fas fa-clipboard-check me-2"></i>Kiểm kê Kho thực tế</h4>
                <form method="POST" action="InventoryController.php">
                    <input type="hidden" name="perform_audit" value="1">
                    <div class="card p-3 mb-3 border-0 shadow-sm bg-white">
                        <label class="fw-bold mb-2">1. Bạn đang đếm hàng ở kho nào? <span class="text-danger">*</span></label>
                        <select name="audit_warehouse_id" class="form-select form-select-lg mb-3" required id="auditWarehouseSelect">
                            <option value="">-- Bấm để chọn Kho kiểm kê --</option>
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= $w['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="alert alert-info small m-0"><i class="fas fa-info-circle"></i> Bảng bên dưới sẽ cập nhật số liệu của Kho bạn vừa chọn. Điền số bạn đếm được, hệ thống sẽ tự động bù/trừ để khớp với thực tế.</div>
                    </div>

                    <div class="card shadow-sm border-0 mb-3 overflow-hidden">
                        <table class="table align-middle mb-0 table-hover" id="auditTable" style="display:none;">
                            <thead class="table-warning">
                                <tr>
                                    <th>Nguyên Liệu</th>
                                    <th>Hệ thống đang ghi nhận</th>
                                    <th width="220">Thực tế đếm được</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inv as $i):
                                    $stocks_json = htmlspecialchars(json_encode($i['stocks']), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr class="audit-row" data-stocks="<?= $stocks_json ?>">
                                        <td><strong><?= htmlspecialchars($i['item_name']) ?></strong></td>
                                        <td><span class="system-qty fw-bold text-danger">0</span> <?= $i['unit_name'] ?></td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="actual_qty[<?= $i['id'] ?>]" step="0.01" min="0" class="form-control text-center physical-input" placeholder="Nhập số...">
                                                <span class="input-group-text"><?= $i['unit_name'] ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-3 fs-5 shadow-sm" onclick="return confirm('Chốt số liệu kiểm kê cho kho này?')">CHỐT KIỂM KÊ</button>
                </form>
            </div>

            <!-- 4. TAB CHUYỂN KHO PHÊ DUYỆT -->
            <div class="tab-pane" id="tab-transfers">
                <h4 class="fw-bold mb-3 text-secondary"><i class="fas fa-exchange-alt me-2"></i>Quản Lý Lệnh Chuyển Kho</h4>
                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>Mã / Ngày</th>
                                <th>Lộ trình (Từ -> Đến)</th>
                                <th>Chi tiết hàng hóa</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transfers as $t):
                                $status_badge = 'bg-warning text-dark';
                                $status_text = 'Đang chờ';
                                if ($t['status'] === 'completed') {
                                    $status_badge = 'bg-success';
                                    $status_text = 'Đã hoàn tất';
                                } elseif ($t['status'] === 'cancelled') {
                                    $status_badge = 'bg-danger';
                                    $status_text = 'Đã hủy';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold">#<?= $t['id'] ?></div>
                                        <small class="text-muted"><?= $t['transfer_date'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $t['from_warehouse_name'] ?></span>
                                        <i class="fas fa-long-arrow-alt-right mx-1"></i>
                                        <span class="badge bg-danger"><?= $t['to_warehouse_name'] ?></span>
                                    </td>
                                    <td class="small text-truncate" style="max-width: 250px;">
                                        <?= htmlspecialchars($t['items_summary']) ?>
                                    </td>
                                    <td><span class="badge <?= $status_badge ?>"><?= $status_text ?></span></td>
                                    <td class="text-end">
                                        <?php if ($t['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success fw-bold" onclick="approveTransfer(<?= $t['id'] ?>)">Duyệt & Xuất</button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="cancelTransfer(<?= $t['id'] ?>)">Hủy</button>
                                        <?php else: ?>
                                            <small class="text-muted">Bởi: <?= $t['approved_by'] ?: $t['performed_by'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. TAB NHÀ CUNG CẤP -->
            <div class="tab-pane" id="tab-suppliers">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-uppercase text-primary">Danh Sách Nhà Cung Cấp</h4>
                    <button class="btn btn-dark fw-bold" onclick="openSupplierModal()">+ Thêm NCC Mới</button>
                </div>
                <div class="row g-3">
                    <?php foreach ($suppliers as $s): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0 p-3 h-100">
                                <div class="d-flex justify-content-between">
                                    <h6 class="fw-bold text-dark m-0"><i class="fas fa-building text-primary me-2"></i><?= htmlspecialchars($s['name']) ?></h6>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-light border-0" onclick='openEditSupplier(<?= json_encode($s) ?>)'><i class="fas fa-edit text-primary"></i></button>
                                        <a href="InventoryController.php?delete_supplier=<?= $s['id'] ?>" class="btn btn-sm btn-light border-0 text-danger" onclick="return confirm('Xóa nhà cung cấp này?')"><i class="fas fa-trash text-danger"></i></a>
                                    </div>
                                </div>
                                <hr class="my-2 opacity-25">
                                <div class="small"><b>Người liên hệ:</b> <?= htmlspecialchars($s['contact_person'] ?? 'Chưa cập nhật') ?></div>
                                <div class="small text-muted"><i class="fas fa-phone-alt me-2"></i><?= htmlspecialchars($s['phone'] ?? '---') ?></div>
                                <div class="small text-muted"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($s['email'] ?? '---') ?></div>
                                <div class="small text-muted text-truncate"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($s['address'] ?? '---') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane" id="tab-history">
                <h4 class="fw-bold mb-3 text-info">Lịch Sử Giao Dịch</h4>
                <table class="table table-sm table-hover bg-white shadow-sm rounded">
                    <thead class="table-secondary">
                        <tr>
                            <th>Thời gian</th>
                            <th>Nguyên Liệu</th>
                            <th>Kho</th>
                            <th>Loại</th>
                            <th>Số lượng</th>
                            <th>Người tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= $h['created_at'] ?></td>
                                <td><strong><?= htmlspecialchars($h['item_name']) ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($h['warehouse_name'] ?? 'Kho Tổng') ?></span></td>
                                <td><?php
                                    $type_map = [
                                        'import'            => ['bg-success',          'fas fa-arrow-down',    'Nhập kho'],
                                        'export'            => ['bg-primary',          'fas fa-arrow-up',      'Xuất dùng'],
                                        'loss'              => ['bg-danger',           'fas fa-times-circle',  'Hủy/Hao hụt'],
                                        'audit_adjust_up'   => ['bg-purple text-white','fas fa-plus-circle',   'Kiểm kê (+)'],
                                        'audit_adjust_down' => ['bg-warning text-dark','fas fa-minus-circle',  'Kiểm kê (-)'],
                                    ];
                                    $type_key = $h['type'];
                                    [$bg, $icon, $label] = $type_map[$type_key] ?? ['bg-secondary', 'fas fa-circle', $type_key];
                                    echo "<span class=\"badge $bg\"><i class=\"$icon me-1\" style=\"pointer-events:none\"></i>$label</span>";
                                ?></td>
                                <td><?= (float)$h['quantity'] ?> <?= $h['unit_name'] ?></td>
                                <td><small><?= htmlspecialchars($h['performed_by']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 7. TAB BÁO CÁO PHÂN BỔ (MATRIX) -->
            <div class="tab-pane" id="tab-distribution">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-primary text-uppercase">Báo Cáo Giá Trị & Phân Bổ Tồn Kho</h4>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i> In báo cáo</button>
                </div>

                <!-- TÓM TẮT GIÁ TRỊ TỪNG KHO -->
                <div class="row g-3 mb-4">
                    <?php
                    $grand_total_value = 0;
                    foreach ($warehouse_values as $wv):
                        $grand_total_value += $wv['total_value'];
                    ?>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                                <div class="small text-muted text-uppercase fw-bold mb-1"><?= $wv['name'] ?></div>
                                <div class="h4 fw-bold text-dark mb-0"><?= number_format($wv['total_value'], 0) ?>đ</div>
                                <div class="small text-success"><i class="fas fa-box me-1"></i> <?= $wv['item_count'] ?> mặt hàng</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-primary text-white p-3 h-100">
                            <div class="small text-uppercase fw-bold mb-1">Tổng Tài Sản Kho</div>
                            <div class="h4 fw-bold mb-0"><?= number_format($grand_total_value, 0) ?>đ</div>
                            <div class="small opacity-75">Toàn bộ hệ thống</div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th rowspan="2" class="align-middle">Nguyên Liệu</th>
                                    <th rowspan="2" class="align-middle">Đơn Vị</th>
                                    <th colspan="<?= count($warehouses) ?>">Số lượng tại từng kho</th>
                                    <th rowspan="2" class="align-middle bg-dark text-white">Tổng Tồn</th>
                                </tr>
                                <tr>
                                    <?php foreach ($warehouses as $w): ?>
                                        <th class="small"><?= $w['name'] ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inv as $i): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($i['item_name']) ?></td>
                                        <td class="text-center small"><?= $i['unit_name'] ?></td>
                                        <?php foreach ($warehouses as $w):
                                            $q = $i['stocks'][$w['id']] ?? 0;
                                        ?>
                                            <td class="text-center <?= $q > 0 ? 'fw-bold' : 'text-muted opacity-50' ?>">
                                                <?= $q > 0 ? number_format($q, 2) : '-' ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="text-center fw-bold bg-light">
                                            <?= number_format($i['total_stock'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-3 alert alert-light border small">
                    <i class="fas fa-info-circle me-1"></i> Báo cáo này giúp bạn đối chiếu nhanh số lượng hàng hóa đang nằm tại các vị trí khác nhau để kịp thời điều phối.
                </div>
            </div>

            <!-- 6. TAB BIỂU ĐỒ & THỐNG KÊ -->
            <div class="tab-pane" id="tab-chart">
                <h4 class="fw-bold mb-3 text-uppercase text-success">Biểu Đồ Kho 6 Tháng Gần Nhất</h4>
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <canvas id="inventoryChart" height="120"></canvas>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 text-center">
                            <div class="small text-muted">Tổng nhập</div>
                            <div class="fs-4 fw-bold text-success"><?= number_format($stats['ti'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 text-center">
                            <div class="small text-muted">Tổng xuất</div>
                            <div class="fs-4 fw-bold text-primary"><?= number_format($stats['te'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 text-center">
                            <div class="small text-muted">Tổng hủy</div>
                            <div class="fs-4 fw-bold text-danger"><?= number_format($stats['tl'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ================= CÁC FORM MODAL ẨN ================= -->

<!-- Modal Nguyên liệu -->
<div class="modal fade" id="modalInventory" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content border-0 shadow" method="POST" action="InventoryController.php">
            <input type="hidden" name="save_inventory" value="1">
            <input type="hidden" name="item_id" id="inv-id">
            <div class="modal-header bg-warning">
                <h5 id="inv-modal-title">Nguyên Liệu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="small fw-bold">Tên nguyên liệu</label><input type="text" name="item_name" id="inv-name" class="form-control" required></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-bold">Danh mục</label><select name="category" id="inv-cat" class="form-select"><?php foreach ($cats as $c) echo "<option value='{$c['name']}'>{$c['name']}</option>"; ?></select></div>
                    <div class="col-6"><label class="small fw-bold">Đơn vị</label><select name="unit_name" id="inv-unit" class="form-select"><?php foreach ($units as $u) echo "<option value='{$u['name']}'>{$u['name']}</option>"; ?></select></div>
                </div>
                <div class="row g-2 mb-0">
                    <div class="col-6"><label class="small fw-bold">Giá vốn (đ)</label><input type="text" name="cost_price" id="inv-price" class="form-control money-input" value="0"></div>
                    <div class="col-6"><label class="small fw-bold">Tồn tối thiểu</label><input type="number" name="min_stock" id="inv-min" class="form-control" value="0" min="0" step="0.01"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100 fw-bold">LƯU</button></div>
        </form>
    </div>
</div>

<!-- Modal Nhập Hàng -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-import" action="InventoryController.php">
            <input type="hidden" name="action" value="import">
            <input type="hidden" name="item_id" id="imp-id">
            <div class="modal-header bg-success text-white">
                <h5>Nhập: <span id="imp-name"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-success small p-2"><i class="fas fa-info-circle"></i> Hàng hóa sẽ được nhập vào <b>Kho Tổng</b>.</div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="fw-bold small">Số lượng (<span id="imp-unit"></span>)</label><input type="number" name="quantity" step="0.01" min="0.01" class="form-control" required></div>
                    <div class="col-6"><label class="fw-bold small">Giá vốn nhập (đ)</label><input type="text" name="import_price" class="form-control money-input" required></div>
                </div>
                <div class="mb-0"><label class="fw-bold small">Hạn sử dụng</label><input type="date" name="expiry_date" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success w-100 fw-bold">XÁC NHẬN</button></div>
        </form>
    </div>
</div>

<!-- Modal Xuất/Hủy (Động Header theo Loại) -->
<div class="modal fade" id="modalExport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-export" action="InventoryController.php">
            <input type="hidden" name="action" id="exp-action">
            <input type="hidden" name="item_id" id="exp-id">
            <div class="modal-header text-white" id="modalExportHeader">
                <h5>Xử lý: <span id="exp-name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <label class="fw-bold mb-2 text-start w-100">Kho thực hiện xuất/hủy:</label>
                <select name="warehouse_id" class="form-select mb-3" required>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= $w['name'] ?></option><?php endforeach; ?>
                </select>
                <label class="fw-bold mb-2 text-start w-100">Số lượng:</label>
                <input type="number" name="quantity" step="0.01" min="0.01" class="form-control form-control-lg text-center" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn w-100 fw-bold text-white" id="modalExportSubmitBtn">XÁC NHẬN</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Chuyển Kho -->
<div class="modal fade" id="modalTransfer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-transfer" action="InventoryController.php">
            <input type="hidden" name="action" value="transfer">
            <input type="hidden" name="item_id" id="trans-id">
            <div class="modal-header bg-dark text-white">
                <h5>Chuyển Kho: <span id="trans-name" class="text-warning"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Từ Kho (Xuất)</label>
                        <select name="from_warehouse_id" class="form-select" required>
                            <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= $w['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Đến Kho (Nhận)</label>
                        <select name="to_warehouse_id" class="form-select" required>
                            <?php foreach (array_reverse($warehouses) as $w): ?><option value="<?= $w['id'] ?>"><?= $w['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div><label class="small fw-bold">Số lượng chuyển (<span id="trans-unit"></span>)</label><input type="number" name="quantity" step="0.01" min="0.01" class="form-control form-control-lg text-center" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-dark w-100 fw-bold text-warning">THỰC HIỆN CHUYỂN</button></div>
        </form>
    </div>
</div>

<!-- Modal Nhà Cung Cấp -->
<div class="modal fade" id="modalSupplier" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="InventoryController.php">
            <input type="hidden" name="save_supplier" value="1">
            <input type="hidden" name="supplier_id" id="s-id">
            <div class="modal-header bg-dark text-white">
                <h5>Nhà Cung Cấp</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="small fw-bold">Tên NCC/Công ty <span class="text-danger">*</span></label><input type="text" name="s_name" id="s-name" class="form-control" required></div>
                <div class="mb-3"><label class="small fw-bold">Người đại diện</label><input type="text" name="s_contact" id="s-contact" class="form-control"></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-bold">SĐT</label><input type="text" name="s_phone" id="s-phone" class="form-control"></div>
                    <div class="col-6"><label class="small fw-bold">Email</label><input type="email" name="s_email" id="s-email" class="form-control"></div>
                </div>
                <div class="mb-0"><label class="small fw-bold">Địa chỉ</label><textarea name="s_address" id="s-address" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary w-100 fw-bold">LƯU NCC</button></div>
        </form>
    </div>
</div>

<!-- Modal Quản lý Tags (Danh mục / Đơn vị) -->
<div class="modal fade" id="modalTags" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-secondary text-white">
                <h5 id="tagTitle">Quản Lý</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="InventoryController.php" class="d-flex gap-2 mb-3">
                    <input type="hidden" name="manage_tag" value="1">
                    <input type="hidden" name="tag_type" id="tagTypeInput">
                    <input type="hidden" name="tag_action" value="add">
                    <input type="text" name="tag_name" class="form-control form-control-sm" placeholder="Tên mới..." required>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Thêm</button>
                </form>
                <div id="tagList" class="list-group list-group-flush border-top"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sửa Tag -->
<div class="modal fade" id="modalEditTag" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="InventoryController.php">
            <input type="hidden" name="manage_tag" value="1">
            <input type="hidden" name="tag_action" value="edit">
            <input type="hidden" name="tag_type" id="editTagType">
            <input type="hidden" name="tag_id" id="editTagId">
            <div class="modal-header">
                <h6 class="m-0">Sửa tên</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3"><input type="text" name="tag_name" id="editTagName" class="form-control" required></div>
            <div class="modal-footer p-2"><button type="submit" class="btn btn-primary btn-sm w-100">Lưu</button></div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // ================= BIẾN TOÀN CỤC =================
    const categories = <?= json_encode($cats) ?>;
    const units = <?= json_encode($units) ?>;
    const chartRaw = <?= json_encode($chart_raw) ?>;
    const PAGE_SIZE = 15;
    let currentPage = 1;
    let activeFilter = 'all';

    $(document).ready(function() {
        // Tự động chuyển tab nếu có tham số ?tab= trên URL
        const urlParams = new URLSearchParams(window.location.search);
        const targetTab = urlParams.get('tab');
        if (targetTab) {
            switchTab(targetTab);
        }
    });

    // ================= HÀM CHUYỂN TAB =================
    function switchTab(tabId) {
        // 1. Chuyển tab ngay lập tức
        $('.tab-pane').removeClass('active');
        $('#tab-' + tabId).addClass('active');

        // 2. Cập nhật nút Menu
        $('.btn-menu').removeClass('active');
        $('#btn-' + tabId).addClass('active');

        // 3. Cập nhật URL
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.pushState({}, '', url);

        if (tabId === 'chart') renderChart();
    }

    // ================= HÀM MỞ MODALS =================
    function openInventoryModal() {
        $('#inv-id, #inv-name').val('');
        new bootstrap.Modal(document.getElementById('modalInventory')).show();
    }

    function openEdit(data) {
        $('#inv-id').val(data.id);
        $('#inv-name').val(data.item_name);
        $('#inv-cat').val(data.category);
        $('#inv-unit').val(data.unit_name);
        $('#inv-price').val(data.cost_price ? parseInt(data.cost_price).toLocaleString('en-US') : '0');
        $('#inv-min').val(data.min_stock || 0);
        new bootstrap.Modal(document.getElementById('modalInventory')).show();
    }

    function openSupplierModal() {
        $('#s-id, #s-name, #s-contact, #s-phone, #s-email, #s-address').val('');
        new bootstrap.Modal(document.getElementById('modalSupplier')).show();
    }

    function openEditSupplier(data) {
        $('#s-id').val(data.id);
        $('#s-name').val(data.name);
        $('#s-contact').val(data.contact_person);
        $('#s-phone').val(data.phone);
        $('#s-email').val(data.email);
        $('#s-address').val(data.address);
        new bootstrap.Modal(document.getElementById('modalSupplier')).show();
    }

    function openImport(id, name, unit) {
        $('#form-import')[0].reset();
        $('#imp-id').val(id);
        $('#imp-name').text(name);
        $('#imp-unit').text(unit);
        new bootstrap.Modal(document.getElementById('modalImport')).show();
    }

    function openTransfer(id, name, unit) {
        $('#form-transfer')[0].reset();
        $('#trans-id').val(id);
        $('#trans-name').text(name);
        $('#trans-unit').text(unit);
        new bootstrap.Modal(document.getElementById('modalTransfer')).show();
    }

    // Xử lý động đổi màu Modal Xuất/Hủy
    function openExport(id, name, type) {
        $('#form-export')[0].reset();
        $('#exp-id').val(id);
        $('#exp-action').val(type);
        $('#exp-name').text(name);

        if (type === 'loss') {
            $('#modalExportHeader').removeClass('bg-primary').addClass('bg-danger');
            $('#modalExportSubmitBtn').removeClass('btn-primary').addClass('btn-danger').text('XÁC NHẬN HỦY');
        } else {
            $('#modalExportHeader').removeClass('bg-danger').addClass('bg-primary');
            $('#modalExportSubmitBtn').removeClass('btn-danger').addClass('btn-primary').text('XÁC NHẬN XUẤT');
        }
        new bootstrap.Modal(document.getElementById('modalExport')).show();
    }

    function openTagManager(type) {
        const data = (type === 'category') ? categories : units;
        $('#tagTitle').text(type === 'category' ? 'Quản lý Danh mục' : 'Quản lý Đơn vị');
        $('#tagTypeInput').val(type);
        let html = '';
        data.forEach(i => {
            html += `<div class="list-group-item d-flex justify-content-between align-items-center py-2"><span>${i.name}</span><div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-primary" onclick="openEditTag(${i.id},'${i.name.replace(/'/g,"\\'")}','${type}')"><i class="fas fa-edit"></i></button><form method="POST" action="InventoryController.php" style="display:inline"><input type="hidden" name="manage_tag" value="1"><input type="hidden" name="tag_type" value="${type}"><input type="hidden" name="tag_action" value="delete"><input type="hidden" name="tag_id" value="${i.id}"><button type="submit" class="btn btn-outline-danger" onclick="return confirm('Xóa \\"${i.name}\\"?')"><i class="fas fa-trash"></i></button></form></div></div>`;
        });
        $('#tagList').html(html || '<div class="p-2 text-muted small">Chưa có dữ liệu</div>');
        new bootstrap.Modal(document.getElementById('modalTags')).show();
    }

    function openEditTag(id, oldName, type) {
        $('#editTagId').val(id);
        $('#editTagType').val(type);
        $('#editTagName').val(oldName);
        new bootstrap.Modal(document.getElementById('modalEditTag')).show();
    }

    // ================= GIAO DỊCH AJAX =================
    // ================= GIAO DỊCH AJAX =================

    // 1. TỰ ĐỘNG XÓA DẤU PHẨY TRƯỚC KHI LƯU DB (Của TẤT CẢ các form)
    $(document).on('submit', 'form', function() {
        $(this).find('.money-input').each(function() {
            this.value = this.value.replace(/,/g, '');
        });
    });

    // 2. Xử lý gửi AJAX cho các form Nhập, Xuất, Chuyển kho
    $(document).on('submit', '#form-import, #form-export, #form-transfer', function(e) {
        e.preventDefault();
        // (Đã xóa lệnh lột phẩy ở đây vì hàm số 1 ở trên đã lo rồi)
        const btn = $(this).find('[type=submit]').prop('disabled', true).text('Đang xử lý...');
        $.post('InventoryController.php', $(this).serialize(), function(r) {
            if (r.status === 'success') location.reload();
            else {
                alert('❌ ' + (r.msg || 'Lỗi'));
                btn.prop('disabled', false).text('XÁC NHẬN');
            }
        }, 'json').fail(function() {
            alert('Lỗi kết nối máy chủ.');
            btn.prop('disabled', false);
        });
    });

    // 3. Tự động thêm dấu phẩy khi gõ
    $(document).on('input', '.money-input', function() {
        let val = this.value.replace(/[^0-9]/g, '');
        this.value = val !== '' ? parseInt(val, 10).toLocaleString('en-US') : '';
    });
    // ================= LỌC & PHÂN TRANG =================
    function filterWarning(type, btn) {
        activeFilter = type;
        $('.btn-outline-secondary, .btn-outline-danger, .btn-outline-warning').removeClass('active');
        $(btn).addClass('active');
        filterTable();
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#invBody .inv-row').forEach(r => {
            const nameMatch = r.dataset.name.includes(q);
            const filterMatch = (activeFilter === 'all') ? true : (activeFilter === 'low' ? r.dataset.low === '1' : r.dataset.expiry === '1');

            // Gán flag thay vì ẩn cứng để Phân trang đọc được
            r.setAttribute('data-visible', (nameMatch && filterMatch) ? '1' : '0');
        });
        currentPage = 1;
        renderPagination();
    }

    function renderPagination() {
        const allRows = document.querySelectorAll('#invBody .inv-row');
        allRows.forEach(r => r.style.display = 'none'); // Ẩn hết toàn bộ

        const visibleRows = [...allRows].filter(r => r.getAttribute('data-visible') === '1');
        const t = visibleRows.length;
        const pgs = Math.ceil(t / PAGE_SIZE) || 1;
        currentPage = Math.min(currentPage, pgs);

        visibleRows.forEach((r, i) => {
            if (i >= (currentPage - 1) * PAGE_SIZE && i < currentPage * PAGE_SIZE) r.style.display = '';
        });

        document.getElementById('paginInfo').textContent = t > 0 ? `Hiển thị ${(currentPage-1)*PAGE_SIZE+1} – ${Math.min(currentPage*PAGE_SIZE, t)} / Tổng ${t}` : 'Không tìm thấy kết quả';

        let html = `<button class="btn btn-outline-secondary" onclick="goPage(${currentPage-1})" ${currentPage<=1?'disabled':''}>‹</button>`;
        for (let p = 1; p <= pgs; p++) {
            if (pgs <= 7 || Math.abs(p - currentPage) <= 1 || p === 1 || p === pgs) {
                html += `<button class="btn ${p===currentPage?'btn-primary':'btn-outline-secondary'}" onclick="goPage(${p})">${p}</button>`;
            } else if (Math.abs(p - currentPage) === 2) {
                html += `<button class="btn btn-outline-secondary" disabled>…</button>`;
            }
        }
        html += `<button class="btn btn-outline-secondary" onclick="goPage(${currentPage+1})" ${currentPage>=pgs?'disabled':''}>›</button>`;
        document.getElementById('paginBtns').innerHTML = html;
    }

    function goPage(p) {
        currentPage = p;
        renderPagination();
    }

    // ================= LOGIC TAB KIỂM KÊ =================
    $('#auditWarehouseSelect').change(function() {
        let w_id = $(this).val();
        if (w_id === "") {
            $('#auditTable').hide();
            return;
        }
        $('#auditTable').show();
        $('.audit-row').each(function() {
            let stocks = $(this).data('stocks');
            let sys_qty = stocks[w_id] !== undefined ? stocks[w_id] : 0;
            $(this).find('.system-qty').text(sys_qty);
            $(this).find('.physical-input').val(sys_qty);
        });
    });

    // ================= BIỂU ĐỒ =================
    let chartInstance = null;

    function renderChart() {
        if (chartInstance || !chartRaw || chartRaw.length === 0) return;
        chartInstance = new Chart(document.getElementById('inventoryChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartRaw.map(d => d.mo),
                datasets: [{
                        label: 'Nhập kho',
                        data: chartRaw.map(d => parseFloat(d.ti)),
                        backgroundColor: 'rgba(25,135,84,.7)'
                    },
                    {
                        label: 'Xuất kho',
                        data: chartRaw.map(d => parseFloat(d.te)),
                        backgroundColor: 'rgba(13,110,253,.7)'
                    },
                    {
                        label: 'Hủy hàng',
                        data: chartRaw.map(d => parseFloat(d.tl)),
                        backgroundColor: 'rgba(220,53,69,.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // ================= DUYỆT / HỦY CHUYỂN KHO =================
    function approveTransfer(id) {
        if (!confirm('Xác nhận phê duyệt và thực hiện trừ/cộng kho cho lệnh này?')) return;
        $.post('InventoryController.php', {
            action: 'approve_transfer',
            transfer_id: id
        }, function(r) {
            if (r.status === 'success') location.reload();
            else alert('❌ ' + (r.msg || 'Lỗi không xác định'));
        }, 'json');
    }

    function cancelTransfer(id) {
        if (!confirm('Bạn có chắc chắn muốn hủy yêu cầu chuyển kho này?')) return;
        $.post('InventoryController.php', {
            action: 'cancel_transfer',
            transfer_id: id
        }, function(r) {
            if (r.status === 'success') location.reload();
            else alert('❌ ' + (r.msg || 'Lỗi không xác định'));
        }, 'json');
    }

    // ================= KHỞI CHẠY =================
    $(function() {
        const tab = new URLSearchParams(window.location.search).get('tab');
        if (tab) switchTab(tab);

        // Kích hoạt phân trang lần đầu
        document.querySelectorAll('#invBody .inv-row').forEach(r => r.setAttribute('data-visible', '1'));
        renderPagination();
    });
</script>
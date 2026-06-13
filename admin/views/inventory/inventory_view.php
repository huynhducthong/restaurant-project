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
    /* Sidebar & Navigation */
    .sidebar-card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        position: relative;
        z-index: 100;
    }

    .btn-menu {
        text-align: left;
        border-radius: 10px;
        transition: all 0.15s ease-in-out;
        border: 1px solid transparent;
        font-size: 0.9rem;
        padding: 12px 18px;
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
    }

    .btn-menu.active {
        background-color: var(--primary-color) !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(44, 62, 80, 0.3);
    }

    .btn-menu i {
        width: 25px;
        pointer-events: none;
    }

    .stat-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s;
        background: #fff;
        border-left: 4px solid var(--accent-color);
        position: relative;
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
                    <?php if ($pending_transfers_count > 0): ?>
                        <span class="badge-notify ms-auto"><?= $pending_transfers_count ?></span>
                    <?php endif; ?>
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-distribution" onclick="switchTab('distribution')">
                    <i class="fas fa-th me-2"></i> BÁO CÁO PHÂN BỔ
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-history" onclick="switchTab('history')">
                    <i class="fas fa-history me-2"></i> LỊCH SỬ GD
                </button>
                <hr class="my-3 opacity-10">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Mở rộng</h6>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-po" onclick="switchTab('po')">
                    <i class="fas fa-file-invoice-dollar me-2"></i> ĐẶT HÀNG (PO)
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-suppliers" onclick="switchTab('suppliers')">
                    <i class="fas fa-truck me-2"></i> NHÀ CUNG CẤP
                </button>
                <button class="btn btn-menu btn-light w-100 mb-2 py-2 fw-bold" id="btn-chart" onclick="switchTab('chart')">
                    <i class="fas fa-chart-bar me-2"></i> BIỂU ĐỒ KHO
                </button>
                <hr class="my-3 opacity-10">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Cài đặt danh mục</h6>
                <button class="btn btn-menu btn-outline-secondary w-100 mb-2 py-2 fw-bold" onclick="openTagManager('category')">
                    <i class="fas fa-tags me-2"></i> DANH MỤC
                </button>
                <button class="btn btn-menu btn-outline-secondary w-100 mb-2 py-2 fw-bold" onclick="openTagManager('unit')">
                    <i class="fas fa-ruler me-2"></i> ĐƠN VỊ TÍNH
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
                        <select id="categoryFilter" class="form-select form-select-sm" style="width:150px" onchange="filterTable()">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach($inv_categories as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Tìm kiếm..." style="width:200px" oninput="filterTable()">
                        <button class="btn btn-success fw-bold shadow-sm" onclick="exportFilteredExcel()"><i class="fas fa-file-excel me-1"></i> Xuất Excel</button>
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
                    <button class="btn btn-sm btn-outline-danger px-3 fw-bold" style="background-color: #ffebee;" onclick="filterWarning('expired', this)">
                        <i class="fas fa-times-circle me-1" style="pointer-events:none"></i>Đã hết HSD
                        <?php if ($expired_count > 0) echo "<span class='badge bg-danger ms-1' style='pointer-events:none'>$expired_count</span>"; ?>
                    </button>
                </div>

                <!-- BỘ LỌC THEO KHO -->
                <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
                    <span class="small text-muted fw-bold text-uppercase" style="font-size:11px">Lọc theo kho:</span>
                    <button class="btn btn-sm btn-dark px-3 fw-bold wh-filter-btn active" data-wh="all">
                        <i class="fas fa-globe me-1" style="pointer-events:none"></i>Tất cả kho
                    </button>
                    <?php foreach ($warehouses as $w):
                        $wh_colors = [
                            'main' => 'btn-primary', 
                            'kitchen' => 'btn-danger', 
                            'bar' => 'btn-info text-dark',
                            'cold' => 'btn-primary', 
                            'supplies' => 'btn-warning text-dark',
                            'virtual' => 'btn-secondary'
                        ];
                        $wh_icons  = [
                            'main' => 'fa-warehouse', 'kitchen' => 'fa-fire-burner', 'bar' => 'fa-glass-martini-alt',
                            'cold' => 'fa-snowflake', 'supplies' => 'fa-tools', 'virtual' => 'fa-file-invoice'
                        ];
                        $wh_color  = $wh_colors[$w['type'] ?? ''] ?? 'btn-outline-secondary';
                        $wh_icon   = $wh_icons[$w['type']  ?? ''] ?? 'fa-box';
                    ?>
                    <button class="btn btn-sm btn-outline-secondary px-3 fw-bold wh-filter-btn"
                            data-wh="<?= $w['id'] ?>"
                            data-wh-type="<?= $w['type'] ?? '' ?>"
                            style="min-width: 120px;">
                        <i class="fas <?= $wh_icon ?> me-1" style="pointer-events:none"></i><?= htmlspecialchars($w['name'] ?: 'Kho ' . $w['id']) ?>
                        <span class="badge bg-white text-dark ms-1" style="font-size:10px;pointer-events:none">
                            <?php
                            $count_in_wh = 0;
                            foreach ($inv as $chk_item) {
                                if (($chk_item['stocks'][$w['id']] ?? 0) > 0) $count_in_wh++;
                            }
                            echo $count_in_wh;
                            ?>
                        </span>
                    </button>
                    <?php endforeach; ?>
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
                                $isExpired = ($exp && $exp < $today && $i['is_active'] == 1) ? 1 : 0;
                                $isExpiring = ($exp && $exp >= $today && $exp <= $warn_date && $i['is_active'] == 1) ? 1 : 0;
                            ?>
                                <?php
                                // Tạo danh sách kho có hàng của dòng này
                                $wh_with_stock = [];
                                foreach ($warehouses as $wh_chk) {
                                    if (($i['stocks'][$wh_chk['id']] ?? 0) > 0) {
                                        $wh_with_stock[] = $wh_chk['id'];
                                    }
                                }
                                ?>
                                <tr class="inv-row <?= $i['is_active'] == 0 ? 'opacity-50 bg-light' : '' ?>"
                                    data-name="<?= strtolower(htmlspecialchars($i['item_name'])) ?>"
                                    data-low="<?= $isLow ?>"
                                    data-expiry="<?= $isExpiring ?>"
                                    data-expired="<?= $isExpired ?>"
                                    data-category="<?= htmlspecialchars($i['category'] ?? '') ?>"
                                    data-wh-stock='<?= json_encode($wh_with_stock) ?>'
                                    data-stocks='<?= json_encode($i['stocks']) ?>'
                                    data-visible="1">
                                    <td>
                                        <strong><?= htmlspecialchars($i['item_name']) ?></strong>
                                        <?php if ($i['is_active'] == 0): ?>
                                            <span class="badge bg-secondary ms-1">Đã ẩn</span>
                                        <?php endif; ?>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars($i['category']) ?> | Min: <?= $min ?>
                                            <?php if (!empty($i['storage_temperature'])): ?>
                                                | <span class="text-info fw-bold"><i class="fas fa-thermometer-half"></i> <?= htmlspecialchars($i['storage_temperature']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $has_stock = false;
                                        foreach ($warehouses as $w):
                                            if ($w['type'] == 'virtual') continue; // Ẩn kho ảo
                                            $qty = $i['stocks'][$w['id']] ?? 0;
                                            if ($qty > 0): $has_stock = true;
                                                $badge_color = ($w['type'] == 'main') ? 'bg-primary' : (($w['type'] == 'kitchen') ? 'bg-danger' : 'bg-info text-dark');
                                        ?>
                                                <div class="mb-1 wh-badge" data-wh-id="<?= $w['id'] ?>"><span class="badge <?= $badge_color ?> me-1"><?= $w['name'] ?></span> <span class="fw-bold"><?= $qty ?></span> <?= $i['unit_name'] ?></div>
                                        <?php endif;
                                        endforeach; ?>

                                        <?php if (!$has_stock): ?>
                                            <span class="text-muted small wh-empty">Chưa có hàng</span>
                                        <?php endif; ?>

                                        <div class="text-success fw-bold small mt-2 wh-total">Tổng: <?= $total ?> <?= $i['unit_name'] ?></div>

                                        <?php 
                                        // Hiển thị chi tiết khối lượng lô sắp hết hạn
                                        if (!empty($i['exp_details'])): 
                                            foreach ($i['exp_details'] as $ed):
                                                $wh_name = 'Không rõ kho';
                                                foreach ($warehouses as $w) { if ($w['id'] == $ed['warehouse_id']) { $wh_name = $w['name']; break; } }
                                        ?>
                                            <div class="mt-1 px-2 py-1 bg-warning text-dark rounded small" style="font-size:0.8rem; border-left: 3px solid #ff9800;">
                                                <i class="fas fa-exclamation-triangle"></i> Sắp hết hạn: <b><?= (float)$ed['qty'] ?></b> <?= $i['unit_name'] ?> tại <i><?= $wh_name ?></i>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif; 

                                        // Hiển thị chi tiết khối lượng lô đã hết hạn
                                        if (!empty($i['expired_details'])): 
                                            foreach ($i['expired_details'] as $ed):
                                                $wh_name = 'Không rõ kho';
                                                foreach ($warehouses as $w) { if ($w['id'] == $ed['warehouse_id']) { $wh_name = $w['name']; break; } }
                                        ?>
                                            <div class="mt-1 px-2 py-1 bg-danger text-white rounded small" style="font-size:0.8rem; border-left: 3px solid #dc3545;">
                                                <i class="fas fa-times-circle"></i> Đã hết hạn: <b><?= (float)$ed['qty'] ?></b> <?= $i['unit_name'] ?> tại <i><?= $wh_name ?></i>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($exp): ?>
                                            <?php if ($isExpired): ?>
                                                <span class="text-danger fw-bold text-decoration-underline"><?= $exp ?></span>
                                                <div class="badge bg-danger mt-1">Đã hết hạn</div>
                                            <?php elseif ($isExpiring): ?>
                                                <span class="text-warning fw-bold"><?= $exp ?></span>
                                            <?php else: ?>
                                                <span class="text-muted"><?= $exp ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="text-success fw-bold small"><?= number_format($i['cost_price']) ?>đ</td>
                                    <td class="text-end">
                                        <div class="btn-group shadow-sm">
                                            <button class="btn btn-sm btn-outline-info border-0" onclick='viewBatches(<?= $i['id'] ?>, <?= json_encode($i['item_name']) ?>)' title="Xem chi tiết các lô hàng (HSD)">
                                                <i class="fas fa-layer-group"></i>
                                            </button>
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
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm fw-bold shadow-sm" onclick="switchTab('po'); setTimeout(() => { const m = new bootstrap.Modal(document.getElementById('modalCreatePO')); m.show(); document.getElementById('modalCreatePO').addEventListener('shown.bs.modal', function () { loadSuggestions({target: document.getElementById('modalCreatePO')}); }, { once: true }); }, 300);"><i class="fas fa-magic me-1"></i>Tạo PO tự động (Tất cả)</button>
                        <button class="btn btn-danger btn-sm fw-bold shadow-sm" onclick="switchTab('po')"><i class="fas fa-arrow-right me-1"></i>Đến trang PO</button>
                    </div>
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
                                    <td>
                                        <?php $suggest_qty = number_format(($r['min_stock'] - $r['total_stock']) + ($r['min_stock'] * 0.5), 1, '.', ''); ?>
                                        <span class="badge bg-success fs-6 me-2">+ <?= $suggest_qty ?> <?= $r['unit_name'] ?></span>
                                        <button class="btn btn-sm btn-outline-primary fw-bold shadow-sm" onclick="switchTab('po'); setTimeout(() => { const m = new bootstrap.Modal(document.getElementById('modalCreatePO')); m.show(); document.getElementById('modalCreatePO').addEventListener('shown.bs.modal', function () { const firstRow = $('#poBody tr').first(); firstRow.find('.item-select').val('<?= $r['id'] ?>').trigger('change'); firstRow.find('.qty-input').val('<?= $suggest_qty ?>').trigger('input'); }, { once: true }); }, 300);">
                                            <i class="fas fa-file-invoice-dollar me-1"></i> Đặt hàng
                                        </button>
                                    </td>
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

        
            <!-- ================= ĐẶT HÀNG (PO) ================= -->
            <div class="tab-pane" id="tab-po">
                <div class="main-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-uppercase m-0"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Phiếu Đặt Hàng (PO)</h3>
                    <div>
                        <a href="POController.php?export_excel=1" class="btn btn-success shadow-sm fw-bold px-3 me-2">
                            <i class="fas fa-file-excel me-2"></i>XUẤT EXCEL (KÈM HSD)
                        </a>
                        <button class="btn btn-primary shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCreatePO">
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
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Chờ nhận</span>
                                    <?php elseif($p['status'] == 'completed'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Đã nhập kho</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border fw-bold me-1" onclick="viewPO(<?= $p['id'] ?>, '<?= htmlspecialchars($p['po_code']) ?>')">Xem</button>
                                    <?php if($p['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-success fw-bold" onclick="openReceivePO(<?= $p['id'] ?>, '<?= htmlspecialchars($p['po_code']) ?>')">
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
</div>

<!-- ================= CÁC FORM MODAL ẨN ================= -->

<!-- MODALS PO -->
<div class="modal fade" id="modalCreatePO" tabindex="-1" data-bs-focus="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" method="POST" action="POController.php" style="border-radius:20px;overflow:hidden;">
            <input type="hidden" name="create_po" value="1">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title mb-0" style="font-family:'Playfair Display',serif;">
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
                    <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm" onclick="loadSuggestions()">
                        <i class="fas fa-lightbulb me-1"></i>Gợi ý từ tồn kho thấp
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 bg-white p-4 pt-0">
                <button type="submit" class="btn btn-warning w-100 py-3 rounded-pill fw-bold text-white shadow-sm" style="background:#cda45e;border:none;">
                    <i class="fas fa-check-circle me-2"></i> HOÀN TẤT & LƯU PHIẾU ĐẶT HÀNG
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalViewPO" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold" style="font-family:'Playfair Display',serif;"><i class="fas fa-info-circle me-2 text-warning"></i>CHI TIẾT PHIẾU: <span id="view-po-code" class="text-warning"></span></h5>
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
            <div class="modal-header bg-success text-white py-3 px-4">
                <h5 class="modal-title fw-bold" style="font-family:'Playfair Display',serif;">
                    <i class="fas fa-check-double me-2"></i>NHẬN HÀNG VÀ NHẬP KHO: <span id="receive-po-code" class="text-white"></span>
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
                <button type="submit" class="btn btn-success px-5 py-2 fw-bold rounded-pill">
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
            <div class="modal-header bg-primary text-white py-2 px-3">
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
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary btn-sm w-100 fw-bold" id="btnSaveQuickIng">LƯU & CHỌN</button>
            </div>
        </div>
    </div>
</div>

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
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-bold">Tồn tối thiểu</label><input type="number" name="min_stock" id="inv-min" class="form-control" value="0" min="0" step="0.01"></div>
                    <div class="col-6"><label class="small fw-bold">Nhiệt độ bảo quản</label><input type="text" name="storage_temperature" id="inv-temp" class="form-control" placeholder="VD: -18°C, 0-4°C"></div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Chất gây dị ứng (FDA Standard)</label>
                    <div class="d-flex flex-wrap gap-2 p-2 bg-light rounded" style="border: 1px solid #f0f0f0;">
                        <?php 
                        $algopts = ['Sữa', 'Trứng', 'Đậu phộng', 'Hạt cây', 'Đậu nành', 'Lúa mì / Gluten', 'Cá', 'Động vật có vỏ', 'Động vật thân mềm', 'Mè / Vừng', 'Mù tạt', 'Cần tây', 'Sulphites', 'Đậu Lupin'];
                        foreach($algopts as $alg): ?>
                        <label class="d-flex align-items-center gap-1 m-0" style="cursor:pointer; font-size:12px; width:30%;">
                            <input type="checkbox" name="allergens[]" class="inv-alg-chk" value="<?= $alg ?>" style="accent-color:#d64545;"> <?= $alg ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
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
                    <option value="" disabled selected>-- Chọn Kho --</option>
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
<!-- Modal Chuyển Kho NHIỀU MẶT HÀNG -->
<div class="modal fade" id="modalTransfer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-transfer" action="InventoryController.php">
            <input type="hidden" name="action" value="transfer_multi">
            <div class="modal-header bg-dark text-white">
                <h5><i class="fas fa-exchange-alt me-2" style="pointer-events:none"></i>Tạo Lệnh Chuyển Kho</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Chọn kho -->
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1">Từ Kho (Xuất) <span class="text-danger">*</span></label>
                        <select name="from_warehouse_id" class="form-select shadow-sm" id="trans-from-wh" required>
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= $w['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1">Đến Kho (Nhận) <span class="text-danger">*</span></label>
                        <select name="to_warehouse_id" class="form-select shadow-sm" id="trans-to-wh" required>
                            <?php foreach (array_reverse($warehouses) as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= $w['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Bảng danh sách mặt hàng -->
                <div class="card border-0 shadow-sm overflow-hidden">
                    <table class="table table-bordered mb-0" id="transferTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th width="160">Số Lượng</th>
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="transferBody">
                            <tr>
                                <td>
                                    <select name="trans_item_id[]" class="form-select form-select-sm trans-item-select" required>
                                        <option value="">-- Chọn nguyên liệu --</option>
                                        <?php foreach ($inv as $i): if ($i['is_active'] != 1) continue; ?>
                                            <option value="<?= $i['id'] ?>" data-unit="<?= htmlspecialchars($i['unit_name']) ?>" data-stocks='<?= json_encode($i['stocks']) ?>'>
                                                <?= htmlspecialchars($i['item_name']) ?> (<?= $i['unit_name'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="trans-stock-info mt-1 text-end"></div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="trans_qty[]" class="form-control text-center" step="0.01" min="0.01" placeholder="0.00" required>
                                        <span class="input-group-text trans-unit-label">đơn vị</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-trans-remove border-0" style="pointer-events:auto" title="Xóa dòng">
                                        <i class="fas fa-trash" style="pointer-events:none"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-3 fw-bold" id="btnAddTransRow">
                    <i class="fas fa-plus me-1" style="pointer-events:none"></i>Thêm mặt hàng
                </button>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="submit" class="btn btn-dark w-100 py-2 fw-bold text-warning shadow">
                    <i class="fas fa-paper-plane me-2" style="pointer-events:none"></i>TẠO LỆNH CHUYỂN KHO
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nhà Cung Cấp -->
<div class="modal fade" id="modalSupplier" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="InventoryController.php" enctype="multipart/form-data">
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
                <div class="mb-3"><label class="small fw-bold">Địa chỉ</label><textarea name="s_address" id="s-address" class="form-control" rows="2"></textarea></div>
                <hr>
                <div class="mb-3">
                    <label class="small fw-bold text-danger"><i class="fas fa-file-certificate me-1"></i>Giấy Phép ATVSTP (Ảnh/PDF)</label>
                    <input type="file" name="s_atvstp_file" id="s-atvstp-file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
                    <div id="s-atvstp-link" class="mt-1 small"></div>
                </div>
                <div class="mb-0">
                    <label class="small fw-bold text-danger">Ngày hết hạn ATVSTP</label>
                    <input type="date" name="s_atvstp_expiry" id="s-atvstp-expiry" class="form-control form-control-sm">
                </div>
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

<!-- MODAL CHI TIẾT LÔ HÀNG (BATCH DETAILS) -->
<div class="modal fade" id="modalBatchDetails" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:15px; overflow:hidden;">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-layer-group me-2"></i>Chi tiết các lô hàng: <span id="batch-item-name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4">Mã lô (PO)</th>
                                <th>Kho chứa</th>
                                <th class="text-center">Số lượng tồn</th>
                                <th class="text-center">Nhiệt độ lúc nhập</th>
                                <th class="text-center">Hạn sử dụng</th>
                                <th class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="batch-list-body">
                            <!-- Data load via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
            if ($(this).val() !== "") {
                warningDiv.removeClass('d-none');
            } else {
                warningDiv.addClass('d-none');
            }
        }
    });
    
    let debounceTimer;
        const urlParams = new URLSearchParams(window.location.search);
        const targetTab = urlParams.get('tab');
        if (targetTab) {
            switchTab(targetTab);
        }
    });

    window.viewBatches = function(id, name) {
        $('#batch-item-name').text(name);
        $('#batch-list-body').html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-info"></div></td></tr>');
        const modal = new bootstrap.Modal(document.getElementById('modalBatchDetails'));
        modal.show();

        $.post('InventoryController.php', { action: 'get_batches', item_id: id }, function(res) {
            if(res.status === 'success') {
                let html = '';
                if(res.data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center py-4 text-muted">Không còn lô hàng nào trong kho.</td></tr>';
                } else {
                    const today = new Date();
                    res.data.forEach(b => {
                        let hsdClass = '', statusText = '<span class="badge bg-success">Ổn định</span>';
                        if(b.expiry_date) {
                            const exp = new Date(b.expiry_date);
                            const diff = (exp - today) / (1000 * 60 * 60 * 24);
                            if(diff < 0) { hsdClass = 'text-danger fw-bold'; statusText = '<span class="badge bg-danger">Hết hạn</span>'; }
                            else if(diff <= 7) { hsdClass = 'text-warning fw-bold'; statusText = '<span class="badge bg-warning text-dark">Sắp hết</span>'; }
                        }
                        let tempText = b.receiving_temperature ? b.receiving_temperature + '°C' : '-';
                        html += `<tr><td class="ps-4">#${b.batch_code || 'N/A'}</td><td>${b.warehouse_name}</td><td class="text-center fw-bold">${parseFloat(b.quantity)}</td><td class="text-center text-info">${tempText}</td><td class="text-center ${hsdClass}">${b.expiry_date || '-'}</td><td class="text-center">${statusText}</td></tr>`;
                    });
                }
                $('#batch-list-body').html(html);
            }
        }, 'json').fail(function() {
            $('#batch-list-body').html('<tr><td colspan="5" class="text-center py-4 text-danger">Lỗi kết nối máy chủ khi tải lô hàng.</td></tr>');
        });
    };

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
        $('#inv-id, #inv-name, #inv-temp').val('');
        $('.inv-alg-chk').prop('checked', false);
        new bootstrap.Modal(document.getElementById('modalInventory')).show();
    }

    function openEdit(data) {
        $('#inv-id').val(data.id);
        $('#inv-name').val(data.item_name);
        $('#inv-cat').val(data.category);
        $('#inv-unit').val(data.unit_name);
        $('#inv-min').val(data.min_stock || 0);
        $('#inv-temp').val(data.storage_temperature || '');
        
        $('.inv-alg-chk').prop('checked', false);
        if (data.allergens) {
            let algs = data.allergens.split(',').map(s => s.trim());
            $('.inv-alg-chk').each(function() {
                if (algs.includes($(this).val())) {
                    $(this).prop('checked', true);
                }
            });
        }
        
        new bootstrap.Modal(document.getElementById('modalInventory')).show();
    }

    function openSupplierModal() {
        $('#s-id, #s-name, #s-contact, #s-phone, #s-email, #s-address, #s-atvstp-expiry').val('');
        $('#s-atvstp-file').val('');
        $('#s-atvstp-link').html('');
        new bootstrap.Modal(document.getElementById('modalSupplier')).show();
    }

    function openEditSupplier(data) {
        $('#s-id').val(data.id);
        $('#s-name').val(data.name);
        $('#s-contact').val(data.contact_person);
        $('#s-phone').val(data.phone);
        $('#s-email').val(data.email);
        $('#s-address').val(data.address);
        $('#s-atvstp-expiry').val(data.atvstp_expiry || '');
        $('#s-atvstp-file').val('');
        if (data.atvstp_file) {
            $('#s-atvstp-link').html('<a href="../../uploads/suppliers/' + data.atvstp_file + '" target="_blank" class="text-decoration-none"><i class="fas fa-file-pdf me-1"></i>Xem file hiện tại</a>');
        } else {
            $('#s-atvstp-link').html('');
        }
        new bootstrap.Modal(document.getElementById('modalSupplier')).show();
    }

    function openImport(id, name, unit) {
        $('#form-import')[0].reset();
        $('#imp-id').val(id);
        $('#imp-name').text(name);
        $('#imp-unit').text(unit);
        new bootstrap.Modal(document.getElementById('modalImport')).show();
    }

    // Template HTML cho 1 dòng nguyên liệu (dùng khi thêm dòng mới)
    const transRowTemplate = `<tr>
        <td>
            <select name="trans_item_id[]" class="form-select form-select-sm trans-item-select" required>
                ${$('#transferBody tr:first .trans-item-select').prop('outerHTML').match(/<option[\s\S]*<\/option>/g)?.[0] ? 
                  $('#transferBody tr:first .trans-item-select').html() : ''}
            </select>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" name="trans_qty[]" class="form-control text-center" step="0.01" min="0.01" placeholder="0.00" required>
                <span class="input-group-text trans-unit-label">đơn vị</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm btn-trans-remove border-0" title="Xóa dòng">
                <i class="fas fa-trash" style="pointer-events:none"></i>
            </button>
        </td>
    </tr>`;

    // Mở modal chuyển kho — pre-select mặt hàng nếu click từ bảng
    function openTransfer(id, name, unit) {
        // Reset bảng về 1 dòng sạch
        const $tbody = $('#transferBody');
        $tbody.find('tr').slice(1).remove(); // Giữ lại dòng đầu, xóa các dòng thêm
        $tbody.find('.trans-item-select').val(''); // Reset dòng đầu
        $tbody.find('input[name="trans_qty[]"]').val('');
        $tbody.find('.trans-unit-label').text('đơn vị');
        $tbody.find('.trans-stock-info').html('');

        // Nếu mở từ nút cụ thể → pre-select mặt hàng đó
        if (id) {
            const $firstSelect = $tbody.find('.trans-item-select').first();
            $firstSelect.val(id);
            $tbody.find('.trans-unit-label').first().text(unit || 'đơn vị');
            updateTransferStock($tbody.find('tr').first());
        }
        new bootstrap.Modal(document.getElementById('modalTransfer')).show();
    }

    // Thêm dòng mới vào bảng
    $(document).on('click', '#btnAddTransRow', function () {
        const $firstRow = $('#transferBody tr:first').clone();
        $firstRow.find('.trans-item-select').val('');
        $firstRow.find('input[name="trans_qty[]"]').val('');
        $firstRow.find('.trans-unit-label').text('đơn vị');
        $firstRow.find('.trans-stock-info').html('');
        $('#transferBody').append($firstRow);
    });

    // Xóa dòng
    $(document).on('click', '.btn-trans-remove', function () {
        if ($('#transferBody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('Phải có ít nhất 1 mặt hàng trong lệnh chuyển kho.');
        }
    });

    // Tự động cập nhật nhãn đơn vị và tồn kho khi chọn nguyên liệu
    $(document).on('change', '.trans-item-select', function () {
        const unit = $(this).find(':selected').data('unit') || 'đơn vị';
        $(this).closest('tr').find('.trans-unit-label').text(unit);
        updateTransferStock($(this).closest('tr'));
    });

    $(document).on('change', '#trans-from-wh', function() {
        $('#transferBody tr').each(function() {
            updateTransferStock($(this));
        });
    });

    function updateTransferStock($row) {
        const fromWhId = $('#trans-from-wh').val();
        const $select = $row.find('.trans-item-select');
        const $opt = $select.find(':selected');
        if(!$opt.val() || !fromWhId) {
            $row.find('.trans-stock-info').html('');
            return;
        }
        const stocks = $opt.data('stocks') || {};
        const qty = parseFloat(stocks[fromWhId]) || 0;
        const unit = $opt.data('unit') || '';
        $row.find('.trans-stock-info').html(`<small class="text-primary fw-bold"><i class="fas fa-box-open"></i> Tồn trong kho: ${qty} ${unit}</small>`);
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

    // ================= QUẢN LÝ TAG (DANH MỤC / ĐƠN VỊ) =================
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
    const warehouses = <?= json_encode($warehouses) ?>;

    // 1. TỰ ĐỘNG XÓA DẤU PHẨY TRƯỚC KHI LƯU DB (Của TẤT CẢ các form)
    $(document).on('submit', 'form', function() {
        $(this).find('.money-input').each(function() {
            this.value = this.value.replace(/,/g, '');
        });
    });

    // 1b. Định dạng tiền tệ khi focus/blur
    $(document).on('blur', '.money-input', function() {
        let val = this.value.replace(/[^0-9]/g, '');
        this.value = val !== '' ? parseInt(val, 10).toLocaleString('en-US') : '';
    });
    $(document).on('focus', '.money-input', function() {
        this.value = this.value.replace(/,/g, '');
    });

    // 2a. AJAX cho form Nhập, Xuất (serialize bình thường)
    $(document).on('submit', '#form-import, #form-export', function(e) {
        e.preventDefault();
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

    // 2b. AJAX cho form Chuyển kho (dùng FormData để gửi mảng đúng chuẩn)
    $(document).on('submit', '#form-transfer', function(e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...');
        $.ajax({
            url: 'InventoryController.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(r) {
                if (r.status === 'success') location.reload();
                else {
                    alert('❌ ' + (r.msg || 'Lỗi không xác định'));
                    $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>TẠO LỆNH CHUYỂN KHO');
                }
            },
            error: function() {
                alert('Lỗi kết nối máy chủ.');
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>TẠO LỆNH CHUYỂN KHO');
            }
        });
    });
    // ================= LỌC & PHÂN TRANG =================
    let activeWarehouse = 'all'; // 'all' hoặc ID kho cụ thể

    // Xử lý click nút filter theo kho
    $(document).on('click', '.wh-filter-btn', function () {
        activeWarehouse = $(this).data('wh').toString();
        // Cập nhật active state
        $('.wh-filter-btn').removeClass('active btn-dark btn-primary btn-danger btn-info btn-warning btn-secondary')
                           .addClass('btn-outline-secondary').css('color', '');
        
        $(this).removeClass('btn-outline-secondary').addClass('active');
        
        if (activeWarehouse === 'all') {
            $(this).addClass('btn-dark');
        } else {
            const type = $(this).data('wh-type');
            const colorMap = { 
                main: 'btn-primary', 
                kitchen: 'btn-danger', 
                bar: 'btn-info', 
                cold: 'btn-primary',
                supplies: 'btn-warning',
                virtual: 'btn-secondary'
            };
            $(this).addClass(colorMap[type] || 'btn-secondary');
        }
        filterTable();
    });

    function filterWarning(type, btn) {
        activeFilter = type;
        
        // Nếu chọn một cảnh báo cụ thể (low hoặc expiry), tự động chuyển về "Tất cả kho" 
        // để đảm bảo người dùng thấy được mặt hàng bị cảnh báo đó
        if (type !== 'all') {
            $('.wh-filter-btn[data-wh="all"]').trigger('click');
        }

        $('#filterButtons button').removeClass('active');
        $(btn).addClass('active');
        filterTable();
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const catFilter = document.getElementById('categoryFilter').value.toLowerCase();
        document.querySelectorAll('#invBody .inv-row').forEach(r => {
            const nameMatch   = r.dataset.name.includes(q);
            const rCat = r.dataset.category ? r.dataset.category.toLowerCase() : '';
            const catMatch    = catFilter === '' || rCat === catFilter;
            const filterMatch = (activeFilter === 'all') ? true
                              : (activeFilter === 'low' ? r.dataset.low === '1' 
                               : (activeFilter === 'expired' ? r.dataset.expired === '1' : r.dataset.expiry === '1'));

            // Filter theo kho: kiểm tra data-wh-stock có chứa ID kho đang chọn không
            let whMatch = true;
            if (activeWarehouse !== 'all') {
                try {
                    const whStock = JSON.parse(r.dataset.whStock || '[]');
                    whMatch = whStock.map(String).includes(activeWarehouse);
                } catch(e) { whMatch = true; }
            }

            r.setAttribute('data-visible', (nameMatch && catMatch && filterMatch && whMatch) ? '1' : '0');

            // --- ĐIỀU CHỈNH HIỂN THỊ CHI TIẾT KHO TRONG DÒNG ---
            const badges = r.querySelectorAll('.wh-badge');
            const totalDiv = r.querySelector('.wh-total');
            const emptyDiv = r.querySelector('.wh-empty');

            if (activeFilter === 'expiry' || activeFilter === 'expired') {
                // Nếu đang lọc Sắp hết hạn / Đã hết hạn -> Ẩn các badge kho bình thường và dòng Tổng
                badges.forEach(b => b.style.display = 'none');
                if (totalDiv) totalDiv.style.display = 'none';
                if (emptyDiv) emptyDiv.style.display = 'none';
            } else {
                // Ngược lại, hiển thị bình thường dựa trên bộ lọc kho
                if (totalDiv) totalDiv.style.display = '';
                if (emptyDiv) emptyDiv.style.display = '';
                badges.forEach(b => {
                    if (activeWarehouse === 'all' || activeWarehouse === '1') {
                        b.style.display = '';
                    } else {
                        b.style.display = (b.dataset.whId === activeWarehouse) ? '' : 'none';
                    }
                });
            }
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

    // ================= XUẤT EXCEL CAO CẤP (MA TRẬN KHO & GIÁ TRỊ) =================
    window.exportFilteredExcel = function() {
        const visibleRows = document.querySelectorAll('#invBody .inv-row[data-visible="1"]');
        if (visibleRows.length === 0) {
            alert('Không có dữ liệu nào để xuất!');
            return;
        }

        // Tạo Header động dựa trên danh sách kho
        let whHeaders = warehouses.map(w => `<th style="background-color: #f8f9fa; font-weight: bold; color: #212529;">${w.name}</th>`).join('');
        
        let tableHTML = `<table border="1">
            <thead>
                <tr style="background-color: #2c3e50; color: white; font-weight: bold;">
                    <th style="color: white;">Nguyên Liệu</th>
                    <th style="color: white;">Danh Mục</th>
                    ${whHeaders}
                    <th style="background-color: #d1e7dd; color: #0f5132;">Tổng Tồn</th>
                    <th style="color: white;">Đơn Vị</th>
                    <th style="color: white;">Giá Vốn (đ)</th>
                    <th style="background-color: #fff3cd; color: #664d03;">Thành Tiền (đ)</th>
                    <th style="color: white;">HSD</th>
                </tr>
            </thead>
            <tbody>`;

        let grandTotalValue = 0;

        visibleRows.forEach(r => {
            let name = r.querySelector('strong').innerText;
            let catText = r.querySelector('.text-muted').innerText;
            let cat = catText.split('|')[0].trim();
            
            // Lấy stocks từ data attribute
            let stocks = {};
            try { stocks = JSON.parse(r.dataset.stocks || '{}'); } catch(e) {}

            // Lấy tổng tồn và đơn vị
            let totalDiv = r.querySelector('.text-success.fw-bold');
            let totalText = totalDiv ? totalDiv.innerText : '0';
            let totalMatch = totalText.match(/([\d.]+)\s*(.*)/);
            let totalQty = totalMatch ? parseFloat(totalMatch[1]) : 0;
            let unitName = totalMatch ? totalMatch[2] : '';

            // Lấy giá vốn
            let priceText = r.cells[3].innerText.replace(/[^\d]/g, '');
            let price = parseFloat(priceText) || 0;
            let lineValue = totalQty * price;
            grandTotalValue += lineValue;

            let hsd = r.cells[2].innerText;

            // Xây dựng các cột kho
            let whCols = warehouses.map(w => {
                let q = parseFloat(stocks[w.id] || 0);
                return `<td style="text-align: center;">${q > 0 ? q : '-'}</td>`;
            }).join('');

            tableHTML += `<tr>
                <td style="font-weight: bold;">${name}</td>
                <td>${cat}</td>
                ${whCols}
                <td style="text-align: center; font-weight: bold; background-color: #d1e7dd;">${totalQty}</td>
                <td style="text-align: center;">${unitName}</td>
                <td style="text-align: right;">${price.toLocaleString('vi-VN')}</td>
                <td style="text-align: right; font-weight: bold; background-color: #fff3cd;">${lineValue.toLocaleString('vi-VN')}</td>
                <td style="text-align: center;">${hsd}</td>
            </tr>`;
        });

        // Dòng tổng cộng tài sản
        tableHTML += `
            <tr style="background-color: #eee;">
                <td colspan="${2 + warehouses.length}" style="text-align: right; font-weight: bold; padding: 10px;">TỔNG GIÁ TRỊ TÀI SẢN KHO:</td>
                <td colspan="4" style="text-align: right; font-weight: bold; color: #d63384; font-size: 14px;">${grandTotalValue.toLocaleString('vi-VN')} VNĐ</td>
                <td></td>
            </tr>
        </tbody></table>`;

        // Thông tin Footer
        let now = new Date().toLocaleString('vi-VN');
        tableHTML += `<p><i>Báo cáo được xuất tự động vào lúc: ${now}</i></p>`;

        // Download
        let uri = 'data:application/vnd.ms-excel;base64,';
        let template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>BaoCaoKho</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>{table}</body></html>';
        let base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) };
        let format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) };
        
        let ctx = {worksheet: 'BaoCaoKho', table: tableHTML};
        let link = document.createElement("a");
        link.download = "BaoCao_TonKho_ChiTiet_" + new Date().toISOString().slice(0,10) + ".xls";
        link.href = uri + base64(format(template, ctx));
        link.click();
    };

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

    // ================= PO JS =================
$(document).ready(function() {
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
                    const btn = `<a href="../../uploads/po_certs/${res.batch_cert_file}" target="_blank" id="view-po-cert-btn" class="btn btn-sm btn-danger ms-3 fw-bold shadow-sm"><i class="fas fa-file-pdf me-1"></i>Xem Chứng Từ Lô Hàng</a>`;
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

    // ================= KHỞI CHẠY =================
    $(function() {
        const tab = new URLSearchParams(window.location.search).get('tab');
        if (tab) switchTab(tab);

        // Kích hoạt phân trang lần đầu
        document.querySelectorAll('#invBody .inv-row').forEach(r => r.setAttribute('data-visible', '1'));
        renderPagination();
    });
</script>
<?php
// Tên file: admin/inventory_view.php (hoặc thư mục views tùy bạn sắp xếp)
// File này KHÔNG CHỨA LOGIC KẾT NỐI DATABASE. Mọi dữ liệu đã được Controller chuẩn bị sẵn.

include '../public/admin_layout_header.php';
?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-dark text-warning fw-bold small">DÙNG NHIỀU NHẤT</div>
                <ul class="list-group list-group-flush">
                    <?php foreach($top_used as $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center small">
                        <?= htmlspecialchars($t['item_name']) ?>
                        <span class="badge bg-warning text-dark"><?= (float)$t['total'] ?> <?= $t['unit_name'] ?></span>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($top_used)): ?>
                    <li class="list-group-item small text-muted">Chưa có dữ liệu</li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($low_stock_count > 0 || $expiry_warn_count > 0): ?>
            <div class="card shadow-sm border-0 border-start border-4 border-danger mb-3 p-3">
                <div class="fw-bold small text-danger text-uppercase mb-2"><i class="fas fa-exclamation-triangle me-1"></i> Cảnh Báo</div>
                <?php if ($low_stock_count > 0): ?>
                <div class="small mb-1">
                    <span class="badge bg-danger me-1"><?= $low_stock_count ?></span>
                    nguyên liệu sắp hết tồn kho
                    <a href="#" onclick="switchTab('reorder');return false;" class="ms-1 text-danger text-decoration-none">[xem đề xuất]</a>
                </div>
                <?php endif; ?>
                <?php if ($expiry_warn_count > 0): ?>
                <div class="small">
                    <span class="badge bg-warning text-dark me-1"><?= $expiry_warn_count ?></span>
                    nguyên liệu sắp / đã hết HSD
                    <a href="#" onclick="switchTab('stock');filterWarning('expiry');return false;" class="ms-1 text-warning text-decoration-none">[xem]</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="card p-3 shadow-sm border-0 mb-3">
                <div class="fw-bold mb-2 small text-muted text-uppercase">Thống kê kho</div>
                <div class="small mb-1 text-success">Tổng Nhập: <b><?= number_format($stats['ti'] ?? 0, 1) ?></b></div>
                <div class="small mb-1 text-primary">Tổng Xuất: <b><?= number_format($stats['te'] ?? 0, 1) ?></b></div>
                <div class="small mb-3 text-danger">Tổng Hủy: <b><?= number_format($stats['tl'] ?? 0, 1) ?></b></div>
                <form method="GET" class="row g-1">
                    <div class="col-6">
                        <select name="f_type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="day"   <?= $f_type=='day'  ?'selected':'' ?>>Ngày</option>
                            <option value="month" <?= $f_type=='month'?'selected':'' ?>>Tháng</option>
                            <option value="year"  <?= $f_type=='year' ?'selected':'' ?>>Năm</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <input type="<?= $f_type=='day'?'date':($f_type=='year'?'number':'month') ?>"
                               name="f_val" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($f_val) ?>" onchange="this.form.submit()">
                    </div>
                </form>
            </div>

            <div class="card p-2 shadow-sm border-0">
                <button class="btn btn-primary btn-sm w-100 mb-2 py-2 fw-bold" onclick="switchTab('stock')"><i class="fas fa-boxes me-2"></i> TỒN KHO & HSD</button>
                <button class="btn btn-danger btn-sm w-100 mb-2 py-2 fw-bold position-relative" onclick="switchTab('reorder')">
                    <i class="fas fa-shopping-cart me-2"></i> ĐỀ XUẤT ĐẶT HÀNG
                    <?php if ($low_stock_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-light"><?= $low_stock_count ?></span>
                    <?php endif; ?>
                </button>
                <button class="btn btn-warning btn-sm w-100 mb-2 py-2 fw-bold" onclick="switchTab('audit')"><i class="fas fa-clipboard-check me-2"></i> KIỂM KÊ KHO</button>
                <button class="btn btn-dark btn-sm w-100 mb-2 py-2 fw-bold" onclick="switchTab('suppliers')"><i class="fas fa-truck me-2"></i> NHÀ CUNG CẤP</button>
                <button class="btn btn-info btn-sm w-100 mb-2 py-2 fw-bold text-white" onclick="switchTab('history')"><i class="fas fa-history me-2"></i> LỊCH SỬ GIAO DỊCH</button>
                <button class="btn btn-success btn-sm w-100 mb-2 py-2 fw-bold" onclick="switchTab('chart')"><i class="fas fa-chart-bar me-2"></i> BIỂU ĐỒ THỐNG KÊ</button>
                <hr class="my-2">
                <button class="btn btn-light btn-sm w-100 mb-2 text-start" onclick="openTagManager('category')"><i class="fas fa-tags me-2"></i> Danh Mục</button>
                <button class="btn btn-light btn-sm w-100 mb-2 text-start" onclick="openTagManager('unit')"><i class="fas fa-balance-scale me-2"></i> Đơn Vị</button>
                <a href="?export_csv=1" class="btn btn-outline-success btn-sm w-100 text-start"><i class="fas fa-file-csv me-2"></i> Xuất CSV</a>
            </div>
        </div>

        <div class="col-lg-9 tab-content">

            <?php if ($msg === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Hoàn tất kiểm kê và cập nhật số liệu thành công!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="tab-pane fade show active" id="tab-stock">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="fw-bold m-0 text-uppercase">Quản Lý Kho Chi Tiết</h4>
                    <div class="d-flex gap-2">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Tìm nguyên liệu..." style="width:200px" oninput="filterTable()">
                        <button class="btn btn-warning fw-bold shadow-sm" onclick="openInventoryModal()">+ Thêm Nguyên Liệu</button>
                    </div>
                </div>
                <div class="mb-2 d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-secondary active" onclick="filterWarning('all', this)">Tất cả</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="filterWarning('low', this)">
                        <i class="fas fa-arrow-down me-1"></i>Tồn kho thấp
                        <?php if ($low_stock_count > 0) echo "<span class='badge bg-danger ms-1'>$low_stock_count</span>"; ?>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="filterWarning('expiry', this)">
                        <i class="fas fa-clock me-1"></i>Sắp hết HSD
                        <?php if ($expiry_warn_count > 0) echo "<span class='badge bg-warning text-dark ms-1'>$expiry_warn_count</span>"; ?>
                    </button>
                </div>

                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table align-middle mb-0 table-hover" id="invTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th>Nhà Cung Cấp</th>
                                <th>Tồn Kho</th>
                                <th>HSD</th>
                                <th>Giá BQGQ</th>
                                <th class="text-end">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="invBody">
                            <?php foreach($inv as $i):
                                $stock = (float)$i['stock_quantity'];
                                $min   = (float)($i['min_stock'] ?? 0);
                                $isLow = ($min > 0 && $stock <= $min);
                                $exp   = $i['expiry_date'] ?? '';
                                $isExpired  = ($exp && $exp < $today);
                                $isExpiring = ($exp && $exp >= $today && $exp <= $warn_date);
                            ?>
                            <tr class="inv-row <?= $isLow ? 'row-low' : '' ?> <?= ($isExpired || $isExpiring) ? 'row-expiry' : '' ?>"
                                data-name="<?= strtolower(htmlspecialchars($i['item_name'])) ?>"
                                data-low="<?= $isLow ? '1' : '0' ?>"
                                data-expiry="<?= ($isExpired || $isExpiring) ? '1' : '0' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($i['item_name']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($i['category']) ?></div>
                                    <?php if($isLow): ?><span class="badge bg-danger" style="font-size:9px">Sắp hết kho</span><?php endif; ?>
                                </td>
                                <td class="text-primary fw-medium small"><?= htmlspecialchars($i['s_name'] ?? 'Chưa gán NCC') ?></td>
                                <td>
                                    <span class="fs-6 fw-bold <?= $isLow ? 'text-danger' : '' ?>"><?= (float)$i['stock_quantity'] ?></span>
                                    <span class="small text-muted"> <?= $i['unit_name'] ?></span>
                                    <?php if($min > 0): ?><div class="small text-muted">Tối thiểu: <?= $min ?></div><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($exp): ?>
                                        <?php if ($isExpired): ?><span class="badge bg-danger">HẾT HSD<br><?= $exp ?></span>
                                        <?php elseif ($isExpiring): ?><span class="badge bg-warning text-dark">Sắp hết<br><?= $exp ?></span>
                                        <?php else: ?><span class="small text-muted"><?= $exp ?></span><?php endif; ?>
                                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                </td>
                                <td class="text-success fw-bold small"><?= number_format($i['cost_price']) ?>đ</td>
                                <td class="text-end">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-sm btn-outline-success" onclick="openImport(<?= $i['id'] ?>, '<?= addslashes($i['item_name']) ?>', '<?= $i['unit_name'] ?>')">Nhập</button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openExport(<?= $i['id'] ?>, '<?= addslashes($i['item_name']) ?>', 'export')">Xuất</button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="openExport(<?= $i['id'] ?>, '<?= addslashes($i['item_name']) ?>', 'loss')">Hủy</button>
                                        <button class="btn btn-sm btn-light border" onclick='openEdit(<?= json_encode($i) ?>)'><i class="fas fa-edit"></i></button>
                                        <a href="?delete_id=<?= $i['id'] ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Xóa nguyên liệu này?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="paginInfo"></div>
                    <div id="paginBtns" class="btn-group btn-group-sm"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-reorder">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-uppercase text-danger"><i class="fas fa-cart-plus me-2"></i>Danh Sách Cần Đặt Hàng</h4>
                    <button class="btn btn-outline-danger btn-sm" onclick="window.print()"><i class="fas fa-print me-2"></i>In danh sách</button>
                </div>
                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table table-hover mb-0">
                        <thead class="table-danger">
                            <tr>
                                <th>Nguyên Liệu</th>
                                <th>Nhà Cung Cấp</th>
                                <th>Tồn hiện tại</th>
                                <th>Ngưỡng cảnh báo</th>
                                <th>Gợi ý mua thêm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reorder_list as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['item_name']) ?></strong></td>
                                <td class="text-muted small"><?= htmlspecialchars($r['s_name'] ?? 'Chưa gán') ?></td>
                                <td class="text-danger fw-bold"><?= (float)$r['stock_quantity'] ?> <?= $r['unit_name'] ?></td>
                                <td class="text-muted"><?= (float)$r['min_stock'] ?> <?= $r['unit_name'] ?></td>
                                <td>
                                    <?php $buy_qty = $r['suggest_qty'] + ($r['min_stock'] * 0.5); ?>
                                    <span class="badge bg-success fs-6">+ <?= number_format($buy_qty, 1) ?> <?= $r['unit_name'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reorder_list)): ?>
                            <tr><td colspan="5" class="text-center text-success py-5"><i class="fas fa-check-circle fa-2x mb-2 d-block"></i>Tuyệt vời! Kho hàng đang ở mức an toàn.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-audit">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-uppercase text-warning"><i class="fas fa-clipboard-check me-2"></i>Kiểm kê kho thực tế</h4>
                    <span class="badge bg-dark fs-6">Ngày kiểm kê: <?= date('d/m/Y') ?></span>
                </div>
                <div class="alert alert-info border-0 shadow-sm small">
                    <i class="fas fa-info-circle me-1"></i> Điền số lượng đếm thực tế. Hệ thống sẽ đối chiếu và tự tạo phiếu Nhập/Hủy điều chỉnh.
                </div>
                <form method="POST" action="InventoryController.php">
                    <input type="hidden" name="perform_audit" value="1">
                    <div class="card shadow-sm border-0 mb-3 overflow-hidden">
                        <table class="table align-middle mb-0 table-hover">
                            <thead class="table-warning">
                                <tr>
                                    <th>Danh Mục</th>
                                    <th>Nguyên Liệu</th>
                                    <th>Hệ thống ghi nhận</th>
                                    <th width="220">Thực tế đếm được</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($inv as $i): ?>
                                <tr>
                                    <td class="text-muted small"><?= htmlspecialchars($i['category']) ?></td>
                                    <td><strong><?= htmlspecialchars($i['item_name']) ?></strong> <small class="text-muted">(<?= $i['unit_name'] ?>)</small></td>
                                    <td class="fw-bold"><?= (float)$i['stock_quantity'] ?></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="actual_qty[<?= $i['id'] ?>]" step="0.01" min="0" class="form-control text-center" placeholder="Nhập SL..." value="<?= (float)$i['stock_quantity'] ?>">
                                            <span class="input-group-text"><?= $i['unit_name'] ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card p-3 mb-3 border-0 shadow-sm bg-white">
                        <label class="small fw-bold mb-2">Ghi chú đợt kiểm kê</label>
                        <textarea name="audit_notes" class="form-control" rows="2" placeholder="VD: Kiểm kê định kỳ cuối tháng..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-3 fs-5 shadow-sm" onclick="return confirm('Xác nhận chốt số liệu?')">
                        CHỐT KIỂM KÊ & ĐỐI CHIẾU
                    </button>
                </form>
            </div>

            <div class="tab-pane fade" id="tab-suppliers">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-uppercase text-primary">Danh Sách Nhà Cung Cấp</h4>
                    <button class="btn btn-dark fw-bold" onclick="openSupplierModal()">+ Thêm NCC Mới</button>
                </div>
                <div class="row g-3">
                    <?php foreach($suppliers as $s): ?>
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

            <div class="tab-pane fade" id="tab-history">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-uppercase text-info">Lịch Sử Giao Dịch</h4>
                    <span class="badge bg-secondary">100 giao dịch gần nhất</span>
                </div>
                <div class="card shadow-sm border-0 overflow-hidden">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-secondary">
                            <tr><th>Thời gian</th><th>Nguyên Liệu</th><th>Loại</th><th>Số Lượng</th><th>Người Thực Hiện</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($history as $h): ?>
                            <tr>
                                <td class="text-muted"><?= $h['created_at'] ?></td>
                                <td><strong><?= htmlspecialchars($h['item_name']) ?></strong></td>
                                <td>
                                    <?php
                                    $badges = ['import' => ['bg-success', 'Nhập kho'], 'export' => ['bg-primary', 'Xuất kho'], 'loss' => ['bg-danger', 'Hủy / Hao hụt']];
                                    [$cls, $lbl] = $badges[$h['type']] ?? ['bg-secondary', $h['type']];
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= $lbl ?></span>
                                </td>
                                <td class="fw-bold"><?= (float)$h['quantity'] ?> <?= $h['unit_name'] ?></td>
                                <td class="text-muted"><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($h['performed_by'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-chart">
                <h4 class="fw-bold mb-3 text-uppercase text-success">Biểu Đồ Kho 6 Tháng Gần Nhất</h4>
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <canvas id="inventoryChart" height="120"></canvas>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 text-center">
                            <div class="small text-muted">Tổng nhập (kỳ lọc)</div>
                            <div class="fs-4 fw-bold text-success"><?= number_format($stats['ti'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 text-center">
                            <div class="small text-muted">Tổng xuất (kỳ lọc)</div>
                            <div class="fs-4 fw-bold text-primary"><?= number_format($stats['te'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-3 text-center">
                            <div class="small text-muted">Tổng hủy (kỳ lọc)</div>
                            <div class="fs-4 fw-bold text-danger"><?= number_format($stats['tl'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalInventory" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content border-0 shadow" method="POST" action="InventoryController.php">
            <input type="hidden" name="save_inventory" value="1">
            <input type="hidden" name="item_id" id="inv-id">
            <div class="modal-header bg-warning"><h5 id="inv-modal-title">Nguyên Liệu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="small fw-bold">Tên nguyên liệu <span class="text-danger">*</span></label><input type="text" name="item_name" id="inv-name" class="form-control" required></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-bold">Danh mục</label><select name="category" id="inv-cat" class="form-select"><?php foreach($cats as $c) echo "<option value='{$c['name']}'>{$c['name']}</option>"; ?></select></div>
                    <div class="col-6"><label class="small fw-bold">Đơn vị</label><select name="unit_name" id="inv-unit" class="form-select"><?php foreach($units as $u) echo "<option value='{$u['name']}'>{$u['name']}</option>"; ?></select></div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Nhà cung cấp mặc định</label>
                    <select name="supplier_id" id="inv-sup" class="form-select">
                        <option value="">-- Chọn NCC --</option>
                        <?php foreach($suppliers as $s) echo "<option value='{$s['id']}'>{$s['name']}</option>"; ?>
                    </select>
                </div>
                <div class="row g-2 mb-0">
                    <div class="col-6"><label class="small fw-bold">Giá vốn (đ)</label><input type="number" name="cost_price" id="inv-price" class="form-control" value="0" min="0"></div>
                    <div class="col-6"><label class="small fw-bold">Tồn tối thiểu</label><input type="number" name="min_stock" id="inv-min" class="form-control" value="0" min="0" step="0.01"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100 fw-bold">LƯU</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-import" action="InventoryController.php">
            <input type="hidden" name="action" value="import">
            <input type="hidden" name="item_id" id="imp-id">
            <div class="modal-header bg-success text-white"><h5>Nhập: <span id="imp-name"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="fw-bold small">Chọn NCC <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="imp-sup" class="form-select" required>
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php foreach($suppliers as $s) echo "<option value='{$s['id']}'>{$s['name']}</option>"; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="fw-bold small">Số lượng (<span id="imp-unit"></span>) <span class="text-danger">*</span></label><input type="number" name="quantity" step="0.01" min="0.01" class="form-control" required></div>
                    <div class="col-6"><label class="fw-bold small">Giá vốn nhập (đ) <span class="text-danger">*</span></label><input type="number" name="import_price" id="imp-price" class="form-control" min="0" required></div>
                </div>
                <div class="mb-0"><label class="fw-bold small">Hạn sử dụng</label><input type="date" name="expiry_date" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success w-100 fw-bold">XÁC NHẬN NHẬP</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalExport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow border-0" id="form-export" action="InventoryController.php">
            <input type="hidden" name="action" id="exp-action">
            <input type="hidden" name="item_id" id="exp-id">
            <div class="modal-header" id="exp-hdr"><h5>Xử lý</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 text-center">
                <label class="fw-bold mb-2">Số lượng <span id="exp-type-txt"></span></label>
                <input type="number" name="quantity" step="0.01" min="0.01" class="form-control form-control-lg text-center" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn w-100 fw-bold text-white" id="exp-btn">XÁC NHẬN</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalSupplier" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="InventoryController.php">
            <input type="hidden" name="save_supplier" value="1">
            <input type="hidden" name="supplier_id" id="s-id">
            <div class="modal-header bg-dark text-white"><h5>Nhà Cung Cấp</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
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

<div class="modal fade" id="modalTags" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-secondary text-white"><h5 id="tagTitle">Quản Lý</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
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

<div class="modal fade" id="modalEditTag" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="InventoryController.php">
            <input type="hidden" name="manage_tag" value="1">
            <input type="hidden" name="tag_action" value="edit">
            <input type="hidden" name="tag_type" id="editTagType">
            <input type="hidden" name="tag_id" id="editTagId">
            <div class="modal-header"><h6 class="m-0">Sửa tên</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-3"><input type="text" name="tag_name" id="editTagName" class="form-control" required></div>
            <div class="modal-footer p-2"><button type="submit" class="btn btn-primary btn-sm w-100">Lưu</button></div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const categories = <?= json_encode($cats) ?>;
const units      = <?= json_encode($units) ?>;
const chartRaw   = <?= json_encode($chart_raw) ?>;

function switchTab(tabId) {
    $('.tab-pane').removeClass('show active');
    $('#tab-' + tabId).addClass('show active');
    if (tabId === 'chart') renderChart();
}

function openInventoryModal() { $('#inv-id, #inv-name, #inv-price, #inv-min, #inv-sup').val(''); $('#inv-price, #inv-min').val(0); $('#inv-modal-title').text('Thêm Nguyên Liệu'); new bootstrap.Modal(document.getElementById('modalInventory')).show(); }
function openEdit(data) { $('#inv-id').val(data.id); $('#inv-modal-title').text('Cập Nhật'); $('#inv-name').val(data.item_name); $('#inv-cat').val(data.category); $('#inv-unit').val(data.unit_name); $('#inv-price').val(data.cost_price); $('#inv-min').val(data.min_stock || 0); $('#inv-sup').val(data.supplier_id || ''); new bootstrap.Modal(document.getElementById('modalInventory')).show(); }

function openSupplierModal() { $('#s-id, #s-name, #s-contact, #s-phone, #s-email, #s-address').val(''); new bootstrap.Modal(document.getElementById('modalSupplier')).show(); }
function openEditSupplier(data) { $('#s-id').val(data.id); $('#s-name').val(data.name); $('#s-contact').val(data.contact_person); $('#s-phone').val(data.phone); $('#s-email').val(data.email); $('#s-address').val(data.address); new bootstrap.Modal(document.getElementById('modalSupplier')).show(); }

function openImport(id, name, unit) { $('#form-import')[0].reset(); $('#imp-id').val(id); $('#imp-name').text(name); $('#imp-unit').text(unit); new bootstrap.Modal(document.getElementById('modalImport')).show(); }
function openExport(id, name, type) { $('#form-export')[0].reset(); $('#exp-id').val(id); $('#exp-action').val(type); const isE = (type === 'export'); $('#exp-hdr').attr('class', 'modal-header ' + (isE ? 'bg-primary' : 'bg-danger') + ' text-white'); $('#exp-hdr h5').text((isE ? 'Xuất kho: ' : 'Hủy hàng: ') + name); $('#exp-type-txt').text(isE ? 'xuất' : 'hủy bỏ'); $('#exp-btn').attr('class', 'btn ' + (isE ? 'btn-primary' : 'btn-danger') + ' w-100 fw-bold text-white'); new bootstrap.Modal(document.getElementById('modalExport')).show(); }

$(document).on('submit', '#form-import, #form-export', function(e) {
    e.preventDefault();
    const btn = $(this).find('[type=submit]').prop('disabled', true).text('Đang xử lý...');
    $.post('InventoryController.php', $(this).serialize(), function(r) {
        if (r.status === 'success') location.reload();
        else { alert('❌ ' + (r.msg || 'Không thể xử lý')); btn.prop('disabled', false).text('XÁC NHẬN'); }
    }, 'json').fail(function() { alert('Lỗi kết nối máy chủ.'); btn.prop('disabled', false); });
});

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
function openEditTag(id, oldName, type) { $('#editTagId').val(id); $('#editTagType').val(type); $('#editTagName').val(oldName); new bootstrap.Modal(document.getElementById('modalEditTag')).show(); }

const PAGE_SIZE = 15; let currentPage = 1; let activeFilter = 'all';
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#invBody .inv-row').forEach(r => {
        const mS = r.dataset.name.includes(q);
        const mF = (activeFilter === 'all') ? true : (activeFilter === 'low' ? r.dataset.low === '1' : r.dataset.expiry === '1');
        r.setAttribute('data-visible', (mS && mF) ? '1' : '0');
    });
    currentPage = 1; renderPagination();
}
function filterWarning(type, btn) { activeFilter = type; document.querySelectorAll('.btn-outline-secondary, .btn-outline-danger, .btn-outline-warning').forEach(b => b.classList.remove('active')); if (btn) btn.classList.add('active'); filterTable(); }
function renderPagination() {
    const rows = [...document.querySelectorAll('#invBody .inv-row')].filter(r => r.getAttribute('data-visible') !== '0');
    const t = rows.length; const pgs = Math.ceil(t / PAGE_SIZE) || 1; currentPage = Math.min(currentPage, pgs);
    rows.forEach((r, i) => r.style.display = (i >= (currentPage-1)*PAGE_SIZE && i < currentPage*PAGE_SIZE) ? '' : 'none');
    document.getElementById('paginInfo').textContent = t > 0 ? `Hiển thị ${(currentPage-1)*PAGE_SIZE+1}–${Math.min(currentPage*PAGE_SIZE, t)} / ${t}` : 'Không tìm thấy';
    let html = `<button class="btn btn-outline-secondary" onclick="goPage(${currentPage-1})" ${currentPage<=1?'disabled':''}>‹</button>`;
    for (let p = 1; p <= pgs; p++) { if (pgs <= 7 || Math.abs(p-currentPage)<=1 || p===1 || p===pgs) html += `<button class="btn ${p===currentPage?'btn-primary':'btn-outline-secondary'}" onclick="goPage(${p})">${p}</button>`; else if (Math.abs(p-currentPage)===2) html += `<button class="btn btn-outline-secondary" disabled>…</button>`; }
    document.getElementById('paginBtns').innerHTML = html + `<button class="btn btn-outline-secondary" onclick="goPage(${currentPage+1})" ${currentPage>=pgs?'disabled':''}>›</button>`;
}
function goPage(p) { currentPage = p; renderPagination(); }

let chartInstance = null;
function renderChart() {
    if (chartInstance) return;
    chartInstance = new Chart(document.getElementById('inventoryChart').getContext('2d'), {
        type: 'bar',
        data: { labels: chartRaw.map(d => d.mo), datasets: [{ label: 'Nhập kho', data: chartRaw.map(d => parseFloat(d.ti)), backgroundColor: 'rgba(25,135,84,.7)' }, { label: 'Xuất kho', data: chartRaw.map(d => parseFloat(d.te)), backgroundColor: 'rgba(13,110,253,.7)' }, { label: 'Hủy hàng', data: chartRaw.map(d => parseFloat(d.tl)), backgroundColor: 'rgba(220,53,69,.7)' }] },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });
}

$(function() {
    document.querySelectorAll('#invBody .inv-row').forEach(r => r.setAttribute('data-visible','1')); renderPagination();
    const tab = new URLSearchParams(window.location.search).get('tab'); if (tab) switchTab(tab);
});
</script>
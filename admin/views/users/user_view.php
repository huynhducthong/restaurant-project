<?php include __DIR__ . '/../../../public/admin_layout_header.php'; ?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-uppercase"><i class="fas fa-users-cog me-2 text-primary"></i>Quản Lý Người Dùng</h3>
        <button class="btn btn-primary fw-bold shadow-sm" onclick="openUserModal()"><i class="fas fa-user-plus me-2"></i>Thêm Người Dùng</button>
    </div>

    <!-- Hiển thị thông báo -->
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Thanh tìm kiếm & Lọc -->
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form method="GET" action="UserController.php" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm theo tên, SĐT, Username..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">-- Tất cả vai trò --</option>
                    <option value="customer" <?= $filter_role === 'customer' ? 'selected' : '' ?>>Khách hàng</option>
                    <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Quản trị (Admin)</option>
                    <option value="cashier" <?= $filter_role === 'cashier' ? 'selected' : '' ?>>Thu ngân</option>
                    <option value="chef" <?= $filter_role === 'chef' ? 'selected' : '' ?>>Bếp</option>
                    <option value="waiter" <?= $filter_role === 'waiter' ? 'selected' : '' ?>>Phục vụ</option>
                </select>
            </div>
            <div class="col-md-5">
                <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-filter me-2"></i>Lọc dữ liệu</button>
                <a href="UserController.php" class="btn btn-light border"><i class="fas fa-redo me-2"></i>Làm mới</a>
                <button type="button" class="btn btn-success fw-bold ms-2 shadow-sm" onclick="exportExcel()">
                    <i class="fas fa-file-excel me-2"></i>Xuất Excel
                </button>
            </div>
        </form>
        <script>
        function exportExcel() {
            // Lấy URL hiện tại bao gồm các tham số search và role, sau đó thêm &export_excel=1
            const url = new URL(window.location.href);
            url.searchParams.set('export_excel', '1');
            window.location.href = url.toString();
        }
        </script>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Họ Tên</th>
                    <th>Tên đăng nhập</th>
                    <th>Liên hệ</th>
                    <th>Vai trò</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                            </div>
                        </td>
                        <td class="fw-bold text-primary">@<?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <div class="small text-muted"><i class="fas fa-phone-alt me-1"></i><?= htmlspecialchars($u['phone'] ?? '---') ?></div>
                            <div class="small text-muted"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($u['email'] ?? '---') ?></div>
                        </td>
                        <td>
                            <?php
                            $roles = [
                                'admin' => ['bg-danger', 'Quản trị (Admin)'],
                                'cashier' => ['bg-success', 'Thu ngân'],
                                'chef' => ['bg-warning text-dark', 'Bếp'],
                                'waiter' => ['bg-info text-dark', 'Phục vụ']
                            ];
                            
                            // Gán nhãn mặc định là "Người dùng"
                            $role_key = $u['role'] ?? '';
                            $role_data = $roles[$role_key] ?? ['bg-secondary', 'Người dùng'];
                            $role_badge = $role_data[0];
                            $role_name = $role_data[1];
                            ?>
                            <span class="badge <?= $role_badge ?>"><?= $role_name ?></span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input toggle-status" type="checkbox" role="switch"
                                    data-id="<?= $u['id'] ?>" <?= $u['is_active'] ? 'checked' : '' ?>
                                    <?= ($u['id'] == $_SESSION['user_id']) ? 'disabled' : '' ?>>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="btn-group shadow-sm">
                                <button class="btn btn-sm btn-info text-white border-0 shadow-sm" onclick='openCrmModal(<?= htmlspecialchars(json_encode([
                                    "id" => $u["id"],
                                    "doneness" => $u["doneness"] ?? "",
                                    "flavor" => $u["flavor_profile"] ?? "",
                                    "fav" => $u["fav_ingredients"] ?? "",
                                    "dislike" => $u["disliked_ingredients"] ?? "",
                                    "allergies" => $u["allergies"] ?? "",
                                    "name" => $u["full_name"],
                                    "total_bookings" => $u["total_bookings"] ?? 0,
                                    "total_spent" => $u["total_spent"] ?? 0,
                                    "vip_plan" => $u["vip_plan_name"] ?? null
                                ])) ?>)' title="Customer 360 View"><i class="fas fa-address-card"></i> Hồ Sơ 360</button>
                                <button class="btn btn-sm btn-light border" onclick='openEditModal(<?= json_encode($u) ?>)' title="Sửa thông tin"><i class="fas fa-edit text-primary"></i> Sửa</button>
                                <!-- Chặn Admin tự xóa tài khoản của chính mình -->
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="UserController.php?delete_id=<?= $u['id'] ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa nhân viên <?= htmlspecialchars($u['full_name']) ?> vĩnh viễn khỏi hệ thống không?')" title="Xóa người dùng"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>">Sau</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<!-- Modal Thêm/Sửa User -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content border-0 shadow" method="POST" action="UserController.php">
            <input type="hidden" name="save_user" value="1">
            <input type="hidden" name="user_id" id="u-id">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modal-title">Thêm Người Dùng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold">Họ và Tên <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" id="u-fullname" class="form-control" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="u-username" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Mật khẩu <span id="pass-req" class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Nhập pass...">
                        <small class="text-muted" id="pass-hint" style="display:none; font-size:11px;">Bỏ trống nếu không đổi</small>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Số điện thoại</label>
                        <input type="text" name="phone" id="u-phone" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Vai trò (Phân quyền)</label>
                        <select name="role" id="u-role" class="form-select">
                            <option value="">-- Chọn vai trò --</option>
                            <option value="waiter">Phục vụ</option>
                            <option value="chef">Bếp</option>
                            <option value="cashier">Thu ngân</option>
                            <option value="admin">Quản trị (Admin)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="small fw-bold">Email</label>
                    <input type="email" name="email" id="u-email" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100 fw-bold">LƯU THÔNG TIN</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal CRM 360 -->
<div class="modal fade" id="modalCrm" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-gem me-2 text-warning"></i>HỒ SƠ KHÁCH HÀNG 360°</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <h4 class="fw-bold text-primary mb-4 text-center" id="crm-name"></h4>
                
                <!-- Business Metrics Row -->
                <div class="row mb-4 text-center">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase small">Tổng số lần đến</h6>
                                <h3 class="fw-bold text-dark" id="crm-bookings">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase small">Tổng chi tiêu</h6>
                                <h3 class="fw-bold text-success" id="crm-spent">0đ</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase small">Phân hạng</h6>
                                <h3 class="fw-bold" id="crm-tier">-</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-dna text-danger me-2"></i>DNA ẨM THỰC (Gastronomy Profile)</div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <tr><td width="35%" class="bg-light fw-bold text-secondary">Độ chín bò</td><td id="crm-doneness"></td></tr>
                            <tr><td class="bg-light fw-bold text-secondary">Hương vị</td><td id="crm-flavor"></td></tr>
                            <tr><td class="bg-light fw-bold text-secondary">Yêu thích</td><td id="crm-fav"></td></tr>
                            <tr><td class="bg-light fw-bold text-secondary">Không thích</td><td id="crm-dislike"></td></tr>
                            <tr><td class="bg-light fw-bold text-danger">DỊ ỨNG Y TẾ</td><td id="crm-allergies" class="fw-bold text-danger"></td></tr>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-history text-primary me-2"></i>LỊCH SỬ GIAO DỊCH (Tối đa 10 đơn gần nhất)</div>
                    <div class="card-body p-0" id="crm-history-content">
                        <div class="text-center p-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</div>
                    </div>
                </div>
                
                <div class="text-center text-muted small mt-3">
                    <i class="fas fa-info-circle"></i> Sử dụng dữ liệu này để tư vấn Menu Bespoke cá nhân hóa cho khách hàng.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi Tiết Đơn (Admin) -->
<div class="modal fade" id="modalBookingDetailsAdmin" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Chi Tiết Đơn Đặt Bàn</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="adminBookingDetailsContent">
                <div class="text-center p-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    function openUserModal() {
        $('#u-id, #u-fullname, #u-username, #u-phone, #u-email, #u-role').val('');
        $('input[name="password"]').attr('required', true);
        $('#pass-req').show();
        $('#pass-hint').hide();
        $('#u-username').prop('readonly', false);
        $('#modal-title').text('Thêm Người Dùng Mới');
        new bootstrap.Modal(document.getElementById('modalUser')).show();
    }

    function openEditModal(data) {
        $('#u-id').val(data.id);
        $('#u-fullname').val(data.full_name);
        $('#u-username').val(data.username).prop('readonly', true);
        $('#u-phone').val(data.phone);
        $('#u-email').val(data.email);
        $('#u-role').val(data.role);

        $('input[name="password"]').val('').removeAttr('required');
        $('#pass-req').hide();
        $('#pass-hint').show();

        $('#modal-title').text('Cập Nhật: ' + data.full_name);
        new bootstrap.Modal(document.getElementById('modalUser')).show();
    }

    function openCrmModal(data) {
        $('#crm-name').text(data.name.toUpperCase());
        
        // Cập nhật Chỉ số Kinh doanh
        let spent = parseFloat(data.total_spent) || 0;
        let bookings = parseInt(data.total_bookings) || 0;
        
        $('#crm-bookings').text(bookings + ' Lần');
        $('#crm-spent').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(spent));
        
        // Tính toán Phân hạng (Tier)
        let tier = 'Khách Mới';
        let tierColor = 'text-secondary';
        
        // Ưu tiên hiển thị thẻ Hội viên VIP nếu có đăng ký
        if (data.vip_plan) {
            tier = 'HỘI VIÊN VIP <i class="fas fa-gem ms-1"></i><br><small class="text-muted" style="font-size: 0.75rem;">' + data.vip_plan + '</small>';
            tierColor = 'text-danger';
        } else {
            if (spent >= 10000000) {
                tier = 'VIP DIAMOND <i class="fas fa-crown"></i>';
                tierColor = 'text-warning';
            } else if (spent >= 5000000) {
                tier = 'GOLD MEMBER';
                tierColor = 'text-warning';
            } else if (bookings > 0) {
                tier = 'SILVER MEMBER';
                tierColor = 'text-info';
            }
        }
        $('#crm-tier').html(tier).removeClass().addClass('fw-bold ' + tierColor);

        // Cập nhật DNA
        $('#crm-doneness').text(data.doneness || 'Chưa thiết lập');
        $('#crm-flavor').text(data.flavor || 'Chưa thiết lập');
        $('#crm-fav').text(data.fav || 'Chưa thiết lập');
        $('#crm-dislike').text(data.dislike || 'Chưa thiết lập');
        $('#crm-allergies').text(data.allergies || 'Không có');
        
        // Gọi AJAX lấy lịch sử giao dịch
        $('#crm-history-content').html('<div class="text-center p-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</div>');
        $.get('ajax_get_user_history.php', { user_id: data.id }, function(res) {
            $('#crm-history-content').html(res);
        });
        
        new bootstrap.Modal(document.getElementById('modalCrm')).show();
    }

    function viewBookingDetailsAdmin(id) {
        // Mở modal
        new bootstrap.Modal(document.getElementById('modalBookingDetailsAdmin')).show();
        $('#adminBookingDetailsContent').html('<div class="text-center p-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</div>');
        
        // Gọi AJAX lấy chi tiết đơn
        $.get('ajax_get_booking_details_admin.php', { booking_id: id }, function(res) {
            $('#adminBookingDetailsContent').html(res);
        }).fail(function() {
            $('#adminBookingDetailsContent').html('<div class="text-danger text-center">Lỗi khi tải dữ liệu!</div>');
        });
    }

    function loadAllHistory(userId) {
        $('#crm-history-content').html('<div class="text-center p-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</div>');
        $.get('ajax_get_user_history.php', { user_id: userId, show_all: 1 }, function(res) {
            $('#crm-history-content').html(res);
        });
    }

    $('.toggle-status').change(function() {
        let userId = $(this).data('id');
        let isChecked = $(this).prop('checked');
        let checkbox = $(this);

        $.post('UserController.php', {
            toggle_status: 1,
            user_id: userId
        }, function(response) {
            if (response.status === 'error') {
                alert(response.msg);
                checkbox.prop('checked', !isChecked);
            }
        }, 'json');
    });
</script>
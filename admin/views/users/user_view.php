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
                                <button class="btn btn-sm btn-light border" onclick='openDnaModal(<?= htmlspecialchars(json_encode([
                                    "doneness" => $u["doneness"] ?? "",
                                    "flavor" => $u["flavor_profile"] ?? "",
                                    "fav" => $u["fav_ingredients"] ?? "",
                                    "dislike" => $u["disliked_ingredients"] ?? "",
                                    "allergies" => $u["allergies"] ?? "",
                                    "name" => $u["full_name"]
                                ])) ?>)' title="Xem DNA Ẩm Thực"><i class="fas fa-utensils text-danger"></i> DNA</button>
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

<!-- Modal Xem Culinary DNA -->
<div class="modal fade" id="modalDna" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-id-card-alt me-2"></i>Hồ sơ Khẩu vị (Culinary DNA)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <h6 class="fw-bold text-primary mb-3" id="dna-name"></h6>
                <table class="table table-bordered">
                    <tr><td width="35%" class="bg-light fw-bold text-secondary">Độ chín bò</td><td id="dna-doneness"></td></tr>
                    <tr><td class="bg-light fw-bold text-secondary">Hương vị</td><td id="dna-flavor"></td></tr>
                    <tr><td class="bg-light fw-bold text-secondary">Yêu thích</td><td id="dna-fav"></td></tr>
                    <tr><td class="bg-light fw-bold text-secondary">Không thích</td><td id="dna-dislike"></td></tr>
                    <tr><td class="bg-light fw-bold text-danger">DỊ ỨNG Y TẾ</td><td id="dna-allergies" class="fw-bold text-danger"></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

    function openDnaModal(data) {
        $('#dna-name').text('Khách hàng: ' + data.name);
        $('#dna-doneness').text(data.doneness || 'Chưa thiết lập');
        $('#dna-flavor').text(data.flavor || 'Chưa thiết lập');
        $('#dna-fav').text(data.fav || 'Chưa thiết lập');
        $('#dna-dislike').text(data.dislike || 'Chưa thiết lập');
        $('#dna-allergies').text(data.allergies || 'Không có');
        new bootstrap.Modal(document.getElementById('modalDna')).show();
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
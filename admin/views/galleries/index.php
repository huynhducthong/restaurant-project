<?php
$current_page = 'galleries.php';
require_once __DIR__ . '/../../../public/admin_layout_header.php';
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Quản Lý Không Gian (Gallery)</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Form thêm mới -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Thêm Hình Ảnh Mới</h5>
                </div>
                <div class="card-body">
                    <form action="/restaurant-project/admin/galleries.php?action=store" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Tên hình ảnh (Tùy chọn)</label>
                            <input type="text" name="title" class="form-control" placeholder="Nhập tên ảnh...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Chọn ảnh <span class="text-danger">*</span></label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                            <small class="text-muted">Nên chọn ảnh chất lượng cao để hiển thị đẹp nhất.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thứ tự ưu tiên (Sort Order)</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                            <small class="text-muted">Số nhỏ hơn sẽ hiển thị trước.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload"></i> Tải Lên & Thêm Mới
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info">
                <strong>Lưu ý:</strong> Trang chủ sẽ tự động ưu tiên lấy <strong>4 bức ảnh mới nhất và đang được Bật</strong> để hiển thị lên lưới không gian Atmosphere chuẩn Michelin.
            </div>
        </div>

        <!-- Danh sách ảnh -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Danh Sách Hình Ảnh</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Hình Ảnh</th>
                                    <th>Thông Tin</th>
                                    <th>Thứ Tự</th>
                                    <th>Trạng Thái</th>
                                    <th class="text-end">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($galleries)): ?>
                                    <?php foreach ($galleries as $gallery): ?>
                                        <tr>
                                            <td>
                                                <img src="/restaurant-project/public/assets/img/gallery/<?= htmlspecialchars($gallery['image_url']) ?>" 
                                                     alt="Gallery" 
                                                     class="img-thumbnail" 
                                                     style="width: 70px; height: 50px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($gallery['title'] ?: 'Không có tên') ?></strong>
                                                <div class="text-muted small">
                                                    Đã thêm: <?= date('d/m/Y', strtotime($gallery['created_at'])) ?>
                                                </div>
                                            </td>
                                            <td><?= $gallery['sort_order'] ?></td>
                                            <td>
                                                <?php if ($gallery['is_active']): ?>
                                                    <span class="badge bg-success">Đang Hiện</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Đã Ẩn</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="/restaurant-project/admin/galleries.php?action=toggle&id=<?= $gallery['id'] ?>" class="btn btn-sm <?= $gallery['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $gallery['is_active'] ? 'Ẩn ảnh này' : 'Bật ảnh này' ?>">
                                                    <i class="bi <?= $gallery['is_active'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                                </a>
                                                <a href="/restaurant-project/admin/galleries.php?action=delete&id=<?= $gallery['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa hình ảnh này không?');" title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Chưa có hình ảnh nào. Hãy thêm ảnh mới ở form bên cạnh.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

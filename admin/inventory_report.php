<?php
include '../public/admin_layout_header.php'; 
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Ngưỡng cảnh báo sắp hết hàng
$threshold = 5;
$query = "SELECT * FROM inventory WHERE stock_quantity < ? ORDER BY stock_quantity ASC";
$stmt = $db->prepare($query);
$stmt->execute([$threshold]);
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-content p-4">
    <h3 class="fw-bold mb-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo tồn kho thấp</h3>
    
    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <table class="table align-middle">
            <thead class="table-danger">
                <tr>
                    <th>Nguyên liệu</th>
                    <th>Danh mục</th>
                    <th>Tồn kho hiện tại</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($low_stock)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Tuyệt vời! Kho hàng vẫn đang đầy đủ.</td></tr>
                <?php else: ?>
                    <?php foreach($low_stock as $item): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['category']) ?></span></td>
                        <td class="text-danger fw-bold"><?= $item['stock_quantity'] ?> <?= $item['unit_name'] ?></td>
                        <td><span class="badge bg-danger rounded-pill">Cần nhập hàng</span></td>
                        <td class="text-end">
                            <a href="manage_inventory.php" class="btn btn-sm btn-primary rounded-pill px-3">Nhập thêm</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
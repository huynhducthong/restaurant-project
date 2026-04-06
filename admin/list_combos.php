<?php 
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Nhúng Header
include '../public/admin_layout_header.php'; 
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold"><i class="fas fa-boxes me-2"></i>Quản lý Combo Món Ăn</h3>
        <a href="add_combo.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> Thêm Combo Mới
        </a>
    </div>

    <div class="card shadow-sm mb-4 border-0" style="border-radius: 15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">ID</th>
                            <th width="10%">Hình ảnh</th>
                            <th width="20%">Tên Combo</th>
                            <th width="15%">Giá Combo</th>
                            <th width="25%">Món ăn trong Combo</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.*, GROUP_CONCAT(f.name SEPARATOR '||') as list_foods 
                                FROM combos c
                                LEFT JOIN combo_items ci ON c.id = ci.combo_id
                                LEFT JOIN foods f ON ci.food_id = f.id
                                GROUP BY c.id 
                                ORDER BY c.id DESC";
                        
                        $stmt = $db->prepare($sql);
                        $stmt->execute();

                        if($stmt->rowCount() > 0) {
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <tr>
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="../public/assets/img/combos/<?= $row['image'] ?>" 
                                                 style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <div class="bg-light text-muted d-flex align-items-center justify-content-center" 
                                                 style="width: 80px; height: 60px; border-radius: 8px; font-size: 10px;">
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="fw-bold text-primary"><?= htmlspecialchars($row['name']) ?></span></td>
                                    <td><span class="badge bg-success-subtle text-success fs-6"><?= number_format($row['price']) ?>đ</span></td>
                                    <td>
                                        <?php 
                                        if($row['list_foods']) {
                                            $foods = explode('||', $row['list_foods']);
                                            foreach($foods as $f) {
                                                echo "<span class='badge bg-info text-dark me-1 mb-1' style='font-size: 11px;'>" . htmlspecialchars($f) . "</span>";
                                            }
                                        } else {
                                            echo "<span class='text-muted small italic'>Chưa có món</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="edit_combo.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_combo.php?id=<?= $row['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Xác nhận xóa combo này?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Chưa có combo nào.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
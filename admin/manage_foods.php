<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// 1. Lấy danh sách đơn vị
$all_units = $db->query("SELECT name FROM inventory_units ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// 2. XỬ LÝ XÓA MÓN ĂN
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM combo_items WHERE food_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM food_recipes WHERE food_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM foods WHERE id = ?")->execute([$id]);
        
        $db->commit();
        echo "<script>alert('Xóa thành công!'); window.location.href='manage_foods.php';</script>";
    } catch (Exception $e) {
        $db->rollBack();
        echo "Lỗi: " . $e->getMessage();
    }
}

// 3. LẤY DỮ LIỆU DANH SÁCH MÓN ĂN
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT f.*, c.name as category_name 
          FROM foods f 
          LEFT JOIN categories c ON f.category_id = c.id";

$params = [];
if ($filter !== 'all' && $filter !== 'latest' && $filter !== 'oldest') {
    $query .= " WHERE c.name = ?";
    $params[] = $filter;
}

$query .= ($filter == 'oldest') ? " ORDER BY f.id ASC" : " ORDER BY f.id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);

$ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">

<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-utensils me-2 text-warning"></i> Quản lý thực đơn & Định mức</h3>
        <a href="add_food.php" class="btn btn-primary shadow-sm rounded-pill px-4"><i class="fas fa-plus"></i> Thêm món mới</a>
    </div>

    <div class="card-custom mb-4" style="background: white; padding: 15px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex flex-wrap gap-2">
                <a href="manage_foods.php?filter=all" class="btn btn-sm <?= $filter == 'all' ? 'btn-dark' : 'btn-outline-dark'; ?> px-3 rounded-pill">Tất cả</a>
                <div class="vr mx-2 text-secondary"></div>
                <?php 
                $cats = ['Khai vị', 'Món chính', 'Món ăn kèm', 'Tráng miệng', 'Đồ uống'];
                foreach($cats as $cat): ?>
                    <a href="manage_foods.php?filter=<?= $cat ?>" class="btn btn-sm <?= $filter == $cat ? 'btn-dark' : 'btn-outline-dark'; ?> px-3 rounded-pill"><?= $cat ?></a>
                <?php endforeach; ?>
            </div>
            <div class="text-muted small fw-bold"><i class="far fa-calendar-alt me-1"></i> <?php date_default_timezone_set('Asia/Ho_Chi_Minh'); echo date('d/m/Y'); ?></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Ảnh</th>
                    <th>Tên món</th>
                    <th>Danh mục</th>
                    <th>Định mức kho</th>
                    <th>Giá bán</th>
                    <th class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody style="background: white;">
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                    $check_c = $db->prepare("SELECT COUNT(*) FROM combo_items WHERE food_id = ?");
                    $check_c->execute([$row['id']]);
                    $in_combo = $check_c->fetchColumn();
                ?>
                <tr>
                    <td class="ps-4">
                        <img src="../public/assets/img/menu/<?= $row['image']; ?>" width="60" height="60" style="object-fit: cover; border-radius: 10px; border: 1px solid #eee;">
                    </td>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']); ?></div>
                        <?php if($in_combo > 0): ?>
                            <span class="badge bg-danger" style="font-size: 9px; letter-spacing: 0.5px;">ĐANG TRONG COMBO</span>
                        <?php else: ?>
                            <small class="text-muted">Mã: #<?= $row['id'] ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= $row['category_name']; ?></span></td>
                    
                    <td>
                        <div class="d-flex flex-wrap gap-1" style="max-width: 250px;">
                        <?php 
                            $recipe_stmt = $db->prepare("SELECT r.quantity_required, r.unit, i.item_name FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id WHERE r.food_id = ?");
                            $recipe_stmt->execute([$row['id']]);
                            $recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);

                            if(empty($recipes)): ?>
                                <small class="text-muted fst-italic">Chưa có định mức</small>
                            <?php else: ?>
                                <?php foreach($recipes as $rcp): ?>
                                    <span class="badge bg-warning-subtle text-dark border-0 shadow-sm" style="font-size: 10px; color: #856404 !important;">
                                        <?= htmlspecialchars($rcp['item_name']) ?>: <?= (float)$rcp['quantity_required'] ?><?= $rcp['unit'] ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="fw-bold text-danger"><?= number_format($row['price'], 0, ',', '.'); ?>đ</td>
                    <td class="text-center pe-4">
                        <div class="btn-group">
                            <button title="Thiết lập định mức" class="btn btn-sm btn-outline-warning btn-add-recipe" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name']) ?>">
                                <i class="fas fa-balance-scale"></i>
                            </button>
                            <a href="edit_food.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary ms-1"><i class="fas fa-edit"></i></a>
                            <a href="manage_foods.php?delete_id=<?= $row['id']; ?>" 
                               class="btn btn-sm btn-outline-danger ms-1" 
                               onclick="return confirm('<?= ($in_combo > 0) ? 'CẢNH BÁO: Món này đang nằm trong COMBO! Việc xóa món ăn sẽ ảnh hưởng đến các gói Combo liên quan.' : 'Xác nhận xóa món ăn này?' ?>')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalRecipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Thiết lập định mức: <span id="recipe-food-name" class="text-warning"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-save-recipe">
                <div class="modal-body p-4">
                    <input type="hidden" name="food_id" id="recipe-food-id">
                    
                    <div id="recipe-items-list">
                         </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-3 rounded-pill" id="btn-add-ingredient-row">
                        <i class="fas fa-plus me-1"></i> Thêm nguyên liệu khác
                    </button>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 rounded-3 text-white shadow-sm" style="background: #cda45e; border: none;">LƯU TẤT CẢ ĐỊNH MỨC</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.allIngredients = <?= json_encode($ingredients); ?>;
    window.allUnits = <?= json_encode($all_units); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/assets/admin/js/admin.js"></script>
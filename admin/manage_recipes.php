<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Lấy danh sách món ăn
$foods = $db->query("SELECT id, name FROM foods ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
// Lấy danh sách nguyên liệu trong kho
$ingredients = $db->query("SELECT id, item_name, unit_name FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4" style="font-family: 'Playfair Display', serif;">Thiết lập Định mức Món ăn (Recipes)</h2>
        
        <div class="card card-custom p-4">
            <p class="text-muted mb-4">Chọn món ăn để thiết lập các nguyên liệu cấu thành và lượng tiêu hao.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tên món ăn</th>
                            <th>Định mức hiện tại</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($foods as $f): 
                            // Lấy các nguyên liệu đã gán cho món này
                            $stmt = $db->prepare("SELECT r.*, i.item_name FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id WHERE r.food_id = ?");
                            $stmt->execute([$f['id']]);
                            $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($f['name']) ?></strong></td>
                            <td>
                                <?php if(empty($recipes)): ?>
                                    <span class="text-muted small">Chưa có định mức</span>
                                <?php else: ?>
                                    <?php foreach($recipes as $r): ?>
                                        <span class="badge bg-info-subtle text-info border me-1">
                                            <?= $r['item_name'] ?>: <?= $r['quantity_required'] ?><?= $r['unit'] ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-dark btn-add-recipe" data-id="<?= $f['id'] ?>" data-name="<?= htmlspecialchars($f['name']) ?>">
                                    <i class="fa fa-utensils me-1"></i> Định mức
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRecipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Định mức cho: <span id="recipe-food-name" class="text-warning"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-save-recipe">
                <div class="modal-body">
                    <input type="hidden" name="food_id" id="recipe-food-id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Chọn nguyên liệu từ kho</label>
                        <select name="ingredient_id" class="form-select" required>
                            <option value="">-- Chọn nguyên liệu --</option>
                            <?php foreach($ingredients as $i): ?>
                                <option value="<?= $i['id'] ?>"><?= $i['item_name'] ?> (tồn: <?= $i['unit_name'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label small fw-bold">Số lượng tiêu hao</label>
                            <input type="number" name="quantity_required" class="form-control" step="0.01" required placeholder="Ví dụ: 0.5">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label small fw-bold">Đơn vị</label>
                            <select name="unit" class="form-select">
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="L">L</option>
                                <option value="ml">ml</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-warning w-100 fw-bold">LƯU ĐỊNH MỨC</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../public/assets/admin/js/admin.js"></script>
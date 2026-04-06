<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM foods WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$food) die("Món ăn không tồn tại!");

    // Lấy định mức nguyên liệu hiện tại của món này để hiển thị
    $recipe_stmt = $db->prepare("SELECT r.*, i.item_name FROM food_recipes r JOIN inventory i ON r.ingredient_id = i.id WHERE r.food_id = ?");
    $recipe_stmt->execute([$id]);
    $current_recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy danh sách danh mục
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $food['image'];

    // Xử lý ảnh mới
    if (!empty($_FILES['image']['name'])) {
        $image = time() . '_' . $_FILES['image']['name'];
        $target = "../public/assets/img/menu/" . $image;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            // Có thể thêm code xóa ảnh cũ ở đây nếu muốn sạch host
        }
    }

    $update_sql = "UPDATE foods SET name = ?, category_id = ?, price = ?, description = ?, image = ? WHERE id = ?";
    $stmt = $db->prepare($update_sql);
    if ($stmt->execute([$name, $category_id, $price, $description, $image, $id])) {
        echo "<script>alert('Cập nhật thành công!'); window.location.href='manage_foods.php';</script>";
        exit();
    }
}
?>

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">

<div class="content-wrapper p-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-custom border-0 shadow-sm" style="border-radius: 20px; background: white; overflow: hidden;">
                <div class="card-header bg-dark py-3 px-4">
                    <h4 class="mb-0 text-white" style="font-family: 'Playfair Display', serif;">
                        <i class="fas fa-edit me-2 text-warning"></i>Chỉnh sửa món ăn
                    </h4>
                </div>
                
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-7 border-end pe-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Tên món ăn</label>
                                    <input type="text" name="name" class="form-control bg-light border-0 py-2" value="<?php echo htmlspecialchars($food['name']); ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small">Danh mục</label>
                                        <select name="category_id" class="form-select bg-light border-0 py-2" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $food['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small">Giá bán (VNĐ)</label>
                                        <input type="number" name="price" class="form-control bg-light border-0 py-2" value="<?php echo $food['price']; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Mô tả món ăn</label>
                                    <textarea name="description" class="form-control bg-light border-0" rows="4"><?php echo htmlspecialchars($food['description']); ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-5 ps-4">
                                <div class="mb-4 text-center">
                                    <label class="form-label d-block fw-bold small text-start">Ảnh món ăn</label>
                                    <div class="position-relative d-inline-block mb-2">
                                        <img src="../public/assets/img/menu/<?php echo $food['image']; ?>" class="rounded-4 shadow-sm" style="width: 100%; max-height: 180px; object-fit: cover; border: 3px solid #f8f9fa;">
                                    </div>
                                    <input type="file" name="image" class="form-control form-control-sm border-0 bg-light" accept="image/*">
                                    <small class="text-muted" style="font-size: 10px;">Để trống nếu không thay đổi.</small>
                                </div>

                                <div class="p-3 rounded-4" style="background: #fffcf0; border: 1px dashed #ffe082;">
                                    <label class="fw-bold small d-block mb-2 text-warning">
                                        <i class="fas fa-balance-scale me-1"></i> Định mức kho hiện có
                                    </label>
                                    <?php if(empty($current_recipes)): ?>
                                        <p class="text-muted small mb-0 italic">Chưa thiết lập định mức.</p>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach($current_recipes as $rcp): ?>
                                                <span class="badge bg-white text-dark border-0 shadow-sm" style="font-size: 11px;">
                                                    <?= $rcp['item_name'] ?>: <?= $rcp['quantity_required'] ?><?= $rcp['unit'] ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <hr class="my-2 opacity-25">
                                    <a href="manage_foods.php" class="text-decoration-none small text-dark fw-bold">
                                        <i class="fas fa-arrow-right me-1" style="font-size: 9px;"></i> Chỉnh sửa tại danh sách
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top justify-content-end">
                            <a href="manage_foods.php" class="btn btn-light px-4 rounded-pill fw-bold text-muted">Hủy bỏ</a>
                            <button type="submit" class="btn btn-warning px-5 rounded-pill fw-bold text-white shadow-sm" style="background: #cda45e; border: none;">LƯU THAY ĐỔI</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
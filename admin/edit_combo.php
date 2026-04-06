<?php
include '../public/admin_layout_header.php';
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) { header("Location: list_combos.php"); exit(); }
$combo_id = $_GET['id'];

// Lấy thông tin combo
$stmt_combo = $db->prepare("SELECT * FROM combos WHERE id = :id");
$stmt_combo->bindParam(':id', $combo_id);
$stmt_combo->execute();
$combo = $stmt_combo->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách món đã chọn
$stmt_selected = $db->prepare("SELECT food_id FROM combo_items WHERE combo_id = :id");
$stmt_selected->bindParam(':id', $combo_id);
$stmt_selected->execute();
$selected_foods = $stmt_selected->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container-fluid">
    <div class="card shadow p-4 border-0" style="border-radius: 15px;">
        <h3 class="mb-4 text-warning"><i class="fas fa-edit"></i> Chỉnh sửa Combo</h3>
        
        <form action="process_edit_combo.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="combo_id" value="<?= $combo['id'] ?>">

            <div class="row">
                <div class="col-md-7 border-end">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên Combo:</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($combo['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Giá Combo (VND):</label>
                        <input type="number" name="price" class="form-control" value="<?= $combo['price'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hình ảnh Combo:</label>
                        <div class="mb-2">
                            <?php if(!empty($combo['image'])): ?>
                                <img src="../public/assets/img/combos/<?= $combo['image'] ?>" class="img-thumbnail mb-2" id="currentImg" style="max-height: 150px;">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="delete_image" id="delImg">
                                    <label class="form-check-label text-danger" for="delImg">Xóa ảnh hiện tại</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô tả:</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($combo['description']) ?></textarea>
                    </div>
                </div>

                <div class="col-md-5 ps-4">
                    <label class="form-label fw-bold text-success">Chọn lại các món ăn trong combo:</label>
                    <div style="border: 1px solid #dee2e6; padding: 15px; max-height: 400px; overflow-y: auto; border-radius: 10px; background: #fdfdfd;">
                        <?php
                        $stmt_all = $db->prepare("SELECT id, name, price FROM foods ORDER BY name ASC");
                        $stmt_all->execute();

                        while ($food = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
                            $is_checked = in_array($food['id'], $selected_foods) ? "checked" : "";
                            echo "<div class='form-check mb-2 p-2 border-bottom'>";
                            echo "<input class='form-check-input' type='checkbox' name='food_ids[]' value='".$food['id']."' id='f".$food['id']."' $is_checked>";
                            echo "<label class='form-check-label ms-2' for='f".$food['id']."'>";
                            echo "<strong>".$food['name']."</strong> <br><small class='text-muted'>".number_format($food['price'])."đ</small>";
                            echo "</label></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="mt-4 border-top pt-3 text-end">
                <a href="list_combos.php" class="btn btn-light px-4 me-2">Hủy bỏ</a>
                <button type="submit" name="update_combo" class="btn btn-success px-5 shadow">
                    <i class="fas fa-save"></i> Cập nhật Combo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('currentImg');
            if(img) img.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php 
include '../public/admin_layout_header.php'; 
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
?>

<div class="container-fluid">
    <div class="card shadow p-4 border-0" style="border-radius: 15px;">
        <h3 class="mb-4 text-primary"><i class="fas fa-plus-circle"></i> Tạo Combo Mới</h3>
        
        <form action="process_combo.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-7 border-end">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên Combo:</label>
                        <input type="text" name="name" class="form-control" placeholder="Nhập tên combo..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Giá Combo (VND):</label>
                        <input type="number" name="price" class="form-control" placeholder="Ví dụ: 500000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hình ảnh Combo:</label>
                        <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                        <div id="imagePreview" class="mt-2 d-none">
                            <img src="#" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô tả:</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Mô tả nội dung combo..."></textarea>
                    </div>
                </div>

                <div class="col-md-5 ps-4">
                    <label class="form-label fw-bold text-success"><i class="fas fa-utensils"></i> Chọn món ăn lẻ cho combo:</label>
                    <div style="border: 1px solid #dee2e6; padding: 15px; max-height: 400px; overflow-y: auto; border-radius: 10px; background: #fdfdfd;">
                        <?php
                        $query = "SELECT id, name, price FROM foods ORDER BY name ASC";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<div class='form-check mb-2 p-2 border-bottom'>";
                            echo "<input class='form-check-input' type='checkbox' name='food_ids[]' value='".$row['id']."' id='f".$row['id']."'>";
                            echo "<label class='form-check-label ms-2' for='f".$row['id']."'>";
                            echo "<strong>".$row['name']."</strong> <br><small class='text-muted'>".number_format($row['price'])."đ</small>";
                            echo "</label></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="mt-4 border-top pt-3 text-end">
                <a href="list_combos.php" class="btn btn-light px-4 me-2">Hủy bỏ</a>
                <button type="submit" name="add_combo" class="btn btn-primary px-5 shadow">
                    <i class="fas fa-save"></i> Lưu Combo
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
            document.getElementById('imagePreview').classList.remove('d-none');
            document.querySelector('#imagePreview img').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
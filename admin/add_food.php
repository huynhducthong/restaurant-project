<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Lấy danh sách danh mục để hiển thị trong select
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    // XỬ LÝ UPLOAD ẢNH
    if (!empty($_FILES['image']['name'])) {
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target = "../public/assets/img/menu/" . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $sql = "INSERT INTO foods (name, category_id, price, description, image) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([$name, $category_id, $price, $description, $file_name])) {
                echo "<script>alert('Thêm món ăn thành công!'); window.location.href='manage_foods.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Lỗi: Không thể tải ảnh lên thư mục assets/img/menu/!');</script>";
        }
    }
}
?>

<link rel="stylesheet" href="../public/assets/admin/css/admin-style.css">

<div class="content-wrapper p-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-custom border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header bg-dark py-3 px-4 text-center">
                    <h4 class="mb-0 text-white" style="font-family: 'Playfair Display', serif;">
                        <i class="fas fa-plus-circle me-2 text-warning"></i>Thêm món ăn mới
                    </h4>
                </div>
                
                <div class="card-body p-4 bg-white">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tên món ăn</label>
                            <input type="text" name="name" class="form-control bg-light border-0 py-2" placeholder="Ví dụ: Bít tết bò sốt tiêu" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Danh mục</label>
                                <select name="category_id" class="form-select bg-light border-0 py-2" required>
                                    <option value="">-- Chọn loại --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Giá bán (VNĐ)</label>
                                <input type="number" name="price" class="form-control bg-light border-0 py-2" placeholder="0" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Ảnh món ăn</label>
                            <div class="input-group">
                                <input type="file" name="image" class="form-control bg-light border-0" accept="image/*" required>
                                <span class="input-group-text bg-light border-0"><i class="fas fa-image text-muted"></i></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Mô tả chi tiết</label>
                            <textarea name="description" class="form-control bg-light border-0" rows="4" placeholder="Hương vị, thành phần chính..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning py-3 rounded-pill fw-bold text-white shadow-sm" style="background: #cda45e; border: none;">
                                <i class="fas fa-save me-2"></i>LƯU MÓN ĂN
                            </button>
                            <a href="manage_foods.php" class="btn btn-light py-2 rounded-pill text-muted fw-bold border-0">
                                Quay lại danh sách
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 p-3 rounded-4 bg-light border text-center">
                <small class="text-muted italic">
                    <i class="fas fa-lightbulb me-1 text-warning"></i>
                    <strong>Mẹo:</strong> Sau khi thêm món, hãy vào phần <strong>"Định mức"</strong> tại danh sách món ăn để thiết lập trừ kho tự động.
                </small>
            </div>
        </div>
    </div>
</div>
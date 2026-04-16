<?php
require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (isset($_POST['add_combo'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $food_ids = isset($_POST['food_ids']) ? $_POST['food_ids'] : [];

    // --- XỬ LÝ UPLOAD ẢNH ---
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = time() . '_' . $_FILES['image']['name'];
        $target = "../public/assets/img/combos/" . $image_name;
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir("../public/assets/img/combos/")) {
            mkdir("../public/assets/img/combos/", 0777, true);
        }
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }
    // ------------------------

    try {
        $db->beginTransaction();

        // Bước 1: Lưu vào bảng combos (Bổ sung cột image)
        $query_combo = "INSERT INTO combos (name, description, price, image) VALUES (:name, :description, :price, :image)";
        $stmt_combo = $db->prepare($query_combo);
        $stmt_combo->bindParam(':name', $name);
        $stmt_combo->bindParam(':description', $description);
        $stmt_combo->bindParam(':price', $price);
        $stmt_combo->bindParam(':image', $image_name);
        $stmt_combo->execute();

        $combo_id = $db->lastInsertId();

        // Bước 2: Lưu vào bảng combo_items
        if (!empty($food_ids)) {
            $query_item = "INSERT INTO combo_items (combo_id, food_id) VALUES (:combo_id, :food_id)";
            $stmt_item = $db->prepare($query_item);
            foreach ($food_ids as $f_id) {
                $stmt_item->bindParam(':combo_id', $combo_id);
                $stmt_item->bindParam(':food_id', $f_id);
                $stmt_item->execute();
            }
        }

        $db->commit();
        echo "<script>alert('Thêm combo thành công!'); window.location.href='list_combos.php';</script>";
    } catch (Exception $e) {
        $db->rollBack();
        echo "Lỗi: " . $e->getMessage();
    }
}
?>
<?php
require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (isset($_POST['update_combo'])) {
    $combo_id = $_POST['combo_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $food_ids = isset($_POST['food_ids']) ? $_POST['food_ids'] : [];

    try {
        $db->beginTransaction();

        // --- XỬ LÝ ẢNH TRONG EDIT ---
        // Lấy thông tin ảnh cũ
        $stmt_old = $db->prepare("SELECT image FROM combos WHERE id = ?");
        $stmt_old->execute([$combo_id]);
        $old_combo = $stmt_old->fetch(PDO::FETCH_ASSOC);
        $new_image = $old_combo['image'];

        // Nếu người dùng chọn xóa ảnh hoặc upload ảnh mới
        if (isset($_POST['delete_image']) || (isset($_FILES['image']) && $_FILES['image']['error'] == 0)) {
            if ($old_combo['image'] && file_exists("../public/assets/img/combos/" . $old_combo['image'])) {
                unlink("../public/assets/img/combos/" . $old_combo['image']);
            }
            $new_image = null;
        }

        // Nếu có upload ảnh mới
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $new_image = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], "../public/assets/img/combos/" . $new_image);
        }
        // ----------------------------

        // 1. Cập nhật thông tin cơ bản của combo
        $sql_update = "UPDATE combos SET name = :name, description = :desc, price = :price, image = :img WHERE id = :id";
        $stmt = $db->prepare($sql_update);
        $stmt->execute([
            ':name' => $name, 
            ':desc' => $description, 
            ':price' => $price, 
            ':img' => $new_image, 
            ':id' => $combo_id
        ]);

        // 2. Xóa tất cả các món ăn cũ thuộc combo này trong bảng combo_items
        $sql_delete_items = "DELETE FROM combo_items WHERE combo_id = :id";
        $stmt_del = $db->prepare($sql_delete_items);
        $stmt_del->execute([':id' => $combo_id]);

        // 3. Chèn lại danh sách món ăn mới đã chọn
        if (!empty($food_ids)) {
            $sql_insert_item = "INSERT INTO combo_items (combo_id, food_id) VALUES (:cid, :fid)";
            $stmt_ins = $db->prepare($sql_insert_item);
            foreach ($food_ids as $f_id) {
                $stmt_ins->execute([':cid' => $combo_id, ':fid' => $f_id]);
            }
        }

        $db->commit();
        echo "<script>alert('Cập nhật combo thành công!'); window.location.href='list_combos.php';</script>";
    } catch (Exception $e) {
        $db->rollBack();
        echo "Lỗi: " . $e->getMessage();
    }
}
?>
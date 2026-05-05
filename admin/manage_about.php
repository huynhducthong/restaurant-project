<?php
include '../public/admin_layout_header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$message = "";

function create_slug($string) {
    $search = array('á','à','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ầ','ẩ','ẫ','ậ','é','è','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ','í','ì','ỉ','ĩ','ị','ó','ò','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ','ú','ù','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự','ý','ỳ','ỷ','ỹ','ỵ','đ','Á','À','Ả','Ã','Ạ','Ă','Ắ','Ằ','Ẳ','Ẵ','Ặ','Â','Ấ','Ầ','Ẩ','Ẫ','Ậ','É','È','Ẻ','Ẽ','Ẹ','Ê','Ế','Ề','Ể','Ễ','Ệ','Í','Ì','Ỉ','Ĩ','Ị','Ó','Ò','Ỏ','Õ','Ọ','Ô','Ố','Ồ','Ổ','Ỗ','Ộ','Ơ','Ớ','ờ','Ở','Ỡ','Ợ','Ú','Ù','Ủ','Ũ','Ụ','Ư','Ứ','Ừ','Ử','Ữ','Ự','Ý','Ỳ','Ỷ','Ỹ','ỵ','Đ');
    $replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','E','E','E','E','E','E','E','E','E','E','E','I','I','I','I','I','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','U','U','U','U','U','U','U','U','U','U','U','Y','Y','Y','Y','Y','D');
    $string = str_replace($search, $replace, $string);
    $string = strtolower(trim(preg_replace('/[^A-Za-z0-0-]+/', '-', $string)));
    return $string;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM about_content WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<div class='alert alert-success'>Đã xóa bài viết!</div>";
}

if (isset($_POST['btn_save'])) {
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'];
    $slug = !empty($_POST['slug']) ? create_slug($_POST['slug']) : create_slug($title);
    $content = $_POST['content'];
    $category_id = $_POST['category_id'];
    $display_order = $_POST['display_order'];
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $publish_date = $_POST['publish_date'] ?: date('Y-m-d'); // Lấy ngày chọn hoặc ngày hiện tại
    
    $thumbnail = $_POST['old_thumbnail'] ?? '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $target_dir = "../public/assets/img/about/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $thumbnail = time() . '_' . basename($_FILES['thumbnail']['name']);
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_dir . $thumbnail);
    }

    try {
        if ($id) {
            $sql = "UPDATE about_content SET title=?, slug=?, content=?, category_id=?, thumbnail=?, display_order=?, is_pinned=?, status=?, publish_date=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$title, $slug, $content, $category_id, $thumbnail, $display_order, $is_pinned, $status, $publish_date, $id]);
            $message = "<div class='alert alert-success'>Cập nhật thành công!</div>";
        } else {
            $sql = "INSERT INTO about_content (title, slug, content, category_id, thumbnail, display_order, is_pinned, status, publish_date) VALUES (?,?,?,?,?,?,?,?,?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$title, $slug, $content, $category_id, $thumbnail, $display_order, $is_pinned, $status, $publish_date]);
            $message = "<div class='alert alert-success'>Thêm bài viết thành công!</div>";
        }
    } catch (Exception $e) { $message = "<div class='alert alert-danger'>Lỗi: ".$e->getMessage()."</div>"; }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM about_content WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

$posts = $db->query("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id = c.id ORDER BY is_pinned DESC, display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $db->query("SELECT * FROM about_categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Quản lý nội dung "Về Chúng Tôi"</h2>
    <?= $message ?>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><?= $edit_data ? 'Chỉnh sửa bài viết' : 'Tạo bài viết mới' ?></h5></div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
                        <input type="hidden" name="old_thumbnail" value="<?= $edit_data['thumbnail'] ?? '' ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tiêu đề bài viết</label>
                                    <input type="text" name="title" class="form-control" value="<?= $edit_data['title'] ?? '' ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nội dung chi tiết</label>
                                    <textarea name="content" id="editor1"><?= $edit_data['content'] ?? '' ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày đăng bài</label>
                                    <input type="date" name="publish_date" class="form-control" value="<?= $edit_data['publish_date'] ?? date('Y-m-d') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Danh mục</label>
                                    <select name="category_id" class="form-select">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= (isset($edit_data['category_id']) && $edit_data['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ảnh Thumbnail</label>
                                    <input type="file" name="thumbnail" class="form-control">
                                    <?php if(isset($edit_data['thumbnail'])): ?>
                                        <img src="../public/assets/img/about/<?= $edit_data['thumbnail'] ?>" class="mt-2 img-thumbnail" width="150">
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Thứ tự</label>
                                    <input type="number" name="display_order" class="form-control" value="<?= $edit_data['display_order'] ?? '0' ?>">
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_pinned" <?= ($edit_data['is_pinned'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold">Ghim đầu</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="status" <?= ($edit_data['status'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold">Hiển thị công khai</label>
                                </div>
                                <button type="submit" name="btn_save" class="btn btn-primary w-100 fw-bold">LƯU BÀI VIẾT</button>
                                <?php if($edit_data): ?>
                                    <a href="manage_about.php" class="btn btn-outline-secondary w-100 mt-2">Hủy Sửa</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ngày</th>
                                <th>Ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($posts as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($p['publish_date'])) ?></td>
                                <td><img src="../public/assets/img/about/<?= $p['thumbnail'] ?>" width="60" class="rounded"></td>
                                <td><div class="fw-bold"><?= $p['title'] ?></div></td>
                                <td>
                                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa?')">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>CKEDITOR.replace('editor1');</script>
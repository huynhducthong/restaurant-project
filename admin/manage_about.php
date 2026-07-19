<?php
include '../public/admin_layout_header.php';
require_once '../config/database.php';
require_once '../config/csrf.php';
$database = new Database();
$db = $database->getConnection();
$message = "";

function create_slug($s){
    $from=['á','à','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ầ','ẩ','ẫ','ậ','é','è','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ','í','ì','ỉ','ĩ','ị','ó','ò','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ','ú','ù','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự','ý','ỳ','ỷ','ỹ','ỵ','đ'];
    $to  =['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d'];
    $s=str_replace($from,$to,mb_strtolower($s));
    return trim(preg_replace('/[^a-z0-9]+/','-',$s),'-');
}

if(isset($_GET['delete'])){
    $del_id = $_GET['delete'];
    $stmt_del = $db->prepare("SELECT thumbnail FROM about_content WHERE id=?");
    $stmt_del->execute([$del_id]);
    $del_row = $stmt_del->fetch(PDO::FETCH_ASSOC);
    if($del_row && !empty($del_row['thumbnail'])) {
        @unlink('../public/assets/img/about/'.$del_row['thumbnail']);
    }
    $db->prepare("DELETE FROM about_content WHERE id=?")->execute([$del_id]);
    $message="<div class='alert alert-success'>Đã xóa!</div>";
}
if(isset($_POST['btn_save'])){
    if(!verify_csrf()){ $message="<div class='alert alert-danger'>Lỗi CSRF!</div>"; }
    else {
        $id=$_POST['id']??null;
        $title=$_POST['title']; $slug=create_slug($_POST['slug']?:$title);
        $content=$_POST['content']; $cat=$_POST['category_id'];
        $milestone_text=$_POST['milestone_text']??null;
        $ord=(int)($_POST['display_order']??0); $st=isset($_POST['status'])?1:0;
        $thumb=$_POST['old_thumbnail']??'';
        if(!empty($_FILES['thumbnail']['name'])){
            $dir='../public/assets/img/about/'; if(!is_dir($dir)) mkdir($dir,0777,true);
            $thumb=time().'_'.basename($_FILES['thumbnail']['name']);
            if(move_uploaded_file($_FILES['thumbnail']['tmp_name'],$dir.$thumb)){
                if($id && !empty($_POST['old_thumbnail']) && file_exists($dir.$_POST['old_thumbnail'])) {
                    @unlink($dir.$_POST['old_thumbnail']);
                }
            }
        }
        try {
            $db->beginTransaction();
            if($id){
                $db->prepare("UPDATE about_content SET title=?,slug=?,content=?,category_id=?,thumbnail=?,display_order=?,status=?,milestone_text=? WHERE id=?")->execute([$title,$slug,$content,$cat,$thumb,$ord,$st,$milestone_text,$id]); 
                $message="<div class='alert alert-success'>Cập nhật thành công!</div>";
            }
            else {
                $db->prepare("INSERT INTO about_content(title,slug,content,category_id,thumbnail,display_order,status,milestone_text) VALUES(?,?,?,?,?,?,?,?)")->execute([$title,$slug,$content,$cat,$thumb,$ord,$st,$milestone_text]); 
                $message="<div class='alert alert-success'>Thêm thành công!</div>";
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $message="<div class='alert alert-danger'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
$edit_data=null;
if(isset($_GET['edit'])){ $s=$db->prepare("SELECT * FROM about_content WHERE id=?"); $s->execute([$_GET['edit']]); $edit_data=$s->fetch(PDO::FETCH_ASSOC); }

$posts=$db->query("SELECT a.id, a.title, a.slug, a.thumbnail, a.display_order, a.status, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id ORDER BY display_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$categories=$db->query("SELECT * FROM about_categories")->fetchAll(PDO::FETCH_ASSOC);



?>
<div class="container-fluid py-4">
<h2 class="fw-bold mb-4">Quản lý Tin Tức</h2>
<?= $message ?>
<ul class="nav nav-tabs mb-4" id="manageTab">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-posts">📝 Bài Viết</a></li>
</ul>
<div class="tab-content">

<!-- TAB 1: POSTS -->
<div class="tab-pane fade show active" id="tab-posts">
<div class="row">
  <div class="col-lg-12 mb-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><?= $edit_data?'Chỉnh sửa':'Tạo mới' ?></h5></div>
      <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $edit_data['id']??'' ?>">
          <input type="hidden" name="old_thumbnail" value="<?= $edit_data['thumbnail']??'' ?>">
          <div class="row">
            <div class="col-md-8">
              <div class="mb-3"><label class="form-label fw-bold">Tiêu đề</label><input type="text" name="title" class="form-control" value="<?= $edit_data['title']??'' ?>" required></div>
              <div class="mb-3"><label class="form-label fw-bold">Nội dung</label><textarea name="content" id="editor1"><?= $edit_data['content']??'' ?></textarea></div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label class="form-label fw-bold">Danh mục</label>
                <select name="category_id" class="form-select"><?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>" <?= (isset($edit_data['category_id'])&&$edit_data['category_id']==$cat['id'])?'selected':'' ?>><?= $cat['name'] ?></option><?php endforeach; ?></select>
              </div>
              <div class="mb-3"><label class="form-label fw-bold">Slug</label><input type="text" name="slug" class="form-control" value="<?= $edit_data['slug']??'' ?>"></div>
              <div class="mb-3"><label class="form-label fw-bold">Mốc năm (VD: 2024)</label><input type="text" name="milestone_text" class="form-control" value="<?= htmlspecialchars($edit_data['milestone_text']??'') ?>" placeholder="Nếu trống sẽ lấy năm của bài viết"></div>
              <div class="mb-3"><label class="form-label fw-bold">Ảnh</label><input type="file" name="thumbnail" class="form-control">
                <?php if(!empty($edit_data['thumbnail'])): ?><img src="../public/assets/img/about/<?= $edit_data['thumbnail'] ?>" class="mt-2 img-thumbnail" width="120"><?php endif; ?>
              </div>
              <div class="row">
                <div class="col-12 mb-3"><label class="form-label fw-bold">Thứ tự</label><input type="number" name="display_order" class="form-control" value="<?= $edit_data['display_order']??0 ?>"></div>
                
              </div>
              <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="status" <?= ($edit_data['status']??1)?'checked':'' ?>><label class="form-check-label fw-bold">Hiển thị</label></div>
              <button type="submit" name="btn_save" class="btn btn-primary w-100 fw-bold">LƯU BÀI VIẾT</button>
              <?php if($edit_data): ?><a href="manage_about.php" class="btn btn-outline-secondary w-100 mt-2">Hủy Sửa</a><?php endif; ?>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>Ảnh</th><th>Tiêu đề</th><th>Danh mục</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody><?php foreach($posts as $p): ?>
            <tr>
              
              <td><?php if($p['thumbnail']): ?><img src="../public/assets/img/about/<?= $p['thumbnail'] ?>" width="55" class="rounded"><?php endif; ?></td>
              <td><div class="fw-bold"><?= htmlspecialchars($p['title']) ?></div><small class="text-muted"><?= htmlspecialchars($p['slug']) ?></small></td>
              <td><span class="badge bg-info"><?= htmlspecialchars($p['cat_name']) ?></span></td>
              <td><?= $p['display_order'] ?></td>
              <td><span class="badge <?= $p['status']?'bg-success':'bg-secondary' ?>"><?= $p['status']?'Hiện':'Ẩn' ?></span></td>
              <td>
                <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">✏️ Sửa</a>
                <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa?')">🗑️ Xóa</a>
              </td>
            </tr>
          <?php endforeach; ?></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

  </div> <!-- end tab-content -->
</div> <!-- end container-fluid -->

<script src="https://cdn.tiny.cloud/1/ehi6s1017gy2rgbgi7qg9fbj7ufj1ccc7lybxdnkb9u2w5tc/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
tinymce.init({
    selector: '#editor1',
    language: 'vi',
    height: 600,
    plugins: [
      'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount'
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | align lineheight | link image media table | numlist bullist indent outdent | emoticons charmap | removeformat',
    font_family_formats: 'Arial=arial,helvetica,sans-serif; Courier New=courier new,courier,monospace; Akubra=alkubra; Poppins=poppins,sans-serif; Cormorant Garamond=Cormorant Garamond,serif; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif',
    content_style: '@import url("https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Poppins:wght@300;400;500;600;700&display=swap"); body { font-family: "Poppins", sans-serif; font-size: 14px; }',
    image_title: true,
    automatic_uploads: true,
    file_picker_types: 'image',
    images_upload_url: 'ajax/ajax_about_upload.php',
    setup: function (editor) {
        editor.on('change', function () {
            editor.save(); // Sync to textarea
        });
    }
});
</script>

</div><!-- end content-area -->
</div><!-- end main-wrapper -->
</body>
</html>
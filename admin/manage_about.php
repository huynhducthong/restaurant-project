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
        $ord=$_POST['display_order']; $pin=isset($_POST['is_pinned'])?1:0; $st=isset($_POST['status'])?1:0;
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
        if($id){ $db->prepare("UPDATE about_content SET title=?,slug=?,content=?,category_id=?,thumbnail=?,display_order=?,is_pinned=?,status=? WHERE id=?")->execute([$title,$slug,$content,$cat,$thumb,$ord,$pin,$st,$id]); $message="<div class='alert alert-success'>Cập nhật thành công!</div>"; }
        else { $db->prepare("INSERT INTO about_content(title,slug,content,category_id,thumbnail,display_order,is_pinned,status) VALUES(?,?,?,?,?,?,?,?)")->execute([$title,$slug,$content,$cat,$thumb,$ord,$pin,$st]); $message="<div class='alert alert-success'>Thêm thành công!</div>"; }
    }
}
$edit_data=null;
if(isset($_GET['edit'])){ $s=$db->prepare("SELECT * FROM about_content WHERE id=?"); $s->execute([$_GET['edit']]); $edit_data=$s->fetch(PDO::FETCH_ASSOC); }

$posts=$db->query("SELECT a.id, a.title, a.slug, a.thumbnail, a.is_pinned, a.display_order, a.status, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id ORDER BY is_pinned DESC,display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories=$db->query("SELECT * FROM about_categories")->fetchAll(PDO::FETCH_ASSOC);

// Stats aggregation queries (tránh N+1)
$likes_map = $db->query("SELECT content_id, COUNT(*) as cnt FROM about_likes GROUP BY content_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$views_map = $db->query("SELECT content_id, COUNT(*) as cnt FROM about_shares WHERE platform='view' GROUP BY content_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$shares_map = $db->query("SELECT content_id, COUNT(*) as cnt FROM about_shares WHERE platform!='view' GROUP BY content_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$comments_map = $db->query("SELECT content_id, COUNT(*) as cnt FROM about_comments WHERE status='approved' GROUP BY content_id")->fetchAll(PDO::FETCH_KEY_PAIR);

// Stats per post
$stats=[];
foreach($posts as $p){
    $pid=$p['id'];
    $stats[$pid]=[
        'likes' => $likes_map[$pid] ?? 0,
        'views' => $views_map[$pid] ?? 0,
        'shares'=> $shares_map[$pid] ?? 0,
        'comments'=> $comments_map[$pid] ?? 0,
        'title' => $p['title']
    ];
}

// All comments
$comments=$db->query("SELECT c.*,ac.title as post_title FROM about_comments c LEFT JOIN about_content ac ON c.content_id=ac.id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Active bans
$bans=$db->query("SELECT b.*, u.username as banned_username 
                  FROM about_comment_bans b 
                  LEFT JOIN users u ON b.user_id = u.id 
                  WHERE b.banned_until > NOW() 
                  ORDER BY b.banned_until DESC")->fetchAll(PDO::FETCH_ASSOC);

// Auto-migration for comment reports status
try {
    $db->exec("ALTER TABLE about_comment_reports ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending'");
} catch(Exception $e){}

// Pending reports (grouped by comment_id, with report counts and aggregated reasons)
$pending_reports = $db->query("
    SELECT 
        r.comment_id,
        c.comment as comment_content,
        c.author_name as comment_author,
        c.author_ip as comment_ip,
        c.user_id as comment_user_id,
        c.created_at as comment_created_at,
        ac.title as post_title,
        COUNT(r.id) as report_count,
        GROUP_CONCAT(DISTINCT r.reason ORDER BY r.created_at DESC SEPARATOR ' | ') as report_reasons,
        MAX(r.created_at) as latest_report_time
    FROM about_comment_reports r
    JOIN about_comments c ON r.comment_id = c.id
    LEFT JOIN about_content ac ON c.content_id = ac.id
    WHERE r.status = 'pending'
    GROUP BY r.comment_id
    ORDER BY report_count DESC, latest_report_time DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Processed reports (grouped by comment_id)
$processed_reports = $db->query("
    SELECT 
        r.comment_id,
        c.comment as comment_content,
        c.author_name as comment_author,
        c.author_ip as comment_ip,
        c.user_id as comment_user_id,
        c.created_at as comment_created_at,
        ac.title as post_title,
        COUNT(r.id) as report_count,
        GROUP_CONCAT(DISTINCT r.reason ORDER BY r.created_at DESC SEPARATOR ' | ') as report_reasons,
        MAX(r.created_at) as latest_report_time
    FROM about_comment_reports r
    JOIN about_comments c ON r.comment_id = c.id
    LEFT JOIN about_content ac ON c.content_id = ac.id
    WHERE r.status = 'processed'
    GROUP BY r.comment_id
    ORDER BY latest_report_time DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<script src="https://cdn.tiny.cloud/1/ehi6s1017gy2rgbgi7qg9fbj7ufj1ccc7lybxdnkb9u2w5tc/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<div class="container-fluid py-4">
<h2 class="fw-bold mb-4">Quản lý Tin Tức</h2>
<?= $message ?>
<ul class="nav nav-tabs mb-4" id="manageTab">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-posts">📝 Bài Viết</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-stats">📊 Tương Tác</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-comments">💬 Bình Luận</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-bans">🚫 Danh sách Cấm</a></li>
  <li class="nav-item">
    <a class="nav-link position-relative" data-bs-toggle="tab" href="#tab-reports">
      🚨 Báo cáo Bình luận
      <?php if (count($pending_reports) > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
          <?= count($pending_reports) ?>
        </span>
      <?php endif; ?>
    </a>
  </li>
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
              <div class="mb-3"><label class="form-label fw-bold">Ảnh</label><input type="file" name="thumbnail" class="form-control">
                <?php if(!empty($edit_data['thumbnail'])): ?><img src="../public/assets/img/about/<?= $edit_data['thumbnail'] ?>" class="mt-2 img-thumbnail" width="120"><?php endif; ?>
              </div>
              <div class="row">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Thứ tự</label><input type="number" name="display_order" class="form-control" value="<?= $edit_data['display_order']??0 ?>"></div>
                <div class="col-6 mb-3 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_pinned" <?= ($edit_data['is_pinned']??0)?'checked':'' ?>><label class="form-check-label fw-bold">Ghim</label></div></div>
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
          <thead class="table-light"><tr><th>Ghim</th><th>Ảnh</th><th>Tiêu đề</th><th>Danh mục</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody><?php foreach($posts as $p): ?>
            <tr>
              <td><?= $p['is_pinned']?'📌':'' ?></td>
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

<!-- TAB 2: STATS -->
<div class="tab-pane fade" id="tab-stats">
<div class="card shadow-sm border-0">
  <div class="card-header bg-white"><h5 class="mb-0 fw-bold">📊 Thống kê tương tác bài viết</h5></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light"><tr><th>Bài viết</th><th class="text-center">❤️ Lượt Like</th><th class="text-center">👁 Lượt Xem</th><th class="text-center">📤 Lượt Chia sẻ</th><th class="text-center">💬 Bình luận</th></tr></thead>
      <tbody>
        <?php foreach($stats as $pid=>$st): ?>
        <tr>
          <td class="fw-bold"><?= htmlspecialchars($st['title']) ?></td>
          <td class="text-center"><span class="badge bg-danger fs-6"><?= $st['likes'] ?></span></td>
          <td class="text-center"><span class="badge bg-primary fs-6"><?= $st['views'] ?></span></td>
          <td class="text-center"><span class="badge bg-info fs-6"><?= $st['shares'] ?></span></td>
          <td class="text-center"><span class="badge bg-secondary fs-6"><?= $st['comments'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- TAB 3: COMMENTS -->
<div class="tab-pane fade" id="tab-comments">
<div class="card shadow-sm border-0">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold">💬 Kiểm duyệt bình luận</h5>
    <span class="badge bg-secondary"><?= count($comments) ?> bình luận</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="cmtTable">
      <thead class="table-light"><tr><th>Bài viết</th><th>Người dùng</th><th>IP</th><th>Nội dung</th><th>Thời gian</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody>
        <?php foreach($comments as $c): ?>
        <tr id="cmt-row-<?= $c['id'] ?>">
          <td><small class="text-muted"><?= htmlspecialchars($c['post_title']??'') ?></small></td>
          <td><strong><?= htmlspecialchars($c['author_name']) ?></strong></td>
          <td><code class="small"><?= htmlspecialchars($c['author_ip']) ?></code></td>
          <td style="max-width:280px"><div class="text-truncate"><?= htmlspecialchars($c['comment']) ?></div></td>
          <td><small><?= date('d/m/Y H:i',strtotime($c['created_at'])) ?></small></td>
          <td>
            <?php if($c['status']==='approved'): ?>
              <span class="badge bg-success">Hiển thị</span>
            <?php elseif($c['status']==='rejected'): ?>
              <span class="badge bg-secondary">Đã ẩn</span>
            <?php else: ?>
              <span class="badge bg-warning">Chờ duyệt</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-danger mb-1" onclick="cmtAction('delete_comment',{comment_id:<?= $c['id'] ?>},'cmt-row-<?= $c['id'] ?>')">🗑️ Xóa</button>
            <?php if($c['status']==='approved'): ?>
            <button class="btn btn-sm btn-outline-secondary mb-1" onclick="cmtAction('reject_comment',{comment_id:<?= $c['id'] ?>})">👁 Ẩn</button>
            <?php else: ?>
            <button class="btn btn-sm btn-outline-success mb-1" onclick="cmtAction('approve_comment',{comment_id:<?= $c['id'] ?>})">✅ Duyệt</button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-warning mb-1" onclick="showBanModal('<?= htmlspecialchars($c['author_ip']) ?>','<?= htmlspecialchars($c['author_name']) ?>', '<?= $c['user_id'] ?? '' ?>')">🚫 Cấm</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- TAB 4: BANS -->
<div class="tab-pane fade" id="tab-bans">
<div class="row g-4">
  <div class="col-md-5">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white"><h5 class="mb-0 fw-bold">🚫 Cấm IP mới</h5></div>
      <div class="card-body">
        <div class="mb-3"><label class="form-label fw-bold">Địa chỉ IP</label><input type="text" id="ban-ip" class="form-control" placeholder="vd: 192.168.1.1"></div>
        <div class="mb-3"><label class="form-label fw-bold">Thời gian cấm</label>
          <select id="ban-hours" class="form-select">
            <option value="1">1 giờ</option><option value="6">6 giờ</option>
            <option value="24" selected>24 giờ (1 ngày)</option><option value="72">3 ngày</option>
            <option value="168">7 ngày</option><option value="720">30 ngày</option>
            <option value="87600">Vĩnh viễn (10 năm)</option>
          </select>
        </div>
        <div class="mb-3"><label class="form-label fw-bold">Lý do</label><input type="text" id="ban-reason" class="form-control" placeholder="Ngôn từ thô tục..."></div>
        <button class="btn btn-danger w-100 fw-bold" onclick="doBan()">🚫 Cấm Ngay</button>
        <div id="ban-msg" class="mt-2"></div>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white"><h5 class="mb-0 fw-bold">📋 Danh sách đang bị cấm</h5></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>IP</th><th>Lý do</th><th>Hết hạn</th><th>Thao tác</th></tr></thead>
          <tbody id="ban-list">
            <?php foreach($bans as $b): ?>
            <tr id="ban-row-<?= $b['id'] ?>">
              <td>
                <?php if($b['ban_type'] === 'ip'): ?>
                  <span class="badge bg-info">IP</span> <code><?= htmlspecialchars($b['user_ip']) ?></code>
                <?php else: ?>
                  <span class="badge bg-primary">Account</span> <strong><?= htmlspecialchars($b['banned_username'] ?? 'ID: '.$b['user_id']) ?></strong>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($b['reason']??'') ?></td>
              <td><?= date('d/m/Y H:i',strtotime($b['banned_until'])) ?></td>
              <td><button class="btn btn-sm btn-outline-success" onclick="doUnban(<?= $b['id'] ?>)">✅ Gỡ cấm</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($bans)): ?><tr><td colspan="4" class="text-center text-muted py-3">Không có IP nào bị cấm</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- TAB 5: REPORTS -->
<div class="tab-pane fade" id="tab-reports">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
      <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Báo cáo Bình luận Vi phạm</h5>
      <ul class="nav nav-pills card-header-pills" id="reportSubTabs">
        <li class="nav-item">
          <a class="nav-link active fw-bold" id="pills-pending-tab" data-bs-toggle="pill" href="#report-pending">
            ⏳ Đang xử lý <span class="badge bg-danger ms-1"><?= count($pending_reports) ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-bold text-secondary" id="pills-processed-tab" data-bs-toggle="pill" href="#report-processed">
            ✅ Đã xử lý <span class="badge bg-secondary ms-1"><?= count($processed_reports) ?></span>
          </a>
        </li>
      </ul>
    </div>
    <div class="card-body p-0">
      <div class="tab-content" id="reportSubTabContent">
        
        <!-- Sub-tab: ĐANG XỬ LÝ (PENDING) -->
        <div class="tab-pane fade show active" id="report-pending">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Bài viết</th>
                  <th>Người viết</th>
                  <th>Bình luận gốc</th>
                  <th>Lý do báo cáo</th>
                  <th class="text-end">Lượt báo cáo</th>
                  <th class="text-end">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pending_reports as $r): ?>
                  <tr id="report-row-<?= $r['comment_id'] ?>">
                    <td><small class="text-muted"><?= htmlspecialchars($r['post_title'] ?? '') ?></small></td>
                    <td>
                      <strong><?= htmlspecialchars($r['comment_author'] ?: 'Ẩn danh') ?></strong>
                      <br><small class="text-muted"><code><?= htmlspecialchars($r['comment_ip']) ?></code></small>
                    </td>
                    <td style="max-width: 250px;">
                      <div class="text-truncate" title="<?= htmlspecialchars($r['comment_content']) ?>">
                        <?= htmlspecialchars($r['comment_content']) ?>
                      </div>
                      <small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['comment_created_at'])) ?></small>
                    </td>
                    <td style="max-width: 250px;">
                      <span class="text-danger fw-semibold" style="font-size: 0.85rem;">
                        ⚠️ <?= htmlspecialchars($r['report_reasons']) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <span class="badge bg-danger rounded-pill fs-6 px-3">
                        <?= $r['report_count'] ?> báo cáo
                      </span>
                    </td>
                    <td class="text-end">
                      <button class="btn btn-danger btn-sm fw-bold" onclick="showModerateModal(<?= $r['comment_id'] ?>, '<?= htmlspecialchars(addslashes($r['comment_author'] ?: 'Ẩn danh')) ?>', '<?= htmlspecialchars(addslashes($r['comment_content'])) ?>', '<?= htmlspecialchars($r['comment_ip']) ?>', <?= (int)$r['comment_user_id'] ?>)">
                        🛡️ Xử lý
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($pending_reports)): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                      <i class="bi bi-shield-check text-success fs-1"></i>
                      <h5 class="mt-2 text-success">Tuyệt vời! Không có báo cáo nào chưa xử lý.</h5>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Sub-tab: ĐÃ XỬ LÝ (PROCESSED) -->
        <div class="tab-pane fade" id="report-processed">
          <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <div class="text-muted" style="font-size: 0.9rem;">
              <i class="bi bi-info-circle-fill text-primary me-1"></i> Quản lý danh sách các báo cáo bình luận đã xử lý thành công.
            </div>
            <button id="btn-bulk-delete-processed" class="btn btn-danger btn-sm fw-bold disabled" onclick="bulkDeleteProcessedReports()">
              <i class="bi bi-trash-fill me-1"></i> Xóa các mục đã chọn (<span id="selected-processed-count">0</span>)
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 40px; text-align: center;">
                    <input type="checkbox" class="form-check-input" id="processed-select-all" onclick="toggleSelectAllProcessed(this)">
                  </th>
                  <th>Bài viết</th>
                  <th>Người viết</th>
                  <th>Bình luận gốc / Nội dung hiện tại</th>
                  <th>Lý do báo cáo đã xử lý</th>
                  <th class="text-end">Lượt báo cáo</th>
                  <th class="text-end">Trạng thái hiện tại</th>
                  <th class="text-end">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($processed_reports as $r): ?>
                  <tr id="processed-row-<?= $r['comment_id'] ?>">
                    <td style="text-align: center;">
                      <input type="checkbox" class="form-check-input processed-item-checkbox" value="<?= $r['comment_id'] ?>" onchange="updateProcessedSelection()">
                    </td>
                    <td><small class="text-muted"><?= htmlspecialchars($r['post_title'] ?? '') ?></small></td>
                    <td>
                      <strong><?= htmlspecialchars($r['comment_author'] ?: 'Ẩn danh') ?></strong>
                      <br><small class="text-muted"><code><?= htmlspecialchars($r['comment_ip']) ?></code></small>
                    </td>
                    <td style="max-width: 250px;">
                      <div class="text-muted text-truncate" title="<?= htmlspecialchars($r['comment_content']) ?>">
                        <?= htmlspecialchars($r['comment_content']) ?>
                      </div>
                      <small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['comment_created_at'])) ?></small>
                    </td>
                    <td style="max-width: 250px;">
                      <span class="text-secondary" style="font-size: 0.85rem;">
                        <?= htmlspecialchars($r['report_reasons']) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <span class="badge bg-secondary rounded-pill">
                        <?= $r['report_count'] ?> báo cáo
                      </span>
                    </td>
                    <td class="text-end">
                      <span class="badge bg-success py-1 px-2">Đã giải quyết</span>
                    </td>
                    <td class="text-end">
                      <button class="btn btn-outline-danger btn-sm fw-bold" onclick="deleteSingleProcessedReport(<?= $r['comment_id'] ?>)" title="Xóa lịch sử báo cáo này">
                        <i class="bi bi-trash-fill"></i> Xóa
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($processed_reports)): ?>
                  <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                      Chưa có báo cáo nào được xử lý trước đây.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>


</div><!-- end tab-content -->
</div><!-- end container -->

<!-- Ban Modal -->
<div class="modal fade" id="banModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">🚫 Cấm người dùng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <p>Cấm bình luận của <strong id="ban-modal-name"></strong></p>
      <div class="mb-3">
        <label class="form-label fw-bold">Loại cấm</label>
        <div class="d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="modal-ban-type" id="ban-type-ip" value="ip" checked>
            <label class="form-check-label" for="ban-type-ip">Theo IP (<code id="ban-modal-ip"></code>)</label>
          </div>
          <div class="form-check" id="ban-acc-wrapper">
            <input class="form-check-input" type="radio" name="modal-ban-type" id="ban-type-acc" value="account">
            <label class="form-check-label" for="ban-type-acc">Theo Tài khoản</label>
          </div>
        </div>
      </div>
      <input type="hidden" id="ban-modal-uid">
      <div class="mb-3"><label class="form-label fw-bold">Thời gian cấm</label>
        <select id="modal-ban-hours" class="form-select">
          <option value="1">1 giờ</option><option value="6">6 giờ</option>
          <option value="24" selected>24 giờ</option><option value="72">3 ngày</option>
          <option value="168">7 ngày</option><option value="720">30 ngày</option>
          <option value="87600">Vĩnh viễn</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label fw-bold">Lý do</label><input type="text" id="modal-ban-reason" class="form-control" value="Ngôn từ thô tục"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
      <button class="btn btn-danger" onclick="confirmModalBan()">🚫 Xác nhận cấm</button>
    </div>
  </div></div>
</div>

<!-- Moderate Report Modal -->
<div class="modal fade" id="moderateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-shield-fill-exclamation text-warning me-2"></i> Xử lý bình luận vi phạm</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 bg-light p-3 rounded border">
          <div class="fw-bold mb-1" style="font-size: 0.9rem;">Bình luận của: <span id="mod-author-name" class="text-primary"></span></div>
          <div id="mod-comment-content" class="text-muted font-monospace bg-white p-2 rounded border" style="font-size: 0.85rem; max-height: 120px; overflow-y: auto;"></div>
        </div>
        
        <input type="hidden" id="mod-comment-id">
        <input type="hidden" id="mod-comment-ip">
        <input type="hidden" id="mod-comment-uid">
        
        <div class="mb-4">
          <label class="form-label fw-bold">Chọn hình thức xử lý:</label>
          
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="mod-action-type" id="mod-act-dismiss" value="dismiss" checked onclick="toggleModBanFields(false)">
            <label class="form-check-label fw-semibold text-success" for="mod-act-dismiss">
              ✅ Không vi phạm (Bỏ qua báo cáo)
              <br><small class="text-muted d-block" style="font-weight: normal; margin-left: 2px;">Báo cáo không chính xác. Giữ nguyên bình luận và chuyển báo cáo này sang danh sách Đã xử lý.</small>
            </label>
          </div>
          
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="mod-action-type" id="mod-act-delete" value="delete" onclick="toggleModBanFields(false)">
            <label class="form-check-label fw-semibold" for="mod-act-delete">
              🗑️ Xóa bình luận
              <br><small class="text-muted d-block" style="font-weight: normal; margin-left: 2px;">Giữ lại bình luận nhưng nội dung hiển thị thông báo "Bình luận này đã vi phạm và bị quản trị viên xóa".</small>
            </label>
          </div>
          
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="mod-action-type" id="mod-act-hide" value="hide" onclick="toggleModBanFields(false)">
            <label class="form-check-label fw-semibold" for="mod-act-hide">
              👁️ Ẩn bình luận
              <br><small class="text-muted d-block" style="font-weight: normal; margin-left: 2px;">Ẩn hoàn toàn bình luận này khỏi bài viết ngoài trang tin tức.</small>
            </label>
          </div>
          
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="mod-action-type" id="mod-act-delete-ban" value="delete_ban" onclick="toggleModBanFields(true)">
            <label class="form-check-label fw-semibold text-danger" for="mod-act-delete-ban">
              🚫 Xóa bình luận &amp; Cấm viết bình luận
              <br><small class="text-muted d-block" style="font-weight: normal; margin-left: 2px;">Thay thế nội dung bằng cảnh báo vi phạm VÀ cấm người dùng/IP này bình luận.</small>
            </label>
          </div>
          
          <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="mod-action-type" id="mod-act-hide-ban" value="hide_ban" onclick="toggleModBanFields(true)">
            <label class="form-check-label fw-semibold text-danger" for="mod-act-hide-ban">
              🚫 Ẩn bình luận &amp; Cấm viết bình luận
              <br><small class="text-muted d-block" style="font-weight: normal; margin-left: 2px;">Ẩn bình luận VÀ cấm người dùng/IP này bình luận.</small>
            </label>
          </div>
        </div>

        <!-- Extra Ban Fields (Shown only when a ban action is selected) -->
        <div id="mod-ban-fields" style="display: none;" class="bg-warning bg-opacity-10 p-3 rounded border border-warning mb-3">
          <div class="mb-2">
            <label class="form-label fw-bold small text-warning-emphasis">Thời hạn cấm</label>
            <select id="mod-ban-hours" class="form-select form-select-sm">
              <option value="1">1 giờ</option>
              <option value="6">6 giờ</option>
              <option value="24" selected>24 giờ (1 ngày)</option>
              <option value="72">3 ngày</option>
              <option value="168">7 ngày</option>
              <option value="720">30 ngày</option>
              <option value="87600">Vĩnh viễn (10 năm)</option>
            </select>
          </div>
          <div>
            <label class="form-label fw-bold small text-warning-emphasis">Lý do cấm</label>
            <input type="text" id="mod-ban-reason" class="form-control form-select-sm" value="Ngôn từ thô tục / Vi phạm chuẩn mực cộng đồng">
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
        <button class="btn btn-primary fw-bold" onclick="confirmModerateReport()">🛡️ Xác nhận xử lý</button>
      </div>
    </div>
  </div>
</div>

<script>
const AJAX_URL = 'ajax/ajax_about_comment_action.php';

function cmtAction(action, data, removeRowId) {
    const body = new URLSearchParams({action,...data});
    fetch(AJAX_URL,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
        .then(r=>r.json()).then(d=>{
            alert(d.message||'Thành công');
            if(d.status==='success' && removeRowId) {
                const row=document.getElementById(removeRowId);
                if(row) row.remove();
            } else if(d.status==='success') { location.reload(); }
        });
}

function showBanModal(ip, name, uid) {
    document.getElementById('ban-modal-ip').textContent = ip;
    document.getElementById('ban-modal-name').textContent = name;
    document.getElementById('ban-modal-uid').value = uid;
    
    const accWrap = document.getElementById('ban-acc-wrapper');
    if (!uid) {
        accWrap.style.display = 'none';
        document.getElementById('ban-type-ip').checked = true;
    } else {
        accWrap.style.display = 'block';
    }
    
    new bootstrap.Modal(document.getElementById('banModal')).show();
}

function confirmModalBan() {
    const ip = document.getElementById('ban-modal-ip').textContent;
    const uid = document.getElementById('ban-modal-uid').value;
    const type = document.querySelector('input[name="modal-ban-type"]:checked').value;
    const hours = document.getElementById('modal-ban-hours').value;
    const reason = document.getElementById('modal-ban-reason').value;
    
    const body = new URLSearchParams({action:'ban_user',ban_type:type,user_ip:ip,user_id:uid,hours,reason});
    fetch(AJAX_URL,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
        .then(r=>r.json()).then(d=>{ alert(d.message); if(d.status==='success') location.reload(); });
}

function doBan() {
    const ip=document.getElementById('ban-ip').value.trim();
    const hours=document.getElementById('ban-hours').value;
    const reason=document.getElementById('ban-reason').value;
    if(!ip){alert('Nhập IP cần cấm!');return;}
    const body=new URLSearchParams({action:'ban_user',ban_type:'ip',user_ip:ip,hours,reason});
    fetch(AJAX_URL,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
        .then(r=>r.json()).then(d=>{
            const msg=document.getElementById('ban-msg');
            msg.innerHTML=`<div class="alert ${d.status==='success'?'alert-success':'alert-danger'} py-2">${d.message}</div>`;
            if(d.status==='success') location.reload();
        });
}

function doUnban(banId) {
    if(!confirm('Gỡ lệnh cấm này?')) return;
    const body=new URLSearchParams({action:'unban',ban_id:banId});
    fetch(AJAX_URL,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
        .then(r=>r.json()).then(d=>{ alert(d.message); location.reload(); });
}

// Moderation of comment reports
function showModerateModal(commentId, authorName, commentContent, authorIp, userId) {
    document.getElementById('mod-comment-id').value = commentId;
    document.getElementById('mod-comment-ip').value = authorIp;
    document.getElementById('mod-comment-uid').value = userId;
    
    document.getElementById('mod-author-name').textContent = authorName;
    document.getElementById('mod-comment-content').textContent = commentContent;
    
    // Reset options
    document.getElementById('mod-act-dismiss').checked = true;
    toggleModBanFields(false);
    
    new bootstrap.Modal(document.getElementById('moderateModal')).show();
}

function toggleModBanFields(show) {
    document.getElementById('mod-ban-fields').style.display = show ? 'block' : 'none';
}

function confirmModerateReport() {
    const commentId = document.getElementById('mod-comment-id').value;
    const actionType = document.querySelector('input[name="mod-action-type"]:checked').value;
    const banHours = document.getElementById('mod-ban-hours').value;
    const banReason = document.getElementById('mod-ban-reason').value;
    
    const body = new URLSearchParams({
        action: 'process_report',
        comment_id: commentId,
        action_type: actionType,
        ban_hours: banHours,
        ban_reason: banReason
    });
    
    fetch(AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(d => {
        alert(d.message || 'Thao tác thành công!');
        if (d.status === 'success') {
            location.reload();
        }
    })
    .catch(() => {
        alert('Lỗi kết nối máy chủ khi xử lý báo cáo.');
    });
}

// Checkbox management for processed reports
function toggleSelectAllProcessed(master) {
    const checkboxes = document.querySelectorAll('.processed-item-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
    updateProcessedSelection();
}

function updateProcessedSelection() {
    const checkboxes = document.querySelectorAll('.processed-item-checkbox');
    const checked = Array.from(checkboxes).filter(cb => cb.checked);
    const countSpan = document.getElementById('selected-processed-count');
    const bulkBtn = document.getElementById('btn-bulk-delete-processed');
    
    if (countSpan) countSpan.textContent = checked.length;
    
    if (bulkBtn) {
        if (checked.length > 0) {
            bulkBtn.classList.remove('disabled');
        } else {
            bulkBtn.classList.add('disabled');
        }
    }
    
    // Update master select-all checkbox state
    const master = document.getElementById('processed-select-all');
    if (master) {
        master.checked = (checked.length === checkboxes.length && checkboxes.length > 0);
    }
}

function deleteSingleProcessedReport(commentId) {
    if (!confirm('Bạn có chắc chắn muốn xóa lịch sử báo cáo này khỏi hệ thống?')) return;
    executeDeleteProcessedReports([commentId]);
}

function bulkDeleteProcessedReports() {
    const checkboxes = document.querySelectorAll('.processed-item-checkbox');
    const checked = Array.from(checkboxes).filter(cb => cb.checked);
    const ids = checked.map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Vui lòng chọn ít nhất 1 mục để xóa!');
        return;
    }
    
    if (!confirm(`Bạn có chắc chắn muốn xóa ${ids.length} lịch sử báo cáo đã chọn khỏi hệ thống?`)) return;
    executeDeleteProcessedReports(ids);
}

function executeDeleteProcessedReports(ids) {
    const body = new URLSearchParams({
        action: 'delete_processed_reports',
        comment_ids: ids.join(',')
    });
    
    fetch(AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(d => {
        alert(d.message || 'Xóa lịch sử báo cáo thành công!');
        if (d.status === 'success') {
            location.reload();
        }
    })
    .catch(() => {
        alert('Lỗi kết nối máy chủ khi xóa lịch sử báo cáo.');
    });
}
</script>

<!-- Bootstrap JS (Required for Tabs and Modals) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
tinymce.init({
    selector: '#editor1',
    language: 'vi',
    height: 600,
    plugins: [
      'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount'
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | align lineheight | link image media table | numlist bullist indent outdent | emoticons charmap | removeformat',
    font_family_formats: 'Arial=arial,helvetica,sans-serif; Courier New=courier new,courier,monospace; Akubra=alkubra; Poppins=poppins,sans-serif; Playfair Display=playfair display,serif; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif',
    content_style: '@import url("https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap"); body { font-family: "Poppins", sans-serif; font-size: 14px; }',
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
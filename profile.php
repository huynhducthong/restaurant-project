<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: public/login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'profile';
$status_filter = $_GET['status'] ?? 'upcoming';

// --- XỬ LÝ POST ---
$message = '';
$msg_type = 'success';

if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $message = $_SESSION['error_msg'];
    $msg_type = 'danger';
    unset($_SESSION['error_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cập nhật hồ sơ
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $birthday = $_POST['birthday'] ?: null;
        
        $avatar_blob = null;
        $avatar_mime = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $avatar_blob = file_get_contents($_FILES['avatar']['tmp_name']);
                $avatar_mime = $_FILES['avatar']['type'];
            }
        }

        $sql = "UPDATE users SET full_name = ?, phone = ?, email = ?, birthday = ?" . ($avatar_blob ? ", avatar_blob = ?, avatar_mime = ?, avatar = NULL" : "") . " WHERE id = ?";
        $params = [$full_name, $phone, $email, $birthday];
        if ($avatar_blob) {
            $params[] = $avatar_blob;
            $params[] = $avatar_mime;
        }
        $params[] = $user_id;

        if ($db->prepare($sql)->execute($params)) {
            $_SESSION['user_name'] = $full_name;
            $message = "Cập nhật thông tin thành công!";
        } else {
            $message = "Có lỗi xảy ra khi cập nhật.";
            $msg_type = 'danger';
        }
    }
    
    // 1.5 Cập nhật Avatar
    if (isset($_POST['update_avatar'])) {
        if (isset($_FILES['avatar_photo']) && $_FILES['avatar_photo']['error'] === UPLOAD_ERR_OK) {
            $blob = file_get_contents($_FILES['avatar_photo']['tmp_name']);
            $mime = $_FILES['avatar_photo']['type'];
            
            $stmt = $db->prepare("UPDATE users SET avatar_blob = ?, avatar_mime = ? WHERE id = ?");
            if ($stmt->execute([$blob, $mime, $user_id])) {
                $message = "Đã cập nhật ảnh đại diện thành công!";
                $current_user['avatar_blob'] = $blob;
                $current_user['avatar_mime'] = $mime;
            } else {
                $message = "Lỗi khi cập nhật ảnh đại diện.";
                $msg_type = "danger";
            }
        }
    }

    // 1.5 Cập nhật Cover
    if (isset($_POST['update_cover'])) {
        if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $cover_dir = 'uploads/covers/';
                if (!is_dir($cover_dir)) mkdir($cover_dir, 0777, true);
                
                $old_covers = glob($cover_dir . 'cover_' . $user_id . '.*');
                foreach($old_covers as $old) {
                    @unlink($old);
                }
                
                $new_name = 'cover_' . $user_id . '.' . $ext;
                move_uploaded_file($_FILES['cover_photo']['tmp_name'], $cover_dir . $new_name);
                $message = "Đã cập nhật ảnh nền thành công!";
            } else {
                $message = "Định dạng ảnh không hợp lệ.";
                $msg_type = "danger";
            }
        }
    }

    // 2. Đổi mật khẩu
    if (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $cf_pass = $_POST['confirm_password'];

        $u = $db->prepare("SELECT password FROM users WHERE id = ?");
        $u->execute([$user_id]);
        $user_data = $u->fetch();

        if ($new_pass !== $cf_pass) {
            $message = "Mật khẩu mới không khớp.";
            $msg_type = 'danger';
        } elseif (!password_verify($old_pass, $user_data['password'])) {
            $message = "Mật khẩu cũ không chính xác.";
            $msg_type = 'danger';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
            $message = "Đổi mật khẩu thành công!";
        }
    }

    // 3. Sổ địa chỉ
    if (isset($_POST['add_address'])) {
        $type = $_POST['address_type'];
        $detail = $_POST['address_detail'];
        $is_def = isset($_POST['is_default']) ? 1 : 0;

        if ($is_def) {
            $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }

        $db->prepare("INSERT INTO user_addresses (user_id, address_type, address_detail, is_default) VALUES (?, ?, ?, ?)")
           ->execute([$user_id, $type, $detail, $is_def]);
        $message = "Thêm địa chỉ thành công!";
    }

    if (isset($_POST['delete_address'])) {
        $addr_id = $_POST['address_id'];
        $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?")->execute([$addr_id, $user_id]);
        $message = "Đã xóa địa chỉ.";
    }

    if (isset($_POST['edit_address'])) {
        $addr_id = $_POST['address_id'];
        $type = $_POST['address_type'];
        $detail = $_POST['address_detail'];
        $is_def = isset($_POST['is_default']) ? 1 : 0;

        if ($is_def) {
            $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }

        $db->prepare("UPDATE user_addresses SET address_type = ?, address_detail = ?, is_default = ? WHERE id = ? AND user_id = ?")
           ->execute([$type, $detail, $is_def, $addr_id, $user_id]);
        $message = "Cập nhật địa chỉ thành công!";
    }

    // 4. Hủy đặt bàn
    if (isset($_POST['cancel_booking'])) {
        $bk_id = $_POST['booking_id'];
        // Kiểm tra thời gian (phải trước 24h)
        $bk = $db->prepare("SELECT booking_date FROM service_bookings WHERE id = ? AND user_id = ?");
        $bk->execute([$bk_id, $user_id]);
        $b_data = $bk->fetch();
        
        if ($b_data) {
            $b_time = strtotime($b_data['booking_date']);
            if ($b_time - time() > 86400) {
                // Thêm ghi chú tự động khi khách hủy
                $db->prepare("UPDATE service_bookings SET status = 'Cancelled', message = CONCAT(IFNULL(message,''), '\n[Hệ thống: Khách tự hủy từ trang cá nhân]') WHERE id = ?")->execute([$bk_id]);
                $message = "Đã hủy yêu cầu đặt bàn.";
            } else {
                $message = "Chỉ có thể hủy trước giờ hẹn 24 tiếng. Vui lòng gọi Hotline.";
                $msg_type = 'warning';
            }
        }
    }
    // 5. Cập nhật Gastronomy Profile
    if (isset($_POST['update_gastronomy'])) {
        $doneness = $_POST['doneness'] ?? '';
        $flavor_profile = isset($_POST['flavor_profile']) ? implode(', ', $_POST['flavor_profile']) : '';
        $fav_ingredients = isset($_POST['fav_ingredients']) ? implode(', ', $_POST['fav_ingredients']) : '';
        $disliked_arr = isset($_POST['disliked_ingredients']) ? $_POST['disliked_ingredients'] : [];
        if (!empty($_POST['other_dislikes'])) {
            $other_d_arr = array_map('trim', explode(',', $_POST['other_dislikes']));
            $disliked_arr = array_merge($disliked_arr, $other_d_arr);
        }
        $disliked_arr = array_unique(array_filter($disliked_arr));
        $disliked_ingredients = implode(', ', $disliked_arr);
        
        $allergies_arr = isset($_POST['allergies']) ? $_POST['allergies'] : [];
        if (!empty($_POST['other_allergies'])) {
            $other_arr = array_map('trim', explode(',', $_POST['other_allergies']));
            $allergies_arr = array_merge($allergies_arr, $other_arr);
        }
        $allergies_arr = array_unique(array_filter($allergies_arr));
        $allergies = implode(', ', $allergies_arr);
        
        $drink_arr = isset($_POST['drink_preferences']) ? $_POST['drink_preferences'] : [];
        $drink_preferences = implode(', ', array_unique(array_filter($drink_arr)));
        
        $db->prepare("UPDATE users SET doneness=?, flavor_profile=?, fav_ingredients=?, disliked_ingredients=?, allergies=?, drink_preferences=? WHERE id=?")
           ->execute([$doneness, $flavor_profile, $fav_ingredients, $disliked_ingredients, $allergies, $drink_preferences, $user_id]);
        $message = "Đã cập nhật Hồ sơ Khẩu vị (Culinary DNA)!";

        // Gửi thông báo Telegram cho nhà hàng
        require_once 'config/notification_helper.php';
        $uname = $_SESSION['username'] ?? 'Khách hàng';
        $msg_tele = "<b>🍽 CẬP NHẬT HỒ SƠ ẨM THỰC (DNA)</b>\n\n";
        $msg_tele .= "Khách hàng <b>@{$uname}</b> vừa cập nhật hồ sơ:\n";
        if ($doneness) $msg_tele .= "- Độ chín: $doneness\n";
        if ($flavor_profile) $msg_tele .= "- Hương vị: $flavor_profile\n";
        if ($fav_ingredients) $msg_tele .= "- Yêu thích: $fav_ingredients\n";
        if ($disliked_ingredients) $msg_tele .= "- Không thích: $disliked_ingredients\n";
        if ($allergies) $msg_tele .= "- <b>DỊ ỨNG: $allergies</b>\n";
        if ($drink_preferences) $msg_tele .= "- Đồ uống: $drink_preferences\n";
        @sendTelegramNotification($msg_tele);
    }
    // 6. Tính năng Nâng cấp VIP được chuyển sang vip_checkout.php
}
// --- LẤY DỮ LIỆU ---
$user = $db->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$current_user = $user->fetch();

$addresses = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
$addresses->execute([$user_id]);
$user_addresses = $addresses->fetchAll();

// Lịch sử đặt bàn
$booking_sql = "SELECT sb.*, rt.table_number, c.name as combo_name 
                FROM service_bookings sb 
                LEFT JOIN restaurant_tables rt ON sb.table_id = rt.id 
                LEFT JOIN combos c ON sb.combo_id = c.id 
                WHERE sb.user_id = ? ";

if ($status_filter === 'upcoming') {
    $booking_sql .= " AND sb.status IN ('Pending', 'Confirmed') AND sb.booking_date >= NOW() ";
} elseif ($status_filter === 'completed') {
    $booking_sql .= " AND (sb.status = 'Completed' OR (sb.status IN ('Pending', 'Confirmed') AND sb.booking_date < NOW())) ";
} else {
    $booking_sql .= " AND sb.status = 'Cancelled' ";
}
$booking_sql .= " ORDER BY sb.booking_date " . ($status_filter === 'upcoming' ? 'ASC' : 'DESC');

$bookings_stmt = $db->prepare($booking_sql);
$bookings_stmt->execute([$user_id]);
$user_bookings = $bookings_stmt->fetchAll();

include __DIR__ . '/views/client/layouts/header.php';

?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">





<style id="custom-layer-theme">
    /* Lớp 1: Nền Đen ngoài cùng */
    .profile-wrap {
        background: #000000 !important;
        color: #ffffff !important;
        min-height: 100vh;
        position: relative;
    }
    
    /* Chữ bên ngoài (Tên, tổng chi tiêu) */
    .profile-wrap h1, .profile-wrap h2, .profile-wrap h3, .profile-wrap h4, .profile-wrap h5, .profile-wrap h6, .hero-account h2.prof-name {
        color: #ffffff !important;
    }
    .hero-account p, .hero-account .text-muted {
        color: #aaaaaa !important;
    }
    
    /* Viền avatar */
    .mx-auto.mb-3.shadow {
        border-color: #181818 !important;
        background: #181818 !important;
    }

    /* Lớp 2: Section #181818 (Bảng chính) */
    .prof-card {
        background: #181818 !important;
        box-shadow: 0 10px 40px rgba(0,0,0,0.8) !important;
        border: none !important;
        border-radius: 16px;
        color: #ffffff !important;
    }
    
    .prof-card-body, label {
        color: #e0e0e0 !important;
    }
    
    .prof-card .text-muted, .form-label.text-muted {
        color: #bbbbbb !important;
    }

    /* Tabs */
    .s-tabs {
        background: #181818 !important;
        border: 1px solid #333 !important;
    }
    .s-tab {
        color: #888 !important;
    }
    .s-tab.on {
        background: #ffffff !important;
        color: #000000 !important;
    }
    .htab {
        color: #888 !important;
    }
    .htab.active {
        color: #ffffff !important;
        border-bottom: 2px solid #ffffff !important;
    }

    /* Lớp 3: Card trắng (Thẻ con, input, dropdown bên trong) */
    .bk-card, .node-content {
        background-color: #ffffff !important;
        border: none !important;
        color: #000000 !important;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3) !important;
    }
    .bk-card .bk-meta, .bk-card .bk-date, .bk-card .text-muted, .node-content .text-muted {
        color: #555555 !important;
    }
    .node-content h1, .node-content h2, .node-content h3, .node-content h4, .node-content h5, .node-content h6 {
        color: #000000 !important;
    }
    
    /* Các input form cũng màu trắng chữ đen */
    .form-control, .form-select, .fi {
        background-color: #ffffff !important;
        color: #000000 !important;
        border: 1px solid #bbbbbb !important;
        border-radius: 0 !important;
        padding: 10px 16px !important;
    }
    .form-control:focus, .form-select:focus, .fi:focus {
        border-color: #888888 !important;
        box-shadow: 0 0 0 3px rgba(255,255,255,0.2) !important;
    }
    .form-control::placeholder {
        color: #999999 !important;
    }
    
    /* Table trong Modal */
    .modal-content {
        background: #181818 !important;
        color: #ffffff !important;
        border: 1px solid #333 !important;
    }
    .modal-body {
        background: #181818 !important;
        color: #ffffff !important;
    }
    .modal-header, .modal-footer {
        border-color: #333 !important;
    }
    .table {
        background-color: #ffffff !important;
        color: #000000 !important;
        border-radius: 8px;
        overflow: hidden;
    }
    .table th, .table td {
        background-color: #ffffff !important;
        color: #000000 !important;
        border-color: #eee !important;
    }

    /* Empty state */
    .empty-state {
        background: #ffffff !important;
        color: #000000 !important;
        border: none !important;
        border-radius: 12px;
    }
</style>

<div class="profile-wrap">
    <?php 
      $my_cover = '';
      $covers = glob('uploads/covers/cover_' . $user_id . '.*');
      if (!empty($covers)) {
          $my_cover = $covers[0] . '?v=' . time();
      } else {
          $my_cover = 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80';
      }
      
      $default_name = urlencode($current_user['full_name'] ?: $current_user['username'] ?: 'U');
      $my_av = 'https://ui-avatars.com/api/?name=' . $default_name . '&background=143B36&color=fff&size=128';
      if ($current_user['avatar_blob']) {
          $my_av = 'ajax/get_avatar.php?user_id=' . $current_user['id'];
      } elseif (!empty($current_user['avatar'])) {
          $my_av = (strpos($current_user['avatar'], 'http') === 0) ? $current_user['avatar'] : $current_user['avatar'];
      }
    ?>

    <!-- Full Width Banner -->
    <div style="position:absolute; top:0; left:0; right:0; height:450px; background: url('<?= $my_cover ?>') center/cover no-repeat; z-index:0;">
        <!-- Gradient overlay to fade smoothly into the black background -->
        <div style="position:absolute; inset:0; background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.8) 70%, #000000 100%);"></div>
        <form method="POST" enctype="multipart/form-data" id="cover_form">
           <input type="hidden" name="update_cover" value="1">
           <label for="cover_upload" class="btn btn-sm shadow" style="background-color: rgba(255,255,255,0.9) !important; color: #000000 !important; position:absolute; bottom:40px; right:40px; font-weight:600; opacity:0.85; transition:0.3s; cursor:pointer; z-index:10; border: none;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.85">
               <i class="bi bi-camera me-1"></i>Thay ảnh nền
           </label>
           <input type="file" name="cover_photo" id="cover_upload" style="visibility:hidden; position:absolute; width:1px; height:1px;" accept="image/*" onchange="this.form.submit()">
        </form>
    </div>

<div class="container" style="position: relative; z-index: 1;">
  <?php
  // Kiểm tra xem có đơn nào đang chờ cọc không (Chỉ hiển thị khi đơn đang Pending - tức là Admin chưa Xác nhận)
  $stmt_pending = $db->prepare("SELECT id FROM service_bookings WHERE user_id = ? AND status = 'Pending' AND deposit_amount > 0 ORDER BY id DESC LIMIT 1");
  $stmt_pending->execute([$user_id]);
  $pending_deposit = $stmt_pending->fetch(PDO::FETCH_ASSOC);

  if ($pending_deposit):
  ?>
  <div class="alert alert-warning d-flex align-items-center mt-4 mb-0 mx-auto" style="max-width: 900px; border: 1px solid #d4b06a; background-color: #fff9eb; color: #856404; border-radius: 8px; box-shadow: 0 4px 15px rgba(212, 176, 106, 0.15);">
      <i class="fas fa-exclamation-circle me-3" style="font-size: 1.5rem; color: #d4b06a;"></i>
      <div>
          <strong>Nhắc nhở:</strong> Bạn có đơn đặt bàn đang chờ thanh toán tiền cọc. 
          <a href="booking_payment.php?id=<?= $pending_deposit['id'] ?>" class="alert-link" style="color: var(--accent-burgundy); text-decoration: underline;">Bấm vào đây để Thanh toán ngay</a>!
      </div>
  </div>
  <?php endif; ?>

  <!-- ══ HERO ACCOUNT ══ -->
  <div class="hero-account text-center mb-5" style="margin-top: 120px;">
    <div>
        <div class="mx-auto mb-3 shadow" style="width:140px; height:140px; border-radius:50%; position:relative; border:4px solid #181818; background:#181818;">
           <img src="<?= $my_av ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
           
           <!-- Avatar Upload Form -->
           <form method="POST" enctype="multipart/form-data" id="avatar_form">
               <input type="hidden" name="update_avatar" value="1">
               <label for="avatar_upload" style="position:absolute; bottom:5px; right:5px; background:rgba(20, 59, 54, 0.85); color:#fff; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:0.3s; z-index:10;" onmouseover="this.style.background='rgba(20, 59, 54, 1)'" onmouseout="this.style.background='rgba(20, 59, 54, 0.85)'">
                   <i class="bi bi-camera"></i>
               </label>
               <input type="file" name="avatar_photo" id="avatar_upload" style="visibility:hidden; position:absolute; width:1px; height:1px;" accept="image/*" onchange="this.form.submit()">
           </form>
        </div>
        <h2 class="prof-name mb-2" style="font-family:'Cormorant Garamond', serif; font-size:3rem; color:#fff; font-weight:700;">
          <?= htmlspecialchars($current_user['full_name'] ?: $current_user['username']) ?>
        </h2>
        <?php
          $s3 = $db->prepare("SELECT SUM(total_amount) FROM service_bookings WHERE user_id=? AND status='Completed'"); $s3->execute([$user_id]);
          $total_spent = (float)$s3->fetchColumn();
        ?>
        <div class="total-spent mb-4" style="font-size:1.1rem; color:#aaa;">
          Tổng chi tiêu: <span style="font-weight:700; color:var(--accent-burgundy); font-size:1.4rem;"><?= number_format($total_spent, 0, ',', '.') ?> VNĐ</span>
        </div>
        <a href="?tab=profile" class="btn btn-outline-dark px-4 py-2 rounded-pill" style="font-size:13px; text-transform:uppercase; letter-spacing:1px; border-color:var(--accent-burgundy); color:var(--accent-burgundy); font-weight:600;"><i class="bi bi-pencil me-2"></i>Chỉnh sửa thông tin</a>
    </div>
  </div>

  <div class="horizontal-tabs d-flex justify-content-center flex-wrap gap-2 gap-md-4 mb-5 border-bottom pb-2">
    <a href="?tab=profile" class="htab <?= $tab=='profile'?'active':'' ?>">Thông tin</a>
    <a href="?tab=bookings" class="htab <?= $tab=='bookings'?'active':'' ?>">Lịch sử Đặt Bàn</a>
    <a href="?tab=vip" class="htab <?= $tab=='vip'?'active':'' ?>">Cột Mốc Đáng Nhớ</a>
    <a href="?tab=gastronomy" class="htab <?= $tab=='gastronomy'?'active':'' ?>">DNA Ẩm thực</a>
    <a href="?tab=security" class="htab <?= $tab=='security'?'active':'' ?>">Bảo mật</a>
    <a href="public/logout.php" class="htab text-danger"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</a>
  </div>

  <!-- ══ MAIN CONTENT ══ -->
  <div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
      <div class="prof-card" style="border:none; box-shadow: 0 10px 40px rgba(0,0,0,0.08); background: #fff;">
        <div class="prof-card-body p-4 p-md-5">

          <!-- Alert -->
          <?php if($message): ?>
          <div class="prof-alert <?= $msg_type ?>">
            <i class="bi bi-<?= $msg_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
          </div>
          <?php endif; ?>

          <!-- ── TAB: HỒ SƠ & ĐỊA CHỈ ── -->
        <?php if($tab=='profile'): ?>
        <form method="POST" enctype="multipart/form-data">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label text-muted small text-uppercase mb-1" style="letter-spacing:1px; font-size:11px;">Họ và tên *</label>
              <input type="text" class="form-control floating-input py-1" name="full_name" value="<?= htmlspecialchars($current_user['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small text-uppercase mb-1" style="letter-spacing:1px; font-size:11px;">Số điện thoại</label>
              <input type="text" class="form-control floating-input py-1" name="phone" value="<?= htmlspecialchars($current_user['phone']) ?>">
            </div>
            <div class="col-md-6 mt-3">
              <label class="form-label text-muted small text-uppercase mb-1" style="letter-spacing:1px; font-size:11px;">Email *</label>
              <input type="email" class="form-control floating-input py-1" name="email" value="<?= htmlspecialchars($current_user['email']) ?>" required>
            </div>
            <div class="col-md-6 mt-3">
              <label class="form-label text-muted small text-uppercase mb-1" style="letter-spacing:1px; font-size:11px;">Ngày sinh</label>
              <input type="date" class="form-control floating-input py-1" name="birthday" value="<?= $current_user['birthday'] ?>">
            </div>
            <div class="col-12 mt-4 text-center">
              <button type="submit" name="update_profile" class="btn btn-dark rounded-pill px-4 py-2" style="background-color: var(--accent-burgundy); border-color: var(--accent-burgundy); font-weight: 600; font-size:13px; letter-spacing: 1px;">
                <i class="bi bi-check2-circle me-1"></i>Lưu thông tin
              </button>
            </div>
          </div>
        </form>

        <hr style="opacity:0.1; margin: 25px 0;">

        <!-- ── TAB: ĐỊA CHỈ (Đã gộp) ── -->
        <h6 class="mb-4" style="color:var(--F); font-family:var(--font-serif); font-size:1.4rem;">Đầu bếp tại gia / Địa điểm phục vụ</h6>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <p class="text-muted small m-0">Quản lý địa chỉ phục vụ tại gia của bạn</p>
          <button class="btn btn-outline-dark rounded-pill px-4 py-2" style="border-color:var(--accent-burgundy); color:var(--accent-burgundy); font-weight:600; font-size:13px;"
                  data-bs-toggle="modal" data-bs-target="#addAddressModal">
            <i class="bi bi-plus me-1"></i>Thêm địa chỉ
          </button>
        </div>

        <?php if(empty($user_addresses)): ?>
        <div class="empty-state text-center py-5">
          <i class="bi bi-geo" style="font-size: 3rem; color: #ddd;"></i>
          <p class="mt-3 text-muted">Bạn chưa lưu địa chỉ nào.</p>
        </div>
        <?php else: ?>
        <?php foreach($user_addresses as $addr): ?>
        <div class="addr-card shadow-sm p-3 mb-3 bg-white rounded" style="border: 1px solid var(--border);">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="addr-icon d-flex align-items-center justify-content-center" style="width:45px; height:45px; background: rgba(168,135,70,0.1); color: var(--accent-burgundy); border-radius: 50%;">
                <i class="bi <?= $addr['address_type']=='Home'?'bi-house':'bi-briefcase' ?> fs-5"></i>
              </div>
              <div>
                <div class="d-flex align-items-center" style="font-weight:600;font-size:15px;color:var(--ink);">
                  <?= htmlspecialchars($addr['address_type']) ?>
                  <?php if($addr['is_default']): ?>
                  <span class="badge bg-warning text-dark ms-2" style="font-size:10px;">Mặc định</span>
                  <?php endif; ?>
                </div>
                <div class="text-muted" style="font-size:13px;margin-top:2px; max-width: 400px;"><?= htmlspecialchars($addr['address_detail']) ?></div>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-light rounded-circle" style="width:35px; height:35px; padding:0;" 
                      onclick="openEditAddress(<?= $addr['id'] ?>, '<?= htmlspecialchars($addr['address_type'], ENT_QUOTES) ?>', '<?= htmlspecialchars(str_replace(["\\r", "\\n"], ["\\\\r", "\\\\n"], $addr['address_detail']), ENT_QUOTES) ?>', <?= $addr['is_default'] ?>)">
                <i class="bi bi-pencil" style="color:var(--accent-burgundy);"></i>
              </button>
              <form method="POST" style="margin:0">
                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                <button type="submit" name="delete_address" class="btn btn-light rounded-circle" style="width:35px; height:35px; padding:0;"
                        onclick="return confirm('Xóa địa chỉ này?')">
                  <i class="bi bi-trash text-danger"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- ── TAB: HÀNH TRÌNH ĐẶC QUYỀN ── -->
        <?php elseif($tab=='vip'): ?>
        <?php
            // Lấy thông tin user
            $stmt = $db->prepare("SELECT visit_count, total_spent FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $u_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $visits = $u_stats['visit_count'] ?? 0;
            
            // Lấy danh sách milestones (dạng visit và spend)
            $stmt = $db->prepare("
                SELECT m.*, um.achieved_at, um.is_redeemed
                FROM milestones m
                LEFT JOIN user_milestones um ON m.id = um.milestone_id AND um.user_id = ?
                ORDER BY (um.achieved_at IS NOT NULL) DESC, m.type DESC, m.threshold ASC
            ");
            $stmt->execute([$user_id]);
            $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        

        <div class="journey-container">
            <div class="journey-header">
                <h3>Cột Mốc Đáng Nhớ</h3>
                <p>Khám phá những đặc quyền bí mật đang chờ đón bạn tại Restaurantly.</p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <div class="visit-count-badge">Đã dùng bữa: <?= $visits ?> lần</div>
                    <div class="visit-count-badge" style="background-color: var(--accent-burgundy);">Chi tiêu: <?= number_format($total_spent, 0, ',', '.') ?> đ</div>
                </div>
            </div>

            <div class="journey-timeline mt-4">
                <?php foreach($milestones as $m): 
                    $is_achieved = false;
                    $badge_text = '';
                    if ($m['type'] === 'visit') {
                        $is_achieved = ($visits >= $m['threshold']);
                        $badge_text = 'Lần thứ ' . $m['threshold'];
                    } elseif ($m['type'] === 'spend') {
                        $is_achieved = ($total_spent >= $m['threshold']);
                        $badge_text = 'Mức ' . number_format($m['threshold'], 0, ',', '.') . 'đ';
                    }
                    $status_class = $is_achieved ? 'achieved' : 'locked';
                    $icon = $is_achieved ? 'bi-award-fill' : 'bi-lock-fill';
                ?>
                <div class="journey-node <?= $status_class ?>">
                    <div class="node-icon"><i class="bi <?= $icon ?>"></i></div>
                    <div class="node-content">
                        <div class="node-badge"><?= $badge_text ?></div>
                        <h5><?= htmlspecialchars($m['reward_title']) ?></h5>
                        <p><?= htmlspecialchars($m['reward_desc']) ?></p>
                        <?php if($is_achieved && !empty($m['achieved_at'])): ?>
                            <small class="text-muted mt-3 d-block"><i class="bi bi-calendar-check"></i> Đạt được vào: <?= date('d/m/Y', strtotime($m['achieved_at'])) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($milestones)): ?>
                <p class="text-center text-muted">Chưa có cột mốc nào được thiết lập.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── TAB: GASTRONOMY PROFILE ── -->
        <?php elseif($tab=='gastronomy'): 
            $my_doneness = $current_user['doneness'] ?? '';
            $my_flavors = explode(', ', $current_user['flavor_profile'] ?? '');
            $my_favs = explode(', ', $current_user['fav_ingredients'] ?? '');
            $my_dislikes = explode(', ', $current_user['disliked_ingredients'] ?? '');
            $my_allergies = explode(', ', $current_user['allergies'] ?? '');
        ?>
        <div class="sec-tip mb-4">
          <i class="bi bi-info-circle-fill"></i>
          <div>Thiết lập DNA Ẩm thực để nhà hàng phục vụ cá nhân hóa nhất. Các món chứa thành phần dị ứng sẽ hiển thị cảnh báo đỏ trên menu!</div>
        </div>
        <form method="POST">
          <div class="row g-4">
            <div class="col-md-6 mb-2">
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-fire me-2"></i>Mức độ chín của Bò (Meat Doneness)</h6>
              <div class="d-flex flex-wrap mt-3 gap-2">
                <?php $dopts = ['Rare', 'Medium Rare', 'Medium', 'Medium Well', 'Well Done']; 
                foreach($dopts as $d): ?>
                <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer; font-size:14px; width:45%;">
                  <input type="radio" name="doneness" value="<?= $d ?>" <?= ($my_doneness == $d) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $d ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-cup-straw me-2"></i>Đồ uống yêu thích (Drink Preferences)</h6>
              <div class="d-flex flex-wrap mt-3 gap-2">
                <?php 
                $drinkopts = ['Rượu Vang (Wine)', 'Cocktail / Mocktail', 'Bia (Beer)', 'Trà / Coffee', 'Nước trái cây (Juice)', 'Có cồn (Alcoholic)', 'Không cồn (Non-alcoholic)']; 
                $my_drinks = array_map('trim', explode(',', $current_user['drink_preferences'] ?? ''));
                foreach($drinkopts as $dr): ?>
                <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer; font-size:14px; width:45%;">
                  <input type="checkbox" name="drink_preferences[]" value="<?= $dr ?>" <?= in_array($dr, $my_drinks) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $dr ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-palette me-2"></i>Phong cách Hương vị (Flavor Profile)</h6>
              <div class="d-flex flex-wrap mt-3 gap-2">
                <?php $fopts = ['Đậm vị (Bold/Rich)', 'Thanh nhẹ (Light/Fresh)', 'Umami (Ngọt tự nhiên)', 'Ít béo (Low Fat)', 'Ăn Cay (Spicy)']; 
                foreach($fopts as $f): ?>
                <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer; font-size:14px; width:45%;">
                  <input type="checkbox" name="flavor_profile[]" value="<?= $f ?>" <?= in_array($f, $my_flavors) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $f ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-star me-2"></i>Nguyên liệu yêu thích (Favorites)</h6>
              <div class="d-flex flex-wrap mt-3 gap-2">
                <?php 
                $favopts = [
                    'Bò' => 'Các loại Bò', 
                    'Nấm' => 'Các loại Nấm', 
                    'Gan ngỗng' => 'Gan ngỗng', 
                    'Trứng cá' => 'Trứng cá tầm', 
                    'Hải sản' => 'Hải sản (Seafood)'
                ]; 
                foreach($favopts as $val => $lbl): ?>
                <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer; font-size:14px; width:45%;">
                  <input type="checkbox" name="fav_ingredients[]" value="<?= $val ?>" <?= (in_array($val, $my_favs) || in_array('Bò Wagyu', $my_favs) && $val=='Bò') ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $lbl ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-x-circle me-2"></i>Không thích ăn (Dislikes)</h6>
              <div class="d-flex flex-wrap mt-3 gap-2">
                <?php 
                $disopts = ['Hành lá', 'Rau mùi', 'Hành tây', 'Tỏi', 'Ớt chuông', 'Tiêu xanh', 'Thịt mỡ']; 
                $my_dislikes_arr = array_map('trim', explode(',', $current_user['disliked_ingredients'] ?? ''));
                $other_dislikes = array_filter($my_dislikes_arr, function($d) use ($disopts) {
                    return !in_array($d, $disopts) && !empty($d);
                });
                $other_dislikes_str = implode(', ', $other_dislikes);
                
                foreach($disopts as $dis): ?>
                <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer; font-size:14px; width:45%;">
                  <input type="checkbox" name="disliked_ingredients[]" value="<?= $dis ?>" <?= in_array($dis, $my_dislikes_arr) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $dis ?>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="mt-3" style="padding-right: 15px;">
                <label class="small fw-bold" style="color:#555;">Không thích khác (nếu có, phân cách bằng dấu phẩy)</label>
                <input type="text" name="other_dislikes" class="form-control form-control-sm mt-1" value="<?= htmlspecialchars($other_dislikes_str) ?>" placeholder="Ví dụ: Hành phi, Cà rốt...">
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:#d64545; font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Dị ứng Y Tế (Allergies)</h6>
              <div class="d-flex flex-wrap mt-3 gap-2">
                <?php 
                $algopts = ['Sữa', 'Trứng', 'Đậu phộng', 'Đậu nành', 'Lúa mì / Gluten', 'Hải sản', 'Cá', 'Hải sản có vỏ', 'Hải sản thân mềm', 'Mè / Vừng', 'Mù tạt', 'Quả hạch', 'Sulphites', 'Đậu Lupin']; 
                $my_allergies = array_map('trim', explode(',', $current_user['allergies'] ?? ''));
                $other_allergies = array_filter($my_allergies, function($alg) use ($algopts) {
                    return !in_array($alg, $algopts) && !empty($alg);
                });
                $other_allergies_str = implode(', ', $other_allergies);
                foreach($algopts as $alg): ?>
                <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer; font-size:14px; color:#d64545; width:45%; font-weight:500;">
                  <input type="checkbox" name="allergies[]" value="<?= $alg ?>" <?= in_array($alg, $my_allergies) ? 'checked' : '' ?> style="accent-color:#d64545;"> <?= $alg ?>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="mt-3">
                <label class="small fw-bold" style="color:#d64545;">Dị ứng khác (nếu có, phân cách bằng dấu phẩy)</label>
                <input type="text" name="other_allergies" class="form-control form-control-sm mt-1" value="<?= htmlspecialchars($other_allergies_str) ?>" placeholder="Ví dụ: Dâu tây, Mật ong...">
              </div>
            </div>

            <div class="col-12 text-end mt-4 pt-3" style="border-top:1px solid var(--border);">
              <button type="submit" name="update_gastronomy" class="btn-prim">
                <i class="bi bi-check2-all me-1"></i>Lưu Hồ sơ Khẩu vị & Dị ứng
              </button>
            </div>
          </div>
        </form>



        <!-- ── TAB: ĐẶT BÀN ── -->
        <?php elseif($tab=='bookings'): ?>
        <div class="s-tabs">
          <a href="?tab=bookings&status=upcoming"   class="s-tab <?= $status_filter=='upcoming'?'on':'' ?>">
            <i class="bi bi-clock me-1"></i>Sắp tới
          </a>
          <a href="?tab=bookings&status=completed"  class="s-tab <?= $status_filter=='completed'?'on':'' ?>">
            <i class="bi bi-check2-circle me-1"></i>Đã hoàn thành
          </a>
          <a href="?tab=bookings&status=cancelled"  class="s-tab <?= $status_filter=='cancelled'?'on':'' ?>">
            <i class="bi bi-x-circle me-1"></i>Đã hủy
          </a>
        </div>

        <?php if(empty($user_bookings)): ?>
        <div class="empty-state">
          <i class="bi bi-calendar-x"></i>
          <p>Không có đơn đặt bàn nào trong mục này.</p>
        </div>
        <?php else: ?>
        <?php foreach($user_bookings as $b):
          $badge_class = match($b['status']) {
            'Confirmed' => 'confirmed', 'Pending' => 'pending',
            'Cancelled' => 'cancelled', default => 'completed'
          };
          $badge_label = match($b['status']) {
            'Confirmed' => 'Đã xác nhận', 'Pending' => 'Đang chờ',
            'Cancelled' => 'Đã hủy', default => 'Hoàn thành'
          };
        ?>
        <div class="bk-card">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-10" style="margin-bottom:10px">
            <div>
              <div class="bk-id">#BK-<?= $b['id'] ?></div>
              <div class="bk-date"><?= date('H:i · d/m/Y', strtotime($b['booking_date'])) ?></div>
              <div class="bk-meta">
                <?= strtoupper($b['service_type']) ?>
                · <?= $b['guests'] ?> khách
                <?php if($b['table_id']): ?> · Bàn <?= $b['table_number'] ?><?php endif; ?>
                <?php if($b['combo_id']): ?> · <?= htmlspecialchars($b['combo_name']) ?><?php endif; ?>
              </div>
            </div>
            <span class="bk-badge <?= $badge_class ?>"><?= $badge_label ?></span>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <button type="button" onclick="viewBookingDetails(<?= $b['id'] ?>)" class="btn-out" style="font-size:12px;padding:7px 14px;">
              <i class="bi bi-eye"></i> Xem chi tiết
            </button>

            <?php if($status_filter=='upcoming'): ?>
            <form method="POST" style="margin:0">
              <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
              <button type="submit" name="cancel_booking" class="btn-danger-out"
                      style="font-size:12px;padding:7px 14px;"
                      onclick="return confirm('Hủy đặt bàn này?')">
                <i class="bi bi-x"></i> Hủy đặt
              </button>
            </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>


        <!-- ── TAB: BẢO MẬT ── -->
        <?php elseif($tab=='security'): ?>
        <div class="sec-tip">
          <i class="bi bi-info-circle-fill"></i>
          <div>Mật khẩu nên có ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số để bảo mật tài khoản tốt hơn.</div>
        </div>
        <form method="POST">
          <div class="row g-3" style="max-width:460px">
            <div class="col-12">
              <label class="fl">Mật khẩu cũ <span style="color:#d64545">*</span></label>
              <input type="password" name="old_password" class="fi" required placeholder="••••••••">
            </div>
            <div class="col-12">
              <label class="fl">Mật khẩu mới <span style="color:#d64545">*</span></label>
              <input type="password" name="new_password" class="fi" required placeholder="••••••••">
            </div>
            <div class="col-12">
              <label class="fl">Xác nhận mật khẩu mới <span style="color:#d64545">*</span></label>
              <input type="password" name="confirm_password" class="fi" required placeholder="••••••••">
            </div>
            <div class="col-12 pt-2 d-flex align-items-center flex-wrap gap-3">
              <button type="submit" name="change_password" class="btn-prim">
                <i class="bi bi-lock me-1"></i>Cập nhật mật khẩu
              </button>
              <a href="public/forgot_password.php" style="color:var(--F); font-size:14px; text-decoration:underline;">
                Quên mật khẩu cũ? Nhận mã qua Email
              </a>
            </div>
          </div>
        </form>
        <?php endif; ?>

      </div><!-- prof-card-body -->
    </div><!-- prof-card -->

    <!-- ══ CTA SECTION ══ -->
    <div class="cta-section text-center mt-5 mb-5 pb-4">
      <p class="mb-4 text-muted" style="font-family:'Cormorant Garamond', serif; font-size:1.4rem; font-style:italic;">Tiếp tục trải nghiệm tinh hoa ẩm thực cùng Restaurantly</p>
      <div class="d-flex justify-content-center flex-wrap gap-3">
        <a href="booking_service.php?type=table" class="btn btn-dark rounded-pill px-5 py-3" style="background-color: var(--accent-burgundy); border-color: var(--accent-burgundy); font-weight: 600; letter-spacing: 1px;"><i class="bi bi-calendar-check me-2"></i>ĐẶT BÀN NGAY</a>
        <a href="menu.php" class="btn btn-outline-dark rounded-pill px-5 py-3" style="font-weight: 600; letter-spacing: 1px; border-color:var(--accent-burgundy); color:var(--accent-burgundy);"><i class="bi bi-book me-2"></i>KHÁM PHÁ MENU</a>
      </div>
    </div>

  </div>

</div><!-- row -->
</div><!-- container -->
</div><!-- profile-wrap -->

<!-- Modal thêm địa chỉ -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-geo-alt me-2 text-muted"></i>Thêm địa chỉ mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body p-4" style="display:flex;flex-direction:column;gap:16px;">
          <div>
            <label class="fl">Loại địa chỉ</label>
            <select name="address_type" class="fi" style="height:auto;padding:11px 14px;appearance:auto">
              <option value="Home">Nhà riêng</option>
              <option value="Office">Văn phòng</option>
              <option value="Other">Khác</option>
            </select>
          </div>
          <div>
            <label class="fl">Địa chỉ chi tiết <span style="color:#d64545">*</span></label>
            <textarea name="address_detail" class="fi" rows="3" required
                      placeholder="Số nhà, tên đường, phường, quận, thành phố..."
                      style="resize:vertical;line-height:1.6"></textarea>
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
            <input type="checkbox" name="is_default" style="accent-color:var(--F);width:16px;height:16px;">
            Đặt làm địa chỉ mặc định
          </label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-out" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" name="add_address" class="btn-prim">Lưu địa chỉ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal sửa địa chỉ -->
<div class="modal fade" id="editAddressModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2 text-muted"></i>Sửa địa chỉ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="address_id" id="edit_address_id">
        <div class="modal-body p-4" style="display:flex;flex-direction:column;gap:16px;">
          <div>
            <label class="fl">Loại địa chỉ</label>
            <select name="address_type" id="edit_address_type" class="fi" style="height:auto;padding:11px 14px;appearance:auto">
              <option value="Home">Nhà riêng</option>
              <option value="Office">Văn phòng</option>
              <option value="Other">Khác</option>
            </select>
          </div>
          <div>
            <label class="fl">Địa chỉ chi tiết <span style="color:#d64545">*</span></label>
            <textarea name="address_detail" id="edit_address_detail" class="fi" rows="3" required
                      placeholder="Số nhà, tên đường, phường, quận, thành phố..."
                      style="resize:vertical;line-height:1.6"></textarea>
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
            <input type="checkbox" name="is_default" id="edit_is_default" style="accent-color:var(--F);width:16px;height:16px;">
            Đặt làm địa chỉ mặc định
          </label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-out" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" name="edit_address" class="btn-prim">Cập nhật</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL XEM CHI TIẾT ĐƠN HÀNG -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border:none; border-radius:12px; overflow:hidden;">
      <div class="modal-header" style="background:var(--F); color:#fff; border-bottom:none;">
        <h5 class="modal-title" style="font-family:'Cormorant Garamond', serif;"><i class="bi bi-receipt me-2"></i>Chi Tiết Đơn Đặt Bàn</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="bookingDetailsContent" style="padding:25px; background: #fff;">
        <div class="text-center p-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải dữ liệu...</div>
      </div>
    </div>
  </div>
</div>



<script>
function viewBookingDetails(id) {
    var myModal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
    myModal.show();
    
    document.getElementById('bookingDetailsContent').innerHTML = '<div class="text-center p-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải dữ liệu...</div>';
    
    fetch('ajax_get_booking_details.php?booking_id=' + id)
        .then(response => response.text())
        .then(html => {
            document.getElementById('bookingDetailsContent').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('bookingDetailsContent').innerHTML = '<div class="text-danger text-center">Lỗi khi tải dữ liệu!</div>';
        });
}

function previewImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar_preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openEditAddress(id, type, detail, isDefault) {
    document.getElementById('edit_address_id').value = id;
    document.getElementById('edit_address_type').value = type;
    document.getElementById('edit_address_detail').value = detail;
    document.getElementById('edit_is_default').checked = isDefault === 1;
    new bootstrap.Modal(document.getElementById('editAddressModal')).show();
}
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
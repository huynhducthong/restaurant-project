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
        $disliked_ingredients = isset($_POST['disliked_ingredients']) ? implode(', ', $_POST['disliked_ingredients']) : '';
        $allergies = isset($_POST['allergies']) ? implode(', ', $_POST['allergies']) : '';
        
        $db->prepare("UPDATE users SET doneness=?, flavor_profile=?, fav_ingredients=?, disliked_ingredients=?, allergies=? WHERE id=?")
           ->execute([$doneness, $flavor_profile, $fav_ingredients, $disliked_ingredients, $allergies, $user_id]);
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
        @sendTelegramNotification($msg_tele);
    }
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

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ══ TOKENS ══ */
:root{
  --F:      #143B36;        /* forest — dùng ít */
  --F-lt:   #1a4d46;
  --F-pale: #e8f0ef;        /* forest rất nhạt — bg highlight */
  --gold:   #c8933a;
  --gold-lt:#f0e0c0;
  --bg:     #f7f5f1;         /* kem ấm — nền chính */
  --bg2:    #ffffff;
  --ink:    #1a1814;
  --ink2:   #4a4640;
  --muted:  #8a8480;
  --border: #e8e2d8;
  --r:      14px;
  --sh:     0 2px 16px rgba(26,24,20,.07);
  --sh-lg:  0 8px 32px rgba(26,24,20,.1);
}

*{box-sizing:border-box;}
body{
  background:var(--bg);
  color:var(--ink);
  font-family:'Be Vietnam Pro',sans-serif;
}

/* ══ WRAPPER ══ */
.profile-wrap{
  padding:110px 0 80px;
  min-height:100vh;
}

/* ══ SIDEBAR ══ */
.prof-sidebar{
  background:var(--bg2);
  border-radius:var(--r);
  box-shadow:var(--sh);
  overflow:hidden;
  border:1px solid var(--border);
}

/* Avatar section — accent #143B36 */
.prof-top{
  background:linear-gradient(145deg, var(--F) 0%, var(--F-lt) 100%);
  padding:32px 24px 28px;
  text-align:center;
  position:relative;
}
.prof-top::after{
  content:'';position:absolute;bottom:-1px;left:0;right:0;
  height:24px;background:var(--bg2);
  border-radius:var(--r) var(--r) 0 0;
}
.av-ring{
  width:96px;height:96px;border-radius:50%;
  border:3px solid rgba(255,255,255,.4);
  overflow:hidden;margin:0 auto 14px;
  background:rgba(255,255,255,.1);
  box-shadow:0 4px 20px rgba(0,0,0,.2);
}
.av-ring img{width:100%;height:100%;object-fit:cover;}
.prof-name{
  font-family:'Playfair Display',serif;font-weight:600;
  font-size:1.1rem;color:#fff;margin:0 0 4px;
}
.prof-email{font-size:12px;color:rgba(255,255,255,.6);margin:0;}

/* Sidebar nav */
.prof-nav{padding:16px 0 12px;}
.prof-nav a{
  display:flex;align-items:center;gap:12px;
  padding:13px 24px;font-size:13px;font-weight:500;
  color:var(--ink2);text-decoration:none;
  transition:all .2s;border-left:3px solid transparent;
}
.prof-nav a i{width:18px;text-align:center;font-size:14px;color:var(--muted);transition:.2s;}
.prof-nav a:hover{background:#f9f6f0;color:var(--F);border-left-color:rgba(20,59,54,.25);}
.prof-nav a:hover i{color:var(--F);}
.prof-nav a.on{
  background:var(--F-pale);color:var(--F);
  border-left-color:var(--F);font-weight:600;
}
.prof-nav a.on i{color:var(--F);}
.prof-nav a.logout{color:#d64545;}
.prof-nav a.logout i{color:#d64545;}
.prof-nav a.logout:hover{background:#fff5f5;border-left-color:#d64545;}
.prof-nav-sep{height:1px;background:var(--border);margin:8px 16px;}

/* Quick stats */
.prof-stats{
  display:grid;grid-template-columns:1fr 1fr;
  gap:1px;background:var(--border);
  border-top:1px solid var(--border);
}
.stat-cell{
  background:var(--bg2);padding:14px 10px;text-align:center;
}
.stat-val{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--F);font-weight:600;}
.stat-lbl{font-size:10px;color:var(--muted);margin-top:2px;letter-spacing:.04em;}

/* ══ CONTENT CARD ══ */
.prof-card{
  background:var(--bg2);
  border-radius:var(--r);
  box-shadow:var(--sh);
  border:1px solid var(--border);
  overflow:hidden;
  min-height:520px;
}
.prof-card-head{
  padding:22px 32px 0;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:12px;
}
.pc-icon{
  width:36px;height:36px;border-radius:9px;
  background:var(--F-pale);border:1px solid rgba(20,59,54,.15);
  display:flex;align-items:center;justify-content:center;
  font-size:16px;color:var(--F);flex-shrink:0;
}
.pc-title{
  font-family:'Playfair Display',serif;font-size:1.1rem;
  font-weight:600;color:var(--ink);margin:0;padding-bottom:18px;
  border-bottom:2px solid var(--F);display:inline-block;
  line-height:1.2;
}
.prof-card-body{padding:28px 32px;}

/* ══ FORM FIELDS ══ */
.fl{
  display:block;font-size:11px;font-weight:600;
  letter-spacing:.09em;text-transform:uppercase;
  color:var(--muted);margin-bottom:6px;
}
.fi{
  width:100%;padding:11px 14px;
  border:1.5px solid var(--border);border-radius:9px;
  font-family:'Be Vietnam Pro',sans-serif;font-size:14px;
  color:var(--ink);background:var(--bg);
  outline:none;transition:.2s;
}
.fi:focus{border-color:var(--F);box-shadow:0 0 0 3px rgba(20,59,54,.08);background:#fff;}
.fi::placeholder{color:rgba(26,24,20,.3);}

/* Avatar upload in form */
.av-upload{
  display:inline-flex;flex-direction:column;align-items:center;
  cursor:pointer;gap:8px;
}
.av-upload-ring{
  width:96px;height:96px;border-radius:50%;
  border:2px dashed var(--border);overflow:hidden;
  background:var(--bg);position:relative;
  transition:border-color .2s;
}
.av-upload-ring:hover{border-color:var(--F);}
.av-upload-ring img{width:100%;height:100%;object-fit:cover;}
.av-upload-ring::after{
  content:'📷';position:absolute;inset:0;
  background:rgba(20,59,54,.5);color:#fff;font-size:20px;
  display:flex;align-items:center;justify-content:center;
  opacity:0;transition:.2s;border-radius:50%;
}
.av-upload-ring:hover::after{opacity:1;}
.av-upload-lbl{font-size:12px;color:var(--F);font-weight:500;}

/* ══ BUTTON ══ */
.btn-prim{
  padding:11px 28px;background:var(--F);
  color:#fff;border:none;border-radius:9px;
  font-family:'Be Vietnam Pro',sans-serif;
  font-size:13px;font-weight:600;cursor:pointer;
  transition:all .2s;letter-spacing:.03em;
}
.btn-prim:hover{background:var(--F-lt);transform:translateY(-1px);}
.btn-prim:active{transform:none;}

.btn-out{
  padding:10px 22px;background:transparent;
  border:1.5px solid var(--border);border-radius:9px;
  color:var(--ink2);font-size:13px;font-weight:500;
  cursor:pointer;transition:.2s;
  font-family:'Be Vietnam Pro',sans-serif;
  text-decoration:none;display:inline-flex;align-items:center;gap:6px;
}
.btn-out:hover{border-color:var(--ink2);color:var(--ink);}

.btn-danger-out{
  padding:10px 20px;background:transparent;
  border:1.5px solid #fcc;border-radius:9px;
  color:#d64545;font-size:13px;cursor:pointer;transition:.2s;
  font-family:'Be Vietnam Pro',sans-serif;
}
.btn-danger-out:hover{background:#fff5f5;border-color:#d64545;}

/* ══ ALERT ══ */
.prof-alert{
  display:flex;align-items:center;gap:10px;
  padding:13px 16px;border-radius:9px;margin-bottom:20px;
  font-size:13px;font-weight:500;
}
.prof-alert.success{background:#edf7f0;border:1px solid #a8d5b8;color:#1a5c30;}
.prof-alert.danger{background:#fff0f0;border:1px solid #f5b8b8;color:#8b2020;}
.prof-alert.warning{background:#fffbf0;border:1px solid #f0d890;color:#7a5c10;}

/* ══ STATUS TABS ══ */
.s-tabs{display:flex;gap:6px;margin-bottom:24px;flex-wrap:wrap;}
.s-tab{
  padding:8px 18px;border-radius:9px;font-size:12px;font-weight:500;
  color:var(--ink2);background:var(--bg);border:1.5px solid var(--border);
  text-decoration:none;transition:.2s;
}
.s-tab:hover{border-color:var(--F);color:var(--F);}
.s-tab.on{background:var(--F);color:#fff;border-color:var(--F);}

/* ══ BOOKING CARD ══ */
.bk-card{
  background:var(--bg);border:1px solid var(--border);
  border-radius:12px;padding:20px 22px;
  margin-bottom:12px;
  border-left:3px solid var(--F);
  transition:box-shadow .2s;
}
.bk-card:hover{box-shadow:var(--sh);}
.bk-id{font-size:10px;letter-spacing:.12em;color:var(--F);font-weight:600;text-transform:uppercase;margin-bottom:3px;}
.bk-date{font-family:'Playfair Display',serif;font-size:1rem;color:var(--ink);font-weight:600;}
.bk-meta{font-size:12px;color:var(--muted);margin-top:4px;}
.bk-badge{
  display:inline-block;font-size:10px;font-weight:600;padding:4px 10px;border-radius:6px;
  letter-spacing:.06em;text-transform:uppercase;
}
.bk-badge.confirmed{background:#e8f5ed;color:#1a7a40;}
.bk-badge.pending{background:#fff8e8;color:#8a6010;}
.bk-badge.cancelled{background:#fff0f0;color:#a02020;}
.bk-badge.completed{background:#e8f0ff;color:#2040a0;}

/* ══ ADDRESS CARD ══ */
.addr-card{
  background:var(--bg);border:1px solid var(--border);
  border-radius:12px;padding:18px 20px;margin-bottom:10px;
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;transition:box-shadow .2s;
}
.addr-card:hover{box-shadow:var(--sh);}
.addr-icon{
  width:38px;height:38px;border-radius:9px;flex-shrink:0;
  background:var(--F-pale);display:flex;align-items:center;justify-content:center;
  font-size:16px;color:var(--F);
}
.addr-default{
  font-size:9px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;
  padding:2px 8px;background:var(--F-pale);color:var(--F);
  border:1px solid rgba(20,59,54,.2);border-radius:4px;margin-left:8px;
}

/* ══ SECURITY TIPS ══ */
.sec-tip{
  background:var(--F-pale);border:1px solid rgba(20,59,54,.15);
  border-radius:10px;padding:14px 16px;margin-bottom:20px;
  font-size:13px;color:var(--F);display:flex;gap:10px;align-items:flex-start;
}
.sec-tip i{font-size:16px;margin-top:1px;flex-shrink:0;}

/* ══ EMPTY STATE ══ */
.empty-state{
  text-align:center;padding:60px 20px;
  color:var(--muted);
}
.empty-state i{font-size:48px;opacity:.25;display:block;margin-bottom:14px;}
.empty-state p{font-size:14px;margin:0;}

/* ══ MODAL ══ */
.modal-content{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--r);box-shadow:var(--sh-lg);
}
.modal-header{border-bottom:1px solid var(--border);padding:20px 24px;}
.modal-title{font-family:'Playfair Display',serif;font-size:1rem;color:var(--ink);font-weight:600;}
.modal-footer{border-top:1px solid var(--border);padding:16px 24px;gap:8px;}
.btn-close{opacity:.4;}

/* ══ RESPONSIVE ══ */
@media(max-width:768px){
  .profile-wrap{padding:90px 0 60px;}
  .prof-card-body{padding:20px;}
  .prof-card-head{padding:16px 20px 0;}
}
</style>

<div class="profile-wrap">
<div class="container">
<div class="row g-4">

  <!-- ══ SIDEBAR ══ -->
  <div class="col-lg-3 col-md-4">
    <div class="prof-sidebar">

      <!-- Avatar + tên -->
      <div class="prof-top">
        <div class="av-ring">
          <?php 
            $my_av = 'public/assets/img/default-avatar.png';
            if ($current_user['avatar_blob']) {
                $my_av = 'ajax/get_avatar.php?user_id=' . $current_user['id'];
            } elseif (!empty($current_user['avatar'])) {
                $my_av = (strpos($current_user['avatar'], 'http') === 0) ? $current_user['avatar'] : $current_user['avatar'];
            }
          ?>
          <img src="<?= $my_av ?>" alt="Avatar" onerror="this.src='public/assets/img/default-avatar.png'">
        </div>
        <h5 class="prof-name"><?= htmlspecialchars($current_user['full_name'] ?: $current_user['username']) ?></h5>
        <p class="prof-email"><?= htmlspecialchars($current_user['email']) ?></p>
      </div>

      <!-- Nav -->
      <nav class="prof-nav">
        <a href="?tab=profile"   class="<?= $tab=='profile'   ? 'on':'' ?>">
          <i class="bi bi-person-circle"></i> Hồ sơ của tôi
        </a>
        <a href="?tab=gastronomy" class="<?= $tab=='gastronomy' ? 'on':'' ?>">
          <i class="bi bi-star"></i> Hồ sơ Ẩm thực VIP
        </a>
        <a href="?tab=bookings"  class="<?= $tab=='bookings'  ? 'on':'' ?>">
          <i class="bi bi-calendar2-check"></i> Lịch sử đặt bàn
        </a>
        <a href="?tab=addresses" class="<?= $tab=='addresses' ? 'on':'' ?>">
          <i class="bi bi-geo-alt"></i> Sổ địa chỉ
        </a>
        <a href="?tab=security"  class="<?= $tab=='security'  ? 'on':'' ?>">
          <i class="bi bi-shield-check"></i> Bảo mật
        </a>
        <div class="prof-nav-sep"></div>
        <a href="public/logout.php" class="logout">
          <i class="bi bi-box-arrow-left"></i> Đăng xuất
        </a>
      </nav>

      <!-- Quick stats -->
      <?php
        $s1 = $db->prepare("SELECT COUNT(*) FROM service_bookings WHERE user_id=?"); $s1->execute([$user_id]);
        $s2 = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id=?"); $s2->execute([$user_id]);
        $total_bookings = (int)$s1->fetchColumn();
        $total_addr     = (int)$s2->fetchColumn();
      ?>
      <div class="prof-stats">
        <div class="stat-cell">
          <div class="stat-val"><?= $total_bookings ?></div>
          <div class="stat-lbl">Lần đặt bàn</div>
        </div>
        <div class="stat-cell">
          <div class="stat-val"><?= $total_addr ?></div>
          <div class="stat-lbl">Địa chỉ</div>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ MAIN CONTENT ══ -->
  <div class="col-lg-9 col-md-8">
    <div class="prof-card">

      <!-- Head -->
      <div class="prof-card-head">
        <?php
          $icons = ['profile'=>'bi-person','gastronomy'=>'bi-star','bookings'=>'bi-calendar2-check','addresses'=>'bi-geo-alt','security'=>'bi-shield-lock'];
          $titles = ['profile'=>'Thông tin cá nhân','gastronomy'=>'Hồ sơ Ẩm thực VIP','bookings'=>'Lịch sử đặt bàn','addresses'=>'Sổ địa chỉ','security'=>'Bảo mật & Mật khẩu'];
        ?>
        <div class="pc-icon"><i class="bi <?= $icons[$tab]??'bi-person' ?>"></i></div>
        <h4 class="pc-title"><?= $titles[$tab]??'Thông tin' ?></h4>
      </div>

      <div class="prof-card-body">

        <!-- Alert -->
        <?php if($message): ?>
        <div class="prof-alert <?= $msg_type ?>">
          <i class="bi bi-<?= $msg_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
          <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- ── TAB: HỒ SƠ ── -->
        <?php if($tab=='profile'): ?>
        <form method="POST" enctype="multipart/form-data">
          <div class="text-center mb-32" style="margin-bottom:28px">
            <label for="avatar_input" class="av-upload">
              <div class="av-upload-ring">
                <?php 
                  $my_av_form = 'public/assets/img/default-avatar.png';
                  if ($current_user['avatar_blob']) {
                      $my_av_form = 'ajax/get_avatar.php?user_id=' . $current_user['id'];
                  } elseif (!empty($current_user['avatar'])) {
                      $my_av_form = (strpos($current_user['avatar'], 'http') === 0) ? $current_user['avatar'] : $current_user['avatar'];
                  }
                ?>
                <img src="<?= $my_av_form ?>" id="avatar_preview" onerror="this.src='public/assets/img/default-avatar.png'">
              </div>
              <span class="av-upload-lbl"><i class="bi bi-camera me-1"></i>Đổi ảnh đại diện</span>
            </label>
            <input type="file" name="avatar" id="avatar_input" class="d-none" accept="image/*" onchange="previewImg(this)">
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="fl">Họ và tên <span style="color:#d64545">*</span></label>
              <input type="text" name="full_name" class="fi"
                     value="<?= htmlspecialchars($current_user['full_name']) ?>" required
                     placeholder="Nguyễn Văn A">
            </div>
            <div class="col-md-6">
              <label class="fl">Số điện thoại</label>
              <input type="tel" name="phone" class="fi"
                     value="<?= htmlspecialchars($current_user['phone']) ?>"
                     placeholder="09xx xxx xxx">
            </div>
            <div class="col-md-6">
              <label class="fl">Email <span style="color:#d64545">*</span></label>
              <input type="email" name="email" class="fi"
                     value="<?= htmlspecialchars($current_user['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="fl">Ngày sinh</label>
              <input type="date" name="birthday" class="fi"
                     value="<?= htmlspecialchars($current_user['birthday'] ?? '') ?>">
            </div>
            <div class="col-12 pt-2">
              <button type="submit" name="update_profile" class="btn-prim">
                <i class="bi bi-check2 me-1"></i>Lưu thay đổi
              </button>
            </div>
          </div>
        </form>

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
            <div class="col-md-12 mb-2">
              <h6 style="color:var(--gold); font-family:'Playfair Display',serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-fire me-2"></i>Mức độ chín của Bò (Meat Doneness)</h6>
              <div class="d-flex flex-wrap gap-3 mt-3">
                <?php $dopts = ['Rare', 'Medium Rare', 'Medium', 'Medium Well', 'Well Done']; 
                foreach($dopts as $d): ?>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer; font-size:14px;">
                  <input type="radio" name="doneness" value="<?= $d ?>" <?= ($my_doneness == $d) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $d ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--gold); font-family:'Playfair Display',serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-palette me-2"></i>Phong cách Hương vị (Flavor Profile)</h6>
              <div class="d-flex flex-column gap-2 mt-3">
                <?php $fopts = ['Đậm vị (Bold/Rich)', 'Thanh nhẹ (Light/Fresh)', 'Umami (Ngọt tự nhiên)', 'Ít béo (Low Fat)', 'Ăn Cay (Spicy)']; 
                foreach($fopts as $f): ?>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer; font-size:14px;">
                  <input type="checkbox" name="flavor_profile[]" value="<?= $f ?>" <?= in_array($f, $my_flavors) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $f ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--gold); font-family:'Playfair Display',serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-star me-2"></i>Nguyên liệu yêu thích (Favorites)</h6>
              <div class="d-flex flex-column gap-2 mt-3">
                <?php $favopts = ['Bò Wagyu', 'Nấm Truffle', 'Gan ngỗng (Foie Gras)', 'Trứng cá tầm (Caviar)', 'Hải sản (Seafood)']; 
                foreach($favopts as $fv): ?>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer; font-size:14px;">
                  <input type="checkbox" name="fav_ingredients[]" value="<?= $fv ?>" <?= in_array($fv, $my_favs) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $fv ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--gold); font-family:'Playfair Display',serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-x-circle me-2"></i>Không thích ăn (Dislikes)</h6>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <?php $disopts = ['Hành lá', 'Rau mùi', 'Hành tây', 'Tỏi', 'Ớt chuông', 'Tiêu xanh', 'Thịt mỡ']; 
                foreach($disopts as $dis): ?>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer; font-size:14px; width:45%;">
                  <input type="checkbox" name="disliked_ingredients[]" value="<?= $dis ?>" <?= in_array($dis, $my_dislikes) ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $dis ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:#d64545; font-family:'Playfair Display',serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Dị ứng Y Tế (Allergies)</h6>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <?php $algopts = ['Đậu phộng', 'Gluten', 'Sữa', 'Hải sản có vỏ', 'Trứng', 'Đậu nành']; 
                foreach($algopts as $alg): ?>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer; font-size:14px; color:#d64545; width:45%; font-weight:500;">
                  <input type="checkbox" name="allergies[]" value="<?= $alg ?>" <?= in_array($alg, $my_allergies) ? 'checked' : '' ?> style="accent-color:#d64545;"> <?= $alg ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-12 text-end mt-4 pt-3" style="border-top:1px solid var(--border);">
              <button type="submit" name="update_gastronomy" class="btn-prim">
                <i class="bi bi-check2-all me-1"></i>Lưu Hồ sơ Khẩu vị & Dị ứng
              </button>
            </div>
          </div>
        </form>

        <hr style="border-color:var(--border); margin:40px 0;">
        <h5 style="font-family:'Playfair Display',serif; color:var(--F); font-weight:600; margin-bottom:20px;">
          <i class="bi bi-journal-bookmark me-2"></i>Nhật ký Ẩm thực (Gastronomy Journal)
        </h5>
        
        <div style="border-left:2px solid var(--F-pale); padding-left:20px; margin-left:10px;">
          <?php
            // Lấy danh sách các đơn đã hoàn thành
            $journal_stmt = $db->prepare("SELECT sb.*, rt.table_number, c.name as combo_name 
                                          FROM service_bookings sb 
                                          LEFT JOIN restaurant_tables rt ON sb.table_id = rt.id 
                                          LEFT JOIN combos c ON sb.combo_id = c.id 
                                          WHERE sb.user_id = ? AND sb.status = 'Completed' 
                                          ORDER BY sb.booking_date DESC");
            $journal_stmt->execute([$user_id]);
            $journals = $journal_stmt->fetchAll();
            
            if (empty($journals)):
          ?>
            <p class="text-muted" style="font-size:13px; margin:0;">Bạn chưa có trải nghiệm dùng bữa nào được ghi lại.</p>
          <?php else: foreach($journals as $j): ?>
            <div style="position:relative; margin-bottom:25px;">
              <!-- Timeline dot -->
              <div style="position:absolute; left:-27px; top:0; width:12px; height:12px; border-radius:50%; background:var(--F); border:2px solid #fff; box-shadow:0 0 0 2px var(--F-pale);"></div>
              
              <div style="font-size:12px; font-weight:600; color:var(--F); margin-bottom:5px;">
                <?= date('d/m/Y - H:i', strtotime($j['booking_date'])) ?>
              </div>
              <div class="bk-card" style="margin:0; border-left:none; padding:15px; background:var(--bg2);">
                <div style="font-size:14px; color:var(--ink); font-weight:500; margin-bottom:6px;">
                  Trải nghiệm tại <?= $j['table_id'] ? 'Bàn '.$j['table_number'] : 'Nhà hàng' ?>
                </div>
                <ul style="margin:0; padding-left:15px; font-size:13px; color:var(--ink2);">
                  <?php if($j['combo_id']): ?>
                    <li>Thưởng thức: <strong><?= htmlspecialchars($j['combo_name']) ?></strong></li>
                  <?php endif; ?>
                  <?php if($j['guests']): ?>
                    <li>Đi cùng: <?= $j['guests'] ?> người</li>
                  <?php endif; ?>
                  <?php if($j['event_type']): ?>
                    <li>Dịp: <?= htmlspecialchars($j['event_type']) ?></li>
                  <?php endif; ?>
                  <?php if($j['decor_package']): ?>
                    <li>Không gian: <?= htmlspecialchars($j['decor_package']) ?></li>
                  <?php endif; ?>
                  <?php if($j['has_cake'] || $j['has_flower']): ?>
                    <li>Dịch vụ kèm: <?= implode(', ', array_filter([$j['has_cake']?'Bánh kem':null, $j['has_flower']?'Hoa tươi':null])) ?></li>
                  <?php endif; ?>
                  <li>Mã đặt bàn: #BK-<?= $j['id'] ?></li>
                </ul>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

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
            <a href="admin/export_pdf.php?id=<?= $b['id'] ?>" target="_blank" class="btn-out" style="font-size:12px;padding:7px 14px;">
              <i class="bi bi-file-earmark-pdf"></i> Xem PDF
            </a>
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

        <!-- ── TAB: ĐỊA CHỈ ── -->
        <?php elseif($tab=='addresses'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <p class="text-muted small m-0">Quản lý địa chỉ giao hàng và nhận sách</p>
          <button class="btn-prim" style="padding:9px 18px;font-size:12px"
                  data-bs-toggle="modal" data-bs-target="#addAddressModal">
            <i class="bi bi-plus me-1"></i>Thêm địa chỉ
          </button>
        </div>

        <?php if(empty($user_addresses)): ?>
        <div class="empty-state">
          <i class="bi bi-geo"></i>
          <p>Bạn chưa lưu địa chỉ nào.</p>
        </div>
        <?php else: ?>
        <?php foreach($user_addresses as $addr): ?>
        <div class="addr-card">
          <div class="d-flex align-items-center gap-14" style="gap:14px">
            <div class="addr-icon">
              <i class="bi <?= $addr['address_type']=='Home'?'bi-house':'bi-briefcase' ?>"></i>
            </div>
            <div>
              <div class="d-flex align-items-center" style="font-weight:600;font-size:14px;color:var(--ink);">
                <?= htmlspecialchars($addr['address_type']) ?>
                <?php if($addr['is_default']): ?>
                <span class="addr-default">Mặc định</span>
                <?php endif; ?>
              </div>
              <div style="font-size:13px;color:var(--ink2);margin-top:3px"><?= htmlspecialchars($addr['address_detail']) ?></div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn-out" style="padding:7px 14px;font-size:12px" 
                    onclick="openEditAddress(<?= $addr['id'] ?>, '<?= htmlspecialchars($addr['address_type'], ENT_QUOTES) ?>', '<?= htmlspecialchars(str_replace(["\r", "\n"], ["\\r", "\\n"], $addr['address_detail']), ENT_QUOTES) ?>', <?= $addr['is_default'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" style="margin:0">
              <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
              <button type="submit" name="delete_address"
                      class="btn-danger-out" style="padding:7px 14px;font-size:12px"
                      onclick="return confirm('Xóa địa chỉ này?')">
                <i class="bi bi-trash"></i>
              </button>
            </form>
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
              <option value="Home">🏠 Nhà riêng</option>
              <option value="Office">💼 Văn phòng</option>
              <option value="Other">📍 Khác</option>
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
              <option value="Home">🏠 Nhà riêng</option>
              <option value="Office">💼 Văn phòng</option>
              <option value="Other">📍 Khác</option>
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

<script>
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
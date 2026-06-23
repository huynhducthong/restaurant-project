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

require_once __DIR__ . '/app/models/UserVip.php';
$userVipModel = new UserVip($db);

$user_id = $_SESSION['user_id'];
$current_vip = $userVipModel->getActiveVipStatus($user_id);
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
        
        $allergies_arr = isset($_POST['allergies']) ? $_POST['allergies'] : [];
        if (!empty($_POST['other_allergies'])) {
            $other_arr = array_map('trim', explode(',', $_POST['other_allergies']));
            $allergies_arr = array_merge($allergies_arr, $other_arr);
        }
        $allergies_arr = array_unique(array_filter($allergies_arr));
        $allergies = implode(', ', $allergies_arr);
        
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

// Lấy danh sách VIP plans
require_once __DIR__ . '/app/models/VipPlan.php';
$vipPlanModel = new VipPlan($db);
$plans = $vipPlanModel->getAllPlans();

include __DIR__ . '/views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
/* ══ TOKENS ══ */
:root{
  --F:      #A88746;        /* Gold */
  --F-lt:   #d4b06a;        /* Bright gold hover */
  --F-pale: rgba(168, 135, 70, 0.1);  /* Gold pale bg highlight */
  --accent-burgundy:   #A88746;
  --accent-burgundy-lt: rgba(168, 135, 70, 0.3);
  --bg:     #F5F2ED;         /* Light beige background */
  --bg2:    #ffffff;         /* White bg for cards/frames */
  --ink:    #222222;         /* Black text */
  --ink2:   #555555;         /* Dark grey text */
  --muted:  #888888;         /* Muted text */
  --border: rgba(0, 0, 0, 0.1); /* Faint gray border */
  --r:      12px;            /* Slightly softer radius for glass */
  --sh:     0 8px 32px rgba(0,0,0,0.06);
  --sh-lg:  0 15px 40px rgba(0,0,0,0.1);
}

*{box-sizing:border-box;}
body{
  background:var(--bg);
  color:var(--ink);
  font-family:'Be Vietnam Pro',sans-serif;
}

.vip-crown-badge {
    background: linear-gradient(135deg, #FFD700 0%, #D4AF37 50%, #B8860B 100%);
    color: #1a1a1a;
    font-size: 0.65rem;
    padding: 3px 8px;
    border-radius: 20px;
    margin-left: 6px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 2px 8px rgba(212,175,55,0.4);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    vertical-align: middle;
}
.vip-crown-badge svg { margin-bottom: 2px; }

/* ══ WRAPPER ══ */
.profile-wrap{
  padding:160px 0 40px;
  min-height:100vh;
  background: radial-gradient(circle at top center, rgba(230, 92, 0, 0.08) 0%, var(--bg) 70%);
}

/* ══ SIDEBAR ══ */
.prof-sidebar{
  background:var(--bg2);
  border-radius:var(--r);
  box-shadow:var(--sh);
  overflow:hidden;
  border:1px solid var(--border);
}

/* Avatar section */
.prof-top{
  background: #ffffff;
  padding:32px 24px 28px;
  text-align:center;
  position:relative;
  border-bottom: 1px solid var(--border);
}
.av-ring{
  width:110px;height:110px;border-radius:50%;
  border:2px solid var(--F);
  overflow:hidden;margin:0 auto 16px;
  background:#ffffff;
  box-shadow:0 0 20px rgba(168, 135, 70, 0.2);
}
.av-ring img{width:100%;height:100%;object-fit:cover;}
.prof-name{
  font-family:'Cormorant Garamond', serif;font-weight:600;
  font-size:1.1rem;color:var(--ink);margin:0 0 4px;
}
.prof-email{font-size:12px;color:var(--ink2);margin:0;}

/* Sidebar nav */
.prof-nav{padding:16px 0 12px;}
.prof-nav a{
  display:flex;align-items:center;gap:12px;
  padding:13px 24px;font-size:13px;font-weight:500;
  color:var(--ink2);text-decoration:none;
  transition:all .2s;border-left:3px solid transparent;
}
.prof-nav a i{width:18px;text-align:center;font-size:14px;color:var(--muted);transition:.2s;}
.prof-nav a:hover{background:var(--F-pale);color:var(--F);border-left-color:var(--F);}
.prof-nav a:hover i{color:var(--F);}
.prof-nav a.on{
  background:var(--F-pale);color:var(--F);
  border-left-color:var(--F);font-weight:600;
}
.prof-nav a.on i{color:var(--F);}
.prof-nav a.logout{color:#d64545;}
.prof-nav a.logout i{color:#d64545;}
.prof-nav a.logout:hover{background:rgba(214, 69, 69, 0.1);border-left-color:#d64545;}
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
.stat-val{font-family:'Cormorant Garamond', serif;font-size:1.4rem;color:var(--F);font-weight:600;}
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
  background:var(--F-pale);border:1px solid rgba(230, 92, 0, 0.3);
  display:flex;align-items:center;justify-content:center;
  font-size:16px;color:var(--F);flex-shrink:0;
}
.pc-title{
  font-family:'Cormorant Garamond', serif;font-size:1.1rem;
  font-weight:600;color:var(--ink);margin:0;padding-bottom:18px;
  border-bottom:2px solid var(--F);display:inline-block;
  line-height:1.2;
}
.prof-card-body{padding:28px 32px;}

/* ══ FORM FIELDS ══ */
.fl{
  display:block;font-size:11px;font-weight:600;
  letter-spacing:.09em;text-transform:uppercase;
  color:var(--ink2);margin-bottom:8px;
}
.fi{
  width:100%;padding:12px 16px;
  border:1px solid rgba(0,0,0,0.1);border-radius:9px;
  font-family:'Be Vietnam Pro',sans-serif;font-size:14px;
  color:var(--ink);background:#fcfcfc;
  outline:none;transition:all .3s ease;
}
.fi:focus{border-color:var(--F);box-shadow:0 0 0 3px rgba(168, 135, 70, 0.15);background: #ffffff;}
.fi::placeholder{color:rgba(0,0,0,.3);}

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
  background:rgba(230, 92, 0, 0.6);color:#fff;font-size:20px;
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
.prof-alert.success{background:rgba(40, 167, 69, 0.1);border:1px solid #28a745;color:#28a745;}
.prof-alert.danger{background:rgba(220, 53, 69, 0.1);border:1px solid #dc3545;color:#dc3545;}
.prof-alert.warning{background:rgba(255, 193, 7, 0.1);border:1px solid #ffc107;color:#ffc107;}

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
.bk-date{font-family:'Cormorant Garamond', serif;font-size:1rem;color:var(--ink);font-weight:600;}
.bk-meta{font-size:12px;color:var(--muted);margin-top:4px;}
.bk-badge{
  display:inline-block;font-size:10px;font-weight:600;padding:4px 10px;border-radius:6px;
  letter-spacing:.06em;text-transform:uppercase;
}
.bk-badge.confirmed{background:rgba(52, 152, 219, 0.15);color:#3498db;}
.bk-badge.pending{background:rgba(255, 193, 7, 0.15);color:#f39c12;}
.bk-badge.cancelled{background:rgba(220, 53, 69, 0.15);color:#e74c3c;}
.bk-badge.completed{background:rgba(40, 167, 69, 0.15);color:#2ecc71;}

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
  border:1px solid rgba(230, 92, 0, 0.3);border-radius:4px;margin-left:8px;
}

/* ══ SECURITY TIPS ══ */
.sec-tip{
  background:var(--F-pale);border:1px solid rgba(230, 92, 0, 0.3);
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
.modal-title{font-family:'Cormorant Garamond', serif;font-size:1rem;color:var(--ink);font-weight:600;}
.modal-footer{border-top:1px solid var(--border);padding:16px 24px;gap:8px;}
.btn-close{opacity:.4;}

/* ══ RESPONSIVE ══ */
@media(max-width:768px){
  .profile-wrap{padding:120px 0 40px;}
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
            $default_name = urlencode($current_user['full_name'] ?: $current_user['username'] ?: 'U');
            $my_av = 'https://ui-avatars.com/api/?name=' . $default_name . '&background=143B36&color=fff&size=128';
            if ($current_user['avatar_blob']) {
                $my_av = 'ajax/get_avatar.php?user_id=' . $current_user['id'];
            } elseif (!empty($current_user['avatar'])) {
                $my_av = (strpos($current_user['avatar'], 'http') === 0) ? $current_user['avatar'] : $current_user['avatar'];
            }
          ?>
          <img src="<?= $my_av ?>" alt="Avatar">
        </div>
        <h5 class="prof-name">
            <?= htmlspecialchars($current_user['full_name'] ?: $current_user['username']) ?>
            <?php if($current_vip): ?>
                <span class="vip-crown-badge" title="Thành viên VIP">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>
                    </svg>
                    VIP CROWN
                </span>
            <?php endif; ?>
        </h5>
        <p class="prof-email"><?= htmlspecialchars($current_user['email']) ?></p>
      </div>

      <!-- Nav -->
      <nav class="prof-nav">
        <a href="?tab=profile"   class="<?= $tab=='profile'   ? 'on':'' ?>">
          <i class="bi bi-person-circle"></i> Hồ sơ của tôi
        </a>
        <a href="?tab=vip" class="<?= $tab=='vip' ? 'on':'' ?>">
          <i class="bi bi-award"></i> Thẻ VIP
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
          $icons = ['profile'=>'bi-person','vip'=>'bi-award','gastronomy'=>'bi-star','bookings'=>'bi-calendar2-check','addresses'=>'bi-geo-alt','security'=>'bi-shield-lock'];
          $titles = ['profile'=>'Thông tin cá nhân','vip'=>'Thẻ VIP','gastronomy'=>'Hồ sơ Ẩm thực VIP','bookings'=>'Lịch sử đặt bàn','addresses'=>'Sổ địa chỉ','security'=>'Bảo mật & Mật khẩu'];
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
                  $default_name = urlencode($current_user['full_name'] ?: $current_user['username'] ?: 'U');
                  $my_av_form = 'https://ui-avatars.com/api/?name=' . $default_name . '&background=143B36&color=fff&size=128';
                  if ($current_user['avatar_blob']) {
                      $my_av_form = 'ajax/get_avatar.php?user_id=' . $current_user['id'];
                  } elseif (!empty($current_user['avatar'])) {
                      $my_av_form = (strpos($current_user['avatar'], 'http') === 0) ? $current_user['avatar'] : $current_user['avatar'];
                  }
                ?>
                <img src="<?= $my_av_form ?>" id="avatar_preview">
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

        <!-- ── TAB: THẺ VIP ── -->
        <?php elseif($tab=='vip'): ?>
        <style>
            .checkout-container {
                background: #FFFFFF;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.08);
                overflow: hidden;
                display: flex;
                margin-top: 20px;
                border: 1px solid rgba(168, 135, 70, 0.2);
            }
            .plan-summary {
                background: #F5F2ED;
                color: #222222;
                padding: 40px;
                width: 45%;
                position: relative;
            }
            .plan-summary::after {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: linear-gradient(135deg, rgba(168, 135, 70, 0.15) 0%, rgba(0,0,0,0) 100%);
                pointer-events: none;
            }
            .plan-summary h2 {
                font-family: 'Cormorant Garamond', serif;
                color: #A88746;
                font-size: 1.8rem;
                margin-bottom: 20px;
                font-weight: 600;
            }
            .price-tag {
                font-size: 2.8rem;
                font-weight: 700;
                font-family: 'Cormorant Garamond', serif;
                color: #222222;
                margin-bottom: 5px;
                line-height: 1;
            }
            .duration-tag {
                font-size: 0.95rem;
                color: rgba(0,0,0,0.7);
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px dashed rgba(0,0,0,0.15);
            }
            .benefits-list {
                list-style: none;
                padding: 0; margin: 0;
            }
            .benefits-list li {
                position: relative;
                padding-left: 30px;
                margin-bottom: 15px;
                font-size: 0.95rem;
                line-height: 1.5;
                color: rgba(0,0,0,0.8);
            }
            .benefits-list li i {
                position: absolute; left: 0; top: 3px;
                color: #A88746; font-size: 1.1rem;
            }
            .payment-form {
                width: 55%;
                padding: 40px;
                background: #FFFFFF;
                display: none; /* Ẩn form lúc đầu */
            }
            .payment-form.active {
                display: block;
                animation: fadeInRight 0.4s ease forwards;
            }
            @keyframes fadeInRight {
                from { opacity: 0; transform: translateX(20px); }
                to { opacity: 1; transform: translateX(0); }
            }
            .payment-form h3 {
                font-family: 'Cormorant Garamond', serif;
                font-size: 1.4rem;
                color: #222222;
                margin-bottom: 20px;
            }
            .payment-methods {
                display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;
            }
            .payment-method-card {
                border: 1px solid rgba(168, 135, 70, 0.2); border-radius: 8px; padding: 15px;
                cursor: pointer; transition: all 0.2s; position: relative;
            }
            .payment-method-card:hover { border-color: #A88746; background: rgba(168, 135, 70, 0.05); }
            .payment-method-card input[type="radio"] { display: none; }
            .payment-method-card input[type="radio"]:checked + .method-content::before {
                content: '\f058'; font-family: 'FontAwesome'; position: absolute;
                top: -10px; right: -10px; color: #A88746; background: #FFFFFF;
                border-radius: 50%; font-size: 1.4rem; line-height: 1;
            }
            .payment-method-card input[type="radio"]:checked + .method-content { color: #A88746; }
            .payment-method-card:has(input[type="radio"]:checked) {
                border-color: #A88746; background: rgba(168, 135, 70, 0.05); box-shadow: 0 4px 10px rgba(168, 135, 70, 0.2);
            }
            .method-content {
                display: flex; flex-direction: column; align-items: center; gap: 8px;
                color: #222222; font-weight: 500; font-size: 0.9rem; text-align: center;
            }
            .method-content i { font-size: 1.8rem; color: #A88746; }
            .card-details {
                background: #F5F2ED; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(168, 135, 70, 0.2);
            }
            .form-control-lux {
                width: 100%; padding: 10px 15px; border: 1px solid rgba(168, 135, 70, 0.3); border-radius: 6px; font-size: 0.95rem; transition: 0.3s;
            }
            .form-control-lux:focus { outline: none; border-color: #A88746; box-shadow: 0 0 0 3px rgba(168, 135, 70, 0.2); background: #ffffff; color: #222222; }
            .btn-pay {
                background: linear-gradient(135deg, #A88746, #d4b06a); color: #fff; border: none; width: 100%; padding: 14px;
                font-size: 1.05rem; font-weight: 600; border-radius: 8px; cursor: pointer; transition: 0.3s;
            }
            .btn-pay:hover { background: linear-gradient(135deg, #d4b06a, #A88746); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(168, 135, 70, 0.4); }
            
            .btn-open-pay {
                background: linear-gradient(135deg, #A88746, #d4b06a); color: #fff; border: none; width: 100%; padding: 15px;
                font-size: 1.1rem; font-weight: 600; border-radius: 8px; cursor: pointer; transition: 0.3s;
            }
            .btn-open-pay:hover { background: linear-gradient(135deg, #d4b06a, #A88746); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(168, 135, 70, 0.4); }
            
            .current-vip-alert {
                background: rgba(168, 135, 70, 0.1); border: 1px solid #A88746; color: #A88746;
                padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;
            }
            .current-vip-alert i { font-size: 24px; color: #A88746; }
            
            @media (max-width: 768px) {
                .checkout-container { flex-wrap: wrap; }
                .plan-summary { width: 100% !important; }
                .payment-form { width: 100%; }
            }
        </style>

        <?php if($current_vip): ?>
            <div class="current-vip-alert" style="background: linear-gradient(135deg, #2b1f0c 0%, #0a1f1c 100%); border: 1px solid #c8933a; color: #f0e0c0; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#c8933a" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">
                        <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>
                    </svg>
                    <div>
                        <h5 class="mb-1 fw-bold" style="color: #c8933a;">Đặc quyền VIP CROWN đang được kích hoạt!</h5>
                        <p class="mb-0 small" style="color: #d1c8b4;">Chiết khấu <?= $current_vip['discount_percent'] ?>% toàn hệ thống. Đặc quyền sẽ kết thúc vào: <strong style="color:#fff;"><?= date('d/m/Y', strtotime($current_vip['end_date'])) ?></strong></p>
                    </div>
                </div>
                <form action="config/process_vip_cancel.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy gia hạn gói VIP này? Mọi đặc quyền của gói sẽ kết thúc ngay lập tức.');">
                    <button type="submit" name="cancel_vip" class="btn btn-outline-danger btn-sm px-4 py-2" style="border-radius: 8px; font-weight: 600; white-space: nowrap;">
                        <i class="fas fa-times-circle me-1"></i> Hủy Gia Hạn
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php 
            // Do hệ thống chỉ có 1 gói VIP, ta lấy gói đầu tiên
            $plan = $plans[0] ?? null;
            if ($plan): 
        ?>
            <div class="checkout-container" id="vipCheckoutFrame">
                
                <!-- Cột trái: Tóm tắt gói -->
                <div class="plan-summary" id="planSummaryCol" style="width: 100%; transition: width 0.4s ease;">
                    <h2>Gói <?= htmlspecialchars($plan['name']) ?></h2>
                    <div class="price-tag"><?= number_format($plan['price'], 0, ',', '.') ?>đ</div>
                    <div class="duration-tag">Thời hạn sử dụng: <strong><?= $plan['duration_days'] ?> ngày</strong></div>
                    
                    <ul class="benefits-list">
                        <li><i class="fas fa-check-circle"></i> Giảm giá trực tiếp <strong><?= floatval($plan['discount_percent']) ?>%</strong> cho mọi hóa đơn.</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Ưu tiên đặt bàn, chọn không gian đẹp nhất</strong> <br><span style="font-size: 0.85rem; color: #aaa;">(Đảm bảo có bàn trong các dịp lễ, tuỳ chọn các vị trí VIP cạnh cửa sổ/không gian riêng tư).</span></li>
                        <li><i class="fas fa-check-circle"></i> <strong>Huy hiệu VIP Đặc Quyền</strong></li>
                        <li><i class="fas fa-check-circle"></i> Mở khóa dịch vụ cao cấp: <strong>Đầu Bếp Tại Gia</strong> và <strong>Thiết Kế Riêng</strong>.</li>
                    </ul>
                    
                    <button type="button" class="btn-open-pay" id="btnOpenPay" onclick="openPaymentForm()">
                        <?= $current_vip ? 'Gia hạn Gói này' : 'Đăng Ký & Thanh Toán Ngay' ?>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <!-- Cột phải: Form thanh toán -->
                <div class="payment-form" id="paymentFormCol">
                    <h3>Xác nhận thanh toán</h3>
                    
                    <form id="paymentForm" method="POST" action="config/process_vip_payment.php">
                        <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                        
                        <div class="payment-methods">
                            <label class="payment-method-card">
                                <input type="radio" name="payment_method" value="credit_card" checked onchange="toggleCardDetails(true)">
                                <div class="method-content">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Thẻ Tín Dụng</span>
                                </div>
                            </label>
                            <label class="payment-method-card">
                                <input type="radio" name="payment_method" value="bank_transfer" onchange="toggleCardDetails(false)">
                                <div class="method-content">
                                    <i class="fas fa-university"></i>
                                    <span>Chuyển Khoản</span>
                                </div>
                            </label>
                        </div>
                        
                        <div id="cardDetails" class="card-details">
                            <div class="mb-3">
                                <input type="text" class="form-control-lux" placeholder="Tên in trên thẻ (VD: NGUYEN VAN A)" required id="cardName">
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control-lux" placeholder="Số thẻ (XXXX XXXX XXXX XXXX)" required id="cardNumber">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" class="form-control-lux" placeholder="MM/YY" required id="cardExp">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control-lux" placeholder="CVV" required id="cardCvv">
                                </div>
                            </div>
                        </div>
                        
                        <div id="bankTransferDetails" class="card-details" style="display: none; text-align: center;">
                            <i class="fas fa-qrcode mb-3" style="font-size: 3rem; color: #c8933a;"></i>
                            <p class="mb-0 text-muted" style="font-size: 0.9rem;">Hệ thống sẽ chuyển hướng bạn đến cổng thanh toán VNPay/Momo để quét mã QR.</p>
                        </div>

                        <div class="d-flex justify-content-between mb-3 fw-bold" style="font-size: 1.1rem; color: #143B36; border-top: 1px solid #eee; padding-top: 15px;">
                            <span>Tổng thanh toán:</span>
                            <span style="color: #c8933a;"><?= number_format($plan['price'], 0, ',', '.') ?>đ</span>
                        </div>
                        
                        <button type="submit" class="btn-pay" id="btnSubmit">
                            <span id="btnText">Hoàn Tất Thanh Toán</span>
                            <i class="fas fa-check-circle ms-1" id="btnIcon"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <script>
                function openPaymentForm() {
                    // Thu gọn cột trái, hiện cột phải
                    if (window.innerWidth > 768) {
                        document.getElementById('planSummaryCol').style.width = '45%';
                    }
                    document.getElementById('btnOpenPay').style.display = 'none';
                    document.getElementById('paymentFormCol').classList.add('active');
                }
                
                function toggleCardDetails(isCard) {
                    document.getElementById('cardDetails').style.display = isCard ? 'block' : 'none';
                    document.getElementById('bankTransferDetails').style.display = isCard ? 'none' : 'block';
                    
                    // Bật tắt required cho các trường thẻ
                    const cardInputs = ['cardName', 'cardNumber', 'cardExp', 'cardCvv'];
                    cardInputs.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.required = isCard;
                    });
                }
                
                document.getElementById('paymentForm').addEventListener('submit', function(e) {
                    const btn = document.getElementById('btnSubmit');
                    const text = document.getElementById('btnText');
                    const icon = document.getElementById('btnIcon');
                    
                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.8';
                    text.innerHTML = 'Đang xử lý giao dịch...';
                    icon.className = 'fas fa-spinner fa-spin ms-1';
                });
            </script>
        <?php endif; ?>

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
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-fire me-2"></i>Mức độ chín của Bò (Meat Doneness)</h6>
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
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-palette me-2"></i>Phong cách Hương vị (Flavor Profile)</h6>
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
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-star me-2"></i>Nguyên liệu yêu thích (Favorites)</h6>
              <div class="d-flex flex-column gap-2 mt-3">
                <?php 
                $favopts = [
                    'Bò' => 'Các loại Bò (Wagyu, Kobe...)', 
                    'Nấm' => 'Các loại Nấm (Truffle...)', 
                    'Gan ngỗng' => 'Gan ngỗng (Foie Gras)', 
                    'Trứng cá' => 'Trứng cá tầm (Caviar)', 
                    'Hải sản' => 'Hải sản (Seafood)'
                ]; 
                foreach($favopts as $val => $lbl): ?>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer; font-size:14px;">
                  <input type="checkbox" name="fav_ingredients[]" value="<?= $val ?>" <?= (in_array($val, $my_favs) || in_array('Bò Wagyu', $my_favs) && $val=='Bò') ? 'checked' : '' ?> style="accent-color:var(--F);"> <?= $lbl ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-md-6 mb-2">
              <h6 style="color:var(--accent-burgundy); font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-x-circle me-2"></i>Không thích ăn (Dislikes)</h6>
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
              <h6 style="color:#d64545; font-family:'Cormorant Garamond', serif; font-size:1.1rem; border-bottom:1px dashed var(--border); padding-bottom:10px;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Dị ứng Y Tế (Allergies)</h6>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <?php 
                $algopts = ['Sữa', 'Trứng', 'Đậu phộng', 'Đậu nành', 'Lúa mì / Gluten', 'Hải sản', 'Cá', 'Hải sản có vỏ', 'Hải sản thân mềm', 'Mè / Vừng', 'Mù tạt', 'Quả hạch', 'Sulphites', 'Đậu Lupin']; 
                $my_allergies = array_map('trim', explode(',', $current_user['allergies'] ?? ''));
                $other_allergies = array_filter($my_allergies, function($alg) use ($algopts) {
                    return !in_array($alg, $algopts) && !empty($alg);
                });
                $other_allergies_str = implode(', ', $other_allergies);
                foreach($algopts as $alg): ?>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer; font-size:14px; color:#d64545; width:45%; font-weight:500;">
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

<!-- MODAL XEM CHI TIẾT ĐƠN HÀNG -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border:none; border-radius:12px; overflow:hidden;">
      <div class="modal-header" style="background:var(--F); color:#fff; border-bottom:none;">
        <h5 class="modal-title" style="font-family:'Cormorant Garamond', serif;"><i class="bi bi-receipt me-2"></i>Chi Tiết Đơn Đặt Bàn</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="bookingDetailsContent" style="padding:25px; background: #FFFFFF;">
        <div class="text-center p-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải dữ liệu...</div>
      </div>
    </div>
  </div>
</div>

<style>
.spin { display:inline-block; animation:spin 1s linear infinite; }
@keyframes spin { 100% { transform:rotate(360deg); } }
</style>

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
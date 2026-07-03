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

include __DIR__ . '/views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
/* ══ TOKENS ══ */
:root{
  --F:      #A88746;        /* Gold */
  --F-lt:   #C4A25C;        /* Bright gold hover */
  --F-pale: rgba(168, 135, 70, 0.04);  /* Extremely subtle gold highlight */
  --bg:     #FAFAF8;         /* Alabaster/Beige very light */
  --bg2:    #ffffff;         /* Pure white */
  --ink:    #1A1A1A;         /* Charcoal black */
  --ink2:   #4A4A4A;         /* Soft dark grey */
  --muted:  #999999;         /* Refined grey */
  --border: rgba(168, 135, 70, 0.18); /* Soft gold border */
  --r:      6px;             /* Sharper, elegant corners */
  --sh:     0 2px 14px rgba(0,0,0,0.03); /* Subtle depth */
  --sh-lg:  0 8px 30px rgba(168, 135, 70, 0.06); /* Luxurious ambient glow */
}

*{box-sizing:border-box;}
body{
  background:var(--bg);
  color:var(--ink);
  font-family:'Source Sans 3',sans-serif; /* Cleaner elegant sans-serif */
  letter-spacing: 0.01em;
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
  width:120px;height:120px;border-radius:50%;
  border: 3px solid transparent;
  background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, var(--F), #E8D099);
  background-origin: border-box;
  background-clip: content-box, border-box;
  padding: 4px;
  overflow:hidden;margin:0 auto 20px;
  box-shadow: 0 10px 30px rgba(168, 135, 70, 0.15);
}
.av-ring img{width:100%;height:100%;object-fit:cover; border-radius: 50%;}
.prof-name{
  font-family:'Cormorant Garamond', serif;font-weight:600;
  font-size:1.1rem;color:var(--ink);margin:0 0 4px;
}
.prof-email{font-size:12px;color:var(--ink2);margin:0;}

/* Sidebar nav */
.prof-nav{padding:12px 0 16px;}
.prof-nav a{
  display:flex;align-items:center;gap:12px;
  padding:14px 28px;font-size:12px;font-weight:400;
  letter-spacing: 0.05em; text-transform: uppercase;
  color:var(--ink2);text-decoration:none;
  transition:all .3s ease;border-left:2px solid transparent;
}
.prof-nav a i{width:18px;text-align:center;font-size:14px;color:var(--muted);transition:.3s;}
.prof-nav a:hover{background:var(--F-pale);color:var(--ink);border-left-color:var(--F-lt);}
.prof-nav a:hover i{color:var(--F);}
.prof-nav a.on{
  background:var(--F-pale);color:var(--F);
  border-left-color:var(--F);font-weight:600;
}
.prof-nav a.on i{color:var(--F);}
.prof-nav a.logout{color:#A94442;}
.prof-nav a.logout i{color:#A94442;}
.prof-nav a.logout:hover{background:rgba(169, 68, 66, 0.05);border-left-color:#A94442;}
.prof-nav-sep{height:1px;background:var(--border);margin:12px 20px;}

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
  display:block;font-size:10px;font-weight:600;
  letter-spacing:.12em;text-transform:uppercase;
  color:var(--ink2);margin-bottom:6px;
}
.fi{
  width:100%;padding:14px 18px;
  border:1px solid var(--border);border-radius:var(--r);
  font-family:'Source Sans 3',sans-serif;font-size:14px;
  color:var(--ink);background:#ffffff;
  outline:none;transition:all .4s ease;
}
.fi:focus{border-color:var(--F);box-shadow:0 0 0 4px rgba(168, 135, 70, 0.08);}
.fi::placeholder{color:rgba(0,0,0,.25); font-style: italic;}

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
  padding:12px 32px;background:var(--F);
  color:#fff;border:none;border-radius:var(--r);
  font-family:'Source Sans 3',sans-serif;
  font-size:12px;font-weight:600;cursor:pointer;
  transition:all .4s ease;letter-spacing:.06em;text-transform: uppercase;
}
.btn-prim:hover{background:var(--F-lt); box-shadow: 0 4px 12px rgba(168, 135, 70, 0.3);}
.btn-prim:active{transform:translateY(1px);}

.btn-out{
  padding:10px 26px;background:transparent;
  border:1px solid var(--border);border-radius:var(--r);
  color:var(--ink2);font-size:12px;font-weight:500;
  cursor:pointer;transition:.4s;letter-spacing: .04em;
  font-family:'Source Sans 3',sans-serif;text-transform: uppercase;
  text-decoration:none;display:inline-flex;align-items:center;gap:8px;
}
.btn-out:hover{border-color:var(--F);color:var(--F);background: var(--F-pale);}

.btn-danger-out{
  padding:10px 26px;background:transparent;
  border:1px solid rgba(169, 68, 66, 0.3);border-radius:var(--r);
  color:#A94442;font-size:12px;cursor:pointer;transition:.4s;
  font-family:'Source Sans 3',sans-serif;text-transform: uppercase;letter-spacing:.04em;
}
.btn-danger-out:hover{background:rgba(169, 68, 66, 0.05);border-color:#A94442;}

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

<style>
:root {
  --accent-burgundy: #A88746;
}
.htab {
  font-family: 'Source Sans 3', sans-serif;
  font-size: 14px;

  font-weight: 600;
  color: var(--muted);
  padding: 10px 20px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 2px solid transparent;
  transition: all 0.3s ease;
  text-decoration: none;
}
.htab:hover { color: var(--accent-burgundy); }
.htab.active { color: var(--accent-burgundy); border-bottom-color: var(--accent-burgundy); }
.floating-input {
  border: none;
  border-bottom: 1px solid var(--border);
  border-radius: 0;
  padding-left: 0;
  background: transparent;
  box-shadow: none !important;
}
.floating-input:focus {
  border-bottom-color: var(--accent-burgundy);
}
.hero-account { animation: fadeInDown 0.6s ease; }
@keyframes fadeInDown { from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:translateY(0);} }

/* Override Topbar for light background */
#topbar { color: var(--ink) !important; }
#topbar i { color: var(--accent-burgundy) !important; }
#topbar .lang-switcher a { color: var(--ink) !important; }
#topbar .lang-switcher span { color: var(--muted) !important; }
</style>

<div class="profile-wrap" style="background-color: #faf9f6;">
<div class="container">

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
  <div class="hero-account text-center mb-5 mt-2">
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
    <div class="hero-cover mb-4" style="height:220px; background: url('<?= $my_cover ?>') center/cover no-repeat; border-radius: 16px; position:relative; box-shadow: inset 0 -50px 100px -20px rgba(0,0,0,0.5);">
        <form method="POST" enctype="multipart/form-data" id="cover_form">
           <input type="hidden" name="update_cover" value="1">
           <label for="cover_upload" class="btn btn-sm btn-light shadow" style="position:absolute; bottom:15px; right:15px; font-weight:600; opacity:0.85; transition:0.3s; cursor:pointer; z-index:10;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.85">
               <i class="bi bi-camera me-1"></i>Thay ảnh nền
           </label>
           <input type="file" name="cover_photo" id="cover_upload" style="visibility:hidden; position:absolute; width:1px; height:1px;" accept="image/*" onchange="this.form.submit()">
        </form>
    </div>

    <div style="position:relative; margin-top:-90px; z-index:2;">
        <div class="mx-auto mb-3 shadow" style="width:140px; height:140px; border-radius:50%; position:relative; border:4px solid #fff; background:#fff;">
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
        <h2 class="prof-name mb-2" style="font-family:'Cormorant Garamond', serif; font-size:3rem; color:var(--ink); font-weight:700;">
          <?= htmlspecialchars($current_user['full_name'] ?: $current_user['username']) ?>
        </h2>
        <?php
          $s3 = $db->prepare("SELECT SUM(total_amount) FROM service_bookings WHERE user_id=? AND status='Completed'"); $s3->execute([$user_id]);
          $total_spent = (float)$s3->fetchColumn();
        ?>
        <div class="total-spent mb-4" style="font-size:1.1rem; color:var(--muted);">
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
      <div class="prof-card" style="border:none; box-shadow: 0 10px 40px rgba(0,0,0,0.03); background: #fff;">
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
        <style>
            .journey-container { padding: 20px 0; }
            .journey-timeline { position: relative; max-width: 600px; margin: 0 auto; }
            .journey-timeline::after {
                content: ''; position: absolute; width: 2px; background: rgba(168, 135, 70, 0.2);
                top: 0; bottom: 0; left: 30px; margin-left: -1px;
            }
            .journey-node { padding: 20px 0 20px 70px; position: relative; }
            .node-icon {
                position: absolute; width: 40px; height: 40px; left: 10px; top: 20px;
                background: #F5F2ED; border: 2px solid #A88746; border-radius: 50%;
                display: flex; align-items: center; justify-content: center; z-index: 1;
                color: #A88746; font-size: 1.2rem;
            }
            .journey-node.locked .node-icon { background: #fff; border-color: #ddd; color: #aaa; }
            .journey-node.locked::after {
                content: '\F43E'; font-family: 'bootstrap-icons'; position: absolute;
                font-size: 14px; top: 30px; left: 24px; z-index: 2; color: #aaa;
            }
            .node-content { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid rgba(168, 135, 70, 0.2); position: relative; }
            .journey-node.locked .node-content { opacity: 0.6; filter: grayscale(1); }
            .node-content h5 { font-family: 'Cormorant Garamond', serif; color: #222; font-size: 1.3rem; margin-bottom: 5px; }
            .node-content p { font-size: 0.95rem; color: #555; margin-bottom: 0; line-height: 1.5; }
            .node-badge { position: absolute; top: 15px; right: 15px; font-size: 0.8rem; background: rgba(168,135,70,0.1); color: #A88746; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
            .locked .node-badge { background: #eee; color: #888; }
            
            .journey-header { text-align: center; margin-bottom: 40px; }
            .journey-header h3 { font-family: 'Cormorant Garamond', serif; color: #A88746; font-size: 2rem; margin-bottom: 10px; font-weight: 600; }
            .journey-header p { font-size: 1.05rem; color: #555; }
            .visit-count-badge { display: inline-block; background: #A88746; color: #fff; padding: 6px 18px; border-radius: 30px; font-weight: 600; margin-top: 10px; font-size: 14px; letter-spacing: 0.5px; }
        </style>

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
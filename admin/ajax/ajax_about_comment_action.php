<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/database.php';

// Basic admin check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';
$db = (new Database())->getConnection();

switch ($action) {
    case 'delete_comment':
        $id = (int)($_POST['comment_id'] ?? 0);
        $db->prepare("DELETE FROM about_comments WHERE id=?")->execute([$id]);
        echo json_encode(['status'=>'success','message'=>'Đã xóa bình luận']);
        break;

    case 'ban_user':
        $type   = $_POST['ban_type'] ?? 'ip'; // 'ip' or 'account'
        $ip     = trim($_POST['user_ip'] ?? '');
        $uid    = (int)($_POST['user_id'] ?? 0);
        $hours  = max(1, (int)($_POST['hours'] ?? 24));
        $reason = mb_substr(trim($_POST['reason'] ?? ''), 0, 255);
        
        if ($type === 'ip' && !$ip) { echo json_encode(['status'=>'error','message'=>'Thiếu IP']); exit; }
        if ($type === 'account' && !$uid) { echo json_encode(['status'=>'error','message'=>'Thiếu ID người dùng']); exit; }
        
        $until = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
        
        if ($type === 'ip') {
            $stmt = $db->prepare("INSERT INTO about_comment_bans (ban_type, user_ip, reason, banned_until) VALUES ('ip', ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE banned_until=VALUES(banned_until), reason=VALUES(reason)");
            $stmt->execute([$ip, $reason, $until]);
            $msg = "Đã cấm IP $ip";
        } else {
            $stmt = $db->prepare("INSERT INTO about_comment_bans (ban_type, user_id, reason, banned_until) VALUES ('account', ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE banned_until=VALUES(banned_until), reason=VALUES(reason)");
            $stmt->execute([$uid, $reason, $until]);
            $msg = "Đã cấm tài khoản ID $uid";
        }
        
        echo json_encode(['status'=>'success','message'=>$msg . " đến ".date('d/m/Y H:i', strtotime($until))]);
        break;

    case 'unban':
        $id = (int)($_POST['ban_id'] ?? 0);
        $db->prepare("DELETE FROM about_comment_bans WHERE id=?")->execute([$id]);
        echo json_encode(['status'=>'success','message'=>'Đã gỡ cấm']);
        break;

    case 'reject_comment':
        $id = (int)($_POST['comment_id'] ?? 0);
        $db->prepare("UPDATE about_comments SET status='rejected' WHERE id=?")->execute([$id]);
        echo json_encode(['status'=>'success','message'=>'Đã ẩn bình luận']);
        break;

    case 'approve_comment':
        $id = (int)($_POST['comment_id'] ?? 0);
        $db->prepare("UPDATE about_comments SET status='approved' WHERE id=?")->execute([$id]);
        echo json_encode(['status'=>'success','message'=>'Đã duyệt bình luận']);
        break;

    case 'process_report':
        $comment_id = (int)($_POST['comment_id'] ?? 0);
        $action_type = $_POST['action_type'] ?? '';
        
        // Fetch comment details to get author_ip and user_id for banning
        $stmt = $db->prepare("SELECT author_ip, user_id, author_name FROM about_comments WHERE id=?");
        $stmt->execute([$comment_id]);
        $cmt = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cmt) {
            echo json_encode(['status'=>'error','message'=>'Bình luận không tồn tại']);
            exit;
        }
        
        $author_ip = $cmt['author_ip'];
        $user_id = (int)($cmt['user_id'] ?? 0);
        $author_name = $cmt['author_name'];
        
        // Perform comment moderation based on action_type
        if ($action_type === 'delete' || $action_type === 'delete_ban') {
            // Delete: Replace comment content with violation message, keep status as approved
            $db->prepare("UPDATE about_comments SET comment='Bình luận này đã vi phạm và bị quản trị viên xóa', status='approved' WHERE id=?")
               ->execute([$comment_id]);
        } else if ($action_type === 'hide' || $action_type === 'hide_ban') {
            // Hide: Set status to rejected
            $db->prepare("UPDATE about_comments SET status='rejected' WHERE id=?")
               ->execute([$comment_id]);
        } else if ($action_type === 'dismiss') {
            // Dismiss / Keep as is: Do nothing to the comment. It will just move reports to processed.
        } else {
            echo json_encode(['status'=>'error','message'=>'Hành động không hợp lệ']);
            exit;
        }
        
        // Apply ban if hide_ban or delete_ban is selected
        if ($action_type === 'hide_ban' || $action_type === 'delete_ban') {
            $hours = max(1, (int)($_POST['ban_hours'] ?? 24));
            $reason = trim($_POST['ban_reason'] ?? 'Vi phạm quy chuẩn bình luận');
            $until = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
            
            // Ban by Account if user is logged in, else Ban by IP
            if ($user_id > 0) {
                $b_stmt = $db->prepare("INSERT INTO about_comment_bans (ban_type, user_id, reason, banned_until) VALUES ('account', ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE banned_until=VALUES(banned_until), reason=VALUES(reason)");
                $b_stmt->execute([$user_id, $reason, $until]);
            } else if ($author_ip) {
                $b_stmt = $db->prepare("INSERT INTO about_comment_bans (ban_type, user_ip, reason, banned_until) VALUES ('ip', ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE banned_until=VALUES(banned_until), reason=VALUES(reason)");
                $b_stmt->execute([$author_ip, $reason, $until]);
            }
        }
        
        // Move all reports for this comment to processed status
        $db->prepare("UPDATE about_comment_reports SET status='processed' WHERE comment_id=?")
           ->execute([$comment_id]);
           
        echo json_encode(['status'=>'success','message'=>'Xử lý bình luận và báo cáo thành công!']);
        break;

    case 'delete_processed_reports':
        $comment_ids_str = trim($_POST['comment_ids'] ?? '');
        if (empty($comment_ids_str)) {
            echo json_encode(['status'=>'error','message'=>'Không có mục nào được chọn']);
            exit;
        }
        
        $ids = array_filter(array_map('intval', explode(',', $comment_ids_str)));
        if (empty($ids)) {
            echo json_encode(['status'=>'error','message'=>'Danh sách ID không hợp lệ']);
            exit;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM about_comment_reports WHERE status='processed' AND comment_id IN ($placeholders)");
        $stmt->execute($ids);
        
        echo json_encode(['status'=>'success','message'=>'Đã xóa thành công các báo cáo được chọn!']);
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action không hợp lệ']);
}

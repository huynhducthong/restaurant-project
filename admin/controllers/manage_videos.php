<?php
// =============================================================
// File: admin/controllers/VideoController.php
// Thay thế: manage_videos.php
// =============================================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->getConnection();

// ✅ FIX: Lấy video không hardcode id=1 — lấy bản ghi đầu tiên, tạo nếu chưa có
$video = $db->query("SELECT * FROM videos ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$video) {
    $db->exec("INSERT INTO videos (video_type, video_url, file_path) VALUES ('youtube', '', '')");
    $video = $db->query("SELECT * FROM videos ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
$video_id_db = (int)$video['id'];

// Flash message
$flash = $_SESSION['video_flash'] ?? null;
unset($_SESSION['video_flash']);

// ============================================================
// XỬ LÝ POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update'])) {

    $type      = $_POST['video_type'] ?? 'youtube';
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $video_url = '';
    $file_path = $video['file_path'] ?? ''; 

    if (in_array($type, ['youtube', 'vimeo', 'muse'])) {
        $url_input = trim($_POST['video_url'] ?? '');
        
        if ($type === 'youtube') {
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url_input, $match)) {
                $video_url = $match[1];
            } else {
                $video_url = preg_replace('/[^a-zA-Z0-9_\-]/', '', $url_input);
            }
        } elseif ($type === 'vimeo') {
            if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url_input, $match)) {
                $video_url = $match[1];
            } else {
                $video_url = preg_replace('/[^0-9]/', '', $url_input);
            }
        } elseif ($type === 'muse') {
            if (preg_match('/(?:muse\.ai\/v\/|muse\.ai\/embed\/)([a-zA-Z0-9]+)/', $url_input, $match)) {
                $video_url = $match[1];
            } else {
                $video_url = preg_replace('/[^a-zA-Z0-9]/', '', $url_input);
            }
        }
    } else {
        // Xử lý upload file local
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext  = ['mp4', 'webm', 'mov'];
            $allowed_mime = ['video/mp4', 'video/webm', 'video/quicktime'];
            $max_size     = 200 * 1024 * 1024;

            $ext      = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $tmp_path = $_FILES['video_file']['tmp_name'];
            $size     = $_FILES['video_file']['size'];

            $upload_error = '';
            if (!in_array($ext, $allowed_ext)) {
                $upload_error = 'Chỉ chấp nhận: MP4, WEBM, MOV.';
            } elseif ($size > $max_size) {
                $upload_error = 'File quá lớn. Tối đa 200MB.';
            }

            if ($upload_error) {
                $_SESSION['video_flash'] = ['type' => 'error', 'msg' => $upload_error];
                header('Location: /restaurant-project/admin/controllers/settings.php?tab=video'); exit;
            }

            $upload_dir = __DIR__ . '/../../uploads/videos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_file_name = bin2hex(random_bytes(10)) . '.' . $ext;
            $target        = $upload_dir . $new_file_name;

            if (move_uploaded_file($tmp_path, $target)) {
                if (!empty($video['file_path'])) {
                    $old = __DIR__ . '/../../' . ltrim($video['file_path'], '/');
                    if (file_exists($old)) @unlink($old);
                }
                $file_path = 'uploads/videos/' . $new_file_name;
            }
        }
    }

    // Lưu vào DB
    $db->prepare("UPDATE videos SET video_type = ?, video_url = ?, file_path = ?, title = ?, description = ? WHERE id = ?")
       ->execute([$type, $video_url, $file_path, $title, $desc, $video_id_db]);

    $_SESSION['video_flash'] = ['type' => 'success', 'msg' => 'Cập nhật video thành công!'];
    header('Location: '/restaurant-project/admin/controllers/settings.php?tab=video); exit;
}


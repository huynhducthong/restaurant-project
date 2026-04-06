<?php
include '../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();
$message = "";

// 1. Lấy dữ liệu video hiện tại
$query = "SELECT * FROM videos WHERE id = 1"; 
$stmt = $db->prepare($query);
$stmt->execute();
$video = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Xử lý Cập nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_update'])) {
    $type = $_POST['video_type'];
    $video_id = "";
    $file_path = $video['file_path']; // Giữ lại đường dẫn cũ nếu có

    if ($type == 'youtube') {
        $url_input = $_POST['video_url'];
        // Tách ID Youtube
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url_input, $match)) {
            $video_id = $match[1];
        } else { $video_id = $url_input; }
    } else {
        // Xử lý Upload File
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $target_dir = "uploads/videos/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_name = time() . '_' . basename($_FILES["video_file"]["name"]);
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
                $file_path = $target_file;
            }
        }
    }

    $update_query = "UPDATE videos SET video_type = :type, video_url = :url, file_path = :path WHERE id = 1";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':type', $type);
    $update_stmt->bindParam(':url', $video_id);
    $update_stmt->bindParam(':path', $file_path);
    
    if ($update_stmt->execute()) {
        $message = "<div class='alert alert-success'>Cập nhật thành công!</div>";
        // Refresh lại dữ liệu
        $stmt->execute();
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<div class="content-wrapper">
    <h3><i class="fas fa-video"></i> Quản lý Video Giới thiệu</h3>
    <?php echo $message; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
        <div class="card-custom" style="background: white; padding: 20px; border-radius: 10px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label"><b>Loại nguồn video:</b></label>
                    <select name="video_type" id="video_type" class="form-control" onchange="toggleInput()">
                        <option value="youtube" <?php if($video['video_type'] == 'youtube') echo 'selected'; ?>>Dùng Link YouTube</option>
                        <option value="local" <?php if($video['video_type'] == 'local') echo 'selected'; ?>>Tải lên từ máy tính</option>
                    </select>
                </div>

                <div id="youtube_input" style="display: <?php echo ($video['video_type'] == 'youtube' ? 'block' : 'none'); ?>;">
                    <label class="form-label">Dán link YouTube:</label>
                    <input type="text" name="video_url" class="form-control" value="<?php echo $video['video_url']; ?>">
                </div>

                <div id="local_input" style="display: <?php echo ($video['video_type'] == 'local' ? 'block' : 'none'); ?>;">
                    <label class="form-label">Chọn file video (mp4, webm):</label>
                    <input type="file" name="video_file" class="form-control" accept="video/*">
                    <?php if($video['file_path']): ?>
                        <small class="text-success">Đã có file: <?php echo basename($video['file_path']); ?></small>
                    <?php endif; ?>
                </div>

                <button type="submit" name="btn_update" class="btn btn-primary w-100 mt-4">Lưu thay đổi</button>
            </form>
        </div>

        <div class="card-custom" style="background: white; padding: 20px; border-radius: 10px;">
            <label class="form-label"><b>Xem trước:</b></label>
            <div class="ratio ratio-16x9">
                <?php if($video['video_type'] == 'youtube'): ?>
                    <iframe src="https://www.youtube.com/embed/<?php echo $video['video_url']; ?>" allowfullscreen></iframe>
                <?php else: ?>
                    <video controls style="width: 100%; height: 100%; border-radius: 5px;">
                        <source src="<?php echo $video['file_path']; ?>" type="video/mp4">
                        Trình duyệt không hỗ trợ xem video.
                    </video>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleInput() {
    var type = document.getElementById('video_type').value;
    document.getElementById('youtube_input').style.display = (type == 'youtube' ? 'block' : 'none');
    document.getElementById('local_input').style.display = (type == 'local' ? 'block' : 'none');
}
</script>
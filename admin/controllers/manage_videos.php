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

    $type      = ($_POST['video_type'] ?? '') === 'local' ? 'local' : 'youtube';
    $video_url = '';
    $file_path = $video['file_path'] ?? ''; // giữ file cũ mặc định

    if ($type === 'youtube') {
        $url_input = trim($_POST['video_url'] ?? '');
        // Tách YouTube ID từ nhiều dạng URL
        if (preg_match(
            '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
            $url_input, $match
        )) {
            $video_url = $match[1];
        } else {
            // Người dùng nhập thẳng ID (11 ký tự)
            $video_url = preg_replace('/[^a-zA-Z0-9_\-]/', '', $url_input);
        }

    } else {
        // ✅ FIX: Validate upload video — ext + MIME + size
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext  = ['mp4', 'webm', 'mov'];
            $allowed_mime = ['video/mp4', 'video/webm', 'video/quicktime'];
            $max_size     = 200 * 1024 * 1024; // 200MB

            $ext      = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $tmp_path = $_FILES['video_file']['tmp_name'];
            $size     = $_FILES['video_file']['size'];

            $upload_error = '';
            if (!in_array($ext, $allowed_ext)) {
                $upload_error = 'Chỉ chấp nhận: MP4, WEBM, MOV.';
            } elseif ($size > $max_size) {
                $upload_error = 'File quá lớn. Tối đa 200MB.';
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $tmp_path);
                finfo_close($finfo);
                if (!in_array($mime, $allowed_mime)) {
                    $upload_error = 'File không phải video hợp lệ.';
                }
            }

            if ($upload_error) {
                $_SESSION['video_flash'] = ['type' => 'error', 'msg' => $upload_error];
                header('Location: ' . $_SERVER['PHP_SELF']); exit;
            }

            // ✅ FIX: Dùng __DIR__ thay relative path
            $upload_dir = __DIR__ . '/../../uploads/videos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_file_name = bin2hex(random_bytes(10)) . '.' . $ext;
            $target        = $upload_dir . $new_file_name;

            if (move_uploaded_file($tmp_path, $target)) {
                // ✅ FIX: Xóa file cũ sau khi upload thành công
                if (!empty($video['file_path'])) {
                    $old = __DIR__ . '/../../' . ltrim($video['file_path'], '/');
                    if (file_exists($old)) @unlink($old);
                }
                $file_path = 'uploads/videos/' . $new_file_name;
            } else {
                $_SESSION['video_flash'] = ['type' => 'error', 'msg' => 'Không thể upload file. Kiểm tra quyền ghi thư mục.'];
                header('Location: ' . $_SERVER['PHP_SELF']); exit;
            }
        }
        // Nếu không upload file mới → giữ nguyên $file_path cũ
    }

    // Lưu vào DB
    $db->prepare(
        "UPDATE videos SET video_type = ?, video_url = ?, file_path = ? WHERE id = ?"
    )->execute([$type, $video_url, $file_path, $video_id_db]);

    // ✅ FIX: Flash session + redirect thay vì echo $message
    $_SESSION['video_flash'] = ['type' => 'success', 'msg' => 'Cập nhật video thành công!'];
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

include '../../public/admin_layout_header.php';
?>

<link rel="stylesheet" href="../../public/assets/admin/css/admin-style.css">

<div class="content-wrapper p-4">

    <!-- Header -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold m-0">
                <i class="fas fa-video me-2 text-primary"></i>Quản lý Video Giới thiệu
            </h3>
            <div class="small text-muted mt-1">Video hiển thị trong phần "Về chúng tôi" trên trang chủ</div>
        </div>
    </div>

    <!-- Flash message -->
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Cột trái: Form cập nhật -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-dark py-3 px-4">
                    <h5 class="m-0 text-white fw-bold">
                        <i class="fas fa-edit me-2 text-warning"></i>Cập nhật nguồn video
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data" id="form-video"
                          action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">

                        <!-- Loại nguồn -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Loại nguồn video</label>
                            <div class="d-flex gap-2">
                                <label class="flex-fill">
                                    <input type="radio" name="video_type" value="youtube"
                                           <?= ($video['video_type'] ?? '') !== 'local' ? 'checked' : '' ?>
                                           class="d-none video-type-radio" id="type-yt">
                                    <div class="type-btn p-3 text-center border rounded-3 cursor-pointer"
                                         style="cursor:pointer;transition:.2s" onclick="switchType('youtube')">
                                        <i class="fab fa-youtube fa-2x text-danger mb-1 d-block"></i>
                                        <div class="small fw-bold">YouTube</div>
                                        <div class="text-muted" style="font-size:11px">Dùng link URL</div>
                                    </div>
                                </label>
                                <label class="flex-fill">
                                    <input type="radio" name="video_type" value="local"
                                           <?= ($video['video_type'] ?? '') === 'local' ? 'checked' : '' ?>
                                           class="d-none video-type-radio" id="type-local">
                                    <div class="type-btn p-3 text-center border rounded-3"
                                         style="cursor:pointer;transition:.2s" onclick="switchType('local')">
                                        <i class="fas fa-upload fa-2x text-primary mb-1 d-block"></i>
                                        <div class="small fw-bold">Tải lên</div>
                                        <div class="text-muted" style="font-size:11px">File MP4/WEBM</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- YouTube input -->
                        <div id="youtube_input"
                             style="display:<?= ($video['video_type'] ?? '') !== 'local' ? 'block' : 'none' ?>">
                            <label class="form-label fw-bold small text-muted">
                                Link YouTube <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="video_url"
                                   class="form-control bg-light border-0"
                                   value="<?= htmlspecialchars($video['video_url'] ?? '') ?>"
                                   placeholder="https://youtube.com/watch?v=... hoặc youtu.be/...">
                            <div class="form-text">Dán link đầy đủ hoặc YouTube ID (11 ký tự)</div>
                        </div>

                        <!-- Local upload input -->
                        <div id="local_input"
                             style="display:<?= ($video['video_type'] ?? '') === 'local' ? 'block' : 'none' ?>">
                            <label class="form-label fw-bold small text-muted">Chọn file video</label>
                            <input type="file" name="video_file" id="video_file_input"
                                   class="form-control bg-light border-0"
                                   accept=".mp4,.webm,.mov">
                            <div class="form-text">MP4, WEBM, MOV — tối đa 200MB. Để trống để giữ file hiện tại.</div>

                            <?php if (!empty($video['file_path'])): ?>
                            <div class="mt-2 p-2 bg-light rounded-3 d-flex align-items-center gap-2">
                                <i class="fas fa-film text-primary"></i>
                                <div>
                                    <div class="small fw-bold">File hiện tại:</div>
                                    <div class="small text-muted font-monospace">
                                        <?= htmlspecialchars(basename($video['file_path'])) ?>
                                    </div>
                                    <?php
                                    $fp = __DIR__ . '/../../' . ltrim($video['file_path'], '/');
                                    if (file_exists($fp)):
                                        $kb = round(filesize($fp) / 1024);
                                        $size_txt = $kb >= 1024 ? round($kb/1024, 1) . ' MB' : $kb . ' KB';
                                    ?>
                                    <div class="small text-muted"><?= $size_txt ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Upload progress -->
                            <div id="upload-progress" class="mt-2" style="display:none">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Đang upload...</span>
                                    <span id="progress-pct">0%</span>
                                </div>
                                <div class="progress" style="height:6px">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                         id="progress-bar" style="width:0%"></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="btn_update"
                                class="btn btn-primary w-100 mt-4 py-2 fw-bold rounded-pill"
                                id="btn-submit">
                            <i class="fas fa-save me-2"></i>LƯU THAY ĐỔI
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cột phải: Xem trước -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-light py-3 px-4">
                    <h5 class="m-0 fw-bold text-muted small text-uppercase">
                        <i class="fas fa-eye me-2"></i>Xem trước
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="ratio ratio-16x9" style="border-radius:10px;overflow:hidden;background:#000">
                        <?php if (($video['video_type'] ?? '') === 'local' && !empty($video['file_path'])): ?>
                        <video controls style="width:100%;height:100%;border-radius:10px">
                            <source src="<?= htmlspecialchars('../../' . ltrim($video['file_path'], '/')) ?>"
                                    type="video/mp4">
                            Trình duyệt không hỗ trợ xem video.
                        </video>
                        <?php elseif (!empty($video['video_url'])): ?>
                        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($video['video_url']) ?>?rel=0"
                                title="Video preview"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                        <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center text-muted"
                             style="background:#f8f9fa;border-radius:10px">
                            <div class="text-center">
                                <i class="fas fa-play-circle fa-4x mb-3 opacity-25"></i>
                                <div>Chưa có video. Hãy cài đặt ở bên trái.</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3 p-3 bg-light rounded-3">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="small text-muted">Loại</div>
                                <div class="fw-bold small">
                                    <?= ($video['video_type'] ?? '') === 'local' ? '📁 Local' : '▶ YouTube' ?>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">ID / File</div>
                                <div class="fw-bold small font-monospace"
                                     style="word-break:break-all;font-size:10px">
                                    <?php if (($video['video_type'] ?? '') === 'local'): ?>
                                        <?= htmlspecialchars(basename($video['file_path'] ?? '—')) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($video['video_url'] ?? '—') ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Trạng thái</div>
                                <?php
                                $has_video = !empty($video['video_url']) || !empty($video['file_path']);
                                ?>
                                <span class="badge <?= $has_video ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $has_video ? 'Đã cài đặt' : 'Chưa có' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ---- Switch type ----
function switchType(type) {
    document.getElementById('youtube_input').style.display = (type === 'youtube') ? 'block' : 'none';
    document.getElementById('local_input').style.display   = (type === 'local')   ? 'block' : 'none';
    document.getElementById(type === 'youtube' ? 'type-yt' : 'type-local').checked = true;
    // Highlight active card
    document.querySelectorAll('.type-btn').forEach(function (el) {
        el.style.borderColor = '';
        el.style.background  = '';
    });
    var active = document.querySelector('#' + (type === 'youtube' ? 'type-yt' : 'type-local') + ' + .type-btn');
    if (!active) active = document.getElementById(type === 'youtube' ? 'type-yt' : 'type-local').nextElementSibling;
}

// ---- Auto-detect: paste YouTube URL → tự chuyển sang tab YouTube ----
var urlInput = document.querySelector('input[name="video_url"]');
if (urlInput) {
    urlInput.addEventListener('paste', function (e) {
        // Lấy text vừa paste
        var pasted = (e.clipboardData || window.clipboardData).getData('text');
        if (/youtube\.com|youtu\.be/i.test(pasted)) {
            switchType('youtube');
            // Hiện toast nhỏ
            showAutoDetectToast('YouTube', 'fab fa-youtube text-danger');
        }
    });
    urlInput.addEventListener('input', function () {
        var val = this.value.trim();
        if (/youtube\.com|youtu\.be/i.test(val)) {
            switchType('youtube');
        }
    });
}

// ---- Auto-detect: chọn file → tự chuyển sang tab Local ----
var fileInputEl = document.getElementById('video_file_input');
if (fileInputEl) {
    fileInputEl.addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;

        // Tự chuyển sang tab local
        switchType('local');
        showAutoDetectToast('File local', 'fas fa-film text-primary');

        var mb = (file.size / 1024 / 1024).toFixed(1);
        if (file.size > 200 * 1024 * 1024) {
            alert('File quá lớn (' + mb + 'MB). Tối đa 200MB.');
            this.value = '';
        }
    });
}

// ---- Toast thông báo nhận diện tự động ----
function showAutoDetectToast(label, iconClass) {
    var existing = document.getElementById('auto-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'auto-toast';
    toast.style.cssText = [
        'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
        'background:#212529', 'color:#fff', 'padding:10px 16px',
        'border-radius:10px', 'font-size:13px', 'display:flex',
        'align-items:center', 'gap:8px', 'box-shadow:0 4px 16px rgba(0,0,0,.3)',
        'animation:fadeInUp .3s ease'
    ].join(';');
    toast.innerHTML = '<i class="' + iconClass + '"></i> Nhận diện: <b>' + label + '</b>';
    document.body.appendChild(toast);
    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .3s';
        setTimeout(function () { toast.remove(); }, 300);
    }, 2500);
}

// ---- Highlight active type on load ----
(function () {
    var checked = document.querySelector('.video-type-radio:checked');
    if (checked) {
        var btn = checked.nextElementSibling;
        if (btn) { btn.style.borderColor = '#0d6efd'; btn.style.background = '#f0f6ff'; }
    }
    document.querySelectorAll('.video-type-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.type-btn').forEach(function (el) {
                el.style.borderColor = ''; el.style.background = '';
            });
            if (radio.checked) {
                var btn = radio.nextElementSibling;
                if (btn) { btn.style.borderColor = '#0d6efd'; btn.style.background = '#f0f6ff'; }
            }
        });
    });
})();

// ---- Upload progress ----
var formEl = document.getElementById('form-video');
if (fileInputEl && formEl) {
    formEl.addEventListener('submit', function () {
        var file = fileInputEl.files[0];
        if (!file) return;
        document.getElementById('upload-progress').style.display = 'block';
        var bar  = document.getElementById('progress-bar');
        var pct  = document.getElementById('progress-pct');
        var prog = 0;
        setInterval(function () {
            prog += Math.random() * 8;
            if (prog > 90) prog = 90;
            bar.style.width = prog + '%';
            pct.textContent = Math.round(prog) + '%';
        }, 300);
        document.getElementById('btn-submit').disabled = true;
    });
}
</script>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
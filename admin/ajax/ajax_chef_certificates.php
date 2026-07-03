<?php
// File: admin/ajax/ajax_chef_certificates.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/database.php';

// Kiểm tra đăng nhập
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Đọc dữ liệu JSON hoặc POST
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

// Kiểm tra CSRF
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
    exit;
}

$db = (new Database())->getConnection();
$action = $input['action'] ?? '';

switch ($action) {
    case 'fetch':
        $chef_id = (int)($input['chef_id'] ?? 0);
        if ($chef_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID đầu bếp không hợp lệ']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM chef_certificates WHERE chef_id = ? ORDER BY issue_date DESC, id DESC");
        $stmt->execute([$chef_id]);
        $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $certs]);
        break;

    case 'add':
        $chef_id = (int)($input['chef_id'] ?? 0);
        $name = trim($input['certificate_name'] ?? '');
        $issuer = trim($input['issuer'] ?? '');
        $issue_date = trim($input['issue_date'] ?? '');
        if (empty($issue_date)) {
            $issue_date = null;
        }

        if ($chef_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID đầu bếp không hợp lệ']);
            exit;
        }
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên chứng chỉ']);
            exit;
        }
        if (empty($issuer)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đơn vị cấp']);
            exit;
        }
        if (empty($_FILES['certificate_image']) || $_FILES['certificate_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng tải lên ảnh chứng chỉ']);
            exit;
        }

        $file = $_FILES['certificate_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ định dạng ảnh JPG, PNG, WEBP, GIF']);
            exit;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            echo json_encode(['success' => false, 'message' => 'MIME file không hợp lệ']);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Dung lượng ảnh vượt quá 5MB']);
            exit;
        }

        $target_dir = __DIR__ . '/../../public/assets/img/chefs/certificates/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $new_name = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
            $stmt = $db->prepare("INSERT INTO chef_certificates (chef_id, certificate_name, issuer, issue_date, certificate_image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$chef_id, $name, $issuer, $issue_date, $new_name]);

            echo json_encode(['success' => true, 'message' => 'Đã thêm chứng chỉ thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể lưu file ảnh chứng chỉ']);
        }
        break;

    case 'edit':
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['certificate_name'] ?? '');
        $issuer = trim($input['issuer'] ?? '');
        $issue_date = trim($input['issue_date'] ?? '');
        if (empty($issue_date)) {
            $issue_date = null;
        }

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID chứng chỉ không hợp lệ']);
            exit;
        }
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên chứng chỉ']);
            exit;
        }
        if (empty($issuer)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đơn vị cấp']);
            exit;
        }

        // Lấy thông tin chứng chỉ hiện tại
        $stmt_get = $db->prepare("SELECT certificate_image FROM chef_certificates WHERE id = ?");
        $stmt_get->execute([$id]);
        $cert = $stmt_get->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy chứng chỉ']);
            exit;
        }

        $image_name = $cert['certificate_image'];

        // Nếu có upload ảnh mới
        if (!empty($_FILES['certificate_image']) && $_FILES['certificate_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['certificate_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ định dạng ảnh JPG, PNG, WEBP, GIF']);
                exit;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Dung lượng ảnh vượt quá 5MB']);
                exit;
            }

            $target_dir = __DIR__ . '/../../public/assets/img/chefs/certificates/';
            $new_name = time() . '_' . uniqid() . '.' . $ext;

            if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
                // Xóa ảnh cũ
                $old_file = $target_dir . $image_name;
                if (file_exists($old_file)) {
                    @unlink($old_file);
                }
                $image_name = $new_name;
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể lưu file ảnh chứng chỉ mới']);
                exit;
            }
        }

        $stmt_upd = $db->prepare("UPDATE chef_certificates SET certificate_name = ?, issuer = ?, issue_date = ?, certificate_image = ? WHERE id = ?");
        $stmt_upd->execute([$name, $issuer, $issue_date, $image_name, $id]);

        echo json_encode(['success' => true, 'message' => 'Đã cập nhật chứng chỉ thành công']);
        break;

    case 'delete':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID chứng chỉ không hợp lệ']);
            exit;
        }

        // Lấy thông tin chứng chỉ để xóa file ảnh vật lý
        $stmt = $db->prepare("SELECT certificate_image FROM chef_certificates WHERE id = ?");
        $stmt->execute([$id]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy chứng chỉ']);
            exit;
        }

        $file_path = __DIR__ . '/../../public/assets/img/chefs/certificates/' . $cert['certificate_image'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        $stmt_del = $db->prepare("DELETE FROM chef_certificates WHERE id = ?");
        $stmt_del->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Đã xóa chứng chỉ thành công']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}
?>

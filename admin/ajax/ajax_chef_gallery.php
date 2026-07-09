<?php
// File: admin/ajax/ajax_chef_gallery.php
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

        $stmt = $db->prepare("SELECT * FROM chef_gallery WHERE chef_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$chef_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $images]);
        break;

    case 'upload':
        $chef_id = (int)($input['chef_id'] ?? 0);
        if ($chef_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID đầu bếp không hợp lệ']);
            exit;
        }

        // Kiểm tra xem số lượng ảnh hiện tại có vượt quá 6 hay không
        $stmt = $db->prepare("SELECT COUNT(*) FROM chef_gallery WHERE chef_id = ?");
        $stmt->execute([$chef_id]);
        $current_count = (int)$stmt->fetchColumn();

        if (empty($_FILES['image'])) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy file ảnh tải lên']);
            exit;
        }

        $files = $_FILES['image'];
        // Hỗ trợ upload 1 hoặc nhiều file
        $file_array = [];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $file_array[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
            }
        } else {
            $file_array[] = $files;
        }

        if ($current_count + count($file_array) > 6) {
            echo json_encode(['success' => false, 'message' => 'Mỗi đầu bếp chỉ được phép có tối đa 6 ảnh trong gallery. Hiện đã có ' . $current_count . ' ảnh.']);
            exit;
        }

        $target_dir = __DIR__ . '/../../public/assets/img/chefs/gallery/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $uploaded_count = 0;
        $errors = [];
        
        $db->beginTransaction();
        try {
            foreach ($file_array as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Lỗi upload file: " . $file['name'];
                    continue;
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $errors[] = "Chỉ hỗ trợ định dạng JPG, PNG, WEBP, GIF. File: " . $file['name'];
                    continue;
                }

                // Kiểm tra MIME type thực tế
                $mime = mime_content_type($file['tmp_name']);
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
                    $errors[] = "File không phải ảnh hợp lệ: " . $file['name'];
                    continue;
                }

                // Kiểm tra kích thước (tối đa 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = "Ảnh vượt quá kích thước 5MB: " . $file['name'];
                    continue;
                }

                $new_name = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
                    // Lấy sort_order lớn nhất hiện tại
                    $stmt_so = $db->prepare("SELECT MAX(sort_order) FROM chef_gallery WHERE chef_id = ?");
                    $stmt_so->execute([$chef_id]);
                    $max_so = (int)$stmt_so->fetchColumn();

                    $stmt_ins = $db->prepare("INSERT INTO chef_gallery (chef_id, image, sort_order) VALUES (?, ?, ?)");
                    $stmt_ins->execute([$chef_id, $new_name, $max_so + 1]);
                    $uploaded_count++;
                } else {
                    $errors[] = "Không thể lưu file: " . $file['name'];
                }
            }

            if ($uploaded_count > 0) {
                $db->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Đã upload thành công ' . $uploaded_count . ' ảnh.',
                    'errors' => $errors
                ]);
            } else {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Upload thất bại.', 'errors' => $errors]);
            }
        } catch (Exception $ex) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $ex->getMessage()]);
        }
        break;

    case 'delete':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID ảnh không hợp lệ']);
            exit;
        }

        // Lấy thông tin ảnh để xóa file vật lý
        $stmt = $db->prepare("SELECT image FROM chef_gallery WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy ảnh']);
            exit;
        }

        $file_path = __DIR__ . '/../../public/assets/img/chefs/gallery/' . $row['image'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        $stmt_del = $db->prepare("DELETE FROM chef_gallery WHERE id = ?");
        $stmt_del->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Đã xóa ảnh thành công']);
        break;

    case 'replace':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID ảnh không hợp lệ']);
            exit;
        }

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File tải lên không hợp lệ']);
            exit;
        }

        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ định dạng JPG, PNG, WEBP, GIF']);
            exit;
        }

        // Lấy ảnh cũ
        $stmt = $db->prepare("SELECT image FROM chef_gallery WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy ảnh cần thay thế']);
            exit;
        }

        $target_dir = __DIR__ . '/../../public/assets/img/chefs/gallery/';
        $new_name = time() . '_' . uniqid() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
            // Xóa ảnh cũ
            $old_file_path = $target_dir . $row['image'];
            if (file_exists($old_file_path)) {
                @unlink($old_file_path);
            }

            // Cập nhật tên ảnh trong DB
            $stmt_upd = $db->prepare("UPDATE chef_gallery SET image = ? WHERE id = ?");
            $stmt_upd->execute([$new_name, $id]);

            echo json_encode(['success' => true, 'message' => 'Đã thay thế ảnh thành công', 'new_image' => $new_name]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lưu file thất bại']);
        }
        break;

    case 'sort':
        $orders = $input['orders'] ?? [];
        if (empty($orders) || !is_array($orders)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu sắp xếp không hợp lệ']);
            exit;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE chef_gallery SET sort_order = :so WHERE id = :id");
            foreach ($orders as $item) {
                $id = (int)($item['id'] ?? 0);
                $so = (int)($item['sort_order'] ?? 0);
                if ($id > 0) {
                    $stmt->execute([':so' => $so, ':id' => $id]);
                }
            }
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật thứ tự hiển thị thành công']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}
?>

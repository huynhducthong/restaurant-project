<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => ['message' => 'Unauthorized']]); exit;
}

$fileInput = isset($_FILES['file']) ? $_FILES['file'] : (isset($_FILES['upload']) ? $_FILES['upload'] : null);

if ($fileInput) {
    $file = $fileInput;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'docx', 'xlsx', 'txt'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => ['message' => 'Định dạng file không được hỗ trợ']]); exit;
    }

    $dir = '../../public/assets/uploads/about/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $url = '/restaurant-project/public/assets/uploads/about/' . $filename;
        // TinyMCE expects 'location', CKEditor expects 'url'
        echo json_encode(['location' => $url, 'url' => $url]);
    } else {
        echo json_encode(['error' => ['message' => 'Không thể tải file lên']]);
    }
} else {
    echo json_encode(['error' => ['message' => 'Không tìm thấy file']]);
}

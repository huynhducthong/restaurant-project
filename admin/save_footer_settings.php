<?php
session_start();
require_once __DIR__ . '/auth_check.php';
require_admin();
require_once '../config/database.php';
require_once '../config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    die("Yêu cầu không hợp lệ.");
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    // Xử lý switch
    foreach (['show_social', 'show_newsletter', 'show_map'] as $s) {
        $val = isset($_POST[$s]) ? '1' : '0';
        $db->prepare("UPDATE footer_settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $s]);
    }

    // Text fields
    $stmtText = $db->prepare("INSERT INTO footer_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($_POST as $k => $v) {
        if (!in_array($k, ['csrf_token', 'show_social', 'show_newsletter', 'show_map']))
            $stmtText->execute([$k, trim($v)]);
    }

    // Hàm upload an toàn
    function validateAndUpload($file, $prefix): ?string {
        if (empty($file['name'])) return null;
        $allowedExt  = ['jpg','jpeg','png','webp','svg'];
        $allowedMime = ['image/jpeg','image/png','image/webp','image/svg+xml'];
        $maxSize     = 2 * 1024 * 1024;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) throw new Exception("Định dạng không hợp lệ.");
        if ($file['size'] > $maxSize) throw new Exception("Dung lượng quá lớn (tối đa 2MB).");
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowedMime)) throw new Exception("File không đúng định dạng ảnh.");
        }
        $newName = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = __DIR__ . '/../public/assets/img/' . $newName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) throw new Exception("Không thể lưu file.");
        return $newName;
    }

    // Upload logo
    if (!empty($_FILES['footer_logo']['name'])) {
        $logoName = validateAndUpload($_FILES['footer_logo'], 'flogo');
        $old = $db->query("SELECT setting_value FROM footer_settings WHERE setting_key='footer_logo'")->fetchColumn();
        if ($old && file_exists(__DIR__ . '/../public/assets/img/' . $old)) @unlink(__DIR__ . '/../public/assets/img/' . $old);
        $stmtText->execute(['footer_logo', $logoName]);
    }
    // Upload background
    if (!empty($_FILES['footer_bg_image']['name'])) {
        $bgName = validateAndUpload($_FILES['footer_bg_image'], 'fbg');
        $old = $db->query("SELECT setting_value FROM footer_settings WHERE setting_key='footer_bg_image'")->fetchColumn();
        if ($old && file_exists(__DIR__ . '/../public/assets/img/' . $old)) @unlink(__DIR__ . '/../public/assets/img/' . $old);
        $stmtText->execute(['footer_bg_image', $bgName]);
    }

    $db->commit();
    $_SESSION['footer_flash'] = ['type'=>'success','msg'=>'Cập nhật footer thành công!'];
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    $_SESSION['footer_flash'] = ['type'=>'error','msg'=>'Lỗi: '.$e->getMessage()];
}
header("Location: footer_settings.php");
exit;
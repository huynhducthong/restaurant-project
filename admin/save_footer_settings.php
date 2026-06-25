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

    $db->commit();
    $_SESSION['settings_flash'] = ['type'=>'success','msg'=>'Cập nhật footer thành công!'];
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    $_SESSION['settings_flash'] = ['type'=>'error','msg'=>'Lỗi: '.$e->getMessage()];
}
header("Location: controllers/settings.php?tab=footer");
exit;
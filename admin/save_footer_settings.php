<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        $switches = ['show_social', 'show_newsletter', 'show_map'];
        foreach ($switches as $s) {
            $val = isset($_POST[$s]) ? '1' : '0';
            $db->prepare("UPDATE footer_settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $s]);
        }

        foreach ($_POST as $key => $value) {
            if (!in_array($key, $switches)) {
                $db->prepare("UPDATE footer_settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $key]);
            }
        }

        // Upload Logo & Background
        $files = ['footer_logo', 'footer_bg_image'];
        foreach ($files as $f) {
            if (!empty($_FILES[$f]['name'])) {
                $filename = $f . '_' . time() . '_' . $_FILES[$f]['name'];
                if (move_uploaded_file($_FILES[$f]['tmp_name'], "../public/assets/img/" . $filename)) {
                    $db->prepare("UPDATE footer_settings SET setting_value = ? WHERE setting_key = ?")->execute([$filename, $f]);
                }
            }
        }

        $db->commit();
        header("Location: footer_settings.php");
    } catch (Exception $e) { $db->rollBack(); die($e->getMessage()); }
}
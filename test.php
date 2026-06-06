<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = (new Database())->getConnection();
    echo "THEMES:\n";
    $themes = $db->query("SELECT id, name, is_active FROM themes")->fetchAll(PDO::FETCH_ASSOC);
    print_r($themes);
    echo "\nCOMBOS:\n";
    $combos = $db->query("SELECT id, name, theme_id FROM combos")->fetchAll(PDO::FETCH_ASSOC);
    print_r($combos);
} catch (Exception $e) {
    echo $e->getMessage();
}

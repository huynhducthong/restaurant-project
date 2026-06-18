<?php
// Bypass auth
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';

ob_start();
try {
    include 'c:/xampp/htdocs/restaurant-project/admin/controllers/InventoryController.php';
} catch (Throwable $e) {
    echo "PHP_CRASH: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
$out = ob_get_clean();
file_put_contents('output.html', $out);
echo "Done. Length: " . strlen($out);

<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$emp_id  = isset($_GET['emp_id']) ? (int)$_GET['emp_id'] : null;

if ($user_id) {
    $stmt = $db->prepare("SELECT avatar_blob, avatar_mime, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($u && $u['avatar_blob']) {
        header("Content-Type: " . ($u['avatar_mime'] ?: "image/jpeg"));
        echo $u['avatar_blob'];
        exit;
    } elseif ($u && strpos($u['avatar'], 'http') === 0) {
        header("Location: " . $u['avatar']);
        exit;
    }
} elseif ($emp_id) {
    $stmt = $db->prepare("SELECT avatar_blob, avatar_mime FROM employees WHERE id = ?");
    $stmt->execute([$emp_id]);
    $e = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($e && $e['avatar_blob']) {
        header("Content-Type: " . ($e['avatar_mime'] ?: "image/jpeg"));
        echo $e['avatar_blob'];
        exit;
    }
}

// Fallback to placeholder if not found
header("Content-Type: image/png");
// You could output a default avatar here
exit;
?>

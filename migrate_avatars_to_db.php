<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

try {
    // 1. Thêm cột avatar_blob vào bảng users và employees
    $db->exec("ALTER TABLE users ADD COLUMN avatar_blob LONGBLOB AFTER avatar");
    $db->exec("ALTER TABLE users ADD COLUMN avatar_mime VARCHAR(50) AFTER avatar_blob");
    
    $db->exec("ALTER TABLE employees ADD COLUMN avatar_blob LONGBLOB AFTER avatar");
    $db->exec("ALTER TABLE employees ADD COLUMN avatar_mime VARCHAR(50) AFTER avatar_blob");
    
    echo "Added blob columns to users and employees.\n";

    // 2. Di chuyển dữ liệu cũ từ file vào DB
    // Xử lý Users
    $users = $db->query("SELECT id, avatar FROM users WHERE avatar IS NOT NULL AND avatar NOT LIKE 'http%'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $path = __DIR__ . '/public/assets/uploads/avatars/' . $u['avatar'];
        if (file_exists($path)) {
            $data = file_get_contents($path);
            $mime = mime_content_type($path);
            $stmt = $db->prepare("UPDATE users SET avatar_blob = ?, avatar_mime = ? WHERE id = ?");
            $stmt->execute([$data, $mime, $u['id']]);
            echo "Migrated user avatar for ID {$u['id']}\n";
        }
    }

    // Xử lý Employees
    $employees = $db->query("SELECT id, avatar FROM employees WHERE avatar IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($employees as $e) {
        $path = __DIR__ . '/public/assets/uploads/avatars/' . $e['avatar'];
        if (file_exists($path)) {
            $data = file_get_contents($path);
            $mime = mime_content_type($path);
            $stmt = $db->prepare("UPDATE employees SET avatar_blob = ?, avatar_mime = ? WHERE id = ?");
            $stmt->execute([$data, $mime, $e['id']]);
            echo "Migrated employee avatar for ID {$e['id']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

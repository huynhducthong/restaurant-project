<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

try {
    // Check if columns already exist to avoid errors
    $columns = $db->query("SHOW COLUMNS FROM about_comment_bans")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('user_id', $columns)) {
        $db->exec("ALTER TABLE about_comment_bans ADD COLUMN user_id int(11) DEFAULT NULL AFTER id");
        echo "Added user_id column.\n";
    }
    
    if (!in_array('ban_type', $columns)) {
        $db->exec("ALTER TABLE about_comment_bans ADD COLUMN ban_type enum('ip', 'account') NOT NULL DEFAULT 'ip' AFTER user_id");
        echo "Added ban_type column.\n";
    }
    
    $db->exec("ALTER TABLE about_comment_bans MODIFY COLUMN user_ip varchar(45) DEFAULT NULL");
    echo "Modified user_ip to be nullable.\n";
    
    // Drop unique index if exists
    try {
        $db->exec("ALTER TABLE about_comment_bans DROP INDEX unique_ip_ban");
        echo "Dropped old unique_ip_ban index.\n";
    } catch (Exception $e) {}

    // Add new unique indexes
    $db->exec("ALTER TABLE about_comment_bans ADD UNIQUE KEY unique_ip_ban (user_ip)");
    $db->exec("ALTER TABLE about_comment_bans ADD UNIQUE KEY unique_user_ban (user_id)");
    echo "Added new unique indexes.\n";

    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

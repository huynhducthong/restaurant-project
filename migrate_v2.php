<?php
include 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$queries = [
    "CREATE TABLE IF NOT EXISTS about_saved_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (user_id, post_id)
    )",
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        from_user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        content_id INT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "ALTER TABLE about_comments ADD COLUMN IF NOT EXISTS parent_id INT DEFAULT 0 AFTER user_id",
    "ALTER TABLE about_comments ADD COLUMN IF NOT EXISTS level INT DEFAULT 0 AFTER parent_id"
];

foreach ($queries as $sql) {
    try {
        $conn->exec($sql);
        echo "Successfully executed: " . substr($sql, 0, 50) . "...<br>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}
?>

<?php
include 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$tables = ['about_likes', 'about_comments', 'notifications', 'about_saved_posts'];

foreach ($tables as $t) {
    echo "<h3>Table: $t</h3>";
    try {
        $stmt = $conn->query("DESCRIBE $t");
        echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

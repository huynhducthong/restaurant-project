<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();

// Xóa cũ
$db->exec("TRUNCATE TABLE footer_links");

// Chèn mới
$links = [
    ['Trang chủ', 'index.php', 1],
    ['Tin tức', 'Aboutus.php', 2],
    ['Thực đơn', 'menu.php', 3],
    ['Đội Bếp', 'chefs.php', 4],
    ['Đặt bàn', 'booking_service.php?type=table', 5],
    ['Liên hệ', 'contact.php', 6]
];

$stmt = $db->prepare("INSERT INTO footer_links (title, url, priority) VALUES (?, ?, ?)");
foreach ($links as $link) {
    $stmt->execute($link);
}

echo "Done";

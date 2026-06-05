<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();

$intro = "Tại Restaurantly, chúng tôi không chỉ phục vụ món ăn mà còn mang đến nghệ thuật ẩm thực đích thực. Mỗi món ăn là một bản giao hưởng của hương vị, được chế tác tỉ mỉ từ những nguyên liệu thượng hạng nhất. Hãy để không gian sang trọng và cung cách phục vụ hoàn hảo nâng tầm trải nghiệm của bạn.";

$stmt = $db->prepare("UPDATE footer_settings SET setting_value = ? WHERE setting_key = 'footer_description'");
$stmt->execute([$intro]);

echo "Done";

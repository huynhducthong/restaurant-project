<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();
$db->exec("UPDATE footer_settings SET setting_value='1' WHERE setting_key='show_social'");
echo "Done";

<?php
require 'c:/xampp/htdocs/restaurant-project/config/database.php';
$db = (new Database())->getConnection();
$iframe = '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4678125860524!2d106.6974453!3d10.7761858!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f4743648f3d%3A0x16ce95918cb14834!2zQ2jhu6MgQuG6v24gVGjDoG5o!5e0!3m2!1svi!2s!4v1716301234567!5m2!1svi!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
$db->exec("UPDATE footer_settings SET setting_value='".addslashes($iframe)."' WHERE setting_key='google_map_iframe'");
echo "Done";

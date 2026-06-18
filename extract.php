<?php
$html = file_get_contents('c:/xampp/htdocs/restaurant-project/admin/views/inventory/inventory_view.php');
preg_match_all('/<script>(.*?)<\/script>/is', $html, $matches);
$js = implode("\n", $matches[1]);
// Replace PHP short tags with empty strings to make it valid JS
$js = preg_replace('/<\?=.*?(\?>)/s', 'null', $js);
file_put_contents('c:/xampp/htdocs/restaurant-project/test.js', $js);

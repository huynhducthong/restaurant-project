<?php
$content = file_get_contents('http://localhost/restaurant-project/booking_service.php?type=birthday');
file_put_contents('c:/xampp/htdocs/restaurant-project/output2.html', $content);
echo "DONE";

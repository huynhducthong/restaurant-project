<?php
$content = file_get_contents("config/notification_helper.php");
$start1 = strpos($content, "function sendVipRegistrationEmail(");
$start2 = strpos($content, "function sendVipCancellationEmail(");

if ($start1 !== false && $start2 !== false) {
    // Find end of sendVipCancellationEmail
    $end2 = strpos($content, "function sendBookingCompleteEmail(", $start2);
    
    if ($end2 !== false) {
        $content = substr_replace($content, "", $start1, $end2 - $start1);
        file_put_contents("config/notification_helper.php", $content);
        echo "SUCCESS";
    }
}


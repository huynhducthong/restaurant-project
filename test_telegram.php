<?php
require_once __DIR__ . '/config/notification_helper.php';

$msg = generateMorningReport();
if ($msg) {
    if (sendTelegramNotification($msg)) {
        echo "Sent successfully:\n" . $msg;
    } else {
        echo "Failed to send.";
    }
} else {
    echo "Nothing to report.";
}

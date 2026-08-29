<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
echo "Current Time: " . time() . " - " . date('Y-m-d H:i:s') . "\n";
echo "Parsed: " . strtotime("08/07/2026 10:25 PM") . " - " . date('Y-m-d H:i:s', strtotime("08/07/2026 10:25 PM")) . "\n";
$date = "08/07/2026 10:25 PM";
$booking_timestamp = strtotime($date);
$min_hours = 1;
$min_timestamp = time() + ($min_hours * 3600) - 300;
echo "Booking TS: $booking_timestamp\n";
echo "Min TS: $min_timestamp\n";
if ($booking_timestamp < $min_timestamp) {
    echo "ERROR: Too close!\n";
} else {
    echo "OK!\n";
}

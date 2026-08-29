<?php
$content = file_get_contents("public/profile.php");

$alerts_start = strpos($content, "<?php\r\n  // Ki");
if ($alerts_start === false) $alerts_start = strpos($content, "<?php\n  // Ki");

$alerts_end = strpos($content, "<!-- ══ HERO ACCOUNT ══ -->");

if ($alerts_start !== false && $alerts_end !== false) {
    $alerts_block = substr($content, $alerts_start, $alerts_end - $alerts_start);
    $content = substr_replace($content, "", $alerts_start, $alerts_end - $alerts_start);
    
    $tabs_pos = strpos($content, "<div class=\"horizontal-tabs");
    if ($tabs_pos !== false) {
        $content = substr_replace($content, $alerts_block . "\n  ", $tabs_pos, 0);
        file_put_contents("public/profile.php", $content);
        echo "SUCCESS";
    } else {
        echo "TABS NOT FOUND";
    }
} else {
    echo "ALERTS OR HERO NOT FOUND: start=" . var_export($alerts_start, true) . ", end=" . var_export($alerts_end, true);
}


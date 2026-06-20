<?php
$html = file_get_contents('index.php');
$sections = explode('<section', $html);
foreach($sections as $i => $sec) {
    $div_open = substr_count($sec, '<div');
    $div_close = substr_count($sec, '</div');
    $id = '';
    if (preg_match('/id="([^"]+)"/', $sec, $matches)) {
        $id = $matches[1];
    }
    echo "Section $i ($id): +$div_open -$div_close (net: " . ($div_open - $div_close) . ")\n";
}

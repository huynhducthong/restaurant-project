<?php
$file = $argv[1] ?? '';
if (!file_exists($file)) exit;
$msg = file_get_contents($file);
unlink($file);
$token = $argv[2] ?? '';
$chat_id = $argv[3] ?? '';
if ($token && $chat_id) {
    $ch = curl_init("https://api.telegram.org/bot$token/sendMessage");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['chat_id' => $chat_id, 'text' => $msg, 'parse_mode' => 'HTML']));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    curl_close($ch);
}

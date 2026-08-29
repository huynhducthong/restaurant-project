<?php
$key = trim(file_get_contents('.env')); 
preg_match('/GEMINI_API_KEY=(.+)/', $key, $m);
$key = trim($m[1]);
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $key;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
echo "Response: $response\n";

<?php
$token = '8935031959:AAEzSndMhjXuiIyXkeSNCtzTzj4TGoCo81s';
$chat_id = '5676940088';
$url = "https://api.telegram.org/bot$token/sendMessage";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['chat_id'=>$chat_id, 'text'=>'test']);
echo curl_exec($ch);
?>

<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo "MAIL_USERNAME=" . ($_ENV['MAIL_USERNAME'] ?? 'EMPTY_ENV');
echo " | SERVER_MAIL=" . ($_SERVER['MAIL_USERNAME'] ?? 'EMPTY_SERVER');

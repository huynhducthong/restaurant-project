<?php
require_once __DIR__ . '/../vendor/autoload.php';

$options = array(
    'cluster' => 'ap1',
    'useTLS' => true
);

$pusher = new Pusher\Pusher(
    'cfbc6305339f352b0089',
    '684e2ef01c9f086338a9',
    '2170992',
    $options
);

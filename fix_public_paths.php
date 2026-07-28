<?php
$dir = __DIR__ . '/public';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Replace require_once '../config/...
    $content = preg_replace("/require_once\s+'\.\.\/config\/(.*?)';/", "require_once __DIR__ . '/../config/$1';", $content);
    $content = preg_replace('/require_once\s+"\.\.\/config\/(.*?)";/', 'require_once __DIR__ . "/../config/$1";', $content);

    // Replace require_once 'config/...
    $content = preg_replace("/require_once\s+'config\/(.*?)';/", "require_once __DIR__ . '/../config/$1';", $content);
    $content = preg_replace('/require_once\s+"config\/(.*?)";/', 'require_once __DIR__ . "/../config/$1";', $content);
    
    // Fix kds.php incorrect __DIR__ . '/config/'
    $content = preg_replace("/require_once\s+__DIR__\s*\.\s*'\/config\/(.*?)';/", "require_once __DIR__ . '/../config/$1';", $content);

    file_put_contents($file, $content);
}
echo "Paths fixed.";

<?php
$dir = new RecursiveDirectoryIterator('c:\\xampp\\htdocs\\restaurant-project');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['php', 'js', 'html', 'css'])) {
        $path = $file->getPathname();
        if (strpos($path, 'vendor') !== false || strpos($path, '.git') !== false) continue;
        if (strpos($path, 'fix_paths.php') !== false) continue;
        
        $content = file_get_contents($path);
        
        $newContent = str_replace('/restaurant-project/', '/', $content);
        
        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            echo "Updated: $path\n";
        }
    }
}
echo "Done.\n";

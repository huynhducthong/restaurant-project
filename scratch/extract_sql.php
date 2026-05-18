<?php
$sqlContent = file_get_contents(__DIR__ . '/../restaurant_db.sql');

$tables = ['inventory_batches', 'payrolls', 'positions', 'shifts', 'shift_assignments'];

foreach ($tables as $table) {
    echo "=== TABLE: $table ===\n";
    // Find CREATE TABLE `table` ( ... ) ENGINE=... ;
    $pattern = '/CREATE TABLE\s+`' . $table . '`\s*\((.*?)\)\s*ENGINE=[^;]+;/is';
    if (preg_match($pattern, $sqlContent, $matches)) {
        echo $matches[0] . "\n\n";
    } else {
        echo "Not found with exact pattern. Trying loose match...\n";
        // Try loose match
        $patternLoose = '/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?' . $table . '`?.*?;/is';
        // Let's find block starting from CREATE TABLE `table` up to the next table or insert
        $startPos = strpos($sqlContent, "CREATE TABLE `$table`");
        if ($startPos !== false) {
            $endPos = strpos($sqlContent, ";", $startPos);
            if ($endPos !== false) {
                echo substr($sqlContent, $startPos, $endPos - $startPos + 1) . "\n\n";
            }
        } else {
            echo "Not found!\n\n";
        }
    }
}

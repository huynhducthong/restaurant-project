<?php
$sqlContent = file_get_contents(__DIR__ . '/../restaurant_db.sql');

$tables = ['inventory_batches', 'payrolls', 'positions', 'shifts', 'shift_assignments'];

foreach ($tables as $table) {
    echo "=== ALTERS FOR TABLE: $table ===\n";
    // Find all ALTER TABLE `table` ... ;
    preg_match_all('/ALTER TABLE\s+`' . $table . '`.*?;/is', $sqlContent, $matches);
    foreach ($matches[0] as $alter) {
        echo $alter . "\n\n";
    }
}

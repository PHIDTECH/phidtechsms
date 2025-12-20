<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking if tables exist...\n";

$tables = ['contact_groups', 'contacts'];

foreach ($tables as $table) {
    $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
    echo "Table '{$table}': " . ($exists ? 'EXISTS' : 'DOES NOT EXIST') . "\n";
    
    if ($exists) {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        echo "  Columns: " . implode(', ', $columns) . "\n";
    }
}
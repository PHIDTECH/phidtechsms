<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking settings table structure...\n";

// Get table columns
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('settings');
echo "Columns in settings table:\n";
foreach ($columns as $column) {
    echo "- " . $column . "\n";
}

echo "\nChecking admin SMS balance record:\n";
$setting = \App\Models\Setting::where('key', 'admin_sms_balance')->first();

if ($setting) {
    echo "Raw data from database:\n";
    foreach ($setting->getAttributes() as $key => $value) {
        echo "- " . $key . ": " . $value . "\n";
    }
} else {
    echo "No admin_sms_balance setting found!\n";
}

echo "\nChecking BeemSmsService getAdminBalance method...\n";
$service = new \App\Services\BeemSmsService();
$balance = $service->getAdminBalance();
echo "Admin balance from service: " . $balance . "\n";
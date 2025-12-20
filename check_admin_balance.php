<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking admin SMS balance setting...\n";

$setting = \App\Models\Setting::where('key', 'admin_sms_balance')->first();

if ($setting) {
    echo "Setting found:\n";
    echo "- Key: " . $setting->key . "\n";
    echo "- Value: " . $setting->value . "\n";
    echo "- Admin SMS Balance: " . ($setting->admin_sms_balance ?? 'null') . "\n";
    echo "- Created at: " . $setting->created_at . "\n";
    echo "- Updated at: " . $setting->updated_at . "\n";
} else {
    echo "Setting not found!\n";
    echo "Creating initial admin SMS balance setting...\n";
    
    $newSetting = \App\Models\Setting::create([
        'key' => 'admin_sms_balance',
        'value' => 1000,
        'admin_sms_balance' => 1000,
        'balance_last_synced' => now()
    ]);
    
    echo "Created setting with ID: " . $newSetting->id . "\n";
}

echo "\nAll settings with 'admin' in key:\n";
$adminSettings = \App\Models\Setting::where('key', 'like', '%admin%')->get();
foreach ($adminSettings as $s) {
    echo "- " . $s->key . ": " . $s->value . "\n";
}
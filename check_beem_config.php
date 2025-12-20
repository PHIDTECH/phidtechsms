<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Current Beem API Configuration:\n";
echo "================================\n\n";

$apiKey = config('services.beem.api_key');
$secretKey = config('services.beem.secret_key');
$defaultSender = config('services.beem.default_sender_id');

echo "API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET') . "\n";
echo "Secret Key: " . ($secretKey ? substr($secretKey, 0, 20) . '...' : 'NOT SET') . "\n";
echo "Default Sender ID: " . ($defaultSender ?? 'NOT SET') . "\n\n";

echo "Expected Configuration:\n";
echo "=======================\n";
echo "API Key: 501e41f128d5a9fe\n";
echo "Secret Key: NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==\n";
echo "Default Sender ID: RodLine\n\n";

if ($apiKey === '501e41f128d5a9fe') {
    echo "✓ API Key is correct!\n";
} else {
    echo "✗ API Key needs to be updated in .env file\n";
}

if ($secretKey === 'NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==') {
    echo "✓ Secret Key is correct!\n";
} else {
    echo "✗ Secret Key needs to be updated in .env file\n";
}

if ($defaultSender === 'RodLine') {
    echo "✓ Default Sender ID is correct!\n";
} else {
    echo "✗ Default Sender ID needs to be updated\n";
}

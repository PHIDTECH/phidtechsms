<?php

use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = '501e41f128d5a9fe';
$secretKey = 'NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==';

echo "Step 1: Testing with an active sender ID (RAHISITECH)...\n";
echo "=========================================================\n";

$payload = [
    'source_addr' => 'RAHISITECH',
    'encoding' => 0,
    'message' => 'Test from PhidSMS',
    'recipients' => [
        [
            'recipient_id' => 1,
            'dest_addr' => '255769500302'
        ]
    ]
];

$response = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
    'Content-Type' => 'application/json',
])->post('https://apisms.beem.africa/v1/send', $payload);

echo "Response Status: " . $response->status() . "\n";
$data = $response->json();

if ($response->successful()) {
    echo "✓ SUCCESS! The system works!\n";
    echo "Message ID: " . ($data['request_id'] ?? 'N/A') . "\n\n";
} else {
    echo "✗ Failed\n";
    print_r($data);
    echo "\n";
}

echo "Step 2: Registering 'Rodway Shop' sender ID...\n";
echo "===============================================\n";

$registerPayload = [
    'senderid' => 'Rodway Shop',
    'sample_content' => 'This is a website for Rodway Shop business communications and customer notifications'
];

$registerResponse = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
    'Content-Type' => 'application/json',
])->post('https://apisms.beem.africa/public/v1/sender-names', $registerPayload);

echo "Response Status: " . $registerResponse->status() . "\n";
$registerData = $registerResponse->json();
print_r($registerData);

if ($registerResponse->successful()) {
    echo "\n✓ Rodway Shop registration submitted!\n";
    echo "Status: " . ($registerData['data']['status'] ?? 'unknown') . "\n";
    echo "\nNote: It may take time for approval. Check status in Beem dashboard.\n";
} else {
    echo "\n⚠ Registration response received\n";
}

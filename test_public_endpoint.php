<?php

use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.beem.api_key');
$secretKey = config('services.beem.secret_key');

$recipient = '255769500302'; // Without + prefix
$message = 'Test from PhidSMS';
$senderId = 'kadijanja';

$payload = [
    'source_addr' => $senderId,
    'encoding' => 0,
    'message' => $message,
    'recipients' => [
        [
            'recipient_id' => 1,
            'dest_addr' => $recipient
        ]
    ]
];

echo "Testing with /public/v1/send endpoint:\n";
echo "Payload:\n";
print_r($payload);
echo "\n";

$response = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
    'Content-Type' => 'application/json',
])->post('https://apisms.beem.africa/public/v1/send', $payload);

echo "Response Status: " . $response->status() . "\n";
echo "Response Body:\n";
print_r($response->json());

if ($response->successful()) {
    echo "\n✓ SUCCESS!\n";
} else {
    echo "\n✗ FAILED\n";
}

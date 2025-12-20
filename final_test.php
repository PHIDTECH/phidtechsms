<?php

use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.beem.api_key');
$secretKey = config('services.beem.secret_key');

$recipient = '255769500302'; // Without + prefix
$message = 'Test from PhidSMS - Final test';
$senderId = 'kadijanja'; // The only active sender ID

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

echo "Final Test Configuration:\n";
echo "========================\n";
echo "Endpoint: https://apisms.beem.africa/v1/send\n";
echo "Sender ID: $senderId\n";
echo "Recipient: $recipient\n";
echo "Message: $message\n\n";

$response = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
    'Content-Type' => 'application/json',
])->post('https://apisms.beem.africa/v1/send', $payload);

echo "Response Status: " . $response->status() . "\n";
echo "Response:\n";
$responseData = $response->json();
print_r($responseData);

if ($response->successful()) {
    echo "\n✓ ✓ ✓ SUCCESS! ✓ ✓ ✓\n";
    echo "Message ID: " . ($responseData['request_id'] ?? 'N/A') . "\n";
    echo "\nThe SMS should now be delivered to +255769500302\n";
} else {
    echo "\n✗ FAILED\n";
    if (isset($responseData['data']['message'])) {
        echo "Error: " . $responseData['data']['message'] . "\n";
    }
}

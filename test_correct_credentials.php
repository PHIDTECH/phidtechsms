<?php

use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use the correct API credentials
$apiKey = '501e41f128d5a9fe';
$secretKey = 'NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==';

echo "Step 1: Fetching sender names with correct credentials...\n";
echo "========================================================\n";

$response = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
    'Content-Type' => 'application/json',
])->get('https://apisms.beem.africa/public/v1/sender-names');

if ($response->successful()) {
    $data = $response->json();
    echo "Active Sender IDs:\n";
    foreach ($data['data'] as $sender) {
        if ($sender['status'] === 'active') {
            echo "  ✓ " . $sender['senderid'] . "\n";
        }
    }
    
    // Find Rodway Shop
    $rodwayShop = null;
    foreach ($data['data'] as $sender) {
        if (stripos($sender['senderid'], 'rodway') !== false) {
            $rodwayShop = $sender;
            break;
        }
    }
    
    if ($rodwayShop) {
        echo "\nFound Rodway Shop:\n";
        echo "  Sender ID: " . $rodwayShop['senderid'] . "\n";
        echo "  Status: " . $rodwayShop['status'] . "\n";
        
        if ($rodwayShop['status'] === 'active') {
            echo "\nStep 2: Testing SMS with Rodway Shop...\n";
            echo "========================================\n";
            
            $payload = [
                'source_addr' => $rodwayShop['senderid'],
                'encoding' => 0,
                'message' => 'Test message from PhidSMS - Rodway Shop',
                'recipients' => [
                    [
                        'recipient_id' => 1,
                        'dest_addr' => '255769500302'
                    ]
                ]
            ];
            
            $smsResponse = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
                'Content-Type' => 'application/json',
            ])->post('https://apisms.beem.africa/v1/send', $payload);
            
            echo "Response Status: " . $smsResponse->status() . "\n";
            $smsData = $smsResponse->json();
            print_r($smsData);
            
            if ($smsResponse->successful()) {
                echo "\n✓✓✓ SUCCESS! ✓✓✓\n";
                echo "Message ID: " . ($smsData['request_id'] ?? 'N/A') . "\n";
                echo "\nThe SMS should be delivered!\n";
            } else {
                echo "\n✗ Failed\n";
            }
        } else {
            echo "\n⚠ Rodway Shop is not active yet (Status: " . $rodwayShop['status'] . ")\n";
        }
    } else {
        echo "\n⚠ Rodway Shop not found in sender names\n";
    }
} else {
    echo "Failed to fetch sender names\n";
    print_r($response->json());
}

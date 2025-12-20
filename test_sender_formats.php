<?php

use App\Services\SmsService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$smsService = new SmsService();
$recipient = '+255769500302';
$message = 'Test message';

$senderIdsToTest = [
    'RODWAYSHOP',      // No space
    'RODWAY',          // Just first word
    'INFO',            // Generic
    'PHIDTECH',        // Original
];

foreach ($senderIdsToTest as $senderId) {
    echo "Testing Sender ID: $senderId\n";
    $result = $smsService->sendSms($recipient, $message, $senderId);
    
    if ($result['success']) {
        echo "  ✓ SUCCESS! Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        echo "  This sender ID works: $senderId\n";
        break;
    } else {
        echo "  ✗ Failed: " . ($result['error'] ?? 'Unknown') . "\n";
    }
    echo "\n";
    sleep(1); // Avoid rate limiting
}

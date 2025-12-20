<?php

use App\Services\SmsService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$smsService = new SmsService();

$recipient = '+255769500302';
$message = 'Test message from PhidSMS - using active sender ID';
$senderId = 'kadijanja'; // The ONLY active sender ID from Beem

echo "Sending SMS with active Sender ID:\n";
echo "  To: $recipient\n";
echo "  Message: $message\n";
echo "  Sender ID: $senderId\n\n";

$result = $smsService->sendSms($recipient, $message, $senderId);

echo "Result:\n";
print_r($result);

if ($result['success']) {
    echo "\n✓ SMS SENT SUCCESSFULLY!\n";
    echo "Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
    echo "\nThe message should be delivered to the phone number.\n";
} else {
    echo "\n✗ SMS FAILED!\n";
    echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
}

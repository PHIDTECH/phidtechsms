<?php

use App\Services\SmsService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$smsService = new SmsService();

$recipient = '+255769500302'; // The phone number from the campaign
$message = 'Hello world'; // The message from the campaign
$senderId = 'RODWAY SHOP'; // The approved sender ID

echo "Sending SMS:\n";
echo "  To: $recipient\n";
echo "  Message: $message\n";
echo "  Sender ID: $senderId\n\n";

$result = $smsService->sendSms($recipient, $message, $senderId);

echo "Result:\n";
print_r($result);

if ($result['success']) {
    echo "\n✓ SMS sent successfully!\n";
    echo "Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
} else {
    echo "\n✗ SMS failed!\n";
    echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
}

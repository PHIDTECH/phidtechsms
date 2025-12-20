<?php

use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$smsService = new SmsService();
$recipient = '+255621764385'; // User's number with +
$message = 'Test message from PhidSMS debug script - No Plus Prefix';
$senderId = 'RodLine'; 

echo "Original Recipient: $recipient\n";

// Check formatting
$reflection = new ReflectionClass($smsService);
$method = $reflection->getMethod('formatPhoneNumber');
$method->setAccessible(true);
$formatted = $method->invoke($smsService, $recipient);
echo "Formatted number by SmsService: $formatted\n";

if (strpos($formatted, '+') === 0) {
    echo "FAIL: SmsService still adds a '+' prefix.\n";
} else {
    echo "PASS: SmsService removed/did not add '+' prefix. Format is: $formatted\n";
}

echo "Sending SMS...\n";
$result = $smsService->sendSms($recipient, $message, $senderId);
print_r($result);

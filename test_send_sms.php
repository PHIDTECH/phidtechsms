<?php

use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$smsService = new SmsService();
$recipient = '+255621764385'; // User's number
$message = 'Test message from PhidSMS debug script';
$senderId = 'RodLine'; // Using default or a known one. The user mentioned "appropriate sender ID".

echo "Sending SMS to $recipient with Sender ID: $senderId\n";

// 1. Send using current logic (which adds +)
echo "Attempt 1: Using SmsService (adds +)\n";
$result = $smsService->sendSms($recipient, $message, $senderId);
print_r($result);

// 2. Manually send without + using raw HTTP if possible, or just observe the first result.
// If the first result says "success" but it doesn't arrive, then the + is likely the issue (or Sender ID).

// Let's check what formatPhoneNumber does
$reflection = new ReflectionClass($smsService);
$method = $reflection->getMethod('formatPhoneNumber');
$method->setAccessible(true);
$formatted = $method->invoke($smsService, $recipient);
echo "Formatted number by SmsService: $formatted\n";

if (strpos($formatted, '+') === 0) {
    echo "WARNING: SmsService adds a '+' prefix. Beem API usually expects format 255xxxxxxxxx.\n";
}

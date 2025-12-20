<?php

use App\Models\SmsMessage;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$messages = SmsMessage::orderBy('created_at', 'desc')->take(5)->get();

echo "Total messages: " . SmsMessage::count() . "\n";
echo "Recent messages:\n";

foreach ($messages as $msg) {
    echo "ID: {$msg->id}, Status: {$msg->status}, Beem ID: {$msg->beem_message_id}, Created: {$msg->created_at}\n";
}

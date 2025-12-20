<?php

use App\Models\Campaign;
use App\Models\SmsMessage;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = Campaign::latest()->first();

echo "Campaign ID: {$campaign->id}\n";
echo "Campaign Sender ID: {$campaign->sender_id}\n";

$messages = $campaign->smsMessages;
foreach ($messages as $msg) {
    echo "Message ID: {$msg->id}, Sender ID: {$msg->sender_id}, Status: {$msg->status}\n";
}

echo "\nUpdating to RODWAY SHOP...\n";

// Update campaign and messages to use approved sender ID
$campaign->update(['sender_id' => 'RODWAY SHOP']);

foreach ($messages as $msg) {
    $msg->update([
        'sender_id' => 'RODWAY SHOP',
        'status' => 'queued' // Reset to queued so it can be sent again
    ]);
}

echo "Updated!\n";

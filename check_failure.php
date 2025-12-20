<?php

use App\Models\Campaign;
use App\Models\SmsMessage;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = Campaign::latest()->first();

if (!$campaign) {
    echo "No campaign found.\n";
    exit;
}

echo "Campaign Details:\n";
echo "================\n";
echo "ID: {$campaign->id}\n";
echo "Name: {$campaign->name}\n";
echo "Status: {$campaign->status}\n";
echo "Sender ID: {$campaign->sender_id}\n";
echo "Failure Reason: " . ($campaign->failure_reason ?? 'N/A') . "\n";

echo "\nMessages:\n";
echo "=========\n";

$messages = $campaign->smsMessages;
foreach ($messages as $msg) {
    echo "Message ID: {$msg->id}\n";
    echo "  Phone: {$msg->phone}\n";
    echo "  Sender ID: {$msg->sender_id}\n";
    echo "  Status: {$msg->status}\n";
    echo "  Failure Reason: " . ($msg->failure_reason ?? 'N/A') . "\n";
    echo "\n";
}

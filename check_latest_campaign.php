<?php

use App\Models\Campaign;
use App\Models\SmsMessage;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = Campaign::latest()->first();

if (!$campaign) {
    echo "No campaigns found.\n";
    exit;
}

echo "Latest Campaign Details:\n";
echo "========================\n";
echo "ID: {$campaign->id}\n";
echo "Name: {$campaign->name}\n";
echo "Status: {$campaign->status}\n";
echo "Sender ID: {$campaign->sender_id}\n";
echo "Recipients: {$campaign->recipient_count}\n";
echo "Failure Reason: " . ($campaign->failure_reason ?? 'N/A') . "\n\n";

echo "Messages:\n";
echo "=========\n";

$messages = $campaign->smsMessages()->latest()->take(5)->get();

if ($messages->isEmpty()) {
    echo "No messages found for this campaign.\n";
} else {
    foreach ($messages as $msg) {
        echo "Message ID: {$msg->id}\n";
        echo "  Phone: {$msg->phone}\n";
        echo "  Sender ID: {$msg->sender_id}\n";
        echo "  Status: {$msg->status}\n";
        echo "  Beem Message ID: " . ($msg->beem_message_id ?? 'N/A') . "\n";
        echo "  Failure Reason: " . ($msg->failure_reason ?? 'N/A') . "\n";
        echo "\n";
    }
}

echo "\nRecent Laravel Logs:\n";
echo "====================\n";
exec('tail -n 20 storage/logs/laravel.log', $output);
foreach ($output as $line) {
    if (strpos($line, 'SMS') !== false || strpos($line, 'Beem') !== false || strpos($line, 'campaign') !== false) {
        echo $line . "\n";
    }
}

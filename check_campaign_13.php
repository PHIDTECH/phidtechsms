<?php

use App\Models\Campaign;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Campaign 13:\n";
echo "=====================\n\n";

$campaign = Campaign::find(13);

if (!$campaign) {
    echo "Campaign 13 not found.\n";
    exit;
}

echo "Campaign Details:\n";
echo "  Name: {$campaign->name}\n";
echo "  Status: {$campaign->status}\n";
echo "  Sender ID (field): {$campaign->sender_id}\n";
echo "  Message: {$campaign->message}\n";
echo "  Recipient Count: " . ($campaign->recipient_count ?? 'NULL') . "\n";
echo "  Sent Count: " . ($campaign->sent_count ?? '0') . "\n";
echo "  Failed Count: " . ($campaign->failed_count ?? '0') . "\n\n";

// Check messages
$messages = SmsMessage::where('campaign_id', 13)->get();

echo "Messages for Campaign 13: " . $messages->count() . "\n";
echo "========================================\n\n";

if ($messages->isEmpty()) {
    echo "No messages found. This means messages were never created.\n\n";
    
    // Check if there are contacts
    echo "Checking campaign contacts/recipients...\n";
    $recipientsData = $campaign->recipients ?? null;
    
    if ($recipientsData) {
        if (is_string($recipientsData)) {
            $recipients = json_decode($recipientsData, true);
        } else {
            $recipients = $recipientsData;
        }
        
        if (is_array($recipients)) {
            echo "Found " . count($recipients) . " recipients in campaign data\n";
            echo "First 3 recipients:\n";
            foreach (array_slice($recipients, 0, 3) as $recipient) {
                print_r($recipient);
            }
        } else {
            echo "Recipients data is not an array\n";
        }
    } else {
        echo "No recipients data found in campaign\n";
    }
} else {
    foreach ($messages as $msg) {
        echo "Message ID: {$msg->id}\n";
        echo "  Phone: {$msg->phone}\n";
        echo "  Status: {$msg->status}\n";
        echo "  Beem ID: " . ($msg->beem_message_id ?? 'N/A') . "\n";
        echo "\n";
    }
}

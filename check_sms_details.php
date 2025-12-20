<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Detailed SMS Message Analysis:\n";
echo "==============================\n\n";

// Get the most recent SMS message
$message = DB::table('sms_messages')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$message) {
    echo "No SMS messages found in database.\n";
    exit;
}

echo "Most Recent SMS Message:\n";
echo "------------------------\n";
echo "ID: " . $message->id . "\n";
echo "Phone: " . $message->phone . "\n";
echo "Message Content: " . $message->message_content . "\n";
echo "Sender ID: " . $message->sender_id . "\n";
echo "Status: " . $message->status . "\n";
echo "Beem Message ID: " . ($message->beem_message_id ?? 'null') . "\n";
echo "Parts Count: " . $message->parts_count . "\n";
echo "Cost: " . $message->cost . " TZS\n";
echo "Created: " . $message->created_at . "\n";
echo "Sent At: " . ($message->sent_at ?? 'null') . "\n";
echo "Delivered At: " . ($message->delivered_at ?? 'null') . "\n";
echo "Failed At: " . ($message->failed_at ?? 'null') . "\n";
echo "Failure Reason: " . ($message->failure_reason ?? 'null') . "\n";

// Check phone number format
echo "\nPhone Number Analysis:\n";
echo "----------------------\n";
$phone = $message->phone;
echo "Original: " . $phone . "\n";
echo "Length: " . strlen($phone) . " characters\n";

// Check if it's E.164 format
if (preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
    echo "Format: ✓ Valid E.164 format\n";
} else {
    echo "Format: ✗ Invalid E.164 format\n";
}

// Check if it's a Tanzanian number
if (preg_match('/^\+255/', $phone)) {
    echo "Country: ✓ Tanzania (+255)\n";
    
    // Extract the local number
    $localNumber = substr($phone, 4);
    echo "Local Number: " . $localNumber . "\n";
    
    // Check if it's a valid Tanzanian mobile format
    if (preg_match('/^[67]\d{8}$/', $localNumber)) {
        echo "Mobile Format: ✓ Valid Tanzanian mobile number\n";
    } else {
        echo "Mobile Format: ✗ Invalid Tanzanian mobile format\n";
        echo "Expected: Should start with 6 or 7 and be 9 digits total\n";
    }
} else {
    echo "Country: Not a Tanzanian number\n";
}

// Check DLR payload if available
if ($message->dlr_payload) {
    echo "\nDelivery Report Data:\n";
    echo "---------------------\n";
    $dlrData = json_decode($message->dlr_payload, true);
    if ($dlrData) {
        foreach ($dlrData as $key => $value) {
            echo ucfirst($key) . ": " . $value . "\n";
        }
    } else {
        echo "DLR Payload: " . $message->dlr_payload . "\n";
    }
}

// Check campaign details
echo "\nCampaign Information:\n";
echo "---------------------\n";
$campaign = DB::table('campaigns')->where('id', $message->campaign_id)->first();
if ($campaign) {
    echo "Campaign ID: " . $campaign->id . "\n";
    echo "Campaign Name: " . $campaign->name . "\n";
    echo "Campaign Status: " . $campaign->status . "\n";
    echo "Campaign Type: " . $campaign->type . "\n";
} else {
    echo "Campaign not found\n";
}
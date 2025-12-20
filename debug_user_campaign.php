<?php

use App\Models\User;
use App\Models\Campaign;
use App\Models\SmsMessage;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find user by phone or just get the first user if not found
$phone = '255621764385';
$user = User::where('phone', 'like', "%$phone%")->first();

if (!$user) {
    echo "User with phone $phone not found. Listing all users:\n";
    foreach (User::all() as $u) {
        echo "ID: {$u->id}, Name: {$u->name}, Phone: {$u->phone}, Credits: {$u->sms_credits}\n";
    }
    exit;
}

$output = "";
$output .= "User Found: ID {$user->id}, Name: {$user->name}, Phone: {$user->phone}\n";
$output .= "Current SMS Credits: {$user->sms_credits}\n";

// Get latest campaign
$campaign = Campaign::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();

if ($campaign) {
    $output .= "\nLatest Campaign (ID: {$campaign->id}):\n";
    $output .= "Name: {$campaign->name}\n";
    $output .= "Status: {$campaign->status}\n";
    $output .= "Failure Reason: {$campaign->failure_reason}\n";
    $output .= "Estimated Parts: {$campaign->estimated_parts}\n";
    $output .= "Estimated Recipients: {$campaign->estimated_recipients}\n";
    $output .= "Message: {$campaign->message}\n";
    
    $messages = SmsMessage::where('campaign_id', $campaign->id)->get();
    $output .= "Total SMS Messages created: " . $messages->count() . "\n";
    $output .= "Sum of parts_count in messages: " . $messages->sum('parts_count') . "\n";
    
    foreach ($messages as $msg) {
        $output .= " - Msg ID: {$msg->id}, Phone: {$msg->phone}, Parts: {$msg->parts_count}, Status: {$msg->status}\n";
    }
} else {
    $output .= "\nNo campaigns found for this user.\n";
}

file_put_contents('debug_output_clean.txt', $output);
echo "Done writing to debug_output_clean.txt\n";


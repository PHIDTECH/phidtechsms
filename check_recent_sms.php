<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Recent SMS Messages Status:\n";
echo "===========================\n\n";

$messages = DB::table('sms_messages')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'phone', 'status', 'beem_message_id', 'created_at', 'delivered_at', 'failed_at']);

if ($messages->isEmpty()) {
    echo "No SMS messages found in database.\n";
} else {
    foreach ($messages as $msg) {
        echo "ID: " . $msg->id . "\n";
        echo "Phone: " . $msg->phone . "\n";
        echo "Status: " . $msg->status . "\n";
        echo "Beem ID: " . ($msg->beem_message_id ?? 'null') . "\n";
        echo "Sent: " . $msg->created_at . "\n";
        echo "Delivered: " . ($msg->delivered_at ?? 'null') . "\n";
        echo "Failed: " . ($msg->failed_at ?? 'null') . "\n";
        echo "---\n";
    }
}

// Check for stuck messages (sent for more than 5 minutes)
echo "\nChecking for stuck messages (sent > 5 minutes ago):\n";
echo "==================================================\n";

$stuckMessages = DB::table('sms_messages')
    ->where('status', 'sent')
    ->where('created_at', '<', now()->subMinutes(5))
    ->get(['id', 'phone', 'beem_message_id', 'created_at']);

if ($stuckMessages->isEmpty()) {
    echo "No stuck messages found.\n";
} else {
    echo "Found " . $stuckMessages->count() . " stuck messages:\n";
    foreach ($stuckMessages as $msg) {
        $duration = now()->diffInMinutes($msg->created_at);
        echo "ID: " . $msg->id . " | Phone: " . $msg->phone . " | Duration: " . $duration . " minutes\n";
    }
}
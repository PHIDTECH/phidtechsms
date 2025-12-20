<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "SMS Message Status Summary:\n";
echo "==========================\n";

$statusCounts = DB::table('sms_messages')
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

foreach ($statusCounts as $row) {
    echo $row->status . ': ' . $row->count . "\n";
}

echo "\nRecent SMS Messages (last 10):\n";
echo "==============================\n";

$recentMessages = DB::table('sms_messages')
    ->select('id', 'phone', 'status', 'created_at', 'sent_at', 'delivered_at', 'failed_at')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($recentMessages as $msg) {
    echo "ID: {$msg->id} | Phone: {$msg->phone} | Status: {$msg->status} | Created: {$msg->created_at}\n";
    echo "  Sent: " . ($msg->sent_at ?? 'null') . " | Delivered: " . ($msg->delivered_at ?? 'null') . " | Failed: " . ($msg->failed_at ?? 'null') . "\n\n";
}
<?php

use App\Models\Campaign;
use App\Models\SmsMessage;
use App\Http\Controllers\CampaignController;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = Campaign::latest()->first();

echo "Campaign ID: {$campaign->id}\n";
echo "Current Campaign Sender ID: {$campaign->sender_id}\n\n";

// Update campaign sender_id to the actual name
$campaign->update(['sender_id' => 'RODWAY SHOP']);

// Reset failed messages to queued
$failedMessages = $campaign->smsMessages()->where('status', 'failed')->get();
echo "Resetting {$failedMessages->count()} failed messages to queued...\n";

foreach ($failedMessages as $msg) {
    $msg->update([
        'status' => 'queued',
        'failed_at' => null,
        'failure_reason' => null
    ]);
}

echo "\nNow processing campaign...\n";

// Process the campaign
$controller = new CampaignController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('sendCampaignMessages');
$method->setAccessible(true);

try {
    $method->invoke($controller, $campaign);
    echo "Campaign messages sent!\n\n";
    
    // Show results
    $campaign->refresh();
    $sent = $campaign->smsMessages()->where('status', 'sent')->count();
    $failed = $campaign->smsMessages()->where('status', 'failed')->count();
    $queued = $campaign->smsMessages()->where('status', 'queued')->count();
    
    echo "Results:\n";
    echo "  Sent: {$sent}\n";
    echo "  Failed: {$failed}\n";
    echo "  Queued: {$queued}\n";
    
    if ($failed > 0) {
        $failedMsg = $campaign->smsMessages()->where('status', 'failed')->first();
        if ($failedMsg) {
            echo "\nFailure reason: {$failedMsg->failure_reason}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

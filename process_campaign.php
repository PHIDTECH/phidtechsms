<?php

use App\Models\Campaign;
use App\Http\Controllers\CampaignController;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the latest campaign
$campaign = Campaign::latest()->first();

if (!$campaign) {
    echo "No campaign found.\n";
    exit;
}

echo "Campaign ID: {$campaign->id}\n";
echo "Campaign Name: {$campaign->name}\n";
echo "Campaign Status: {$campaign->status}\n";

// Get queued messages
$queuedMessages = $campaign->smsMessages()->where('status', 'queued')->count();
echo "Queued Messages: {$queuedMessages}\n";

if ($queuedMessages > 0) {
    echo "\nAttempting to process campaign...\n";
    
    // Use reflection to call the private method
    $controller = new CampaignController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('processCampaign');
    $method->setAccessible(true);
    
    try {
        $method->invoke($controller, $campaign);
        echo "Campaign processing triggered!\n";
        
        // Refresh and show updated stats
        $campaign->refresh();
        echo "\nUpdated Campaign Status: {$campaign->status}\n";
        echo "Sent Count: {$campaign->sent_count}\n";
        echo "Failed Count: {$campaign->failed_count}\n";
        
        $sentMessages = $campaign->smsMessages()->where('status', 'sent')->count();
        $failedMessages = $campaign->smsMessages()->where('status', 'failed')->count();
        $queuedMessages = $campaign->smsMessages()->where('status', 'queued')->count();
        
        echo "\nMessage Breakdown:\n";
        echo "  Sent: {$sentMessages}\n";
        echo "  Failed: {$failedMessages}\n";
        echo "  Still Queued: {$queuedMessages}\n";
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "No queued messages to process.\n";
}

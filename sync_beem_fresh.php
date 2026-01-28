<?php
/**
 * Sync ONLY your own Beem sender IDs (7 total)
 * Run: php sync_beem_fresh.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SenderID;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Beem Sender ID Sync Script ===\n\n";

// Find admin user (Super Admin)
$adminUser = User::where('role', 'admin')->first();
if (!$adminUser) {
    $adminUser = User::first();
}
$adminUserId = $adminUser ? $adminUser->id : null;
echo "Using admin user ID: " . ($adminUserId ?? 'NULL') . "\n\n";

// Your EXACT sender IDs from Beem dashboard
$mySenderIds = [
    ['sender_id' => 'GEDECOM', 'status' => 'pending', 'sample' => 'To be used in Promotion', 'use_case' => 'promotional'],
    ['sender_id' => 'JOEVE', 'status' => 'approved', 'sample' => 'TO BE USED IN BUSINESS', 'use_case' => 'promotional'],
    ['sender_id' => 'Wedding Day', 'status' => 'approved', 'sample' => 'I WANT TO USE IT FOR PROMOTION', 'use_case' => 'promotional'],
    ['sender_id' => 'DEILETH DAY', 'status' => 'rejected', 'sample' => 'I WANT TO USE IT IN PROMOTIONS', 'use_case' => 'promotional'],
    ['sender_id' => 'DILLETH', 'status' => 'rejected', 'sample' => 'TO BE USED IN MY BUSINESS FOR PROMOTIONS', 'use_case' => 'promotional'],
    ['sender_id' => 'PHIDTECH', 'status' => 'approved', 'sample' => 'THIS IS FOR SENDING CAMPAIGNS', 'use_case' => 'promotional'],
    ['sender_id' => 'PHIDHOST', 'status' => 'approved', 'sample' => 'Dear Customer your order have been received successfully', 'use_case' => 'transactional'],
];

// Step 1: Clear all existing sender IDs
echo "Step 1: Clearing all existing sender ID records...\n";
$deletedCount = SenderID::count();
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
SenderID::query()->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "Deleted {$deletedCount} records.\n\n";

// Step 2: Insert your sender IDs
echo "Step 2: Adding your 7 sender IDs...\n\n";

$approvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;

foreach ($mySenderIds as $item) {
    $status = $item['status'];
    
    if ($status === 'approved') $approvedCount++;
    elseif ($status === 'pending') $pendingCount++;
    elseif ($status === 'rejected') $rejectedCount++;
    
    SenderID::create([
        'user_id' => $adminUserId,
        'sender_id' => $item['sender_id'],
        'use_case' => $item['use_case'],
        'sample_messages' => $item['sample'],
        'status' => $status,
        'beem_sender_id' => null,
        'reviewed_at' => ($status === 'approved') ? now() : null,
        'is_default' => ($item['sender_id'] === 'PHIDTECH'),
    ]);
    
    echo "  + {$item['sender_id']} ({$status})\n";
}

echo "\n=== SYNC COMPLETE ===\n";
echo "Total: " . count($mySenderIds) . "\n";
echo "Approved: {$approvedCount}\n";
echo "Pending: {$pendingCount}\n";
echo "Rejected: {$rejectedCount}\n";
echo "\nDone!\n";

<?php
/**
 * Clear all sender IDs and sync fresh from Beem API
 * Run this script directly: php sync_beem_fresh.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SenderID;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== Beem Sender ID Sync Script ===\n\n";

// Use specific Beem credentials
$apiKey = '9f8e390920107e24';
$secretKey = 'ZDQzZDg1ZWNjNDQxODZmMzRkYTVjNzA0OTA4Y2Y2ZDdmMDY2MGFjOWE4MDEzZDY0ZjUxYzY2Zjk2Y2ZmYWUzNg==';

echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Credentials loaded successfully.\n\n";

// Step 1: Clear all existing sender IDs (use DELETE to avoid foreign key issues)
echo "Step 1: Clearing all existing sender ID records...\n";
$deletedCount = SenderID::count();
SenderID::query()->delete();
echo "Deleted {$deletedCount} records.\n\n";

// Step 2: Fetch sender IDs from Beem API
echo "Step 2: Fetching sender IDs from Beem API...\n";

$url = 'https://apisms.beem.africa/v1/sender-names';

try {
    $response = Http::timeout(60)
        ->withOptions(['verify' => false])
        ->withHeaders([
            'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->get($url);

    echo "API Response Status: " . $response->status() . "\n";

    if ($response->successful()) {
        $data = $response->json();
        $items = $data['data'] ?? [];
        
        echo "Found " . count($items) . " sender IDs from Beem.\n\n";
        
        $syncedCount = 0;
        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;
        
        foreach ($items as $item) {
            $senderId = $item['senderid'] ?? null;
            $status = $item['status'] ?? 'pending';
            $sampleContent = $item['sample_content'] ?? '';
            $useCase = $item['use_case'] ?? '';
            $beemId = $item['id'] ?? null;
            
            // Map Beem status to local status
            $localStatus = $status;
            if ($status === 'active') {
                $localStatus = 'approved';
                $approvedCount++;
            } elseif ($status === 'pending') {
                $pendingCount++;
            } elseif ($status === 'rejected') {
                $rejectedCount++;
            }
            
            if ($senderId) {
                SenderID::create([
                    'user_id' => null,
                    'sender_id' => $senderId,
                    'use_case' => $useCase ?: 'Synced from Beem',
                    'sample_messages' => $sampleContent ?: 'Synced from Beem API',
                    'status' => $localStatus,
                    'beem_sender_id' => $beemId,
                    'reviewed_at' => ($localStatus === 'approved') ? now() : null,
                    'is_default' => false,
                ]);
                $syncedCount++;
                
                // Show progress every 10 items
                if ($syncedCount % 10 == 0) {
                    echo "Synced {$syncedCount} sender IDs...\n";
                }
            }
        }
        
        echo "\n=== SYNC COMPLETE ===\n";
        echo "Total synced: {$syncedCount}\n";
        echo "Approved (active): {$approvedCount}\n";
        echo "Pending: {$pendingCount}\n";
        echo "Rejected: {$rejectedCount}\n";
        
    } else {
        echo "ERROR: API request failed!\n";
        echo "Response: " . $response->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";

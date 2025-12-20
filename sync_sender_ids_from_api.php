<?php

use App\Models\SenderID;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use the specific API credentials provided by the user
$apiKey = '501e41f128d5a9fe';
$secretKey = 'NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==';

echo "Syncing Sender IDs from Beem API...\n";
echo "===================================\n\n";

// Fetch sender names from Beem API
echo "Fetching sender IDs from Beem API...\n";
$response = Http::withHeaders([
    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
    'Content-Type' => 'application/json',
])->get('https://apisms.beem.africa/public/v1/sender-names');

if (!$response->successful()) {
    echo "✗ Failed to fetch sender IDs from Beem API\n";
    echo "Status: " . $response->status() . "\n";
    exit(1);
}

$data = $response->json();
$items = $data['data'] ?? [];

echo "Found " . count($items) . " sender IDs from Beem API\n\n";

$syncedCount = 0;
$updatedCount = 0;
$skippedCount = 0;

foreach ($items as $item) {
    $senderId = $item['senderid'] ?? null;
    $status = $item['status'] ?? 'unknown';
    $sampleContent = $item['sample_content'] ?? 'Synced from Beem API';
    $useCase = $item['use_case'] ?? 'Synced from Beem reseller account';
    $beemId = $item['id'] ?? null;
    
    if (!$senderId) {
        continue;
    }
    
    // Check if this sender ID already exists
    $existingSender = SenderID::where('sender_id', $senderId)->first();
    
    if (!$existingSender) {
        // Create new sender ID as system-wide (user_id = null)
        try {
            SenderID::create([
                'user_id' => null, // System sender ID (available to all users)
                'sender_id' => $senderId,
                'use_case' => $useCase,
                'sample_messages' => $sampleContent,
                'status' => $status === 'active' ? 'approved' : $status,
                'beem_sender_id' => $beemId,
                'reviewed_at' => $status === 'active' ? now() : null,
                'is_default' => false,
            ]);
            
            $statusLabel = $status === 'active' ? 'Active' : ucfirst($status);
            echo "✓ Created: $senderId ($statusLabel)\n";
            $syncedCount++;
        } catch (\Exception $e) {
            echo "✗ Failed to create $senderId: " . $e->getMessage() . "\n";
        }
    } else {
        // Update existing sender ID status if it changed
        $newStatus = $status === 'active' ? 'approved' : $status;
        if ($existingSender->status !== $newStatus || $existingSender->beem_sender_id !== $beemId) {
            $existingSender->update([
                'status' => $newStatus,
                'beem_sender_id' => $beemId ?? $existingSender->beem_sender_id,
                'reviewed_at' => $newStatus === 'approved' ? now() : $existingSender->reviewed_at,
            ]);
            echo "↻ Updated: $senderId (Status: $newStatus)\n";
            $updatedCount++;
        } else {
            echo "- Exists: $senderId ($existingSender->status)\n";
            $skippedCount++;
        }
    }
}

echo "\n===================================\n";
echo "Sync Complete!\n";
echo "  Created: $syncedCount\n";
echo "  Updated: $updatedCount\n";
echo "  Skipped: $skippedCount\n";
echo "  Total:   " . count($items) . "\n";
echo "\nAll sender IDs are now available in the admin panel!\n";

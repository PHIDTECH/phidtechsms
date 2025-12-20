<?php

use Illuminate\Foundation\Application;

require_once __DIR__.'/vendor/autoload.php';

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        //
    })
    ->withExceptions(function ($exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BeemSmsService;
use App\Models\SenderID;

echo "Requesting a test sender ID...\n";

$beemService = new BeemSmsService();

// Request a sender ID
$senderIdName = "RODLINETEST";
$sampleContent = "This is a test message for RodLine SMS service to verify sender ID functionality and compliance with Beem Africa requirements.";

echo "Requesting sender ID: $senderIdName\n";
echo "Sample content: $sampleContent\n\n";

$result = $beemService->requestSenderName($senderIdName, $sampleContent);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

if ($result['success']) {
    // Save to local database
    $senderID = SenderID::create([
        'user_id' => null, // Admin requested
        'sender_id' => $senderIdName,
        'status' => 'pending',
        'purpose' => 'Test sender ID requested via script',
        'sample_content' => $sampleContent,
        'beem_id' => $result['data']['id'] ?? null,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "Sender ID saved to local database with ID: " . $senderID->id . "\n";
} else {
    echo "Failed to request sender ID: " . ($result['error'] ?? 'Unknown error') . "\n";
}

echo "\nNow checking all sender IDs from Beem...\n";
$allSenders = $beemService->getSenderNames();
echo "All sender IDs: " . json_encode($allSenders, JSON_PRETTY_PRINT) . "\n";
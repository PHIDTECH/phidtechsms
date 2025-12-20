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
use Illuminate\Support\Facades\Http;

echo "=== FETCHING ALL BEEM API DATA ===\n\n";

// API Credentials
$apiKey = "501e41f128d5a9fe";
$secretKey = "NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==";
$baseUrl = "https://apisms.beem.africa/v1";

echo "API Key: $apiKey\n";
echo "Secret Key: " . substr($secretKey, 0, 20) . "...\n";
echo "Base URL: $baseUrl\n\n";

// Initialize Beem Service
$beemService = new BeemSmsService();

echo "1. FETCHING ALL SENDER IDs\n";
echo "=" . str_repeat("=", 50) . "\n";
$allSenders = $beemService->getSenderNames();
echo "All Sender IDs Response:\n";
echo json_encode($allSenders, JSON_PRETTY_PRINT) . "\n\n";

echo "2. FETCHING APPROVED SENDER IDs\n";
echo "=" . str_repeat("=", 50) . "\n";
$approvedSenders = $beemService->getSenderNames(null, 'approved');
echo "Approved Sender IDs Response:\n";
echo json_encode($approvedSenders, JSON_PRETTY_PRINT) . "\n\n";

echo "3. FETCHING PENDING SENDER IDs\n";
echo "=" . str_repeat("=", 50) . "\n";
$pendingSenders = $beemService->getSenderNames(null, 'pending');
echo "Pending Sender IDs Response:\n";
echo json_encode($pendingSenders, JSON_PRETTY_PRINT) . "\n\n";

echo "4. FETCHING REJECTED SENDER IDs\n";
echo "=" . str_repeat("=", 50) . "\n";
$rejectedSenders = $beemService->getSenderNames(null, 'rejected');
echo "Rejected Sender IDs Response:\n";
echo json_encode($rejectedSenders, JSON_PRETTY_PRINT) . "\n\n";

echo "5. FETCHING ACCOUNT BALANCE\n";
echo "=" . str_repeat("=", 50) . "\n";
try {
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
        'Content-Type' => 'application/json',
    ])->get($baseUrl . '/account/balance');

    if ($response->successful()) {
        echo "Account Balance Response:\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "Failed to fetch account balance: " . $response->body() . "\n\n";
    }
} catch (Exception $e) {
    echo "Error fetching account balance: " . $e->getMessage() . "\n\n";
}

echo "6. FETCHING SMS HISTORY\n";
echo "=" . str_repeat("=", 50) . "\n";
try {
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
        'Content-Type' => 'application/json',
    ])->get($baseUrl . '/sms');

    if ($response->successful()) {
        echo "SMS History Response:\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "Failed to fetch SMS history: " . $response->body() . "\n\n";
    }
} catch (Exception $e) {
    echo "Error fetching SMS history: " . $e->getMessage() . "\n\n";
}

echo "7. FETCHING ACCOUNT INFORMATION\n";
echo "=" . str_repeat("=", 50) . "\n";
try {
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
        'Content-Type' => 'application/json',
    ])->get($baseUrl . '/account');

    if ($response->successful()) {
        echo "Account Information Response:\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "Failed to fetch account information: " . $response->body() . "\n\n";
    }
} catch (Exception $e) {
    echo "Error fetching account information: " . $e->getMessage() . "\n\n";
}

echo "8. TESTING DIRECT API CALLS\n";
echo "=" . str_repeat("=", 50) . "\n";

// Test different endpoints
$endpoints = [
    '/sender-names',
    '/sender-names?status=approved',
    '/sender-names?status=pending',
    '/sender-names?status=active',
    '/templates',
    '/contacts',
    '/groups'
];

foreach ($endpoints as $endpoint) {
    echo "Testing endpoint: $endpoint\n";
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
            'Content-Type' => 'application/json',
        ])->get($baseUrl . $endpoint);

        echo "Status: " . $response->status() . "\n";
        if ($response->successful()) {
            $data = $response->json();
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Error: " . $response->body() . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    echo str_repeat("-", 50) . "\n";
}

echo "\n=== SUMMARY ===\n";
if ($allSenders['success']) {
    $totalSenders = count($allSenders['data'] ?? []);
    echo "Total Sender IDs found: $totalSenders\n";
    
    $statusCounts = [];
    foreach ($allSenders['data'] ?? [] as $sender) {
        $status = $sender['status'] ?? 'unknown';
        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    }
    
    echo "Status breakdown:\n";
    foreach ($statusCounts as $status => $count) {
        echo "  - $status: $count\n";
    }
} else {
    echo "Failed to fetch sender IDs: " . ($allSenders['error'] ?? 'Unknown error') . "\n";
}

echo "\nDone!\n";
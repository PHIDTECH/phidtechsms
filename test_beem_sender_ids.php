<?php

require_once 'vendor/autoload.php';

use App\Services\BeemSmsService;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Testing Beem API Configuration...\n";
echo "API Key: " . (env('BEEM_API_KEY') ? 'Set (' . substr(env('BEEM_API_KEY'), 0, 10) . '...)' : 'Not set') . "\n";
echo "Secret Key: " . (env('BEEM_SECRET_KEY') ? 'Set (' . substr(env('BEEM_SECRET_KEY'), 0, 10) . '...)' : 'Not set') . "\n";
echo "Base URL: " . (env('BEEM_BASE_URL') ?: 'Not set') . "\n";
echo "\n";

$beemService = new BeemSmsService();

echo "Testing getSenderNames() method...\n";
$result = $beemService->getSenderNames();
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
echo "\n";

echo "Testing getSenderNames() with approved status...\n";
$approvedResult = $beemService->getSenderNames(null, 'approved');
echo "Approved Result: " . json_encode($approvedResult, JSON_PRETTY_PRINT) . "\n";
echo "\n";

echo "Testing getSenderNames() with pending status...\n";
$pendingResult = $beemService->getSenderNames(null, 'pending');
echo "Pending Result: " . json_encode($pendingResult, JSON_PRETTY_PRINT) . "\n";
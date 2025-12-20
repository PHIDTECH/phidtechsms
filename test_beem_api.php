<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BeemSmsService;

echo "Testing Beem API Configuration...\n";
echo "=================================\n\n";

$service = new BeemSmsService();

// Test API connection by trying to get balance
try {
    $balance = $service->getBalance();
    if ($balance['success']) {
        echo "API Connection: SUCCESS\n";
        echo "Balance: " . $balance['balance'] . " credits\n";
    } else {
        echo "API Connection: FAILED\n";
        echo "Error: " . $balance['error'] . "\n";
    }
} catch (Exception $e) {
    echo "API Connection: ERROR\n";
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\nConfiguration Check:\n";
echo "API Key: " . (config('services.beem.api_key') ? 'SET' : 'NOT SET') . "\n";
echo "Secret Key: " . (config('services.beem.secret_key') ? 'SET' : 'NOT SET') . "\n";
echo "Base URL: " . config('services.beem.base_url') . "\n";
echo "Webhook URL: " . env('WEBHOOK_URL', config('app.url')) . "/webhooks/beem/dlr\n";

// Test webhook URL accessibility
echo "\nWebhook URL Analysis:\n";
$webhookUrl = env('WEBHOOK_URL', config('app.url')) . "/webhooks/beem/dlr";
echo "Full Webhook URL: " . $webhookUrl . "\n";

if (strpos($webhookUrl, 'localhost') !== false) {
    echo "⚠️  WARNING: Webhook URL contains 'localhost' - this won't work for external services!\n";
    echo "   External services like Beem API cannot reach localhost URLs.\n";
    echo "   For production, update WEBHOOK_URL in .env to your actual domain.\n";
    echo "   For development, consider using ngrok or similar tunneling service.\n";
} else {
    echo "✅ Webhook URL looks good for production use.\n";
}
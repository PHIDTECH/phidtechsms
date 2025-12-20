<?php

use App\Services\BeemSmsService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$beemService = new BeemSmsService();

echo "Fetching sender IDs from Beem API...\n\n";

// Use reflection to call the private method
$reflection = new ReflectionClass($beemService);
$method = $reflection->getMethod('makeApiRequest');
$method->setAccessible(true);

try {
    $result = $method->invoke($beemService, 'GET', '/sender-ids');
    
    echo "API Response:\n";
    print_r($result);
    
    if ($result['success'] && isset($result['data'])) {
        echo "\n\nSender IDs:\n";
        if (isset($result['data']['data'])) {
            foreach ($result['data']['data'] as $sender) {
                echo "  - " . ($sender['sender_id'] ?? $sender['name'] ?? json_encode($sender)) . "\n";
            }
        } else {
            print_r($result['data']);
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

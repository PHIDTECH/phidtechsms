<?php

use App\Services\BeemSmsService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$beemService = new BeemSmsService();

echo "Fetching sender names from Beem API...\n\n";

// Use reflection to call the private method
$reflection = new ReflectionClass($beemService);
$method = $reflection->getMethod('makeApiRequest');
$method->setAccessible(true);

try {
    // Fetch all sender names
    $result = $method->invoke($beemService, 'GET', '/sender-names');
    
    if ($result['success'] && isset($result['data']['data'])) {
        echo "Active Sender IDs:\n";
        echo "==================\n";
        foreach ($result['data']['data'] as $sender) {
            $status = $sender['status'] ?? 'unknown';
            $senderId = $sender['senderid'] ?? 'N/A';
            $statusIcon = $status === 'active' ? '✓' : '✗';
            
            echo "{$statusIcon} {$senderId} (Status: {$status})\n";
            
            if ($status === 'active') {
                echo "   → Use this exact string: '{$senderId}'\n";
            }
        }
    } else {
        echo "Failed to fetch sender names\n";
        print_r($result);
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

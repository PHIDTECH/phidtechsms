<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BeemSmsService;

$service = new BeemSmsService();

$groupName = 'TraeTestGroup_' . date('YmdHis');
$description = 'Sanity check from test_beem_contacts.php';

echo "Attempting createAddressBook: {$groupName}\n";

echo "Beem Contacts base: " . config('services.beem_contacts.base') . "\n";

try {
    $result = $service->createAddressBook($groupName, $description);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nCheck storage/logs/laravel.log for 'Beem createAddressBook request' entries with base + url.\n";
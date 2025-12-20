<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once __DIR__.'/vendor/autoload.php';

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BeemSmsService;

echo "Testing Beem API Configuration...\n";

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
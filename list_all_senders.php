<?php

use App\Models\SenderID;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Sender IDs in Database:\n";
echo "=======================\n\n";

$systemSenders = SenderID::whereNull('user_id')->orderBy('sender_id')->get();
$userSenders = SenderID::whereNotNull('user_id')->orderBy('sender_id')->get();

echo "System-Wide Sender IDs (Available to All Users):\n";
echo "-------------------------------------------------\n";
foreach ($systemSenders as $sender) {
    $statusLabel = ucfirst($sender->status);
    echo "  ✓ {$sender->sender_id} ({$statusLabel})\n";
}

echo "\nUser-Specific Sender IDs:\n";
echo "-------------------------\n";
foreach ($userSenders as $sender) {
    $statusLabel = ucfirst($sender->status);
    echo "  • {$sender->sender_id} ({$statusLabel}) - User ID: {$sender->user_id}\n";
}

echo "\n=======================\n";
echo "Total: " . ($systemSenders->count() + $userSenders->count()) . " sender IDs\n";
echo "  System-Wide: " . $systemSenders->count() . "\n";
echo "  User-Specific: " . $userSenders->count() . "\n";

<?php

use App\Models\SenderID;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Current Sender IDs in Database:\n";
echo "================================\n\n";

$senderIds = SenderID::orderBy('created_at', 'desc')->get();

foreach ($senderIds as $sender) {
    $userInfo = $sender->user_id ? "User: {$sender->user_id}" : "System (null)";
    echo "- {$sender->sender_id} | Status: {$sender->status} | $userInfo\n";
}

echo "\nTotal: " . $senderIds->count() . " sender IDs\n";

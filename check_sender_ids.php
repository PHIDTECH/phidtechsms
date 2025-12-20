<?php

use App\Models\SenderID;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$senderIds = SenderID::all();

echo "Registered Sender IDs:\n";
foreach ($senderIds as $sid) {
    echo "ID: {$sid->id}, Name: {$sid->sender_id}, Status: {$sid->status}, User: {$sid->user_id}\n";
}

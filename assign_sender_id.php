<?php

use App\Models\User;
use App\Models\SenderID;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::where('phone', '+255621764385')->first();

if (!$user) {
    echo "User with phone +255621764385 not found.\n";
    exit;
}

echo "User found: {$user->name} (ID: {$user->id})\n";

// Check if STORYZETU sender ID already exists for this user
$existingSender = SenderID::where('user_id', $user->id)
    ->where('sender_id', 'STORYZETU')
    ->first();

if ($existingSender) {
    echo "STORYZETU already assigned to this user.\n";
    echo "Status: {$existingSender->status}\n";
    
    // Update to approved if not already
    if ($existingSender->status !== 'approved') {
        $existingSender->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
        echo "✓ Updated status to approved\n";
    }
} else {
    // Create new sender ID for this user
    $senderID = SenderID::create([
        'user_id' => $user->id,
        'sender_id' => 'STORYZETU',
        'use_case' => 'Business communications and customer notifications',
        'sample_messages' => 'Order confirmations, delivery updates, promotional messages',
        'status' => 'approved', // Set as approved since it's active in Beem
        'reviewed_at' => now(),
        'is_default' => false,
    ]);
    
    echo "✓ STORYZETU assigned to user {$user->name}\n";
    echo "Sender ID: {$senderID->sender_id}\n";
    echo "Status: {$senderID->status}\n";
}

// Show all sender IDs for this user
echo "\nAll sender IDs for this user:\n";
$allSenders = SenderID::where('user_id', $user->id)->get();
foreach ($allSenders as $sender) {
    $default = $sender->is_default ? ' (DEFAULT)' : '';
    echo "  - {$sender->sender_id} (Status: {$sender->status}){$default}\n";
}

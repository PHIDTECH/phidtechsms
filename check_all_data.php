<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\SenderID;

echo "Checking all users in database:\n";
echo "===============================\n";
$users = User::all();
echo "Total users found: " . $users->count() . "\n\n";

if ($users->count() > 0) {
    foreach ($users as $user) {
        echo "ID: " . $user->id . "\n";
        echo "Name: " . $user->name . "\n";
        echo "Phone: " . $user->phone . "\n";
        echo "Email: " . ($user->email ?? 'null') . "\n";
        echo "Role: " . $user->role . "\n";
        echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
        echo "Phone Verified: " . ($user->phone_verified ? 'Yes' : 'No') . "\n";
        echo "Created: " . $user->created_at . "\n";
        echo "---\n";
    }
}

echo "\nChecking Sender IDs:\n";
echo "===================\n";
$senderIds = SenderID::all();
echo "Total sender IDs found: " . $senderIds->count() . "\n\n";

if ($senderIds->count() > 0) {
    foreach ($senderIds as $senderId) {
        echo "ID: " . $senderId->id . "\n";
        echo "Sender ID: " . $senderId->sender_id . "\n";
        echo "User ID: " . $senderId->user_id . "\n";
        echo "Status: " . $senderId->status . "\n";
        echo "Created: " . $senderId->created_at . "\n";
        echo "---\n";
    }
}

echo "\nLooking specifically for RodlineHost:\n";
echo "====================================\n";
$rodlineHost = SenderID::where('sender_id', 'RodlineHost')->first();
if ($rodlineHost) {
    echo "Found RodlineHost sender ID!\n";
    echo "ID: " . $rodlineHost->id . "\n";
    echo "User ID: " . $rodlineHost->user_id . "\n";
    echo "Status: " . $rodlineHost->status . "\n";
    echo "Created: " . $rodlineHost->created_at . "\n";
    
    // Check if the user still exists
    $user = User::find($rodlineHost->user_id);
    if ($user) {
        echo "Associated user found:\n";
        echo "  Name: " . $user->name . "\n";
        echo "  Phone: " . $user->phone . "\n";
        echo "  Email: " . ($user->email ?? 'null') . "\n";
        echo "  Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    } else {
        echo "WARNING: Associated user (ID: " . $rodlineHost->user_id . ") not found!\n";
    }
} else {
    echo "RodlineHost sender ID not found in database.\n";
}

// Check database file size and modification time
echo "\nDatabase file information:\n";
echo "=========================\n";
$dbPath = database_path('database.sqlite');
if (file_exists($dbPath)) {
    echo "Database file exists: " . $dbPath . "\n";
    echo "File size: " . filesize($dbPath) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($dbPath)) . "\n";
} else {
    echo "Database file not found at: " . $dbPath . "\n";
}
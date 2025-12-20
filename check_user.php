<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "Checking for phone number +255769500302 in users table:\n";
echo "========================================================\n\n";

$user = User::where('phone', '+255769500302')->first();

if ($user) {
    echo "User found:\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Phone: " . $user->phone . "\n";
    echo "Email: " . ($user->email ?? 'null') . "\n";
    echo "Phone Verified: " . ($user->phone_verified ? 'Yes' : 'No') . "\n";
    echo "Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "Role: " . $user->role . "\n";
    echo "Created At: " . $user->created_at . "\n";
} else {
    echo "No user found with phone number +255769500302\n";
}

echo "\nTotal users in database: " . User::count() . "\n";

// Check for similar phone numbers
echo "\nChecking for similar phone numbers:\n";
$similarUsers = User::where('phone', 'LIKE', '%769500302%')->get();
if ($similarUsers->count() > 0) {
    echo "Found " . $similarUsers->count() . " users with similar phone numbers:\n";
    foreach ($similarUsers as $user) {
        echo "- " . $user->phone . " (ID: " . $user->id . ", Name: " . $user->name . ")\n";
    }
} else {
    echo "No users found with similar phone numbers\n";
}
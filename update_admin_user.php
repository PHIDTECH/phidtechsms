<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Updating admin user...\n";
    
    // Find user by phone number
    $user = User::where('phone', '+255769500302')->first();
    
    if ($user) {
        echo "Found existing user:\n";
        echo "ID: " . $user->id . "\n";
        echo "Name: " . $user->name . "\n";
        echo "Email: " . ($user->email ?: 'Not set') . "\n";
        echo "Phone: " . $user->phone . "\n";
        echo "Role: " . $user->role . "\n";
        
        // Update user with admin credentials
        $user->update([
            'name' => 'RodLine Admin',
            'email' => 'info@rodline.co.tz',
            'password' => Hash::make('@200r320KK'),
            'role' => 'admin',
            'is_active' => true,
            'phone_verified' => true,
            'phone_verified_at' => now(),
            'sms_credits' => 1000,
        ]);
        
        echo "\nUser updated successfully!\n";
        echo "New details:\n";
        echo "ID: " . $user->id . "\n";
        echo "Name: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Phone: " . $user->phone . "\n";
        echo "Role: " . $user->role . "\n";
        echo "SMS Credits: " . $user->sms_credits . "\n";
        echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
        echo "Phone Verified: " . ($user->phone_verified ? 'Yes' : 'No') . "\n";
        
    } else {
        echo "No user found with phone +255769500302\n";
        
        // Create new admin user
        $adminUser = User::create([
            'name' => 'RodLine Admin',
            'email' => 'info@rodline.co.tz',
            'phone' => '+255769500302',
            'password' => Hash::make('@200r320KK'),
            'role' => 'admin',
            'is_active' => true,
            'phone_verified' => true,
            'phone_verified_at' => now(),
            'sms_credits' => 1000,
        ]);
        
        echo "New admin user created!\n";
        echo "ID: " . $adminUser->id . "\n";
        echo "Name: " . $adminUser->name . "\n";
        echo "Email: " . $adminUser->email . "\n";
        echo "Phone: " . $adminUser->phone . "\n";
        echo "Role: " . $adminUser->role . "\n";
    }
    
    // Show all users
    echo "\n--- All Users in Database ---\n";
    $allUsers = User::all();
    foreach ($allUsers as $u) {
        echo "ID: {$u->id} | Name: {$u->name} | Email: " . ($u->email ?: 'Not set') . " | Phone: {$u->phone} | Role: {$u->role} | Active: " . ($u->is_active ? 'Yes' : 'No') . "\n";
    }
    
    echo "\n=== ADMIN LOGIN CREDENTIALS ===\n";
    echo "Email: info@rodline.co.tz\n";
    echo "Phone: +255769500302\n";
    echo "Password: @200r320KK\n";
    echo "Role: admin\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

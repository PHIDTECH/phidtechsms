<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Creating admin user...\n";
    
    // Check if admin user already exists
    $existingUser = User::where('email', 'info@rodline.co.tz')
                       ->orWhere('phone', '+255769500302')
                       ->first();
    
        if ($existingUser) {
        echo "User already exists:\n";
        echo "ID: " . $existingUser->id . "\n";
        echo "Name: " . $existingUser->name . "\n";
        echo "Email: " . $existingUser->email . "\n";
        echo "Phone: " . $existingUser->phone . "\n";
        echo "Role: " . $existingUser->role . "\n";
        echo "Active: " . ($existingUser->is_active ? 'Yes' : 'No') . "\n";
        echo "Phone Verified: " . ($existingUser->phone_verified ? 'Yes' : 'No') . "\n";
        
        // Update existing user to admin if not already
        if ($existingUser->role !== 'admin') {
            $existingUser->update([
                'role' => 'admin',
                'is_active' => true,
                'phone_verified' => true,
                'phone_verified_at' => now(),
                'password' => Hash::make('@200r320KK')
            ]);
            echo "\nUpdated existing user to admin role with new password.\n";
        }
    } else {
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
            'sms_credits' => 1000, // Give admin some initial credits
        ]);
        
        echo "Admin user created successfully!\n";
        echo "ID: " . $adminUser->id . "\n";
        echo "Name: " . $adminUser->name . "\n";
        echo "Email: " . $adminUser->email . "\n";
        echo "Phone: " . $adminUser->phone . "\n";
        echo "Role: " . $adminUser->role . "\n";
        echo "SMS Credits: " . $adminUser->sms_credits . "\n";
    }
    
    // Show all users in database
    echo "\n--- All Users in Database ---\n";
    $allUsers = User::all();
    if ($allUsers->count() > 0) {
        foreach ($allUsers as $user) {
            echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Phone: {$user->phone} | Role: {$user->role} | Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "No users found in database.\n";
    }
    
    echo "\n--- Admin Login Credentials ---\n";
    echo "Email: info@rodline.co.tz\n";
    echo "Phone: +255769500302\n";
    echo "Password: @200r320KK\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

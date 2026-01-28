<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'phidtechnology@gmail.com'],
            [
                'name' => 'Super Admin',
                'email' => 'phidtechnology@gmail.com',
                'phone' => '255000000000',
                'password' => Hash::make('Dativa@@5006'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'sms_credits' => 0,
            ]
        );
    }
}

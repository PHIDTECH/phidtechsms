<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--phone=+255123456789} {--password=admin123} {--name=Admin} {--email=admin@phidtechsms.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user for the SMS platform';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->option('phone');
        $password = $this->option('password');
        $name = $this->option('name');
        $email = $this->option('email');

        // Check if user already exists
        $existingUser = User::where('phone', $phone)->first();
        if ($existingUser) {
            $this->error("User with phone {$phone} already exists!");
            return 1;
        }

        // Create admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'phone_verified_at' => now(),
            'role' => 'admin',
            'sms_credits' => 10000, // Give admin some initial credits (integer - SMS parts)
            'is_active' => true,
        ]);

        $this->info('Admin user created successfully!');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Phone', $user->phone],
                ['Password', $password],
                ['SMS Credits', $user->sms_credits . ' SMS parts'],
                ['Email', $user->email],
                ['Role', $user->role],
            ]
        );

        $this->info('You can now login with:');
        $this->info("Phone: {$phone}");
        $this->info("Password: {$password}");

        return 0;
    }
}
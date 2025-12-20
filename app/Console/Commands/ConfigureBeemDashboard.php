<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class ConfigureBeemDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beem:configure-dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure Beem dashboard credentials for balance synchronization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Configuring Beem Dashboard Credentials');
        $this->line('');
        
        $this->warn('⚠️  Important: These credentials will be used to login to your Beem dashboard');
        $this->warn('   to retrieve SMS balance information automatically.');
        $this->line('');
        
        // Get dashboard email
        $email = $this->ask('Enter your Beem dashboard email address');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Invalid email address format.');
            return Command::FAILURE;
        }
        
        // Get dashboard password
        $password = $this->secret('Enter your Beem dashboard password');
        
        if (empty($password)) {
            $this->error('❌ Password cannot be empty.');
            return Command::FAILURE;
        }
        
        // Confirm password
        $confirmPassword = $this->secret('Confirm your Beem dashboard password');
        
        if ($password !== $confirmPassword) {
            $this->error('❌ Passwords do not match.');
            return Command::FAILURE;
        }
        
        try {
            // Store encrypted credentials
            Setting::updateOrCreate(
                ['key' => 'beem_dashboard_email'],
                ['value' => $email]
            );
            
            Setting::updateOrCreate(
                ['key' => 'beem_dashboard_password'],
                ['value' => encrypt($password)]
            );
            
            $this->info('');
            $this->info('✅ Beem dashboard credentials configured successfully!');
            $this->line('');
            
            // Test the configuration
            if ($this->confirm('Would you like to test the balance synchronization now?', true)) {
                $this->info('Testing balance synchronization...');
                $this->call('beem:sync-balance', ['--force' => true]);
            }
            
            $this->line('');
            $this->info('💡 You can now run automatic balance sync with:');
            $this->line('   php artisan beem:sync-balance');
            $this->line('');
            $this->info('💡 To schedule automatic sync, add this to your cron:');
            $this->line('   0 */6 * * * php ' . base_path('artisan') . ' beem:sync-balance');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to save credentials: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
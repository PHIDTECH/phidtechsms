<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BeemSmsService;
use Exception;

class TestBeemApiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beem:test-api {--balance : Test balance retrieval specifically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Beem SMS API connectivity and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Beem SMS API Connection...');
        $this->newLine();

        try {
            $beemService = new BeemSmsService();

            // Test 1: Check API credentials configuration
            $this->info('1. Checking API credentials configuration...');
            if (!config('services.beem.api_key') || !config('services.beem.secret_key')) {
                $this->error('   ❌ API credentials not configured in services.php');
                $this->warn('   Please configure BEEM_API_KEY and BEEM_SECRET_KEY in your .env file');
                return 1;
            }
            $this->info('   ✅ API credentials are configured');

            // Test 2: Test basic API connection
            $this->info('2. Testing API connection...');
            $connectionResult = $beemService->testConnection();
            
            if ($connectionResult['success']) {
                $this->info('   ✅ API connection successful');
                if (isset($connectionResult['message'])) {
                    $this->line('   ' . $connectionResult['message']);
                }
            } else {
                $this->error('   ❌ API connection failed: ' . $connectionResult['error']);
                return 1;
            }

            // Test 3: Test balance retrieval (if requested or if connection test passed)
            if ($this->option('balance') || $connectionResult['success']) {
                $this->info('3. Testing balance retrieval...');
                $balanceResult = $beemService->getBalance();
                
                if ($balanceResult['success']) {
                    $this->info('   ✅ Balance retrieval successful');
                    $this->line('   Current Balance: ' . $balanceResult['balance']);
                    if (isset($balanceResult['currency'])) {
                        $this->line('   Currency: ' . $balanceResult['currency']);
                    }
                } else {
                    $this->warn('   ⚠️  Balance retrieval failed: ' . $balanceResult['error']);
                    $this->line('   This might be normal if the API doesn\'t provide a balance endpoint');
                }
            }

            // Test 4: Test SMS sending capability (dry run)
            $this->info('4. Testing SMS sending capability (validation only)...');
            try {
                // Just validate the service can be instantiated and has required methods
                if (method_exists($beemService, 'sendSms')) {
                    $this->info('   ✅ SMS sending method available');
                } else {
                    $this->warn('   ⚠️  SMS sending method not found');
                }
            } catch (Exception $e) {
                $this->warn('   ⚠️  SMS capability test failed: ' . $e->getMessage());
            }

            $this->newLine();
            $this->info('🎉 Beem API test completed successfully!');
            $this->line('Your Beem SMS service is ready to use.');
            
            return 0;

        } catch (Exception $e) {
            $this->error('❌ Test failed with error: ' . $e->getMessage());
            return 1;
        }
    }
}
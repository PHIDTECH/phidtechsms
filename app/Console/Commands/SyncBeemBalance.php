<?php

namespace App\Console\Commands;

use App\Services\BeemSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBeemBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beem:sync-balance {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize SMS balance from Beem admin dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Beem balance synchronization from admin dashboard...');
        
        try {
            $beemService = new BeemSmsService();
            $force = $this->option('force');
            
            // Check if dashboard credentials are configured
            $this->info('Checking Beem dashboard credentials...');
            
            $result = $beemService->syncBalance($force);
            
            if ($result['success']) {
                if (isset($result['balance'])) {
                    $this->info('✅ Balance synchronized successfully from Beem dashboard!');
                    $this->info('Current balance: ' . number_format($result['balance']) . ' SMS credits');
                    $this->info('Sync time: ' . $result['sync_time']);
                    
                    // Log successful sync
                    Log::info('Beem SMS balance synchronized from dashboard', [
                        'balance' => $result['balance'],
                        'synced_at' => $result['sync_time'],
                        'command' => 'beem:sync-balance'
                    ]);
                } else {
                    $this->info('ℹ️  ' . $result['message']);
                    if (isset($result['last_sync'])) {
                        $this->info('Last sync: ' . $result['last_sync']);
                    }
                }
            } else {
                $this->error('❌ Balance synchronization failed!');
                $this->error('Error: ' . $result['error']);
                
                // Provide helpful suggestions
                if (strpos($result['error'], 'credentials') !== false) {
                    $this->warn('💡 Make sure to configure Beem dashboard credentials:');
                    $this->line('   - Run: php artisan beem:configure-dashboard');
                    $this->line('   - Or set credentials in admin panel');
                } elseif (strpos($result['error'], 'login') !== false) {
                    $this->warn('💡 Login failed. Please check:');
                    $this->line('   - Dashboard email and password are correct');
                    $this->line('   - Beem dashboard is accessible');
                } elseif (strpos($result['error'], 'parse') !== false || strpos($result['error'], 'extract') !== false) {
                    $this->warn('💡 Could not extract balance from dashboard.');
                    $this->line('   - Dashboard layout may have changed');
                    $this->line('   - Contact support for assistance');
                }
                
                // Log failed sync
                Log::error('Beem SMS balance sync failed', [
                    'error' => $result['error'],
                    'attempted_at' => now(),
                    'command' => 'beem:sync-balance'
                ]);
                
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Command failed with exception: ' . $e->getMessage());
            
            Log::error('Beem Balance Sync Command Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempted_at' => now(),
                'command' => 'beem:sync-balance'
            ]);
            
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}

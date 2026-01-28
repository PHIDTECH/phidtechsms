<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BeemSmsService;
use App\Models\Setting;

class TestBeemCredentials extends Command
{
    protected $signature = 'beem:test-credentials {--set-credentials : Set new API credentials}';
    protected $description = 'Test Beem SMS API credentials or set new ones';

    public function handle()
    {
        if ($this->option('set-credentials')) {
            $this->setCredentials();
            return;
        }

        $this->testCurrentCredentials();
    }

    private function testCurrentCredentials()
    {
        $settings = Setting::getBeemSettings();
        
        $this->info('Current Beem SMS Settings:');
        $this->line('API Key: ' . ($settings['api_key'] ? (strlen($settings['api_key']) > 10 ? substr($settings['api_key'], 0, 10) . '...' : $settings['api_key']) : 'Not set'));
        $this->line('Secret Key: ' . ($settings['secret_key'] ? (strlen($settings['secret_key']) > 10 ? substr($settings['secret_key'], 0, 10) . '...' : $settings['secret_key']) : 'Not set'));
        $this->line('Base URL: ' . $settings['base_url']);
        
        if (!$settings['api_key'] || !$settings['secret_key']) {
            $this->error('API credentials are not configured!');
            return;
        }

        $this->info('Testing connection...');
        $beemService = new BeemSmsService();
        $result = $beemService->testConnection();
        
        if ($result['success']) {
            $this->info('✓ Connection successful!');
            $this->info('✓ Beem SMS API credentials are working correctly!');
            $this->line('');
            $this->warn('Note: Beem SMS API does not provide a balance endpoint.');
            $this->warn('SMS balance must be managed manually through the admin panel.');
        } else {
            $this->error('✗ Connection failed: ' . $result['error']);
        }
    }

    private function setCredentials()
    {
        $this->info('Setting new Beem SMS API credentials...');
        
        $apiKey = $this->ask('Enter API Key');
        $secretKey = $this->secret('Enter Secret Key');
        $baseUrl = $this->ask('Enter Base URL', 'https://apisms.beem.africa/v1');
        $senderId = $this->ask('Enter Default Sender ID', 'Phidtech');
        
        if (!$apiKey || !$secretKey) {
            $this->error('API Key and Secret Key are required!');
            return;
        }
        
        // Test credentials before saving
        $this->info('Testing credentials...');
        $beemService = new BeemSmsService($apiKey, $secretKey);
        $result = $beemService->testConnection();
        
        if ($result['success']) {
            $this->info('✓ Credentials are valid!');
            
            // Save credentials
            Setting::setBeemSettings($apiKey, $secretKey, $baseUrl, $senderId);
            $this->info('✓ Credentials saved successfully!');
            
        } else {
            $this->error('✗ Invalid credentials: ' . $result['error']);
            $this->error('Credentials not saved.');
        }
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConfigureBeemApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beem:configure-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure Beem SMS API credentials interactively';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Beem SMS API Configuration');
        $this->line('This command will help you configure your Beem SMS API credentials.');
        $this->newLine();

        // Get current values from .env
        $currentApiKey = env('BEEM_API_KEY', 'your_beem_api_key_here');
        $currentSecretKey = env('BEEM_SECRET_KEY', 'your_beem_secret_key_here');
        $currentBaseUrl = env('BEEM_BASE_URL', 'https://apisms.beem.africa/v1');
        $currentSenderId = env('BEEM_DEFAULT_SENDER_ID', 'Phidtech');

        // Show current configuration
        $this->info('Current Configuration:');
        $this->line('API Key: ' . ($currentApiKey !== 'your_beem_api_key_here' ? '***' . substr($currentApiKey, -4) : 'Not configured'));
        $this->line('Secret Key: ' . ($currentSecretKey !== 'your_beem_secret_key_here' ? '***' . substr($currentSecretKey, -4) : 'Not configured'));
        $this->line('Base URL: ' . $currentBaseUrl);
        $this->line('Default Sender ID: ' . $currentSenderId);
        $this->newLine();

        // Ask if user wants to update
        if (!$this->confirm('Do you want to update the Beem API configuration?')) {
            $this->info('Configuration unchanged.');
            return 0;
        }

        // Get new values
        $apiKey = $this->ask('Enter your Beem API Key', $currentApiKey !== 'your_beem_api_key_here' ? $currentApiKey : null);
        $secretKey = $this->secret('Enter your Beem Secret Key');
        $baseUrl = $this->ask('Enter Beem API Base URL', $currentBaseUrl);
        $senderId = $this->ask('Enter Default Sender ID', $currentSenderId);

        // Validate inputs
        if (empty($apiKey) || empty($secretKey)) {
            $this->error('API Key and Secret Key are required!');
            return 1;
        }

        // Update .env file
        try {
            $this->updateEnvFile([
                'BEEM_API_KEY' => $apiKey,
                'BEEM_SECRET_KEY' => $secretKey,
                'BEEM_BASE_URL' => $baseUrl,
                'BEEM_DEFAULT_SENDER_ID' => $senderId,
            ]);

            $this->info('✅ Beem API configuration updated successfully!');
            $this->newLine();

            // Test the configuration
            if ($this->confirm('Do you want to test the API connection now?')) {
                $this->call('beem:test-api');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to update configuration: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Update the .env file with new values
     */
    private function updateEnvFile(array $values)
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw new \Exception('.env file not found');
        }

        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                // Update existing key
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                // Add new key at the end
                $envContent .= "\n{$replacement}";
            }
        }

        File::put($envPath, $envContent);

        // Clear config cache to reload new values
        $this->call('config:clear');
    }
}
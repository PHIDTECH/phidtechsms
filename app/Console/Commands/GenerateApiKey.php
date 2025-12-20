<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ApiKey;

class GenerateApiKey extends Command
{
    protected $signature = 'apikey:generate {phone} {name?}';
    protected $description = 'Generate an API key/secret for a user identified by phone';

    public function handle(): int
    {
        $phoneInput = trim($this->argument('phone'));
        $name = $this->argument('name') ?? 'Default';

        $user = $this->findUserByPhoneVariants($phoneInput);
        if (!$user) {
            $this->error('User not found for phone: ' . $phoneInput);
            return Command::FAILURE;
        }

        $key = 'rk_' . Str::random(24);
        $secret = 'rs_' . Str::random(48);
        $hash = hash_hmac('sha256', $secret, config('app.key'));

        $record = ApiKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'key' => $key,
            'secret_hash' => $hash,
            'permissions' => ['sms.send' => true],
            'active' => true,
            'rate_limit_per_min' => 60,
        ]);

        $this->line('API Key: ' . $key);
        $this->line('API Secret: ' . $secret);
        $this->line('Use Basic auth or X-API-KEY/X-API-SECRET headers.');
        return Command::SUCCESS;
    }

    private function findUserByPhoneVariants(string $phone): ?User
    {
        $variants = [$phone];
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $variants[] = '+' . '255' . substr($digits, 1);
            $variants[] = '255' . substr($digits, 1);
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '255')) {
            $variants[] = '+' . $digits;
            $variants[] = '0' . substr($digits, 3);
        }
        return User::whereIn('phone', array_unique($variants))->first();
    }
}


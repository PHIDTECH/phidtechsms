<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\SenderID;
use App\Services\BeemSmsService;

class AssignSenderId extends Command
{
    protected $signature = 'senderid:assign {phone} {sender?}';
    protected $description = 'Assign an approved Sender ID to a user by phone number';

    public function handle(): int
    {
        $phoneInput = trim($this->argument('phone'));
        $providedSender = $this->argument('sender');

        $user = $this->findUserByPhoneVariants($phoneInput);
        if (!$user) {
            $this->error('User not found for phone: ' . $phoneInput);
            return Command::FAILURE;
        }

        $senderName = $providedSender ? trim($providedSender) : null;

        if (!$senderName) {
            $beem = new BeemSmsService();
            $this->info('Retrieving approved sender names from Beem...');
            $res = $beem->getSenderNames();
            if (!($res['success'] ?? false)) {
                $this->warn('Failed to retrieve sender names: ' . ($res['error'] ?? 'unknown'));
                $senderName = strtoupper(trim(config('services.beem.default_sender_id', 'PHIDTECH')));
            } else {
                $approved = [];
                foreach (($res['data'] ?? []) as $item) {
                    $name = $item['senderid'] ?? $item['sender_id'] ?? $item['sender'] ?? $item['name'] ?? null;
                    $status = strtolower($item['status'] ?? '');
                    if ($name && in_array($status, ['approved','active'], true)) {
                        $approved[] = strtoupper($name);
                    }
                }
                $preferred = strtoupper(trim(config('services.beem.default_sender_id', 'PHIDTECH')));
                if (in_array($preferred, $approved, true)) {
                    $senderName = $preferred;
                } elseif (!empty($approved)) {
                    $senderName = $approved[0];
                } else {
                    $this->warn('No approved sender names found from Beem; falling back to default');
                    $senderName = $preferred;
                }
            }
        }

        if (!preg_match('/^[A-Za-z0-9\s\-\.]{3,11}$/', $senderName)) {
            $this->error('Invalid sender name format. Must be 3–11 alphanumeric characters. Given: ' . $senderName);
            return Command::FAILURE;
        }

        $existing = SenderID::where('user_id', $user->id)
            ->whereRaw('LOWER(sender_id) = ?', [strtolower($senderName)])
            ->first();

        if ($existing) {
            $existing->update([
                'sender_id' => $senderName,
                'sender_name' => $senderName,
                'status' => 'approved',
                'approved_at' => now(),
                'business_type' => 'other',
                'use_case' => 'Assigned for testing',
                'sample_messages' => 'Test message for verification',
                'target_countries' => json_encode(['TZ']),
            ]);
            $sender = $existing;
        } else {
            $sender = SenderID::updateOrCreate([
                'user_id' => $user->id,
                'sender_id' => $senderName,
            ], [
            'sender_name' => $senderName,
            'status' => 'approved',
            'approved_at' => now(),
            'business_type' => 'other',
            'use_case' => 'Assigned for testing',
            'sample_messages' => 'Test message for verification',
            'target_countries' => json_encode(['TZ']),
            ]);
        }

        $this->info("Sender ID '{$sender->sender_name}' assigned to user '{$user->name}' (ID: {$user->id}, Phone: {$user->phone}).");
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

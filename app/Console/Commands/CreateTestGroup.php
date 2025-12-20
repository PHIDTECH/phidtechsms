<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ContactGroup;
use App\Models\Contact;

class CreateTestGroup extends Command
{
    protected $signature = 'contacts:create-test-group {phone} {group=Test Group A} {numbers?}';
    protected $description = 'Create a contact group with sample active contacts for a user by phone';

    public function handle(): int
    {
        $phoneInput = trim($this->argument('phone'));
        $groupName = trim($this->argument('group'));
        $rawNumbers = $this->argument('numbers');
        $numbers = $rawNumbers ? preg_split('/[\s,;]+/', $rawNumbers, -1, PREG_SPLIT_NO_EMPTY) : ['+255769500302', '+255712345678'];

        $user = $this->findUserByPhoneVariants($phoneInput);
        if (!$user) {
            $this->error('User not found for phone: ' . $phoneInput);
            return Command::FAILURE;
        }

        $group = ContactGroup::firstOrCreate([
            'user_id' => $user->id,
            'name' => $groupName,
        ], [
            'description' => 'Test group for campaign sending',
            'is_active' => true,
            'color' => '#3B82F6',
        ]);

        $created = 0; $skipped = 0;
        foreach ($numbers as $idx => $raw) {
            $normalized = Contact::normalizePhoneNumber($raw);
            if (!$normalized) { $skipped++; continue; }
            if (Contact::existsForUser($user->id, $normalized)) { $skipped++; continue; }
            Contact::create([
                'user_id' => $user->id,
                'contact_group_id' => $group->id,
                'name' => 'Test ' . ($idx + 1),
                'phone' => $normalized,
                'is_active' => true,
            ]);
            $created++;
        }

        $this->info("Group '{$group->name}' ready (ID: {$group->id}). Contacts created: {$created}, skipped: {$skipped}.");
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

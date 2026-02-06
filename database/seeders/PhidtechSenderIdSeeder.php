<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SenderID;

class PhidtechSenderIdSeeder extends Seeder
{
    public function run(): void
    {
        SenderID::updateOrCreate(
            ['sender_name' => 'PHIDTECH'],
            [
                'user_id' => null,
                'status' => 'approved',
                'business_name' => 'Phidtech SMS',
                'business_type' => 'Technology',
                'purpose' => 'SMS notifications and marketing',
                'sample_message' => 'Your OTP code is 123456',
                'reference_number' => 'PHID-' . time(),
                'application_date' => now(),
                'approved_at' => now(),
            ]
        );
    }
}

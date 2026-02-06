<?php
/**
 * Cleanup script - Delete all data except NYABIYONZA SECONDARY SCHOOL
 * Run this directly on the server: php cleanup_data.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SenderID;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Starting cleanup...\n";

// Find NYABIYONZA user
$nyabiyonzaUser = User::where('name', 'like', '%NYABIYONZA%')->first();

if ($nyabiyonzaUser) {
    echo "Found NYABIYONZA user with ID: {$nyabiyonzaUser->id}\n";
    
    // Disable foreign key checks temporarily
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    // Delete campaigns that reference sender IDs not owned by NYABIYONZA
    $deletedCampaigns = DB::table('campaigns')
        ->whereNotIn('sender_id', function($q) use ($nyabiyonzaUser) {
            $q->select('id')->from('sender_ids')->where('user_id', $nyabiyonzaUser->id);
        })
        ->delete();
    echo "Deleted {$deletedCampaigns} campaigns.\n";
    
    // Delete all sender ID applications except NYABIYONZA
    $deletedSenderIds = SenderID::where(function($q) use ($nyabiyonzaUser) {
        $q->where('user_id', '!=', $nyabiyonzaUser->id)
          ->orWhereNull('user_id');
    })->delete();
    echo "Deleted {$deletedSenderIds} sender ID applications.\n";
    
    // Delete all payments except NYABIYONZA
    $deletedPayments = Payment::where('user_id', '!=', $nyabiyonzaUser->id)->delete();
    echo "Deleted {$deletedPayments} payment transactions.\n";
    
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
} else {
    echo "ERROR: NYABIYONZA user not found!\n";
    exit(1);
}

echo "\nCleanup complete! Only NYABIYONZA data remains.\n";

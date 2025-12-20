<?php

use App\Models\User;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::find(9);

if (!$user) {
    echo "User 9 not found\n";
    exit;
}

echo "User 9 found. Credits: " . $user->sms_credits . "\n";
echo "Type of credits: " . gettype($user->sms_credits) . "\n";

// Attempt to charge 1 credit
echo "Attempting to charge 1 credit...\n";

// We need to be careful not to actually deduct credits permanently if we are just testing.
// But chargeSms commits the transaction.
// So we will manually revert it if successful.

$initialCredits = $user->sms_credits;

try {
    $result = WalletController::chargeSms($user, 1, null);
    
    if ($result) {
        echo "Charge successful!\n";
        $user->refresh();
        echo "New Credits: " . $user->sms_credits . "\n";
        
        // Revert
        echo "Reverting credits...\n";
        $user->update(['sms_credits' => $initialCredits]);
        echo "Reverted to: " . $user->sms_credits . "\n";
    } else {
        echo "Charge FAILED!\n";
        echo "Check laravel.log for details (I added logging).\n";
    }
} catch (\Exception $e) {
    echo "Exception caught in script: " . $e->getMessage() . "\n";
}

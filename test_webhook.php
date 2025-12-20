<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\DB;

echo "Testing Beem Webhook Endpoint\n";
echo "============================\n\n";

// Create a mock request with delivery report data
$mockData = [
    'request_id' => '1', // This should match the SMS message ID in database
    'recipient' => '+255769500302',
    'status' => 'delivered',
    'message_id' => '41496604', // Use the actual beem_message_id from the database
    'timestamp' => date('c'),
];

echo "Mock delivery report data:\n";
print_r($mockData);
echo "\n";

// Create request instance
$request = Request::create('/webhooks/beem/dlr', 'POST', $mockData);
$request->headers->set('Content-Type', 'application/json');
$request->merge($mockData); // Ensure data is properly merged into request

// Create controller instance
$controller = new WebhookController();

try {
    echo "Calling beemDeliveryReport method...\n";
    $response = $controller->beemDeliveryReport($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nChecking SMS message status after webhook processing...\n";

$smsMessage = DB::table('sms_messages')->where('id', 1)->first();
if ($smsMessage) {
    echo "SMS Message ID 1 Status: " . $smsMessage->status . "\n";
    echo "Delivered At: " . ($smsMessage->delivered_at ?? 'null') . "\n";
} else {
    echo "SMS Message not found\n";
}
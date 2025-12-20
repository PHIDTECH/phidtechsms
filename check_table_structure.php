<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "SMS Messages table columns:\n";
$columns = \Schema::getColumnListing('sms_messages');
foreach ($columns as $column) {
    echo "- $column\n";
}

echo "\nSample SMS message data:\n";
$smsMessage = \DB::table('sms_messages')->first();
if ($smsMessage) {
    foreach ($smsMessage as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "No SMS messages found\n";
}
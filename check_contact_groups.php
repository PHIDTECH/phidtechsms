<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ContactGroup;

$id = isset($argv[1]) ? (int) $argv[1] : null;

if ($id) {
    echo "Inspecting contact_groups record with id {$id}:\n";
    $group = ContactGroup::find($id);
    if ($group) {
        echo "ID: {$group->id}\n";
        echo "Name: {$group->name}\n";
        echo "User ID: {$group->user_id}\n";
        echo "Is Default: " . ($group->is_default ? 'Yes' : 'No') . "\n";
        echo "Is Active: " . ($group->is_active ? 'Yes' : 'No') . "\n";
        echo "Beem Address Book ID: " . ($group->beem_address_book_id ?? 'null') . "\n";
        echo "Created At: {$group->created_at}\n";
        echo "Updated At: {$group->updated_at}\n";
        $count = $group->contacts()->count();
        echo "Contacts Count: {$count}\n";
    } else {
        echo "No contact group found with id {$id}.\n";
    }
} else {
    echo "Listing recent contact_groups (id, user_id, name, is_default):\n";
    $groups = ContactGroup::orderBy('id', 'asc')->limit(20)->get(['id','user_id','name','is_default','beem_address_book_id']);
    foreach ($groups as $g) {
        echo "- ID: {$g->id}, User: {$g->user_id}, Name: {$g->name}, Default: " . ($g->is_default ? 'Y' : 'N') . ", Beem: " . ($g->beem_address_book_id ?? 'null') . "\n";
    }
}


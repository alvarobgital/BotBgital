<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Contact;

$phone = '5217227453989';
$contact = Contact::where('phone', $phone)->first();

if ($contact) {
    echo "--- CONTACT INFO ---\n";
    echo "ID: " . $contact->id . "\n";
    echo "Name: " . $contact->name . "\n";
    echo "Type: " . $contact->type . "\n";
} else {
    echo "Contact not found: $phone\n";
}

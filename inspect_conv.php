<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Conversation;
use App\Models\Contact;

$phone = '527227453989'; // Try both formats
$contact = Contact::where('phone', $phone)->first();
if (!$contact) {
    $contact = Contact::where('phone', '5217227453989')->first();
}

if ($contact) {
    $c = Conversation::where('contact_id', $contact->id)->latest()->first();
    if ($c) {
        echo "Conv ID: " . $c->id . "\n";
        echo "State: " . $c->bot_state . "\n";
        echo "Data: " . json_encode($c->bot_state_data, JSON_PRETTY_PRINT) . "\n";
        
        $messages = $c->messages()->latest()->take(5)->get();
        echo "Recent Messages:\n";
        foreach ($messages as $m) {
            echo "[{$m->created_at}] {$m->direction}: {$m->content}\n";
        }
    } else {
        echo "Conversation not found for contact\n";
    }
} else {
    echo "Contact not found\n";
}

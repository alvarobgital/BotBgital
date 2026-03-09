<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BotEngineService;
use App\Models\Conversation;
use App\Models\Contact;

$contact = Contact::firstOrCreate(['phone' => '1234567890'], ['name' => 'Tester']);
$conv = Conversation::firstOrCreate(['contact_id' => $contact->id]);
$conv->status = 'bot_active';
$conv->bot_state = 'show_categories';
$conv->save();

$engine = new BotEngineService();
$replies = $engine->handleSequence($conv, 'Pyme');

echo "\n--- TEST ---\n";
print_r($replies);
echo "\n--- DONE ---\n";

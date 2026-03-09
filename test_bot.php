<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BotEngineService;
use App\Models\Conversation;
use App\Models\Contact;

// 1. Setup Test Contact & Conversation
$contact = Contact::updateOrCreate(
['phone' => '1234567890'],
['name' => 'Tester', 'is_customer' => false]
);
$conv = Conversation::updateOrCreate(
['contact_id' => $contact->id],
['status' => 'bot_active']
);

// Reset state
$conv->bot_state = null;
$conv->bot_state_data = null;
$conv->save();

$engine = new BotEngineService();

function simulate($input)
{
    global $engine, $conv;
    echo "\nUSER: {$input}\n";
    $replies = $engine->handleSequence($conv, $input);

    // Unwind all reply attachments
    if (isset($replies[0]) && is_array($replies[0])) { // if returned multiple (doesn't usually happen)
        print_r($replies);
    }
    else {
        if (isset($replies['text'])) {
            echo "BOT: " . $replies['text'] . "\n";
            if (isset($replies['buttons'])) {
                echo "     [Botones: ";
                foreach ($replies['buttons'] as $b)
                    echo $b['reply']['title'] . " | ";
                echo "]\n";
            }
        }
        else {
            print_r($replies);
        }
    }
}

// 2. Run Flow
simulate('Hola');
simulate('Posible Cliente'); // Should ask CP
simulate('50000'); // Valid CP, should find coverage
// Check coverage zones match
simulate('✅ Sí, mi colonia está'); // Should go to coverage_success
simulate('Hablar con Asesor'); // Should escalate

echo "\n--- TEST DONE ---\n";

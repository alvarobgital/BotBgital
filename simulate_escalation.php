<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BotEngineService;
use App\Models\Conversation;
use App\Models\BotFlowStep;

$phone = '5217227453989';
$conversation = Conversation::whereHas('contact', function($q) use ($phone) {
    $q->where('phone', $phone);
})->latest()->first();

if (!$conversation) {
    echo "Conversation not found for $phone" . PHP_EOL;
    exit;
}

echo "Current state: " . $conversation->bot_state . PHP_EOL;
echo "Simulating entry to step 'not_resolved' (ID 59)..." . PHP_EOL;

$step = BotFlowStep::where('step_key', 'not_resolved')->first();
$botEngine = new BotEngineService();
$response = $botEngine->executeStep($conversation, $step);

echo "Response from executeStep: " . json_encode($response) . PHP_EOL;
echo "Check bot_debug.log for 'BotEngine: Calling notifyTechnicalAlert'" . PHP_EOL;

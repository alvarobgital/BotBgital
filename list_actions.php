<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BotFlowStep;

echo "--- STEPS WITH ACTIONS ---" . PHP_EOL;
$steps = BotFlowStep::whereNotNull('action_type')->where('action_type', '!=', '')->get();

foreach ($steps as $step) {
    echo "ID: " . $step->id . " | Key: " . $step->step_key . " | Action: " . $step->action_type . PHP_EOL;
    echo "Message: " . substr($step->message_text, 0, 50) . "..." . PHP_EOL;
    echo "--------------------------" . PHP_EOL;
}

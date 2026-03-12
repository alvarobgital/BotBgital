<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BotFlowStep;

$text = "He notificado a un asesor de soporte técnico";
$steps = BotFlowStep::where('message_text', 'like', "%$text%")->get();

echo "--- ESCALATION STEPS ---" . PHP_EOL;
foreach ($steps as $step) {
    echo "ID: " . $step->id . PHP_EOL;
    echo "Key: " . $step->step_key . PHP_EOL;
    echo "Action: " . $step->action_type . PHP_EOL;
    echo "Config: " . json_encode($step->action_config) . PHP_EOL;
    echo "------------------------" . PHP_EOL;
}

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BotFlowStep;

$steps = BotFlowStep::all();
foreach ($steps as $s) {
    echo "ID: {$s->id} | Key: {$s->step_key} | Action: {$s->action_type} | Next: {$s->next_step_default}\n";
    echo "Message: " . substr(str_replace("\n", " ", $s->message_text), 0, 60) . "...\n";
    echo "----------------------------------\n";
}

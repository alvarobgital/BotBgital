<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BotFlow;
use App\Models\BotFlowStep;

echo "--- STEPS FOR PROSPECT FLOW ---\n";
$prospectFlow = BotFlow::where('slug', 'prospect')->first();
if ($prospectFlow) {
    echo "Flow Name: {$prospectFlow->name}\n";
    foreach ($prospectFlow->steps()->orderBy('sort_order')->get() as $s) {
        echo "ID: {$s->id} | Key: {$s->step_key} | Action: {$s->action_type} | Next: {$s->next_step_default}\n";
        echo "Msg: " . substr(str_replace("\n", " ", $s->message_text), 0, 80) . "...\n";
        echo "Options: " . json_encode($s->options) . "\n";
        echo "------------------\n";
    }
} else {
    echo "Prospect slug not found\n";
}

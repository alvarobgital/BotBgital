<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BotFlow;
use App\Models\BotFlowStep;

echo "--- FLOWS ---\n";
$flows = BotFlow::all();
foreach ($flows as $f) {
    echo "Slug: {$f->slug} | Name: {$f->name} | Trigger: " . json_encode($f->trigger_keywords) . "\n";
}

echo "\n--- STEPS FOR PROSPECT FLOW ---\n";
$prospectFlow = BotFlow::where('slug', 'prospecto')->first();
if ($prospectFlow) {
    foreach ($prospectFlow->steps()->orderBy('sort_order')->get() as $s) {
        echo "ID: {$s->id} | Key: {$s->step_key} | Action: {$s->action_type} | Next: {$s->next_step_default}\n";
        echo "Msg: " . substr(str_replace("\n", " ", $s->message_text), 0, 80) . "...\n";
        echo "Options: " . json_encode($s->options) . "\n";
        echo "------------------\n";
    }
} else {
    echo "Prospecto flow not found\n";
}

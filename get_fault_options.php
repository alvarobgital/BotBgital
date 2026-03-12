<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BotFlowStep;

$step = BotFlowStep::where('step_key', 'report_fault')->first();
if ($step) {
    echo "--- OPTIONS FOR report_fault ---" . PHP_EOL;
    print_r($step->options);
} else {
    echo "Step not found.";
}

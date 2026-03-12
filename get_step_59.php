<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BotFlowStep;

echo "--- STEP 59 DETAILS ---" . PHP_EOL;
$step = BotFlowStep::find(59);
if ($step) {
    print_r($step->toArray());
} else {
    echo "Step not found.";
}

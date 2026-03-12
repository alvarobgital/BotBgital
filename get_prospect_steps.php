<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BotFlowStep;

$ids = [70, 83, 86];
foreach ($ids as $id) {
    echo "--- STEP $id DETAILS ---" . PHP_EOL;
    $step = BotFlowStep::find($id);
    if ($step) {
        print_r($step->toArray());
    } else {
        echo "Step not found.";
    }
    echo "------------------------" . PHP_EOL;
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$steps = \App\Models\BotFlowStep::whereHas('flow', function ($q) {
    $q->where('slug', 'prospect');
})->select('step_key', 'action_type', 'next_step_default', 'options')->get();

echo json_encode($steps, JSON_PRETTY_PRINT);

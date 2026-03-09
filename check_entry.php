<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = \App\Models\BotFlowStep::whereHas('flow', function ($q) {
    $q->where('slug', 'prospect');
})->where('is_entry_point', true)->get();

echo json_encode($entries->toArray(), JSON_PRETTY_PRINT);

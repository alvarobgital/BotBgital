<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$steps = \App\Models\BotFlowStep::whereHas('flow', function ($q) {
    $q->where('slug', 'plans');
})->orderBy('sort_order')->get();

echo json_encode($steps->toArray(), JSON_PRETTY_PRINT);

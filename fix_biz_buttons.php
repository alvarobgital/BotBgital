<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$step = \App\Models\BotFlowStep::where('step_key', 'business_size')->first();
if (!$step) {
    echo "Step business_size not found!\n";
    exit;
}

echo "BEFORE:\n";
echo json_encode($step->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

$newOptions = [
    [
        "id" => "biz_small",
        "title" => "Negocio Pequeño 🏪",
        "action" => "set_category",
        "action_config" => ["category" => "Pyme"]
    ],
    [
        "id" => "biz_medium",
        "title" => "Empresa Mediana 🏢",
        "action" => "set_category",
        "action_config" => ["category" => "Pyme"]
    ],
    [
        "id" => "biz_large",
        "title" => "Empresa Grande 💎",
        "action" => "set_category",
        "action_config" => ["category" => "Pyme"]
    ]
];

$step->options = $newOptions;
$step->save();

echo "\nAFTER:\n";
echo json_encode($step->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// Also fix Type Home to set category Hogar!
$step2 = \App\Models\BotFlowStep::where('step_key', 'ask_type')->first();
if ($step2) {
    $opts2 = $step2->options;
    $opts2[0] = [
        "id" => "type_home",
        "title" => "Internet Casa 🏠",
        "action" => "set_category",
        "action_config" => ["category" => "Hogar"]
    ];
    $step2->options = $opts2;
    $step2->save();
    echo "\nFixed ask_type as well.\n";
}

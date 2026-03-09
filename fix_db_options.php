<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BotFlowStep;

// 1. Update 'ask_type' options (Internet Casa -> Hogar plans)
$askType = BotFlowStep::where('step_key', 'ask_type')->first();
if ($askType) {
    $askType->options = [
        [
            "id" => "type_home",
            "title" => "Internet Casa 🏠",
            "action" => "set_category",
            "action_config" => ["category" => "Hogar"]
        ],
        [
            "id" => "type_business",
            "title" => "Internet Empresa 🏢",
            "next_step" => "business_size"
        ]
    ];
    $askType->save();
}

// 2. Update 'business_size' options (Direct to respective plan categories)
$bizSize = BotFlowStep::where('step_key', 'business_size')->first();
if ($bizSize) {
    $bizSize->options = [
        [
            "id" => "biz_small",
            "title" => "Negocio Pequeño 🏪",
            "action" => "set_category",
            "action_config" => ["category" => "Negocio"]
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
            "action_config" => ["category" => "Dedicado"]
        ]
    ];
    $bizSize->save();
}

echo "DB Steps modified successfully!\n";

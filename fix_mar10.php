<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. UPDATE PLAN CATEGORIES
$step = \App\Models\BotFlowStep::where('step_key', 'business_size')->first();
if ($step) {
    $newOptions = [
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
    $step->options = $newOptions;
    $step->save();
    echo "business_size options updated.\n";
}

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
    echo "ask_type options updated.\n";
}

// 2. UPDATE WHATSAPP TOKEN IN .ENV
$envPath = base_path('.env');
$envContent = file_get_contents($envPath);
$newToken = 'EAAeq9wShvScBQZCnbR2qw0QGuhozdFhqTe57UVWyuUif8RPq8FL3aVeBFwrQu3wXOq6gUJkEUr9P28Aq6fcNzAUVf8CzDZClMMesqqZBrPLdn7s08ZCOtAnfo9a85TG4nSo5rSE3O1jG6P7vcRYPhZC3XGLvdht8sv5QueI6TBoMubDZAGpAZCtBvcwlCKuXql7LZBfjLdLVflAYTFWyJz2h6eNNJ3qesQSJ27kH7JoJt43OMkkylcwlBstbfx6dDT7oLxZCjdmHzcaDbwJfWYErr8qIeF4D24ccdVKlAQ1gZD';

$envContent = preg_replace('/^WHATSAPP_ACCESS_TOKEN=.*$/m', 'WHATSAPP_ACCESS_TOKEN=' . $newToken, $envContent);
file_put_contents($envPath, $envContent);

echo "WhatsApp Token updated in .env.\n";

// Clear cache
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "Caches cleared.\n";

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Show current welcome step
$welcome = \App\Models\BotFlow::where('slug', 'welcome')->first();
echo "Welcome flow:\n";
if ($welcome) {
    $step = $welcome->steps()->where('is_entry_point', true)->first();
    echo "Step key: " . $step->step_key . "\n";
    echo "Message text:\n" . $step->message_text . "\n";
    echo "Options:\n" . json_encode($step->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Update the message to include smart shortcuts hints
    $newMessage = "🌐 ¡Bienvenido a *BGITAL* Telecomunicaciones!\nSomos tu empresa de *Internet de Fibra Óptica*.\n\n📌 _Atajos rápidos:_ escribe *info*, *web*, *horario* o *pago* para respuestas instantáneas.\n\n¿Cómo podemos ayudarte hoy?";

    // Update options to include the full button set (Soy Cliente + Posible Cliente)
    $newOptions = [
        ['id' => 'client_validation', 'title' => 'Soy Cliente', 'next_flow' => 'client_validation'],
        ['id' => 'opt_prospect', 'title' => 'Posible Cliente', 'next_flow' => 'prospect'],
    ];

    $step->message_text = $newMessage;
    $step->options = $newOptions;
    $step->save();

    echo "Updated welcome message!\n";
    echo "New options:\n" . json_encode($step->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
else {
    echo "Welcome flow not found!\n";
    // List all flows
    $flows = \App\Models\BotFlow::all(['slug', 'category']);
    echo json_encode($flows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

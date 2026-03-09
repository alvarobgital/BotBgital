<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$flows = \App\Models\BotFlow::with('steps')->get();

$output = [];
foreach ($flows as $f) {
    if (!$f->is_active)
        continue;
    $entry = [
        'name' => $f->name,
        'slug' => $f->slug,
        'steps' => []
    ];
    foreach ($f->steps as $s) {
        $entry['steps'][] = [
            'key' => $s->step_key,
            'message' => mb_substr($s->message_text, 0, 30),
            'action' => $s->action_type,
            'next' => $s->next_step_default,
            'options' => $s->options
        ];
    }
    $output[] = $entry;
}
echo json_encode($output, JSON_PRETTY_PRINT);

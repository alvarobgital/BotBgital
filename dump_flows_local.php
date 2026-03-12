<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BotFlow;

$flows = BotFlow::all();
foreach ($flows as $f) {
    $kws = is_array($f->trigger_keywords) ? implode(', ', $f->trigger_keywords) : $f->trigger_keywords;
    echo "ID: {$f->id} | Name: {$f->name} | Slug: {$f->slug} | Keywords: " . $kws . "\n";
    echo "----------------------------------\n";
}

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('display_errors', 1);
error_reporting(E_ALL);

\Illuminate\Support\Facades\DB::table('sessions')->truncate();
echo "Sessions truncated.\n";

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$table = 'contacts';
echo "--- COLUMNS FOR TABLE: $table ---\n";
$columns = Schema::getColumnListing($table);
foreach ($columns as $column) {
    echo "- $column\n";
}

echo "\n--- SAMPLE RECORD ---\n";
$sample = DB::table($table)->first();
print_r($sample);

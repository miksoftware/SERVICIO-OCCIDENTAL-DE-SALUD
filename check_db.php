<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
echo "Tables:\n";
foreach ($tables as $t) {
    echo "  - " . $t->name . "\n";
}

echo "\nSession driver: " . config('session.driver') . "\n";

// Check if sessions table exists
$hasSessionsTable = false;
foreach ($tables as $t) {
    if ($t->name === 'sessions') {
        $hasSessionsTable = true;
        break;
    }
}
echo "Sessions table exists: " . ($hasSessionsTable ? 'YES' : 'NO') . "\n";

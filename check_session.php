<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check if recent consulta has cedulas in session
$consulta = App\Models\Consulta::orderByDesc('id')->first();
echo "Latest consulta: ID={$consulta->id}, status={$consulta->status}\n";

// Check sessions table
$sessions = DB::table('sessions')->get();
echo "Sessions in DB: " . $sessions->count() . "\n";
foreach ($sessions as $s) {
    echo "  Session {$s->id}: user_id={$s->user_id}, last_activity=" . date('Y-m-d H:i:s', $s->last_activity) . "\n";
    
    // Try to decode payload to find cedulas
    $payload = base64_decode($s->payload);
    $data = @unserialize($payload);
    if ($data) {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'consulta_')) {
                echo "    Found: {$key} = " . (is_array($value) ? count($value) . " cedulas" : $value) . "\n";
            }
        }
    }
}

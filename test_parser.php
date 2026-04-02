<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\SOSService;

// Read the saved response HTML
$file = 'storage/app/private/sos_logs/2026-04-02/2026-04-02_060305_f2a54_07_consulta_response_1028181132.html';
$html = file_get_contents($file);
echo "HTML loaded: " . strlen($html) . " bytes\n\n";

// Use reflection to call the private parseConsultaResponse method
$service = new SOSService();
$reflection = new ReflectionMethod($service, 'parseConsultaResponse');
$reflection->setAccessible(true);

$result = $reflection->invoke($service, $html, '1028181132');

echo "=== PARSED RESULT ===\n";
foreach ($result as $key => $value) {
    if (is_array($value)) {
        echo "$key: " . json_encode($value, JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "$key: " . ($value ?? '(null)') . "\n";
    }
}

// Count non-null fields
$filled = 0;
$total = 0;
foreach ($result as $key => $value) {
    if ($key === 'cedula') continue;
    $total++;
    if ($value !== null) $filled++;
}
echo "\n=== SUMMARY: $filled / $total fields filled ===\n";

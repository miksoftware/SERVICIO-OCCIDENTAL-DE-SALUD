<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Services\SOSService;

echo "=== Test SOS Scraper ===" . PHP_EOL;

$service = new SOSService();

echo "1. Login..." . PHP_EOL;
$loginOk = $service->login();
echo "   Login: " . ($loginOk ? "OK" : "FAIL") . PHP_EOL;

if ($loginOk) {
    $cedulas = ['1028181132', '1107057896'];
    foreach ($cedulas as $i => $cedula) {
        echo ($i + 2) . ". Consultando cedula {$cedula}..." . PHP_EOL;
        $result = $service->consultarCedula($cedula);
        if (isset($result['error'])) {
            echo "   ERROR: {$result['error']}" . PHP_EOL;
        } else {
            echo "   Nombre: {$result['primer_nombre']} {$result['segundo_nombre']} {$result['primer_apellido']} {$result['segundo_apellido']}" . PHP_EOL;
            echo "   Estado: {$result['estado']} | Derecho: {$result['derecho']}" . PHP_EOL;
            echo "   Tipo Afiliado: {$result['tipo_afiliado']} | IPS: {$result['ips_primaria']}" . PHP_EOL;
            echo "   Empleador: {$result['empleador_razon_social']}" . PHP_EOL;
        }
    }
}

echo "=== FIN ===" . PHP_EOL;

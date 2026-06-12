<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $controller = app()->make(\App\Http\Controllers\P3_GestionAcademicaBase\CU8_GestionarGrupos\GrupoGeneradorController::class);
    $res = $controller->getResumen(1);
    if (method_exists($res, 'getData')) {
        echo json_encode($res->getData());
    } else {
        echo "NO DATA";
    }
} catch (\Exception $e) {
    echo "ERROR: " . substr($e->getMessage(), 0, 500);
}

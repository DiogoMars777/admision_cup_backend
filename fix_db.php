<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pagos = \App\Models\P2_GestionDePostulantes\Pago::pluck('id_postulante')->toArray();
\App\Models\P2_GestionDePostulantes\Postulante::whereNotIn('id_persona', $pagos)->update(['id_gestionacademica' => null]);
echo "Updated successfully.";

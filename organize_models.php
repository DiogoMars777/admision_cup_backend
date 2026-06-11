<?php
$packages = [
    'P1_GestionDeSeguridadYAcceso' => [
        'Usuario', 'Rol', 'Bitacora', 'PasswordResetToken', 'PersonalAccessToken', 'Session'
    ],
    'P2_GestionDePostulantes' => [
        'Postulante', 'Requisito', 'PostulanteRequisito', 'PostulanteCarrera', 'PostulanteGrupo', 'Pago', 'Comprobante'
    ],
    'P3_GestionAcademicaBase' => [
        'Materia', 'Carrera', 'Aula', 'Grupo', 'Docente', 'Especialidad', 'GestionAcademica', 'GestionCup', 'CupoCarrera', 'Modalidad', 'ModalidadCarrera', 'Horario', 'Evaluacion', 'Nota', 'Asistencia', 'DocenteMateria', 'DocenteEspecialidad', 'DocenteRequisito', 'AspiranteDocente', 'AspiranteRequisito', 'PostulacionDocente', 'MateriaRequisito'
    ],
    'Shared' => [
        'Persona', 'Administrativo', 'Admision', 'SuperAdministrador', 'Cargamasiva', 'Reporte', 'Migration', 'FailedJob', 'Job', 'JobBatch', 'Cache', 'CacheLock'
    ]
];

$modelsDir = __DIR__ . '/app/Models';
$controllersDir = __DIR__ . '/app/Http/Controllers';

// Create directories
foreach (array_keys($packages) as $pkg) {
    if (!is_dir("$modelsDir/$pkg")) {
        mkdir("$modelsDir/$pkg", 0755, true);
    }
}

$modelMap = []; // Model => Namespace

// Move models and update their namespaces
foreach ($packages as $pkg => $models) {
    foreach ($models as $model) {
        $oldPath = "$modelsDir/$model.php";
        $newPath = "$modelsDir/$pkg/$model.php";
        
        if (file_exists($oldPath)) {
            $content = file_get_contents($oldPath);
            $content = str_replace('namespace App\Models;', "namespace App\Models\\$pkg;", $content);
            file_put_contents($newPath, $content);
            unlink($oldPath);
            
            $modelMap[$model] = "App\\Models\\$pkg\\$model";
            echo "Moved $model to $pkg\n";
        }
    }
}

// Update controllers
$dir = new RecursiveDirectoryIterator($controllersDir);
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        foreach ($modelMap as $model => $fullNamespace) {
            // Replace \App\Models\ModelName::
            $content = str_replace("\\App\\Models\\$model::", "\\$fullNamespace::", $content);
            // In case there are use statements (like use App\Models\ModelName;)
            $content = preg_replace("/use App\\\\Models\\\\$model;/", "use $fullNamespace;", $content);
        }

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated references in: " . $file->getFilename() . "\n";
        }
    }
}

echo "Organization complete.\n";

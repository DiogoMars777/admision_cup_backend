<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateDumpDocenteMateria extends Command
{
    protected $signature = 'dump:update-docente-materia';
    protected $description = 'Agrega la tabla docente_materia al db_dump.json';

    public function handle()
    {
        $path = base_path('db_dump.json');
        
        if (!file_exists($path)) {
            $this->error('No se encontró db_dump.json');
            return 1;
        }

        $dump = json_decode(file_get_contents($path), true);
        $rows = DB::table('docente_materia')->get();
        $arr = [];
        foreach ($rows as $r) {
            $arr[] = (array) $r;
        }
        $dump['docente_materia'] = $arr;
        file_put_contents($path, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('db_dump.json actualizado con ' . count($arr) . ' registros de docente_materia.');
        return 0;
    }
}

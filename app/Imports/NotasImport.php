<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use App\Models\Shared\Persona;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Exception;

class NotasImport implements ToModel, WithHeadingRow
{
    private $errores = [];
    private $exitos = 0;

    public function model(array $row)
    {
        try {
            if (!isset($row['carnet']) || !isset($row['materia_nombre']) || !isset($row['evaluacion_id']) || !isset($row['puntaje'])) {
                $this->errores[] = "Fila incompleta. Requiere carnet, materia_nombre, evaluacion_id y puntaje.";
                return null;
            }

            $ci = $row['carnet'];
            $nombreMateria = $row['materia_nombre'];
            $evaluacionId = $row['evaluacion_id'];
            $puntaje = $row['puntaje'];

            $persona = Persona::where('ci', $ci)->first();
            if (!$persona) {
                $this->errores[] = "Carnet {$ci} no encontrado.";
                return null;
            }

            $materia = DB::table('materia')->where('nombre', $nombreMateria)->first();
            if (!$materia) {
                $this->errores[] = "Materia {$nombreMateria} no encontrada.";
                return null;
            }

            // Buscar programacion de esa evaluacion para la materia
            $prog = DB::table('programacion_evaluacion')
                ->where('id_evaluacion', $evaluacionId)
                ->where('id_materia', $materia->id)
                ->first();

            if (!$prog) {
                $this->errores[] = "Evaluación ID {$evaluacionId} no programada para {$nombreMateria}.";
                return null;
            }

            // Actualizar o Insertar Nota
            DB::table('nota')->updateOrInsert(
                [
                    'id_postulante' => $persona->id,
                    'id_programacion_evaluacion' => $prog->id,
                    'id_materia' => $materia->id
                ],
                [
                    'puntaje_obtenido' => $puntaje,
                    'estado' => $puntaje >= 51 ? 'Aprobado' : 'Reprobado',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $this->exitos++;

        } catch (Exception $e) {
            $this->errores[] = "Error en el Carnet {$row['carnet']}: " . $e->getMessage();
        }

        return null;
    }

    public function getResultados()
    {
        return [
            'exitos' => $this->exitos,
            'errores' => $this->errores
        ];
    }
}

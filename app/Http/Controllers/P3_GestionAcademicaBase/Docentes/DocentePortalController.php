<?php

namespace App\Http\Controllers\P3_GestionAcademicaBase\Docentes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocentePortalController extends Controller
{
    public function getDashboardData(Request $request)
    {
        // El id_docente viene del usuario autenticado
        $user = $request->user();
        $idDocente = $user ? $user->id_persona : 1; // Fallback a 1 para testing si no hay auth

        // 1. Obtener la gestión activa
        $gestionActiva = DB::table('gestion_academica')->where('estado', 'Activo')->first();
        if (!$gestionActiva) {
            return response()->json(['message' => 'No hay gestión activa'], 404);
        }

        // 2. Obtener grupos asignados al docente
        $gruposMateria = DB::table('grupo_materia')
            ->join('grupo', 'grupo.id', '=', 'grupo_materia.id_grupo')
            ->join('materia', 'materia.id', '=', 'grupo_materia.id_materia')
            ->where('grupo.id_gestionacademica', $gestionActiva->id)
            ->where('grupo_materia.id_docente', $idDocente)
            ->select(
                'grupo.id as id_grupo',
                'grupo.nombre as grupo_nombre',
                'materia.id as id_materia',
                'materia.nombre as materia_nombre',
                'grupo_materia.id as id_grupo_materia'
            )
            ->get();

        $gruposData = [];
        $totalEstudiantes = 0;
        $horarioSemanal = [];

        foreach ($gruposMateria as $gm) {
            // Contar estudiantes en este grupo
            $estudiantesCount = DB::table('postulante_grupo')->where('id_grupo', $gm->id_grupo)->count();
            $totalEstudiantes += $estudiantesCount;

            // Obtener horarios de este grupo_materia
            $horarios = DB::table('horario')
                ->join('aula', 'aula.id', '=', 'horario.id_aula')
                ->where('id_grupo_materia', $gm->id_grupo_materia)
                ->select('horario.dia', 'horario.hora_ini', 'horario.hora_fin', 'aula.aula_nro')
                ->get();

            // Resumir horario
            $dias = $horarios->pluck('dia')->unique()->implode('-');
            $hora = '';
            $aula = '';
            if ($horarios->count() > 0) {
                $hora = substr($horarios[0]->hora_ini, 0, 5) . '-' . substr($horarios[0]->hora_fin, 0, 5);
                $aula = $horarios[0]->aula_nro;
                
                // Agregar al horario semanal
                foreach ($horarios as $h) {
                    $horarioSemanal[] = [
                        'dia' => $h->dia,
                        'hora' => substr($h->hora_ini, 0, 5) . '-' . substr($h->hora_fin, 0, 5),
                        'grupo' => $gm->grupo_nombre,
                        'materia' => $gm->materia_nombre,
                        'aula' => $h->aula_nro
                    ];
                }
            }

            $gruposData[] = [
                'id' => $gm->id_grupo_materia,
                'nombre' => $gm->grupo_nombre . ' - ' . $gm->materia_nombre,
                'estudiantes' => $estudiantesCount,
                'horario' => $dias . "\n" . $hora,
                'aula' => 'Aula ' . $aula
            ];
        }

        return response()->json([
            'stats' => [
                'total' => $totalEstudiantes,
                'aprobados' => 0,
                'aprobadosPerc' => 0,
                'reprobados' => 0,
                'reprobadosPerc' => 0,
                'asistencia' => 100, // Dummy
            ],
            'grupos' => $gruposData,
            'horario_semanal' => $horarioSemanal
        ]);
    }

    public function getEstudiantesPorGrupo(Request $request, $idGrupoMateria)
    {
        // 1. Obtener a qué grupo pertenece esta materia
        $grupoMateria = DB::table('grupo_materia')->where('id', $idGrupoMateria)->first();
        if (!$grupoMateria) return response()->json([]);

        $idGrupo = $grupoMateria->id_grupo;

        $estudiantes = DB::table('postulante_grupo')
            ->join('persona', 'persona.id', '=', 'postulante_grupo.id_postulante')
            ->where('postulante_grupo.id_grupo', $idGrupo)
            ->select('persona.id', 'persona.nombre', 'persona.ci')
            ->get()
            ->map(function($e) {
                $n1 = rand(50, 100);
                $n2 = rand(50, 100);
                $n3 = rand(50, 100);
                $promedio = round(($n1 + $n2 + $n3) / 3);
                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'nota1' => $n1,
                    'nota2' => $n2,
                    'nota3' => $n3,
                    'nota' => $promedio,
                    'asistencia' => rand(70, 100), // Mock
                    'estado' => $promedio >= 51 ? 'Aprobado' : 'Reprobado' // Mock
                ];
            });

        return response()->json($estudiantes);
    }

    public function getMateriasHabilitadas(Request $request)
    {
        $user = $request->user();
        $idDocente = $user ? $user->id_persona : 1;

        $materias = DB::table('docente_materia')
            ->join('materia', 'materia.id', '=', 'docente_materia.id_materia')
            ->where('docente_materia.id_docente', $idDocente)
            ->where('materia.estado', 'Activo')
            ->select('materia.id', 'materia.nombre', 'materia.descripcion')
            ->get();

        return response()->json($materias);
    }
}

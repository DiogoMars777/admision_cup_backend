<?php

namespace App\Http\Controllers\Pendientes;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\P3_GestionAcademicaBase\GestionAcademica;
use App\Models\P3_GestionAcademicaBase\GestionCup;
use Illuminate\Support\Facades\DB;

class GestionAcademicaController extends Controller
{
    public function index(Request $request)
    {
        $query = GestionAcademica::query()
            ->leftJoin('gestion_cup', 'gestion_academica.id_gestion_cup', '=', 'gestion_cup.id')
            ->select('gestion_academica.*', 'gestion_cup.nombre as cup_nombre');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('gestion_academica.nombre', 'ilike', "%{$search}%")
                  ->orWhere('gestion_academica.año', 'ilike', "%{$search}%");
        }

        $gestiones = $query->orderBy('gestion_academica.id', 'desc')->get();
        return response()->json($gestiones);
    }

    public function getCups()
    {
        return response()->json(GestionCup::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'id_gestion_cup' => 'required|integer',
            'fecha_ini' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_ini',
            'estado' => 'nullable|string|max:20'
        ]);

        if ($request->estado === 'Activo') {
            GestionAcademica::where('estado', 'Activo')->update(['estado' => 'Inactivo']);
        }

        $gestion = GestionAcademica::create([
            'nombre' => $request->nombre,
            'año' => date('Y'), // Año automático
            'id_gestion_cup' => $request->id_gestion_cup,
            'fecha_ini' => $request->fecha_ini,
            'fecha_fin' => $request->fecha_fin,
            'estado' => $request->estado ?? 'Inactivo',
        ]);

        // Crear evaluaciones por defecto si no existen
        $nombres = ['Evaluacion 1', 'Evaluacion 2', 'Evaluacion 3'];
        $evaluacionIds = [];
        foreach ($nombres as $nombre) {
            $evalId = DB::table('evaluacion')->where('nombre_eva', $nombre)->value('id');
            if (!$evalId) {
                $evalId = DB::table('evaluacion')->insertGetId([
                    'nombre_eva' => $nombre,
                    'puntaje_max' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $evaluacionIds[] = $evalId;
        }

        // Obtener todas las materias y generar la programacion_evaluacion con fecha null
        $materias = DB::table('materia')->pluck('id');
        foreach ($materias as $matId) {
            foreach ($evaluacionIds as $evalId) {
                DB::table('programacion_evaluacion')->insert([
                    'id_evaluacion' => $evalId,
                    'id_gestionacademica' => $gestion->id,
                    'id_materia' => $matId,
                    'fecha' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Gestión Académica creada exitosamente'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'id_gestion_cup' => 'required|integer',
            'fecha_ini' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_ini',
            'estado' => 'nullable|string|max:20'
        ]);

        if ($request->estado === 'Activo') {
            GestionAcademica::where('id', '!=', $id)->update(['estado' => 'Inactivo']);
        }

        GestionAcademica::where('id', $id)->update([
            'nombre' => $request->nombre,
            // 'año' se mantiene o se actualiza si es necesario, pero lo dejamos como estaba
            'id_gestion_cup' => $request->id_gestion_cup,
            'fecha_ini' => $request->fecha_ini,
            'fecha_fin' => $request->fecha_fin,
            'estado' => $request->estado ?? 'Inactivo',
        ]);

        return response()->json(['message' => 'Gestión Académica actualizada exitosamente']);
    }

    public function destroy($id)
    {
        // Simple eliminación, se podría agregar validaciones de llaves foráneas si aplica
        GestionAcademica::where('id', $id)->delete();
        return response()->json(['message' => 'Gestión Académica eliminada exitosamente']);
    }

    public function getEvaluaciones($id)
    {
        // Los 3 nombres de evaluación globales
        $nombres = ['Evaluacion 1', 'Evaluacion 2', 'Evaluacion 3'];
        $resultado = [];

        foreach ($nombres as $nombre) {
            // Buscamos si existe alguna programacion para esta gestion con este nombre de evaluacion
            $programacion = DB::table('programacion_evaluacion')
                ->join('evaluacion', 'evaluacion.id', '=', 'programacion_evaluacion.id_evaluacion')
                ->where('programacion_evaluacion.id_gestionacademica', $id)
                ->where('evaluacion.nombre_eva', $nombre)
                ->select('programacion_evaluacion.fecha')
                ->first();

            $resultado[] = [
                'nombre_eva' => $nombre,
                'fecha' => $programacion ? $programacion->fecha : ''
            ];
        }

        return response()->json($resultado);
    }

    public function updateEvaluacion(Request $request, $id)
    {
        $request->validate([
            'nombre_eva' => 'required|string',
            'fecha' => 'required|date'
        ]);

        $nombre = $request->nombre_eva;
        $fecha = $request->fecha;

        // Buscar el ID de la evaluación global
        $evalId = DB::table('evaluacion')->where('nombre_eva', $nombre)->value('id');

        // Si no existe, crearla
        if (!$evalId) {
            $evalId = DB::table('evaluacion')->insertGetId([
                'nombre_eva' => $nombre,
                'puntaje_max' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Obtener todas las materias para asignar la programación
        $materias = DB::table('materia')->pluck('id');
        
        foreach ($materias as $matId) {
            $exists = DB::table('programacion_evaluacion')
                ->where('id_evaluacion', $evalId)
                ->where('id_gestionacademica', $id)
                ->where('id_materia', $matId)
                ->exists();

            if ($exists) {
                DB::table('programacion_evaluacion')
                    ->where('id_evaluacion', $evalId)
                    ->where('id_gestionacademica', $id)
                    ->where('id_materia', $matId)
                    ->update([
                        'fecha' => $fecha,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('programacion_evaluacion')->insert([
                    'id_evaluacion' => $evalId,
                    'id_gestionacademica' => $id,
                    'id_materia' => $matId,
                    'fecha' => $fecha,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Fecha actualizada correctamente']);
    }
}

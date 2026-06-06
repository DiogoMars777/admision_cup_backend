<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequisitoController extends Controller
{
    // --- 1. GESTIÓN DEL CATÁLOGO DE REQUISITOS (BASE) ---
    
    public function getCatalogo()
    {
        return response()->json(DB::table('requisito')->get());
    }

    public function storeCatalogo(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255'
        ]);

        DB::table('requisito')->insert([
            'id_abministrador' => $request->user() ? ($request->user()->id_persona ?? 1) : 1,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => 'Activo',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Requisito base creado en el catálogo.']);
    }

    public function updateCatalogo(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255'
        ]);

        DB::table('requisito')->where('id', $id)->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Requisito base actualizado.']);
    }

    public function deleteCatalogo($id)
    {
        DB::table('requisito')->where('id', $id)->delete();
        return response()->json(['message' => 'Requisito base eliminado.']);
    }

    // --- 2. GESTIÓN DE REQUISITOS ENLAZADOS A POSTULANTES ---

    public function index(Request $request)
    {
        $query = DB::table('postulante_requisito as pr')
            ->join('requisito', 'pr.id_requisito', '=', 'requisito.id')
            ->join('persona as postulante', 'pr.id_postulante', '=', 'postulante.id')
            ->select(
                DB::raw("pr.id_postulante || '-' || pr.id_requisito as id"), // Compatible con PostgreSQL
                'pr.id_postulante',
                'pr.id_requisito',
                'requisito.nombre',
                'pr.estado',
                'pr.observacion',
                'requisito.descripcion',
                'postulante.nombre as nombre_postulante',
                'postulante.ci as ci_postulante',
                'pr.created_at'
            );

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('postulante.nombre', 'ilike', "%{$search}%")
                  ->orWhere('postulante.ci', 'ilike', "%{$search}%")
                  ->orWhere('requisito.nombre', 'ilike', "%{$search}%");
        }

        return response()->json($query->orderBy('pr.created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_postulante' => 'required|exists:persona,id',
            'id_catalogo' => 'required|exists:requisito,id'
        ]);

        // Validar si el postulante ya tiene este requisito asignado
        $existe = DB::table('postulante_requisito')
            ->where('id_postulante', $request->id_postulante)
            ->where('id_requisito', $request->id_catalogo)
            ->exists();

        if ($existe) {
            return response()->json(['message' => 'El postulante ya tiene asignado este requisito.'], 422);
        }

        DB::table('postulante_requisito')->insert([
            'id_postulante' => $request->id_postulante,
            'id_requisito' => $request->id_catalogo,
            'fecha_asignacion' => now()->format('Y-m-d'),
            'estado' => $request->estado ?? 'Pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Requisito enlazado al postulante.']);
    }

    public function updateEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|string',
            'observacion' => 'nullable|string|max:255'
        ]);

        $ids = explode('-', $id);
        if (count($ids) != 2) return response()->json(['message' => 'ID inválido'], 400);

        DB::table('postulante_requisito')
            ->where('id_postulante', $ids[0])
            ->where('id_requisito', $ids[1])
            ->update([
                'estado' => $request->estado,
                'observacion' => $request->observacion,
                'updated_at' => now()
            ]);

        return response()->json(['message' => "Requisito marcado como {$request->estado}"]);
    }

    public function destroy($id)
    {
        $ids = explode('-', $id);
        if (count($ids) != 2) return response()->json(['message' => 'ID inválido'], 400);

        DB::table('postulante_requisito')
            ->where('id_postulante', $ids[0])
            ->where('id_requisito', $ids[1])
            ->delete();

        return response()->json(['message' => 'Enlace de requisito eliminado.']);
    }
}

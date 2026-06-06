<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PostulanteController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->select(
                'persona.id',
                'persona.ci',
                'persona.nombre',
                'persona.sexo',
                'persona.telefono',
                'postulante.fecha_nac',
                'postulante.direccion',
                'postulante.colegio'
            );

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('persona.nombre', 'ilike', "%{$search}%")
                  ->orWhere('persona.ci', 'ilike', "%{$search}%");
        }

        return response()->json($query->orderBy('persona.id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'ci' => 'required|string|unique:persona,ci',
            'nombre' => 'required|string|max:150',
            'fecha_nac' => 'nullable|date',
            'colegio' => 'nullable|string|max:150',
            'email' => 'required|email|unique:usuario,email',
            // Nuevos campos
            'turno' => 'nullable|string|max:50',
            'modalidad_preferida' => 'nullable|string|max:50',
            'carrera1' => 'required|string',
            'modalidad1' => 'required|string',
            'carrera2' => 'nullable|string',
            'modalidad2' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $personaId = DB::table('persona')->insertGetId([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'sexo' => $request->sexo,
                'telefono' => $request->telefono,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('postulante')->insert([
                'id_persona' => $personaId,
                'fecha_nac' => $request->fecha_nac,
                'direccion' => $request->direccion,
                'colegio' => $request->colegio,
                'turno_preferido' => $request->turno ?? 'Mañana',
                'modalidad_preferida' => $request->modalidad_preferida ?? 'Presencial',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Guardar Carrera 1
            if ($request->carrera1 && $request->modalidad1) {
                $carreraId1 = DB::table('carrera')->where('nombre', $request->carrera1)->value('id');
                if (!$carreraId1) $carreraId1 = DB::table('carrera')->insertGetId(['nombre' => $request->carrera1, 'created_at' => now(), 'updated_at' => now()]);
                
                $modalidadId1 = DB::table('modalidad')->where('nombre', $request->modalidad1)->value('id');
                if (!$modalidadId1) $modalidadId1 = DB::table('modalidad')->insertGetId(['nombre' => $request->modalidad1, 'created_at' => now(), 'updated_at' => now()]);

                // Asegurar relación en clase intermedia modalidad_carrera
                $existeRelacion1 = DB::table('modalidad_carrera')->where('id_carrera', $carreraId1)->where('id_modalidad', $modalidadId1)->exists();
                if (!$existeRelacion1) {
                    DB::table('modalidad_carrera')->insert([
                        'id_carrera' => $carreraId1,
                        'id_modalidad' => $modalidadId1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('postulante_carrera')->insert([
                    'id_postulante' => $personaId,
                    'id_carrera' => $carreraId1,
                    'id_modalidad' => $modalidadId1,
                    'prioridad' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Guardar Carrera 2
            if ($request->carrera2 && $request->modalidad2) {
                $carreraId2 = DB::table('carrera')->where('nombre', $request->carrera2)->value('id');
                if (!$carreraId2) $carreraId2 = DB::table('carrera')->insertGetId(['nombre' => $request->carrera2, 'created_at' => now(), 'updated_at' => now()]);
                
                $modalidadId2 = DB::table('modalidad')->where('nombre', $request->modalidad2)->value('id');
                if (!$modalidadId2) $modalidadId2 = DB::table('modalidad')->insertGetId(['nombre' => $request->modalidad2, 'created_at' => now(), 'updated_at' => now()]);

                // Asegurar relación en clase intermedia modalidad_carrera
                $existeRelacion2 = DB::table('modalidad_carrera')->where('id_carrera', $carreraId2)->where('id_modalidad', $modalidadId2)->exists();
                if (!$existeRelacion2) {
                    DB::table('modalidad_carrera')->insert([
                        'id_carrera' => $carreraId2,
                        'id_modalidad' => $modalidadId2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('postulante_carrera')->insert([
                    'id_postulante' => $personaId,
                    'id_carrera' => $carreraId2,
                    'id_modalidad' => $modalidadId2,
                    'prioridad' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Usuario automático ELIMINADO según solicitud. Queda pendiente conectar a otro proceso.

            DB::commit();
            return response()->json(['message' => 'Postulante registrado exitosamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar postulante.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'fecha_nac' => 'nullable|date',
            'colegio' => 'nullable|string|max:150',
        ]);

        DB::beginTransaction();
        try {
            DB::table('persona')->where('id', $id)->update([
                'nombre' => $request->nombre,
                'sexo' => $request->sexo,
                'telefono' => $request->telefono,
                'updated_at' => now(),
            ]);

            DB::table('postulante')->where('id_persona', $id)->update([
                'fecha_nac' => $request->fecha_nac,
                'direccion' => $request->direccion,
                'colegio' => $request->colegio,
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Postulante actualizado.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar postulante.'], 500);
        }
    }

    public function destroy($id)
    {
        DB::table('postulante')->where('id_persona', $id)->delete();
        DB::table('persona')->where('id', $id)->delete();
        return response()->json(['message' => 'Postulante eliminado.']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AspiranteDocenteController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('aspirante_docente')
            ->join('persona', 'aspirante_docente.id_persona', '=', 'persona.id')
            ->select(
                'persona.id',
                'persona.ci',
                'persona.nombre',
                'persona.sexo',
                'persona.telefono',
                'aspirante_docente.grado_academico',
                'aspirante_docente.experiencia',
                'aspirante_docente.estado'
            );

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('persona.nombre', 'ilike', "%{$search}%")
                  ->orWhere('persona.ci', 'ilike', "%{$search}%");
        }

        $aspirantes = $query->orderBy('persona.id', 'desc')->get();

        foreach ($aspirantes as $aspirante) {
            $postulaciones = DB::table('postulacion_docente')
                ->join('materia', 'postulacion_docente.id_materia', '=', 'materia.id')
                ->where('postulacion_docente.id_aspirante_docente', $aspirante->id)
                ->select(
                    'postulacion_docente.id',
                    'materia.id as id_materia',
                    'materia.nombre as materia_nombre',
                    'postulacion_docente.fecha_postulacion',
                    'postulacion_docente.estado'
                )
                ->get();
            
            $aspirante->materias = $postulaciones;
            $aspirante->cantidad_materias = count($postulaciones);
            $aspirante->email = DB::table('usuario')->where('id_persona', $aspirante->id)->value('email') ?? '';
        }

        return response()->json($aspirantes);
    }

    public function getMateriasPostuladas($id)
    {
        $postulaciones = DB::table('postulacion_docente')
            ->join('materia', 'postulacion_docente.id_materia', '=', 'materia.id')
            ->where('postulacion_docente.id_aspirante_docente', $id)
            ->select(
                'postulacion_docente.id as id_postulacion',
                'materia.id as id_materia',
                'materia.nombre',
                'postulacion_docente.fecha_postulacion',
                'postulacion_docente.estado'
            )
            ->get();
            
        return response()->json($postulaciones);
    }

    public function getRequisitosMateria($idAspirante, $idMateria)
    {
        $requisitos = DB::table('materia_requisito')
            ->join('requisito', 'materia_requisito.id_requisito', '=', 'requisito.id')
            ->leftJoin('aspirante_requisito', function ($join) use ($idAspirante) {
                $join->on('materia_requisito.id', '=', 'aspirante_requisito.id_materia_requisito')
                     ->where('aspirante_requisito.id_aspirante', '=', $idAspirante);
            })
            ->where('materia_requisito.id_materia', $idMateria)
            ->select(
                'materia_requisito.id as id_materia_requisito',
                'requisito.nombre as requisito_nombre',
                'requisito.descripcion',
                'materia_requisito.obligatorio',
                DB::raw('COALESCE(aspirante_requisito.cumplido, false) as cumplido'),
                DB::raw("COALESCE(aspirante_requisito.estado, 'Pendiente') as estado"),
                'aspirante_requisito.documento_url'
            )
            ->get();
            
        return response()->json($requisitos);
    }

    public function toggleRequisito(Request $request)
    {
        $request->validate([
            'id_aspirante' => 'required|integer',
            'id_materia_requisito' => 'required|integer',
            'cumplido' => 'required|boolean'
        ]);

        DB::table('aspirante_requisito')->updateOrInsert(
            [
                'id_aspirante' => $request->id_aspirante,
                'id_materia_requisito' => $request->id_materia_requisito
            ],
            [
                'cumplido' => $request->cumplido,
                'estado' => $request->cumplido ? 'Cumplido' : 'Pendiente',
                'updated_at' => now()
            ]
        );

        // Actualizar estado de la postulación si todos están cumplidos
        $this->actualizarEstadoPostulacion($request->id_aspirante, $request->id_materia_requisito);

        return response()->json(['message' => 'Requisito actualizado']);
    }

    private function actualizarEstadoPostulacion($idAspirante, $idMateriaRequisito)
    {
        $idMateria = DB::table('materia_requisito')->where('id', $idMateriaRequisito)->value('id_materia');
        if (!$idMateria) return;

        $requisitos = DB::table('materia_requisito')
            ->where('id_materia', $idMateria)
            ->where('obligatorio', true)
            ->get();

        $cumplidos = DB::table('aspirante_requisito')
            ->join('materia_requisito', 'aspirante_requisito.id_materia_requisito', '=', 'materia_requisito.id')
            ->where('aspirante_requisito.id_aspirante', $idAspirante)
            ->where('materia_requisito.id_materia', $idMateria)
            ->where('materia_requisito.obligatorio', true)
            ->where('aspirante_requisito.cumplido', true)
            ->count();

        $estado = 'En preparación';
        if (count($requisitos) > 0 && count($requisitos) == $cumplidos) {
            $estado = 'Aprobada';
        } else if ($cumplidos > 0) {
            $estado = 'En revisión';
        }

        DB::table('postulacion_docente')
            ->where('id_aspirante_docente', $idAspirante)
            ->where('id_materia', $idMateria)
            ->update(['estado' => $estado]);
    }

    public function postularMateria(Request $request)
    {
        $request->validate([
            'id_aspirante' => 'required|integer',
            'id_materia' => 'required|integer'
        ]);

        $existe = DB::table('postulacion_docente')
            ->where('id_aspirante_docente', $request->id_aspirante)
            ->where('id_materia', $request->id_materia)
            ->exists();

        if ($existe) {
            return response()->json(['message' => 'Ya está postulado a esta materia'], 400);
        }

        DB::table('postulacion_docente')->insert([
            'id_aspirante_docente' => $request->id_aspirante,
            'id_materia' => $request->id_materia,
            'fecha_postulacion' => now(),
            'estado' => 'En preparación',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Postulación registrada exitosamente']);
    }

    public function createAspirante(Request $request)
    {
        $request->validate([
            'ci' => 'required|string|unique:persona,ci',
            'nombre' => 'required|string',
            'email' => 'required|email|unique:usuario,email',
            'telefono' => 'nullable|string',
            'sexo' => 'nullable|string|max:1',
            'grado_academico' => 'required|string',
            'experiencia' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            $personaId = DB::table('persona')->insertGetId([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'sexo' => $request->sexo ?? 'M',
                'telefono' => $request->telefono,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('aspirante_docente')->insert([
                'id_persona' => $personaId,
                'fecha_registro' => now(),
                'grado_academico' => $request->grado_academico,
                'experiencia' => $request->experiencia,
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $rolAspirante = DB::table('rol')->where('nombre', 'Postulante')->value('id'); // O usar un rol específico
            DB::table('usuario')->insert([
                'id_persona' => $personaId,
                'id_rol' => $rolAspirante,
                'email' => $request->email,
                'password' => Hash::make($request->ci),
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Aspirante registrado exitosamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function convertirADocente($id)
    {
        DB::beginTransaction();
        try {
            // Verificar si ya es docente
            $esDocente = DB::table('docente')->where('id_persona', $id)->exists();
            if ($esDocente) {
                return response()->json(['message' => 'El aspirante ya es docente'], 400);
            }

            // Mover a Docente
            DB::table('docente')->insert([
                'id_persona' => $id,
                'grado_academico' => DB::table('aspirante_docente')->where('id_persona', $id)->value('grado_academico') ?? 'Licenciatura',
                'experiencia_docente' => DB::table('aspirante_docente')->where('id_persona', $id)->value('experiencia') ?? 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Asignar rol docente
            $rolDocente = DB::table('rol')->where('nombre', 'Docente')->value('id');
            DB::table('usuario')->where('id_persona', $id)->update([
                'id_rol' => $rolDocente,
                'updated_at' => now()
            ]);

            // Obtener materias aprobadas
            $materiasAprobadas = DB::table('postulacion_docente')
                ->where('id_aspirante_docente', $id)
                ->where('estado', 'Aprobada')
                ->get();

            foreach ($materiasAprobadas as $materia) {
                DB::table('docente_materia')->updateOrInsert(
                    ['id_docente' => $id, 'id_materia' => $materia->id_materia],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // Cambiar estado del aspirante
            DB::table('aspirante_docente')->where('id_persona', $id)->update([
                'estado' => 'Docente Oficial'
            ]);

            // Enviar correo
            $usuario = DB::table('usuario')->where('id_persona', $id)->first();
            $persona = DB::table('persona')->where('id', $id)->first();

            try {
                $htmlContent = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;'>
                    <h2 style='color: #1e3a8a; text-align: center;'>¡Felicidades, nuevo Docente!</h2>
                    <p style='color: #374151; font-size: 16px;'>Estimado/a <b>{$persona->nombre}</b>,</p>
                    <p style='color: #374151; font-size: 16px;'>Usted ha completado exitosamente todos los requisitos de postulación y ha sido habilitado como docente oficial en el Sistema CUP.</p>
                    <div style='background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 0 0 10px 0; color: #111827;'><b>Sus credenciales de acceso:</b></p>
                        <p style='margin: 0 0 5px 0; color: #374151;'><b>Usuario:</b> {$usuario->email}</p>
                        <p style='margin: 0; color: #374151;'><b>Contraseña:</b> {$persona->ci}</p>
                    </div>
                    <p style='color: #6b7280; font-size: 14px; text-align: center; margin-top: 30px;'>
                        Atentamente,<br><b>Dirección Académica CUP</b>
                    </p>
                </div>
                ";

                \Illuminate\Support\Facades\Mail::html($htmlContent, function ($message) use ($usuario) {
                    $message->to($usuario->email)
                            ->subject('Credenciales de Acceso - Sistema CUP');
                });
            } catch (\Exception $e) {
                // ignorar si no hay conf de correo
            }

            DB::commit();
            return response()->json(['message' => 'Aspirante convertido a Docente exitosamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }
}

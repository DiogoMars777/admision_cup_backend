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
                'postulante.colegio',
                'postulante.turno_preferido',
                'postulante.modalidad_preferida'
            );

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('persona.nombre', 'ilike', "%{$search}%")
                  ->orWhere('persona.ci', 'ilike', "%{$search}%");
        }

        $postulantes = $query->orderBy('persona.id', 'desc')->get();

        // Agregar carreras y modalidades de cada postulante
        foreach ($postulantes as $postulante) {
            $carreras = DB::table('postulante_carrera')
                ->join('carrera', 'postulante_carrera.id_carrera', '=', 'carrera.id')
                ->leftJoin('modalidad', 'postulante_carrera.id_modalidad', '=', 'modalidad.id')
                ->where('postulante_carrera.id_postulante', $postulante->id)
                ->select(
                    'carrera.nombre as carrera_nombre',
                    'modalidad.nombre as modalidad_nombre',
                    'postulante_carrera.prioridad'
                )
                ->orderBy('postulante_carrera.prioridad')
                ->get();

            $postulante->carrera1 = '';
            $postulante->modalidad1 = '';
            $postulante->carrera2 = '';
            $postulante->modalidad2 = '';

            foreach ($carreras as $c) {
                if ($c->prioridad == 1) {
                    $postulante->carrera1 = $c->carrera_nombre;
                    $postulante->modalidad1 = $c->modalidad_nombre ?? '';
                } elseif ($c->prioridad == 2) {
                    $postulante->carrera2 = $c->carrera_nombre;
                    $postulante->modalidad2 = $c->modalidad_nombre ?? '';
                }
            }
        }

        return response()->json($postulantes);
    }

    public function getPendientesPago(Request $request)
    {
        // Traer todos los postulantes
        $postulantes = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('usuario', 'usuario.id_persona', '=', 'persona.id')
            ->select(
                'persona.id',
                'persona.ci',
                'persona.nombre',
                'persona.telefono',
                'postulante.colegio',
                'usuario.email',
                'usuario.estado as estado_usuario'
            )
            ->orderBy('persona.nombre')
            ->get();

        $resultado = [];
        foreach ($postulantes as $p) {
            // Contar requisitos totales y entregados
            $totalReqs = DB::table('postulante_requisito')
                ->where('id_postulante', $p->id)->count();
            $entregados = DB::table('postulante_requisito')
                ->where('id_postulante', $p->id)
                ->where('estado', 'Entregado')->count();

            // Solo incluir si tiene requisitos Y todos están entregados
            if ($totalReqs > 0 && $totalReqs === $entregados) {
                // Buscar si ya tiene pago
                $pago = DB::table('pago')->where('id_postulante', $p->id)->latest('fecha')->first();

                $resultado[] = [
                    'id'           => $p->id,
                    'ci'           => $p->ci,
                    'nombre'       => $p->nombre,
                    'telefono'     => $p->telefono,
                    'colegio'      => $p->colegio,
                    'email'        => $p->email,
                    'estado_usuario' => $p->estado_usuario,
                    'tiene_pago'   => !is_null($pago),
                    'pago'         => $pago,
                    'docs_total'   => $totalReqs,
                    'docs_entregados' => $entregados,
                ];
            }
        }

        return response()->json($resultado);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ci' => 'required|string|unique:persona,ci',
            'nombre' => 'required|string|max:150',
            'fecha_nac' => 'nullable|date',
            'colegio' => 'nullable|string|max:150',
            'email' => 'nullable|email',
            // Campos académicos
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
            $this->guardarCarrera($personaId, $request->carrera1, $request->modalidad1, 1);

            // Guardar Carrera 2
            if ($request->carrera2 && $request->modalidad2) {
                $this->guardarCarrera($personaId, $request->carrera2, $request->modalidad2, 2);
            }

            // Auto-asignar todos los requisitos activos al nuevo postulante
            $requisitos = DB::table('requisito')->where('estado', 'Activo')->get();
            foreach ($requisitos as $req) {
                DB::table('postulante_requisito')->insert([
                    'id_postulante' => $personaId,
                    'id_requisito'  => $req->id,
                    'fecha_asignacion' => now()->format('Y-m-d'),
                    'estado' => 'Pendiente',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Crear cuenta de usuario inactiva (se activará al pagar)
            $rolPostulanteId = DB::table('rol')->where('nombre', 'Postulante')->value('id');
            if ($rolPostulanteId) {
                // Usar correo proveído o generar uno genérico para la demo si no hay
                $correoGenerado = strtolower(str_replace(' ', '', explode(' ', $request->nombre)[0])) . $personaId . '@cup.edu.bo';
                DB::table('usuario')->insert([
                    'id_persona' => $personaId,
                    'id_rol' => $rolPostulanteId,
                    'email' => $request->email ?: $correoGenerado,
                    'password' => Hash::make($request->ci), // Contraseña inicial es el CI
                    'estado' => 'Inactivo', // Queda Inactivo hasta procesar el pago
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
            'turno' => 'nullable|string|max:50',
            'modalidad_preferida' => 'nullable|string|max:50',
            'carrera1' => 'nullable|string',
            'modalidad1' => 'nullable|string',
            'carrera2' => 'nullable|string',
            'modalidad2' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar persona
            DB::table('persona')->where('id', $id)->update([
                'nombre' => $request->nombre,
                'sexo' => $request->sexo,
                'telefono' => $request->telefono,
                'updated_at' => now(),
            ]);

            // Actualizar postulante con turno y modalidad preferida
            DB::table('postulante')->where('id_persona', $id)->update([
                'fecha_nac' => $request->fecha_nac,
                'direccion' => $request->direccion,
                'colegio' => $request->colegio,
                'turno_preferido' => $request->turno ?? 'Mañana',
                'modalidad_preferida' => $request->modalidad_preferida ?? 'Presencial',
                'updated_at' => now(),
            ]);

            // Limpiar carreras anteriores y re-insertar
            DB::table('postulante_carrera')->where('id_postulante', $id)->delete();

            // Guardar Carrera 1
            if ($request->carrera1 && $request->modalidad1) {
                $this->guardarCarrera($id, $request->carrera1, $request->modalidad1, 1);
            }

            // Guardar Carrera 2
            if ($request->carrera2 && $request->modalidad2) {
                $this->guardarCarrera($id, $request->carrera2, $request->modalidad2, 2);
            }

            DB::commit();
            return response()->json(['message' => 'Postulante actualizado.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar postulante.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::table('postulante_carrera')->where('id_postulante', $id)->delete();
        DB::table('postulante')->where('id_persona', $id)->delete();
        DB::table('persona')->where('id', $id)->delete();
        return response()->json(['message' => 'Postulante eliminado.']);
    }

    public function pagar($id)
    {
        DB::beginTransaction();
        try {
            // Verificar si el usuario ya está activo
            $usuarioExistente = DB::table('usuario')->where('id_persona', $id)->first();
            if ($usuarioExistente && $usuarioExistente->estado === 'Activo') {
                return response()->json(['message' => 'El postulante ya tiene una cuenta activa.'], 400);
            }

            // Generar Comprobante simulado
            $comprobanteId = DB::table('comprobante')->insertGetId([
                'nro_comprobante' => 'COMP-' . strtoupper(uniqid()),
                'fecha_emision' => now()->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Registrar el Pago
            DB::table('pago')->insert([
                'id_postulante' => $id,
                'id_comprobante' => $comprobanteId,
                'monto' => 300.00,
                'metodo_pago' => 'Pasarela Virtual',
                'codigo_transaccion' => 'TXN-' . rand(10000, 99999),
                'estado' => 'Procesado',
                'fecha' => now()->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Habilitar Usuario y enviarle sus credenciales
            if ($usuarioExistente) {
                DB::table('usuario')->where('id_persona', $id)->update([
                    'estado' => 'Activo',
                    'updated_at' => now(),
                ]);
                $usuario = DB::table('usuario')->where('id_persona', $id)->first();
                $persona = DB::table('persona')->where('id', $id)->first();

                // Enviar el correo real con las credenciales
                try {
                    $htmlContent = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;'>
                        <h2 style='color: #1e3a8a; text-align: center;'>¡Bienvenido a la UAGRM CUP!</h2>
                        <p style='color: #374151; font-size: 16px;'>Estimado/a <b>{$persona->nombre}</b>,</p>
                        <p style='color: #374151; font-size: 16px;'>Su pago de matrícula ha sido procesado exitosamente y su cuenta ha sido habilitada en nuestro sistema.</p>
                        <div style='background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                            <p style='margin: 0 0 10px 0; color: #111827;'><b>Sus credenciales de acceso son:</b></p>
                            <p style='margin: 0 0 5px 0; color: #374151;'><b>Usuario / Correo:</b> {$usuario->email}</p>
                            <p style='margin: 0; color: #374151;'><b>Contraseña:</b> {$persona->ci}</p>
                        </div>
                        <p style='color: #6b7280; font-size: 14px; text-align: center; margin-top: 30px;'>
                            Atentamente,<br><b>Dirección de Admisión CUP UAGRM</b>
                        </p>
                    </div>
                    ";

                    \Illuminate\Support\Facades\Mail::html($htmlContent, function ($message) use ($usuario) {
                        $message->to($usuario->email)
                                ->subject('Credenciales de Acceso y Confirmación de Pago - UAGRM CUP');
                    });
                    
                    \Illuminate\Support\Facades\Log::info("Correo de credenciales enviado exitosamente a: {$usuario->email}");
                } catch (\Exception $mailError) {
                    \Illuminate\Support\Facades\Log::error("Error enviando correo a {$usuario->email}: " . $mailError->getMessage());
                    // Continuamos con el proceso aunque el correo falle
                }
            }

            DB::commit();
            return response()->json(['message' => 'Pago procesado y usuario habilitado exitosamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al procesar el pago.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Método reutilizable para guardar una carrera con su modalidad para un postulante.
     * Crea la carrera/modalidad si no existen, asegura la relación modalidad_carrera,
     * e inserta en postulante_carrera.
     */
    private function guardarCarrera(int $postulantId, string $carreraNombre, string $modalidadNombre, int $prioridad): void
    {
        // Buscar o crear carrera
        $carreraId = DB::table('carrera')->where('nombre', $carreraNombre)->value('id');
        if (!$carreraId) {
            $carreraId = DB::table('carrera')->insertGetId([
                'nombre' => $carreraNombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Buscar o crear modalidad
        $modalidadId = DB::table('modalidad')->where('nombre', $modalidadNombre)->value('id');
        if (!$modalidadId) {
            $modalidadId = DB::table('modalidad')->insertGetId([
                'nombre' => $modalidadNombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Asegurar relación en clase intermedia modalidad_carrera
        $existeRelacion = DB::table('modalidad_carrera')
            ->where('id_carrera', $carreraId)
            ->where('id_modalidad', $modalidadId)
            ->exists();
        if (!$existeRelacion) {
            DB::table('modalidad_carrera')->insert([
                'id_carrera' => $carreraId,
                'id_modalidad' => $modalidadId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insertar en postulante_carrera
        DB::table('postulante_carrera')->insert([
            'id_postulante' => $postulantId,
            'id_carrera' => $carreraId,
            'id_modalidad' => $modalidadId,
            'prioridad' => $prioridad,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\P1_GestionDeSeguridadYAcceso\CU16_GestionarBitacora;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\P1_GestionDeSeguridadYAcceso\Bitacora::query()
            ->join('usuario', 'bitacora.id_usuario', '=', 'usuario.id')
            ->join('persona', 'usuario.id_persona', '=', 'persona.id')
            ->join('rol', 'usuario.id_rol', '=', 'rol.id')
            ->select(
                'bitacora.id',
                'persona.nombre as usuario',
                'rol.nombre as rol',
                'bitacora.accion',
                'bitacora.modulo',
                'bitacora.descripcion',
                'bitacora.fecha',
                'bitacora.hora',
                'bitacora.ip_usuario as ip'
            );

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('persona.nombre', 'ilike', "%{$search}%")
                  ->orWhere('bitacora.descripcion', 'ilike', "%{$search}%");
        }

        if ($request->has('accion') && $request->accion != '') {
            $query->where('bitacora.accion', $request->accion);
        }

        if ($request->has('fecha') && $request->fecha != '') {
            $query->where('bitacora.fecha', $request->fecha);
        }

        return response()->json($query->orderBy('bitacora.id', 'desc')->get());
    }

    public function stats()
    {
        $totalMes = \App\Models\P1_GestionDeSeguridadYAcceso\Bitacora::query()
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->count();

        $hoy = \App\Models\P1_GestionDeSeguridadYAcceso\Bitacora::query()
            ->where('fecha', now()->toDateString())
            ->count();

        $usuariosActivos = \App\Models\P1_GestionDeSeguridadYAcceso\Usuario::query()
            ->where('estado', 'Activo')
            ->count();

        return response()->json([
            'total_mes' => $totalMes,
            'hoy' => $hoy,
            'usuarios_activos' => $usuariosActivos
        ]);
    }
}

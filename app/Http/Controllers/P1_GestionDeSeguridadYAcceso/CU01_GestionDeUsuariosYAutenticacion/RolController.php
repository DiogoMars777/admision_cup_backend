<?php

namespace App\Http\Controllers\P1_GestionDeSeguridadYAcceso\CU01_GestionDeUsuariosYAutenticacion;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\P1_GestionDeSeguridadYAcceso\Rol::query();
        if ($request->has('search')) {
            $query->where('nombre', 'ilike', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'ilike', '%' . $request->search . '%');
        }
        return response()->json($query->orderBy('id', 'asc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:rol,nombre',
            'descripcion' => 'nullable|string|max:255'
        ]);

        \App\Models\P1_GestionDeSeguridadYAcceso\Rol::insert([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Rol creado correctamente.']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:rol,nombre,' . $id,
            'descripcion' => 'nullable|string|max:255'
        ]);

        \App\Models\P1_GestionDeSeguridadYAcceso\Rol::where('id', $id)->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Rol actualizado correctamente.']);
    }

    public function destroy($id)
    {
        // Verificar si hay usuarios usando este rol
        $usersCount = \App\Models\P1_GestionDeSeguridadYAcceso\Usuario::where('id_rol', $id)->count();
        if ($usersCount > 0) {
            return response()->json(['message' => 'No se puede eliminar el rol porque tiene usuarios asignados.'], 400);
        }

        \App\Models\P1_GestionDeSeguridadYAcceso\Rol::where('id', $id)->delete();
        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }
}

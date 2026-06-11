<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGestionAcademica
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Desactivar gestiones cuya fecha_fin ya pasó
        \Illuminate\Support\Facades\DB::table('gestion_academica')
            ->where('estado', 'Activo')
            ->where('fecha_fin', '<', now()->toDateString())
            ->update(['estado' => 'Inactivo']);

        return $next($request);
    }
}

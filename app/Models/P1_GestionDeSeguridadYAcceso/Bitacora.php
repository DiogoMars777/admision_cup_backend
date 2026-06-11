<?php

namespace App\Models\P1_GestionDeSeguridadYAcceso;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    use HasFactory;

    protected $table = 'bitacora';

    protected $fillable = [
        'id_usuario', 'accion', 'modulo', 'descripcion', 'fecha', 'hora', 'ip_usuario'
    ];
}

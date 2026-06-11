<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargamasiva extends Model
{
    use HasFactory;

    protected $table = 'cargamasiva';

    protected $fillable = [
        'id_usuario', 'nombre_archivo', 'tipo_archivo', 'fecha_carga', 'cant_registro', 'registro_correcto', 'registro_error'
    ];
}

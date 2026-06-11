<?php

namespace App\Models\P3_GestionAcademicaBase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupo';

    protected $fillable = [
        'id_gestionacademica', 'nombre', 'cupo_max', 'cant_estudiante', 'modalidad', 'turno', 'estado'
    ];
}

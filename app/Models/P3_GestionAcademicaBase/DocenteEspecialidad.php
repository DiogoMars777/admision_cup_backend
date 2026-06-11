<?php

namespace App\Models\P3_GestionAcademicaBase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocenteEspecialidad extends Model
{
    use HasFactory;

    protected $table = 'docente_especialidad';

    protected $fillable = [
        'id_docente', 'id_especialidad'
    ];
}

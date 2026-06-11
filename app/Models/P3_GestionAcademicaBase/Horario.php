<?php

namespace App\Models\P3_GestionAcademicaBase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use HasFactory;

    protected $table = 'horario';

    protected $fillable = [
        'id_grupo', 'id_docente', 'id_materia', 'id_aula', 'dia', 'hora_ini', 'hora_fin', 'modalidad'
    ];
}

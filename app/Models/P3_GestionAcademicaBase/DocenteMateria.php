<?php

namespace App\Models\P3_GestionAcademicaBase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocenteMateria extends Model
{
    use HasFactory;

    protected $table = 'docente_materia';

    protected $fillable = [
        'id_docente', 'id_materia'
    ];
}

<?php

namespace App\Models\P2_GestionDePostulantes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostulanteRequisito extends Model
{
    use HasFactory;

    protected $table = 'postulante_requisito';

    protected $fillable = [
        'id_postulante', 'id_requisito', 'fecha_asignacion', 'estado', 'observacion'
    ];
}

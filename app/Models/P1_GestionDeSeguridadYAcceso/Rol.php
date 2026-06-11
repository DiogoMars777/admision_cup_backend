<?php

namespace App\Models\P1_GestionDeSeguridadYAcceso;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'rol';

    protected $fillable = [
        'nombre', 'descripcion'
    ];
}

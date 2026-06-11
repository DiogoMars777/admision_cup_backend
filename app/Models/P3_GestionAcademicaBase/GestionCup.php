<?php

namespace App\Models\P3_GestionAcademicaBase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GestionCup extends Model
{
    use HasFactory;

    protected $table = 'gestion_cup';

    protected $fillable = [
        'nombre'
    ];
}

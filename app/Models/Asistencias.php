<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencias extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        "nombre",
        "cargo",
        "dependenciasId",
        "email",
        "telefono",
        "perfil",
        "tipo_enlace",
        "evento"
    ];
}

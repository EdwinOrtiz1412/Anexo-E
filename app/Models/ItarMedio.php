<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItarMedio extends Model
{
    protected $table = "itar_medios";
    protected $fillable = [
        "idITAR",
        "tipo",
        "nombre",
        "ubicacion",
        "descripcion"
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformeMedio extends Model
{
    protected $table = "informe_medios";
    protected $fillable = [
        "idParrafo",
        "nombre",
        "ubicacion",
        "descripcion"
    ];
}

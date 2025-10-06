<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAPoblacion extends Model
{
    public $timestamps = false;
    protected $table = "ia_poblacion";
    protected $fillable = [
        "tipo",
        "tipo_poblacion_id",
        "tipo_poblacion_otro",
        "descripcion_poblacion",
        "nombre_enfoque",
        "ia_id",
        "descripcion_area"
    ];
}

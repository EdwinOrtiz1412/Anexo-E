<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAPoblacionAnual extends Model
{

    public $timestamps = false;
    protected $table = "ia_poblacion_anual";
    protected $fillable = [
        "idPoblacion",
        "anio",
        "mujeres",
        "hombres",
        "total",
        "impacto_esperado",
        "descripcion_impacto",
        "total_area"
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformeAccion extends Model
{
    protected $table ="informe_acciones";
    protected $fillable = [
        "nombre",
        "idDependencia",
        "idTemaPED",
        "alineacion_la",
        "ae_cuadros",
        "orden",
        "creacion",
        "parrafos_max",
        "objetivo",
        "descripcion",
        "cobertura",
        //"p_entrega",
        //"p_otro",
        "anio_inicio",
        "estado",
        "tipo",
        "r_o",
        "prioritario"
    ];
}

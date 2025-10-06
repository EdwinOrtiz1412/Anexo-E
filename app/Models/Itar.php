<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itar extends Model
{
    protected $table = "itar";
    protected $fillable = [
        "folio",
        "fecha_envio",
        "idDependencia",
        "tipo",
        "reglas",
        "nombre",
        "objetivo",
        "descripcion",
        "cobertura",
        "periodicidad",
        "anio_inicio",
        "ejercicio",
        "idEjePED",
        "idTemaPED",
        "idObjetivoPED",
        "idEstrategiaPED",
        "idLAPED",
        "transversales",
        "idIndicador",
        "idPoblacion",
        "descripcioin_pb",
        "po",
        "po_m",
        "po_h",
        "pb1_t",
        "pb1_m",
        "pb1_h",
        "pb2_t",
        "pb2_m",
        "pb2_h",
        "pb3_t",
        "pb3_m",
        "pb3_h",
        "pb4_t",
        "pb4_m",
        "pb4_h",
        "im_s",
        "im_e",
        "im_a",
        "p_o",
        "r_s",
        "b_d",
        "a_t",
        "a_p",
        "otro",
        "periodo_reporte",
        "idUser",
        "estado",
        "tipologia_gasto",
        "o_a",
        "o_e",
        "p_acumulada"
    ];
}

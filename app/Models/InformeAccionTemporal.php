<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformeAccionTemporal extends Model
{
   protected $table = "informe_acciones_temporal";
   protected $fillable = [
        "nombre",
        "descripcion",
        "objetivo",
        "tipo",
        "r_o",
        "link_r_o",
        "idEjePED",
        "idTemaPED",
        "estado",
        "idDependencia",
        "created_at",
        "updated_at",
        "justificacion",
        "bss"

   ];

}

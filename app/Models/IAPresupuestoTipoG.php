<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAPresupuestoTipoG extends Model
{
    public $timestamps = false;
    protected $table = "ia_presupuesto_tipog";
    protected $fillable = [
        "ia_presupuesto_general_id",
        "tipo_gasto",
        "pp_id",
        "componente",
        "actividad"
    ];

}

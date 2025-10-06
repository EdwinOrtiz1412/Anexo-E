<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAAlineacion extends Model
{
    public $timestamps=false;
    protected $table = "ia_alineacion";
    protected $fillable = [
        "ia_id",
        "idEjePED",
        "idTemaPED",
        "idObjetivoPED",
        "lineas",
        "ejes_trans",
        "idSector",
        "idObjetivoSector",
        "idEstrategiaSector",
        //"idProductoSector",
        "i_estrategicos"
    ];
}

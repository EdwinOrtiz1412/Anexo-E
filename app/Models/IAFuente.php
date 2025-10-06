<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAFuente extends Model
{
    public $timestamps = false;
    protected $table = "ia_fuente";
    protected $fillable = [
        "fuente_id",
        "monto_total",
        "monto_federal",
        "monto_estatal",
        "monto_municipal",
        "ia_presupuesto_tipog_id",
        "f_otra"
    ];
}

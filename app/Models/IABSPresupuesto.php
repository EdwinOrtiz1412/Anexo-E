<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IABSPresupuesto extends Model
{
    public $timestamps = false;
    protected $table = "ia_bs_presupuesto";
    protected $fillable = [
        "idBS",
        "anio",
        "tipo",
        "m1",
        "m2",
        "m3",
        "m4",
        "e1",
        "e2",
        "e3",
        "e4",
        "idPrograma",
        "componente"
    ];

}

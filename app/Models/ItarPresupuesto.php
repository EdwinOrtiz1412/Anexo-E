<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItarPresupuesto extends Model
{
    protected $table="itar_presupuestos";
    protected $fillable = [
        "idITAR",
        "idPrograma",
        "fecha_corte",
        "ejercicio",

        "f1m",
        "f2m",
        "f3m",
        "f4m",

        "f1e",
        "f2e",
        "f3e",
        "f4e",

        "e1m",
        "e2m",
        "e3m",
        "e4m",

        "e1e",
        "e2e",
        "e3e",
        "e4e",

        "m1m",
        "m2m",
        "m3m",
        "m4m",

        "m1e",
        "m2e",
        "m3e",
        "m4e",

    ];
}

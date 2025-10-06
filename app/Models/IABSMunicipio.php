<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IABSMunicipio extends Model
{
    public $timestamps=false; 
    protected $table="ia_bs_municipios";
    protected $fillable =  [
        "idBS",
        "clave_municipio",
        "anio",
        "trimestre",
        "mujeres",
        "hombres",
        "area",
        "entregas"
    ];
}

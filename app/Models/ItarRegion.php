<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItarRegion extends Model
{
    public $timestamps = false;
    protected $table = "itar_regiones";
    protected $fillable = [
        "idRegion",
        "idITAR",
        "tp",
        "tpm",
        "tph",
        "num_mun"
    ];
}

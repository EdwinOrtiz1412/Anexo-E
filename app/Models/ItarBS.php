<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItarBS extends Model
{
    use HasFactory;
    protected $table = "itar_bs";
    protected $fillable = [
        "id",
        "descripcion_bs",
        "unidad_bs",
        "bs1p",
        "bs1r",
        "bs2p",
        "bs2r",
        "bs3p",
        "bs3r",
        "bs4p",
        "bs4r",
        "idItar"
    ];
}

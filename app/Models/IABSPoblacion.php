<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IABSPoblacion extends Model
{
    public $timestamps = false;
    protected $table = "ia_bs_poblacion";
    protected $fillable = [
        "idBS",
        "ph1",
        "ah1",
        "ph2",
        "ah2",
        "ph3",
        "ah3",
        "ph4",
        "ah4",
        "pm1",
        "am1",
        "pm2",
        "am2",
        "pm3",
        "am3",
        "pm4",
        "am4",
        "anio"
    ];
}

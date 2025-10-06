<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAPresupuestoGeneral extends Model
{
    
    protected $table = "ia_presupuesto_general";
    protected $fillable = [
        "ia_id",
        "anio"
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAMedio extends Model
{
    protected $table = "ia_medios";
    protected $fillable = [
        "ia_id",
        "anio",
        "trimestre",
        "nombre",
        "descripcion",
        "archivo",
        "created_at",
        "updated_at"
    ];
}

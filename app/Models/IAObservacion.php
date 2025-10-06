<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAObservacion extends Model
{
    protected $table = "ia_observaciones";
    protected $fillable = [
        "ia_id",
        "anio",
        "trimestre",
        "observaciones",
        "created_at",
        "updated_at"
    ];
}

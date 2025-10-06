<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IABS extends Model
{
    protected $table = "ia_bs";
    protected $fillable = [
        "idBS",
        "nombreBS",
        "descripcionBS",
        "p_entrega",
        "p_otro",
        "unidad_medidaBS",
        "ia_id",
        "created_at",
        "updated_at"
    ];
    
}

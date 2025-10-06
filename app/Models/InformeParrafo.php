<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformeParrafo extends Model
{
    protected $table="informe_parrafos";
    protected $fillable =[
        "users_id",
        "texto",
        "campos",
        "resultado",
        "informe_acciones_id",
        "tipo",
        "orden",
        "orden_ct"
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poblacion extends Model
{
    public $timestamps=false;
    protected $table="itar_poblacion";
    protected $fillable = [
        "descripcion"
    ];
}

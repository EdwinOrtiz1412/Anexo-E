<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicadorSector extends Model
{
    public $timestamps = false;
    protected $table = "indicadorsector";
    protected $fillable = ['idIndicador', 'idSector','idObjetivo','idEstrategia'];
    
}

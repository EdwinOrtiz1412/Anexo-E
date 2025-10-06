<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Columna extends Model
{
    protected $table = 'columnas';
    protected $primaryKey = 'idColumna';
    public $timestamps = false;

    protected $fillable = [
        'idCuadro',
        'idColumnaPadre',
        'nombreColumna',
        'nivel',
        'orden',
    ];
}

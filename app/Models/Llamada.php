<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Llamada extends Model
{
    protected $table = 'llamadas';
    protected $primaryKey = 'idLlamada';
    public $timestamps = true;

    protected $fillable = [
        'idCuadro',
        'idFila',
        'idColumna',
        'idCategoria',
        'nota',
        'orden',
    ];
}

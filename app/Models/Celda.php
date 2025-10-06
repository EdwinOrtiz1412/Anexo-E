<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Celda extends Model
{
    protected $table = 'celdas';
    protected $primaryKey = 'idCelda';
    public $timestamps = true;

    protected $fillable = [
        'idCuadro',
        'idFila',
        'idColumna',
        'idCategoria',
        'user_id',
        'valor_numero',
    ];
}

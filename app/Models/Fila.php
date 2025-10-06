<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fila extends Model
{
    protected $table = 'filas';
    protected $primaryKey = 'idFila';
    public $timestamps = false;

    protected $fillable = [
        'idCuadro',
        'nombreFila',
        'nivel',
        'orden',
        'estilos',
    ];
}

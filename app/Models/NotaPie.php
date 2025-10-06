<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaPie extends Model
{
    protected $table = 'notas_pie';
    protected $primaryKey = 'idNotaPie';
    public $timestamps = false;

    protected $fillable = [
        'idCuadro',
        'idCategoria',
        'idDependencia',
        'texto',
        'orden',
    ];
}

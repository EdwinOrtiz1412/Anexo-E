<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoMeta extends Model
{
    protected $table = 'seguimiento_metas';
    public $timestamps = false;

    protected $fillable = [
        'idProducto',
        'año',
        'programado',
        'realizado',
        'valor_indicador',
        'edicion_programacion',
    ];
}

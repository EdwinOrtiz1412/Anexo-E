<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicadorProducto extends Model
{
    protected $table = 'indicadores_producto';
    public $timestamps = false;

    protected $fillable = [
        'idProducto',
        'nombreIndicador',
        'tipo',
        'metodo_calculo',
        'frecuencia_medicion',
        'sentido_esperado',
        'unidad_medida_producto',
        'unidad_medida_indicador',
        'medio_verificacion_indicador',
    ];
}

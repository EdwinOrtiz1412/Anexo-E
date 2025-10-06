<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoPE extends Model
{
    protected $table = 'productos_pes';
    protected $primaryKey = 'idProducto';
    public $timestamps = false;

    protected $fillable = [
        'nombre_producto',
        'idDependencia',
        'estado_producto', 
    ];
}

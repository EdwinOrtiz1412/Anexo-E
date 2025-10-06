<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoSector extends Model
{
   

    protected $table = 'productosector';
    protected $primaryKey = 'idProducto';
    public $timestamps = false;

    protected $fillable = [
        'claveProducto',
        'producto',
        'idEstrategia',
        'estado_producto',
        'idDependencia',
        'guardar_generales',
        'guardar_seguimiento',
        'seccion_ped',
        'seccion_pes',
        'seccion_ppa',
        'seccion_DI',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlineacionGeneralProducto extends Model
{
    protected $table = 'alineacion_general_producto';
    protected $primaryKey = 'idAlineacion';
    public $timestamps = false;

    protected $fillable = [
        'idProducto',
        'idEjePED',
        'idTemaPED',
        'idObjetivoPED',
        'idEstrategiaPED',
        'idLAPED',
        'idSector',
        'idObjetivo',
        'idEstrategia',
        'id',
        'idBS',
    ];
}

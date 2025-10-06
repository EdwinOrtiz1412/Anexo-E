<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedioVerificacion extends Model
{
    protected $table = 'medios_verificacion';

    protected $primaryKey = 'idMedio';

    public $timestamps = true;

    protected $fillable = [
        'idProducto',
        'anio',         
        'nombreArchivo',
        'rutaArchivo',
        'descripcion',
    ];
}

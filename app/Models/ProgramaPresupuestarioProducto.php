<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramaPresupuestarioProducto extends Model
{
    protected $table = 'programa_presupuestario_producto';
    protected $primaryKey = 'idProgramaProducto';
    public $timestamps = false;

    protected $fillable = [
        'idProducto',
        'idPrograma',
        'componente',
        'actividad',
        'anio',
    ];
}
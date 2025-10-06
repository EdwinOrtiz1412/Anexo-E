<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeCoordinadorContenido extends Model
{
    protected $table = 'informe_coordinador_contenido';
    protected $primaryKey = 'idInformeCT';

    public $timestamps = true;

    protected $fillable = [
        'idDependencia',
        'idTemaPED',
        'seccion',
        'parrafo',
        'orden',
        'anio',
    ];


}

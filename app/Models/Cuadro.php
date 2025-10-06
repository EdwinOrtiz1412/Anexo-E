<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuadro extends Model
{
    protected $table = 'cuadros';
    protected $primaryKey = 'idCuadro';

    protected $fillable = [
        'numero',
        'control',
        'titulo',
        'descripcion',
        'idEjePED',
        'idTemaPED',
        'idDependencia',
        'idGrupo'
    ];
    
    public $timestamps = true;
}

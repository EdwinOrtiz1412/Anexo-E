<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'idCategoria';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'anio',
        'vigente',
        'solicitado',
        'idGrupo'
    ];
}

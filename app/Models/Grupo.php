<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupos';
    protected $primaryKey = 'idGrupo';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
    ];
}

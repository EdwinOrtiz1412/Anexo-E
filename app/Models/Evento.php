<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'eventos';
    protected $primaryKey = 'idEvento';
    public $timestamps = true;// habilitralo por defecto

    protected $fillable = [
        'nombre',
        'descripcion',
        'sede',
        'idDependencia_invitadas',
        'fecha_inicio',
        'fecha_fin',
        'estado',

    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registro extends Model
{
    use HasFactory;

    protected $table = 'registros';
    protected $primaryKey = 'idRegistro';
    public $timestamps = true;

    protected $fillable = [
        'idDependencia',
        'nombre',
        'cargo',
        'email',
        'telefono',
        'perfil',
        'tipo_enlace',
        'qr_uuid',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsistenciaEvento extends Model
{
    protected $table = 'asistencia_eventos';
    protected $primaryKey = 'idAsistencia';

    public $timestamps = true;

    protected $fillable = [
        'idEvento',
        'idRegistro',
        'scanned_at'

    ];
    

}

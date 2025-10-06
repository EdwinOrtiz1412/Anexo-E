<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsObservacion extends Model
{
    use HasFactory;

    protected $table = 'psobservaciones';

    protected $primaryKey = 'idPsObservacion';

    public $timestamps = false;

    protected $fillable = [
        'idProducto',
        'anio',
        'observacion',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaBsEstado extends Model
{
    protected $table = 'ia_bs_estado';
    protected $primaryKey = 'idEstado';

    public $incrementing = true;
    protected $keyType = 'int';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'idBs',
        'anio',
        'aplica',
    ];
}

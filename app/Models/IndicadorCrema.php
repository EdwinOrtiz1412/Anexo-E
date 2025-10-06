<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicadorCrema extends Model
{
    protected $table = 'indicador_crema';
    protected $primaryKey = 'idValidacionCrema';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idIndicador',
        'claro', 'relevante', 'economico', 'monitoreable', 'adecuado','aporteMarginal'
    ];

    public $timestamps = false;
}

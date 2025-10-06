<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CremaComentario extends Model
{
    use HasFactory;

    protected $table = 'crema_comentarios';
    protected $primaryKey = 'idComentario';
    public $timestamps = true;

    protected $fillable = [
        'idValidacionCrema',
        'criterio',
        'comentario',
    ];
}

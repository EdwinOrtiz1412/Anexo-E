<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaSiibien extends Model
{
    protected $table = 'encuesta_siibien';

    protected $fillable = [
        "p1",
        "p2",        
        "p3",
        "p4",
        "p5",
        "p6",
        "p7"
    ];
}

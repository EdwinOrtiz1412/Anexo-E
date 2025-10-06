<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaSiibien2025 extends Model
{
    protected $table = 'encuesta_siibien2025';

    protected $fillable = [
        "p1",
        "p2",        
        "p3",
        "p4",
        "p5",        
    ];
}

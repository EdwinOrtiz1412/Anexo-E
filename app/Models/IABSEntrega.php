<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IABSEntrega extends Model
{
    public $timestamps=false; 
    protected $table = "ia_bs_entregas";
    protected $fillable =  [
        "idBS",
        "anio",
        "p1",
        "p2",
        "p3",
        "p4",
        "r1",
        "r2",
        "r3",
        "r4"
    ];
}

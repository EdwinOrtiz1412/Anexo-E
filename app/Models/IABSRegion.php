<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IABSRegion extends Model
{
    public $timestamps = false;
    protected $table = "ia_bs_region";
    protected $fillable = [
        "idBS",
        "idRegion",
        "anio",
        "h1",
        "h2",
        "h3",
        "h4",
        "m1",
        "m2",
        "m3",
        "m4",
        "a1",
        "a2",
        "a3",
        "a4"
    ];
}

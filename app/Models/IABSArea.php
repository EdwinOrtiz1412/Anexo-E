<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IABSArea extends Model
{
    public $timestamps = false;
    protected $table = "ia_bs_area";
    protected $fillable = [
        "idBS",
        "arp1",
        "ara1",
        "arp2",
        "ara2",
        "arp3",
        "ara3",
        "arp4",
        "ara4",
        "anio"
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accesos extends Model
{
    protected $table = 'accesos';

    protected $fillable = [
        "users_id",
        "tipo"        
    ];
}

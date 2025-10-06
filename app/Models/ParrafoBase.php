<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParrafoBase extends Model
{
   protected $table="parrafos_base";
   protected $fillable =[
    "cuerpo",
    "campos",

   ];
}

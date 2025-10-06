<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnlaceDependencia extends Model
{
    public $timestamps = false;
    protected $table = 'enlacedependencia';

    public function dependencia(){
        return $this->belongsTo(Dependencia::class,"idDependencia","idDependencia");
    }

}

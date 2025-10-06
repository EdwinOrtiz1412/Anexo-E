<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicador extends Model
{
        /**
    * The table associated with the model.
    *
    * @var int
    */
    public $timestamps = false;
    protected $table = 'indicador';

    public function dependencia():BelongsTo{
        return $this->BelongsTo(Dependencia::class);
    }
}

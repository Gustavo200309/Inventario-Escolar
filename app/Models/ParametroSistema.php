<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroSistema extends Model
{
    protected $table = 'parametros_sistema';
    protected $primaryKey = 'clave';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'clave',
        'valor',
    ];
}

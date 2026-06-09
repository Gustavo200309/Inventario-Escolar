<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $table = 'areas';
    protected $primaryKey = 'id_area';
    public $timestamps = false;

    protected $fillable = [
        'nombre_area',
        'descripcion',
        'estatus',
        'fecha_registro',
    ];

    public function personal()
    {
        return $this->hasMany(Personal::class, 'id_area');
    }

    public function bienes()
    {
        return $this->hasMany(Bien::class, 'id_area');
    }
}

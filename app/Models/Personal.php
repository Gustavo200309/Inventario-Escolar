<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personal';
    protected $primaryKey = 'id_personal';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'puesto',
        'correo',
        'telefono',
        'id_area',
        'estatus',
        'fecha_registro',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function bienes()
    {
        return $this->hasMany(Bien::class, 'id_personal');
    }
}

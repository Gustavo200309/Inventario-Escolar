<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    use HasFactory;

    protected $table = 'bienes';
    protected $primaryKey = 'id_bien';
    public $timestamps = false;

    protected $fillable = [
        'id_sep',
        'no_inventario',
        'nombre_bien',
        'marca',
        'modelo',
        'serie',
        'adq',
        'valor',
        'resguardo_excel',
        'codigo_barras',
        'id_area',
        'id_personal',
        'estatus',
        'fecha_registro',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'valor' => 'decimal:2',
        ];
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'id_personal');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialAsignacion::class, 'id_bien');
    }

    public function ultimoHistorial()
    {
        return $this->hasOne(HistorialAsignacion::class, 'id_bien')->latestOfMany('fecha_movimiento');
    }
}

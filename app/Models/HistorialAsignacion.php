<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialAsignacion extends Model
{
    use HasFactory;

    protected $table = 'historial_asignaciones';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;

    protected $fillable = [
        'id_bien',
        'id_personal_anterior',
        'id_personal_nuevo',
        'id_area_anterior',
        'id_area_nueva',
        'fecha_movimiento',
        'tipo_movimiento',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'datetime',
        ];
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class, 'id_bien');
    }

    public function personalAnterior()
    {
        return $this->belongsTo(Personal::class, 'id_personal_anterior');
    }

    public function personalNuevo()
    {
        return $this->belongsTo(Personal::class, 'id_personal_nuevo');
    }

    public function areaAnterior()
    {
        return $this->belongsTo(Area::class, 'id_area_anterior');
    }

    public function areaNueva()
    {
        return $this->belongsTo(Area::class, 'id_area_nueva');
    }
}

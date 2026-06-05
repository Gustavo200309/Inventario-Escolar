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

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'id_personal');
    }
}

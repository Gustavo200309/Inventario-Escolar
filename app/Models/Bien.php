<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Picqer\Barcode\BarcodeGeneratorSVG;

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

    public function getBarcodeSvgAttribute(): ?string
    {
        if (empty($this->codigo_barras)) {
            return null;
        }

        if (! class_exists(BarcodeGeneratorSVG::class)) {
            return $this->fallbackBarcodeSvg($this->codigo_barras);
        }

        $generator = new BarcodeGeneratorSVG();
        return $generator->getBarcode($this->codigo_barras, $generator::TYPE_CODE_128, 1.5, 40);
    }

    public function getBarcodeDataUriAttribute(): ?string
    {
        $svg = $this->getBarcodeSvgAttribute();
        if ($svg === null) {
            return null;
        }
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function fallbackBarcodeSvg(string $code): string
    {
        $bars = '';
        $x = 10;

        foreach (str_split($code) as $index => $char) {
            $pattern = ord($char) + $index;
            for ($bit = 0; $bit < 7; $bit++) {
                $width = (($pattern >> $bit) & 1) ? 2 : 1;
                if (($bit + $pattern) % 2 === 0) {
                    $bars .= '<rect x="' . $x . '" y="6" width="' . $width . '" height="34" fill="#111"/>';
                }
                $x += $width + 1;
            }
        }

        $width = max(120, $x + 10);
        $text = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="52" viewBox="0 0 ' . $width . ' 52">'
            . '<rect width="100%" height="100%" fill="#fff"/>'
            . $bars
            . '<text x="' . ($width / 2) . '" y="49" text-anchor="middle" font-family="monospace" font-size="8" fill="#111">' . $text . '</text>'
            . '</svg>';
    }
}

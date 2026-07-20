<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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
        'id_marca',
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
        'eliminado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'valor' => 'decimal:2',
            'eliminado' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('sin_eliminar', function ($builder) {
            $builder->where('eliminado', false);
        });
    }

    public function delete()
    {
        $this->update(['eliminado' => true]);
        return true;
    }

    public function scopeWithEliminados($query)
    {
        return $query->withoutGlobalScope('sin_eliminar');
    }

    public function marcaRelacion()
    {
        return $this->belongsTo(Marca::class, 'id_marca');
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

        return $this->makeBarcodeSvg($this->codigo_barras);
    }

    public function getScannableBarcodeSvgAttribute(): ?string
    {
        if (empty($this->codigo_barras)) {
            return null;
        }

        return $this->makeBarcodeSvg($this->scan_url, 1.35, 62);
    } 

    public function getScanUrlAttribute(): ?string
    {
        if (empty($this->codigo_barras)) {
            return null;
        }

        return $this->publicScanBaseUrl() . '/b/' . rawurlencode($this->codigo_barras);
    }

    private function publicScanBaseUrl(): string
    {
        $configuredUrl = config('app.public_qr_url');
        if (! empty($configuredUrl)) {
            return rtrim((string) $configuredUrl, '/');
        }

        $request = request();
        $scheme = $request->getScheme() ?: 'http';
        $host = $request->getHost();
        $port = $request->getPort();

        if ($this->isLocalOnlyHost($host)) {
            $host = $this->detectLanIp() ?? $host;
        }

        $baseUrl = $scheme . '://' . $host;
        if ($port && ! in_array($port, [80, 443], true)) {
            $baseUrl .= ':' . $port;
        }

        return rtrim($baseUrl, '/');
    }

    private function isLocalOnlyHost(?string $host): bool
    {
        return in_array($host, ['0.0.0.0', '127.0.0.1', 'localhost', '::1'], true);
    }

    private function detectLanIp(): ?string
    {
        $ips = gethostbynamel(gethostname()) ?: [];

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && ! str_starts_with($ip, '127.') && $ip !== '0.0.0.0') {
                return $ip;
            }
        }

        return null;
    }

    private function makeBarcodeSvg(string $value, float $widthFactor = 0.8, int $height = 14): string
    {
        if (! class_exists(BarcodeGeneratorSVG::class)) {
            return $this->fallbackBarcodeSvg($value);
        }

        $generator = new BarcodeGeneratorSVG();
        return $generator->getBarcode($value, $generator::TYPE_CODE_128, $widthFactor, $height);
    }

    public function getBarcodeDataUriAttribute(): ?string
    {
        $svg = $this->getBarcodeSvgAttribute();
        if ($svg === null) {
            return null;
        }
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function getScannableBarcodeDataUriAttribute(): ?string
    {
        $svg = $this->getScannableBarcodeSvgAttribute();
        if ($svg === null) {
            return null;
        }
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    public function getQrSvgAttribute(): ?string
    {
        if (empty($this->codigo_barras)) {
            return null;
        }

        $renderer = new ImageRenderer(
            new RendererStyle(220, 4),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($this->scan_url, 'UTF-8', ErrorCorrectionLevel::H());
    }

    public function getQrDataUriAttribute(): ?string
    {
        $svg = $this->getQrSvgAttribute();
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
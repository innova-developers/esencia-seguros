<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'presentation_id',
        'tipo_operacion',
        'tipo_especie',
        'codigo_especie',
        'cant_especies',
        'codigo_afectacion',
        'tipo_valuacion',
        'fecha_movimiento',
        'fecha_liquidacion',
        'precio_compra',
        'fecha_pase_vt',
        'precio_pase_vt',
        'precio_venta',
        'tipo_especie_a',
        'codigo_especie_a',
        'cant_especies_a',
        'codigo_afectacion_a',
        'tipo_valuacion_a',
        'fecha_vt_a',
        'precio_vt_a',
        'tipo_especie_b',
        'codigo_especie_b',
        'cant_especies_b',
        'codigo_afectacion_b',
        'tipo_valuacion_b',
        'fecha_vt_b',
        'precio_vt_b',
        'tipo_pf',
        'bic',
        'cdf',
        'fecha_constitucion',
        'fecha_vencimiento',
        'moneda',
        'valor_nominal_origen',
        'valor_nominal_nacional',
        'tipo_tasa',
        'tasa',
        'titulo_deuda',
        'codigo_titulo',
        'validation_errors',
        'notes',
    ];

    protected $casts = [
        'cant_especies' => 'decimal:6',
        'cant_especies_a' => 'decimal:6',
        'cant_especies_b' => 'decimal:6',
        'precio_compra' => 'decimal:2',
        'precio_pase_vt' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_vt_a' => 'decimal:2',
        'precio_vt_b' => 'decimal:2',
        'valor_nominal_origen' => 'decimal:0',
        'valor_nominal_nacional' => 'decimal:0',
        'tasa' => 'decimal:3',
        'titulo_deuda' => 'boolean',
        'validation_errors' => 'array',
    ];

    // Tipos de operación
    const TIPO_COMPRA = 'C';
    const TIPO_VENTA = 'V';
    const TIPO_CANJE = 'J';
    const TIPO_PLAZO_FIJO = 'P';

    // Tipos de especie
    const ESPECIE_TITULOS_PUBLICOS = 'TP';
    const ESPECIE_OBLIGACIONES_NEGOCIABLES = 'ON';
    const ESPECIE_ACCIONES = 'AC';
    const ESPECIE_FIDEICOMISOS = 'FF';
    const ESPECIE_FONDOS_COMUNES = 'FC';
    const ESPECIE_OTRAS = 'OP';

    // Tipos de valuación
    const VALUACION_TECNICO = 'T';
    const VALUACION_MERCADO = 'V';

    /**
     * Relación con la presentación
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }

    /**
     * Verificar si es operación de compra
     */
    public function isCompra(): bool
    {
        return $this->tipo_operacion === self::TIPO_COMPRA;
    }

    /**
     * Verificar si es operación de venta
     */
    public function isVenta(): bool
    {
        return $this->tipo_operacion === self::TIPO_VENTA;
    }

    /**
     * Verificar si es operación de canje
     */
    public function isCanje(): bool
    {
        return $this->tipo_operacion === self::TIPO_CANJE;
    }

    /**
     * Verificar si es operación de plazo fijo
     */
    public function isPlazoFijo(): bool
    {
        return $this->tipo_operacion === self::TIPO_PLAZO_FIJO;
    }

    /**
     * Obtener el JSON para enviar a SSN
     */
    public function getSsnJson(): array
    {
        $json = ['tipoOperacion' => $this->tipo_operacion];

        switch ($this->tipo_operacion) {
            case self::TIPO_COMPRA:
                return $this->getCompraJson($json);
            case self::TIPO_VENTA:
                return $this->getVentaJson($json);
            case self::TIPO_CANJE:
                return $this->getCanjeJson($json);
            case self::TIPO_PLAZO_FIJO:
                return $this->getPlazoFijoJson($json);
            default:
                return $json;
        }
    }

    /**
     * Obtener JSON para operación de compra
     */
    private function getCompraJson(array $json): array
    {
        return array_merge($json, [
            'tipoEspecie' => $this->tipo_especie,
            'codigoEspecie' => $this->codigo_especie,
            'cantEspecies' => $this->cant_especies,
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoValuacion' => $this->tipo_valuacion,
            'fechaMovimiento' => $this->fecha_movimiento,
            'precioCompra' => $this->precio_compra,
            'fechaLiquidacion' => $this->fecha_liquidacion,
        ]);
    }

    /**
     * Obtener JSON para operación de venta
     */
    private function getVentaJson(array $json): array
    {
        $ventaJson = [
            'tipoEspecie' => $this->tipo_especie,
            'codigoEspecie' => $this->codigo_especie,
            'cantEspecies' => $this->cant_especies,
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoValuacion' => $this->tipo_valuacion,
            'fechaMovimiento' => $this->fecha_movimiento,
            'fechaLiquidacion' => $this->fecha_liquidacion,
            'precioVenta' => $this->precio_venta,
        ];

        // Agregar campos específicos de VT si aplica
        if ($this->fecha_pase_vt) {
            $ventaJson['fechaPaseVT'] = $this->fecha_pase_vt;
            $ventaJson['precioPaseVT'] = $this->precio_pase_vt;
        }

        return array_merge($json, $ventaJson);
    }

    /**
     * Obtener JSON para operación de canje
     */
    private function getCanjeJson(array $json): array
    {
        $canjeJson = [
            'tipoEspecieA' => $this->tipo_especie_a,
            'codigoEspecieA' => $this->codigo_especie_a,
            'cantEspeciesA' => $this->cant_especies_a,
            'codigoAfectacionA' => $this->codigo_afectacion_a,
            'tipoValuacionA' => $this->tipo_valuacion_a,
            'tipoEspecieB' => $this->tipo_especie_b,
            'codigoEspecieB' => $this->codigo_especie_b,
            'cantEspeciesB' => $this->cant_especies_b,
            'codigoAfectacionB' => $this->codigo_afectacion_b,
            'tipoValuacionB' => $this->tipo_valuacion_b,
            'fechaMovimiento' => $this->fecha_movimiento,
            'fechaLiquidacion' => $this->fecha_liquidacion,
        ];

        // Agregar campos específicos de VT si aplica
        if ($this->fecha_vt_a) {
            $canjeJson['fechaVTA'] = $this->fecha_vt_a;
            $canjeJson['precioVTA'] = $this->precio_vt_a;
        }

        if ($this->fecha_vt_b) {
            $canjeJson['fechaVTB'] = $this->fecha_vt_b;
            $canjeJson['precioVTB'] = $this->precio_vt_b;
        }

        return array_merge($json, $canjeJson);
    }

    /**
     * Obtener JSON para operación de plazo fijo
     */
    private function getPlazoFijoJson(array $json): array
    {
        $plazoFijoJson = [
            'tipoPF' => $this->tipo_pf,
            'bic' => $this->bic,
            'cdf' => $this->cdf,
            'fechaConstitucion' => $this->fecha_constitucion,
            'fechaVencimiento' => $this->fecha_vencimiento,
            'moneda' => $this->moneda,
            'valorNominalOrigen' => $this->valor_nominal_origen,
            'valorNominalNacional' => $this->valor_nominal_nacional,
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoTasa' => $this->tipo_tasa,
            'tasa' => $this->tasa,
            'tituloDeuda' => $this->titulo_deuda ? '1' : '0',
        ];

        if ($this->codigo_titulo) {
            $plazoFijoJson['codigoTitulo'] = $this->codigo_titulo;
        }

        return array_merge($json, $plazoFijoJson);
    }

    /**
     * Scope para operaciones de compra
     */
    public function scopeCompra($query)
    {
        return $query->where('tipo_operacion', self::TIPO_COMPRA);
    }

    /**
     * Scope para operaciones de venta
     */
    public function scopeVenta($query)
    {
        return $query->where('tipo_operacion', self::TIPO_VENTA);
    }

    /**
     * Scope para operaciones de canje
     */
    public function scopeCanje($query)
    {
        return $query->where('tipo_operacion', self::TIPO_CANJE);
    }

    /**
     * Scope para operaciones de plazo fijo
     */
    public function scopePlazoFijo($query)
    {
        return $query->where('tipo_operacion', self::TIPO_PLAZO_FIJO);
    }
}

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
     * Formatear fecha para SSN (DDMMYYYY)
     */
    private function formatDateForSSN(?string $date): ?string
    {
        if (!$date) {
            return null;
        }
        
        try {
            // Si ya está en formato DDMMYYYY, devolverlo tal como está
            if (preg_match('/^\d{8}$/', $date)) {
                return $date;
            }
            
            // Si está en formato YYYY-MM-DD, convertirlo a DDMMYYYY
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            // Si está en formato DD/MM/YYYY, convertirlo a DDMMYYYY
            $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            return $date; // Si no se puede parsear, devolver como está
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Formatear fecha para mostrar en vistas (DDMMYYYY -> DD/MM/YYYY)
     */
    public function formatDateForDisplay(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }
        
        try {
            // Si está en formato DDMMYYYY, convertirlo a DD/MM/YYYY
            if (preg_match('/^\d{8}$/', $dateString)) {
                $day = substr($dateString, 0, 2);
                $month = substr($dateString, 2, 2);
                $year = substr($dateString, 4, 4);
                return "{$day}/{$month}/{$year}";
            }
            
            // Si ya está en formato DD/MM/YYYY, devolverlo tal como está
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateString)) {
                return $dateString;
            }
            
            // Si está en formato YYYY-MM-DD, convertirlo a DD/MM/YYYY
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateString);
                if ($date) {
                    return $date->format('d/m/Y');
                }
            }
            
            return $dateString; // Si no se puede parsear, devolver como está
            
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    /**
     * Obtener fecha de movimiento formateada para mostrar
     */
    public function getFechaMovimientoDisplayAttribute(): ?string
    {
        return $this->formatDateForDisplay($this->fecha_movimiento);
    }

    /**
     * Obtener fecha de liquidación formateada para mostrar
     */
    public function getFechaLiquidacionDisplayAttribute(): ?string
    {
        return $this->formatDateForDisplay($this->fecha_liquidacion);
    }

    /**
     * Obtener fecha de pase VT formateada para mostrar
     */
    public function getFechaPaseVtDisplayAttribute(): ?string
    {
        return $this->formatDateForDisplay($this->fecha_pase_vt);
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
            'cantEspecies' => (float) $this->cant_especies,
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoValuacion' => $this->tipo_valuacion,
            'fechaMovimiento' => $this->formatDateForSSN($this->fecha_movimiento),
            'fechaLiquidacion' => $this->formatDateForSSN($this->fecha_liquidacion),
            'precioCompra' => $this->precio_compra ? (float) $this->precio_compra : 0,
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
            'cantEspecies' => (float) $this->cant_especies,
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoValuacion' => $this->tipo_valuacion,
            'fechaMovimiento' => $this->formatDateForSSN($this->fecha_movimiento),
            'fechaLiquidacion' => $this->formatDateForSSN($this->fecha_liquidacion),
            'precioVenta' => $this->precio_venta ? (float) $this->precio_venta : 0,
        ];

        // Verificar si debe incluir fechaPaseVT y precioPaseVT
        $tipoEspecie = strtoupper(trim($this->tipo_especie ?? ''));
        $tipoValuacion = strtoupper(trim($this->tipo_valuacion ?? ''));
        $debeIncluirPaseVT = in_array($tipoEspecie, ['TP', 'ON']) && $tipoValuacion === 'T';
        
        // Log para debuggear
        \Log::info('WeeklyOperation getVentaJson - Debug', [
            'operation_id' => $this->id,
            'tipo_especie' => $tipoEspecie,
            'tipo_valuacion' => $tipoValuacion,
            'debe_incluir_pase_vt' => $debeIncluirPaseVT,
            'fecha_pase_vt_raw' => $this->fecha_pase_vt,
            'precio_pase_vt_raw' => $this->precio_pase_vt,
        ]);
        
        // Siempre incluir los campos, pero con valores apropiados según las condiciones
        if ($debeIncluirPaseVT) {
            $fechaPaseVT = $this->formatDateForSSN($this->fecha_pase_vt);
            $ventaJson['fechaPaseVT'] = $fechaPaseVT !== null ? $fechaPaseVT : "";
            $ventaJson['precioPaseVT'] = $this->precio_pase_vt !== null && $this->precio_pase_vt !== "" ? (float) $this->precio_pase_vt : "";
            
            \Log::info('WeeklyOperation getVentaJson - Incluyendo campos PaseVT con valores', [
                'operation_id' => $this->id,
                'fechaPaseVT_final' => $ventaJson['fechaPaseVT'],
                'precioPaseVT_final' => $ventaJson['precioPaseVT']
            ]);
        } else {
            // Incluir campos con string vacío cuando no corresponda
            $ventaJson['fechaPaseVT'] = "";
            $ventaJson['precioPaseVT'] = "";
            
            \Log::info('WeeklyOperation getVentaJson - Incluyendo campos PaseVT vacíos', [
                'operation_id' => $this->id,
                'razon' => 'No cumple condiciones (TP/ON y T)'
            ]);
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
            'cantEspeciesA' => (float) $this->cant_especies_a,
            'codigoAfectacionA' => $this->codigo_afectacion_a,
            'tipoValuacionA' => $this->tipo_valuacion_a,
            'tipoEspecieB' => $this->tipo_especie_b,
            'codigoEspecieB' => $this->codigo_especie_b,
            'cantEspeciesB' => (float) $this->cant_especies_b,
            'codigoAfectacionB' => $this->codigo_afectacion_b,
            'tipoValuacionB' => $this->tipo_valuacion_b,
            'fechaMovimiento' => $this->formatDateForSSN($this->fecha_movimiento),
            'fechaLiquidacion' => $this->formatDateForSSN($this->fecha_liquidacion),
            'fechaVTA' => $this->formatDateForSSN($this->fecha_vt_a),
            'precioVTA' => $this->precio_vt_a ? (float) $this->precio_vt_a : 0,
            'fechaVTB' => $this->formatDateForSSN($this->fecha_vt_b),
            'precioVTB' => $this->precio_vt_b ? (float) $this->precio_vt_b : 0,
        ];

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
            'fechaConstitucion' => $this->formatDateForSSN($this->fecha_constitucion),
            'fechaVencimiento' => $this->formatDateForSSN($this->fecha_vencimiento),
            'moneda' => $this->moneda,
            'valorNominalOrigen' => $this->valor_nominal_origen ? (float) $this->valor_nominal_origen : 0,
            'valorNominalNacional' => $this->valor_nominal_nacional ? (float) $this->valor_nominal_nacional : 0,
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoTasa' => $this->tipo_tasa,
            'tasa' => $this->tasa ? (float) $this->tasa : 0,
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

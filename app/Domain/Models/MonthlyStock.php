<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'presentation_id',
        'nombre',
        'tipo',
        'tipo_especie',
        'codigo_especie',
        'cantidad_devengado_especies',
        'cantidad_percibido_especies',
        'codigo_afectacion',
        'tipo_valuacion',
        'con_cotizacion',
        'libre_disponibilidad',
        'emisor_grupo_economico',
        'emisor_art_ret',
        'prevision_desvalorizacion',
        'valor_contable',
        'fecha_pase_vt',
        'precio_pase_vt',
        'en_custodia',
        'financiera',
        'valor_financiero',
        'tipo_pf',
        'bic',
        'cdf',
        'fecha_constitucion',
        'fecha_vencimiento_pf',
        'moneda',
        'valor_nominal_origen',
        'valor_nominal_nacional',
        'tipo_tasa',
        'tasa',
        'titulo_deuda',
        'codigo_titulo',
        'codigo_sgr',
        'codigo_cheque',
        'fecha_emision',
        'fecha_vencimiento_cheque',
        'valor_nominal',
        'valor_adquisicion',
        'fecha_adquisicion',
        'validation_errors',
        'notes',
    ];

    protected $casts = [
        'cantidad_devengado_especies' => 'decimal:6',
        'cantidad_percibido_especies' => 'decimal:6',
        'prevision_desvalorizacion' => 'decimal:0',
        'valor_contable' => 'decimal:0',
        'precio_pase_vt' => 'decimal:2',
        'valor_financiero' => 'decimal:0',
        'valor_nominal_origen' => 'decimal:0',
        'valor_nominal_nacional' => 'decimal:0',
        'tasa' => 'decimal:3',
        'valor_nominal' => 'decimal:0',
        'valor_adquisicion' => 'decimal:0',
        'con_cotizacion' => 'boolean',
        'libre_disponibilidad' => 'boolean',
        'emisor_grupo_economico' => 'boolean',
        'emisor_art_ret' => 'boolean',
        'en_custodia' => 'boolean',
        'financiera' => 'boolean',
        'titulo_deuda' => 'boolean',
        'validation_errors' => 'array',
    ];

    // Tipos de stock
    const TIPO_INVERSIONES = 'I';
    const TIPO_PLAZO_FIJO = 'P';
    const TIPO_CHEQUE_PAGO_DIFERIDO = 'C';

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

    // Tipos de tasa
    const TASA_FIJA = 'F';
    const TASA_VARIABLE = 'V';

    /**
     * Relación con la presentación
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }

    /**
     * Verificar si es stock de inversiones
     */
    public function isInversiones(): bool
    {
        return $this->tipo === self::TIPO_INVERSIONES;
    }

    /**
     * Verificar si es stock de plazo fijo
     */
    public function isPlazoFijo(): bool
    {
        return $this->tipo === self::TIPO_PLAZO_FIJO;
    }

    /**
     * Verificar si es stock de cheque pago diferido
     */
    public function isChequePagoDiferido(): bool
    {
        return $this->tipo === self::TIPO_CHEQUE_PAGO_DIFERIDO;
    }

    /**
     * Obtener el JSON para enviar a SSN
     */
    public function getSsnJson(): array
    {
        $json = ['tipo' => $this->tipo];

        switch ($this->tipo) {
            case self::TIPO_INVERSIONES:
                return $this->getInversionesJson($json);
            case self::TIPO_PLAZO_FIJO:
                return $this->getPlazoFijoJson($json);
            case self::TIPO_CHEQUE_PAGO_DIFERIDO:
                return $this->getChequePagoDiferidoJson($json);
            default:
                return $json;
        }
    }

    /**
     * Obtener JSON para stock de inversiones
     */
    private function getInversionesJson(array $json): array
    {
        $inversionesJson = [
            'tipoEspecie' => $this->tipo_especie,
            'codigoEspecie' => $this->codigo_especie,
            'cantidadDevengadoEspecies' => $this->cantidad_devengado_especies,
            'cantidadPercibidoEspecies' => $this->cantidad_percibido_especies,
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoValuacion' => $this->tipo_valuacion,
            'conCotizacion' => $this->con_cotizacion ? '1' : '0',
            'libreDisponibilidad' => $this->libre_disponibilidad ? '1' : '0',
            'emisorGrupoEconomico' => $this->emisor_grupo_economico ? '1' : '0',
            'emisorArtRet' => $this->emisor_art_ret ? '1' : '0',
            'previsionDesvalorizacion' => $this->prevision_desvalorizacion,
            'valorContable' => $this->valor_contable,
            'enCustodia' => $this->en_custodia ? '1' : '0',
            'financiera' => $this->financiera ? '1' : '0',
        ];

        // Agregar campos específicos si aplica
        if ($this->fecha_pase_vt) {
            $inversionesJson['fechaPaseVT'] = $this->fecha_pase_vt;
            $inversionesJson['precioPaseVT'] = $this->precio_pase_vt;
        }

        if ($this->valor_financiero) {
            $inversionesJson['valorFinanciero'] = $this->valor_financiero;
        }

        return array_merge($json, $inversionesJson);
    }

    /**
     * Obtener JSON para stock de plazo fijo
     */
    private function getPlazoFijoJson(array $json): array
    {
        $plazoFijoJson = [
            'tipoPF' => $this->tipo_pf,
            'bic' => $this->bic,
            'cdf' => $this->cdf,
            'fechaConstitucion' => $this->fecha_constitucion,
            'fechaVencimiento' => $this->fecha_vencimiento_pf,
            'moneda' => $this->moneda,
            'valorNominalOrigen' => $this->valor_nominal_origen,
            'valorNominalNacional' => $this->valor_nominal_nacional,
            'emisorGrupoEconomico' => $this->emisor_grupo_economico ? '1' : '0',
            'libreDisponibilidad' => $this->libre_disponibilidad ? '1' : '0',
            'enCustodia' => $this->en_custodia ? '1' : '0',
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoTasa' => $this->tipo_tasa,
            'tasa' => $this->tasa,
            'tituloDeuda' => $this->titulo_deuda ? '1' : '0',
            'valorContable' => $this->valor_contable,
            'financiera' => $this->financiera ? '1' : '0',
        ];

        if ($this->codigo_titulo) {
            $plazoFijoJson['codigoTitulo'] = $this->codigo_titulo;
        }

        return array_merge($json, $plazoFijoJson);
    }

    /**
     * Obtener JSON para stock de cheque pago diferido
     */
    private function getChequePagoDiferidoJson(array $json): array
    {
        return array_merge($json, [
            'CodigoSGR' => $this->codigo_sgr,
            'CodigoCheque' => $this->codigo_cheque,
            'FechaEmision' => $this->fecha_emision,
            'FechaVencimiento' => $this->fecha_vencimiento_cheque,
            'moneda' => $this->moneda,
            'valorNominal' => $this->valor_nominal,
            'valorAdquisicion' => $this->valor_adquisicion,
            'emisorGrupoEconomico' => $this->emisor_grupo_economico ? '1' : '0',
            'libreDisponibilidad' => $this->libre_disponibilidad ? '1' : '0',
            'enCustodia' => $this->en_custodia ? '1' : '0',
            'codigoAfectacion' => $this->codigo_afectacion,
            'tipoTasa' => $this->tipo_tasa,
            'tasa' => $this->tasa,
            'valorContable' => $this->valor_contable,
            'financiera' => $this->financiera ? '1' : '0',
            'fechaAdquisicion' => $this->fecha_adquisicion,
        ]);
    }

    /**
     * Scope para stocks de inversiones
     */
    public function scopeInversiones($query)
    {
        return $query->where('tipo', self::TIPO_INVERSIONES);
    }

    /**
     * Scope para stocks de plazo fijo
     */
    public function scopePlazoFijo($query)
    {
        return $query->where('tipo', self::TIPO_PLAZO_FIJO);
    }

    /**
     * Scope para stocks de cheque pago diferido
     */
    public function scopeChequePagoDiferido($query)
    {
        return $query->where('tipo', self::TIPO_CHEQUE_PAGO_DIFERIDO);
    }

    /**
     * Scope para una especie específica
     */
    public function scopeEspecie($query, $tipoEspecie)
    {
        return $query->where('tipo_especie', $tipoEspecie);
    }
}

<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsnSpecie extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_ssn',
        'tipo_especie',
        'descripcion',
        'emisor',
        'serie',
        'moneda',
        'activo',
        'metadata',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'metadata' => 'array',
    ];

    // Tipos de especie
    const ESPECIE_TITULOS_PUBLICOS = 'TP';
    const ESPECIE_OBLIGACIONES_NEGOCIABLES = 'ON';
    const ESPECIE_ACCIONES = 'AC';
    const ESPECIE_FIDEICOMISOS = 'FF';
    const ESPECIE_FONDOS_COMUNES = 'FC';
    const ESPECIE_OTRAS = 'OP';

    /**
     * Scope para especies activas
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para un tipo de especie específico
     */
    public function scopeTipoEspecie($query, $tipoEspecie)
    {
        return $query->where('tipo_especie', $tipoEspecie);
    }

    /**
     * Obtener especies por tipo
     */
    public static function getByTipo($tipoEspecie)
    {
        return self::activo()->tipoEspecie($tipoEspecie)->get();
    }

    /**
     * Obtener descripción completa
     */
    public function getDescripcionCompletaAttribute()
    {
        $descripcion = $this->descripcion;
        
        if ($this->emisor) {
            $descripcion .= ' - ' . $this->emisor;
        }
        
        if ($this->serie) {
            $descripcion .= ' (Serie: ' . $this->serie . ')';
        }
        
        return $descripcion;
    }
}

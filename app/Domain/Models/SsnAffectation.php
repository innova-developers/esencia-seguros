<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsnAffectation extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'descripcion',
        'detalle',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Scope para afectaciones activas
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener afectación por código
     */
    public static function getByCodigo($codigo)
    {
        return self::activo()->where('codigo', $codigo)->first();
    }
}

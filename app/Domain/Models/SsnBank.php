<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsnBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'bic',
        'nombre_banco',
        'codigo_banco',
        'pais',
        'activo',
        'metadata',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Scope para bancos activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener banco por BIC
     */
    public static function getByBic($bic)
    {
        return self::activo()->where('bic', $bic)->first();
    }

    /**
     * Obtener banco por nombre
     */
    public static function getByNombre($nombre)
    {
        return self::activo()->where('nombre_banco', 'like', "%{$nombre}%")->get();
    }
}

<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsnSgrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'descripcion',
        'nombre_sgr',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Scope para códigos SGR activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener código SGR por código
     */
    public static function getByCodigo($codigo)
    {
        return self::activo()->where('codigo', $codigo)->first();
    }

    /**
     * Obtener códigos SGR por nombre
     */
    public static function getByNombre($nombre)
    {
        return self::activo()->where('nombre_sgr', 'like', "%{$nombre}%")->get();
    }
}

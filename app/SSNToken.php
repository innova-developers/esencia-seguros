<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SSNToken extends Model
{
    use HasFactory;

    protected $table = 'ssn_tokens';


    protected $fillable = [
        'token',
        'expiration',
        'is_mock',
        'username',
        'cia'
    ];

    protected $casts = [
        'expiration' => 'datetime',
        'is_mock' => 'boolean',
    ];

    /**
     * Obtener el token actual válido
     */
    public static function getValidToken(): ?string
    {
        $tokenRecord = self::where('expiration', '>', now())
            ->orWhere('is_mock', true)
            ->orderBy('created_at', 'desc')
            ->first();

        return $tokenRecord ? $tokenRecord->token : null;
    }

    /**
     * Verificar si el token actual es válido
     */
    public static function isTokenValid(): bool
    {
        $tokenRecord = self::where('expiration', '>', now())
            ->orWhere('is_mock', true)
            ->orderBy('created_at', 'desc')
            ->first();

        return $tokenRecord !== null;
    }

    /**
     * Guardar un nuevo token
     */
    public static function saveToken(string $token, ?string $expiration = null, bool $isMock = false, ?string $username = null, ?string $cia = null): void
    {
        // Limpiar tokens antiguos
        self::where('expiration', '<', now())->delete();

        // Crear nuevo registro
        self::create([
            'token' => $token,
            'expiration' => $expiration ? Carbon::createFromFormat('d/m/Y H:i:s', $expiration) : null,
            'is_mock' => $isMock,
            'username' => $username,
            'cia' => $cia,
        ]);
    }

    /**
     * Limpiar tokens expirados
     */
    public static function cleanExpiredTokens(): void
    {
        self::where('expiration', '<', now())->delete();
    }
}

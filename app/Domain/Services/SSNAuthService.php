<?php

namespace App\Domain\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SSNAuthService
{
    private string $baseUrl;
    private bool $mockEnabled;

    public function __construct()
    {
        $environment = config('services.ssn.environment', 'testing');
        $this->baseUrl = config("services.ssn.base_url_{$environment}");
        $this->mockEnabled = config('services.ssn.mock_enabled', true);
    }

    /**
     * Verifica si está en modo mock
     */
    public function isMockMode(): bool
    {
        return $this->mockEnabled;
    }

    /**
     * Obtiene la URL de autenticación
     */
    public function getAuthUrl(): string
    {
        $endpoint = config('services.ssn.auth_endpoint', '/login');
        return $this->baseUrl . $endpoint;
    }

    /**
     * Obtiene los headers para las peticiones
     */
    public function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Autentica con la SSN y retorna el token
     */
    public function authenticate(string $username, string $cia, string $password): ?array
    {
        if ($this->mockEnabled) {
            return $this->mockAuthentication();
        }

        return $this->realAuthentication($username, $cia, $password);
    }

    /**
     * Autenticación real con la API de la SSN
     */
    private function realAuthentication(string $username, string $cia, string $password): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())->post($this->getAuthUrl(), [
                'USER' => $username,
                'CIA' => $cia,
                'PASSWORD' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'token' => $data['token'] ?? null,
                    'expiration' => $data['fecha_expiracion'] ?? null,
                    'success' => true,
                ];
            }

            Log::error('SSN Authentication failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('SSN Authentication error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Autenticación mock para desarrollo
     */
    private function mockAuthentication(): array
    {
        return [
            'token' => config('services.ssn.mock_token'),
            'expiration' => config('services.ssn.mock_expiration'),
            'success' => true,
            'mock' => true,
        ];
    }

    /**
     * Verifica si el token está vigente
     */
    public function isTokenValid(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        // Para el mock, siempre retornamos true
        if ($this->mockEnabled && $token === config('services.ssn.mock_token')) {
            return true;
        }

        // Para tokens reales, podríamos verificar con la SSN
        // Por ahora, asumimos que es válido si existe
        return !empty($token);
    }

    /**
     * Obtiene información del token (para desarrollo)
     */
    public function getTokenInfo(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        if ($this->mockEnabled && $token === config('services.ssn.mock_token')) {
            return [
                'token' => $token,
                'expiration' => config('services.ssn.mock_expiration'),
                'mock' => true,
            ];
        }

        // Para tokens reales, obtener información desde la base de datos
        $tokenRecord = \App\SSNToken::where('token', $token)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($tokenRecord) {
            return [
                'token' => $token,
                'expiration' => $tokenRecord->expiration ? $tokenRecord->expiration->format('d/m/Y H:i:s') : null,
                'mock' => false,
            ];
        }

        // Fallback si no se encuentra en la base de datos
        return [
            'token' => $token,
            'expiration' => null,
            'mock' => false,
        ];
    }

    /**
     * Convierte el formato de fecha de la SSN al formato estándar
     */
    private function convertSSNDateFormat(string $ssnDate): string
    {
        // La SSN devuelve: "15 jul 2025 20:40:34"
        // Necesitamos convertir a: "15/07/2025 20:40:34"
        
        $months = [
            'ene' => '01', 'feb' => '02', 'mar' => '03', 'abr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'ago' => '08',
            'sep' => '09', 'oct' => '10', 'nov' => '11', 'dic' => '12'
        ];
        
        // Parsear la fecha de la SSN
        if (preg_match('/(\d+)\s+(\w+)\s+(\d+)\s+(\d+):(\d+):(\d+)/', strtolower($ssnDate), $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = $months[$matches[2]] ?? '01';
            $year = $matches[3];
            $hour = str_pad($matches[4], 2, '0', STR_PAD_LEFT);
            $minute = str_pad($matches[5], 2, '0', STR_PAD_LEFT);
            $second = str_pad($matches[6], 2, '0', STR_PAD_LEFT);
            
            return "{$day}/{$month}/{$year} {$hour}:{$minute}:{$second}";
        }
        
        // Si no se puede parsear, devolver la fecha original
        return $ssnDate;
    }

    /**
     * Guarda el token en cache y base de datos
     */
    public function cacheToken(string $token, string $expiration, ?string $username = null, ?string $cia = null): void
    {
        // Convertir el formato de fecha de la SSN si es necesario
        $formattedExpiration = $this->convertSSNDateFormat($expiration);
        
        $expirationTime = \DateTime::createFromFormat('d/m/Y H:i:s', $formattedExpiration);
        
        // Guardar en cache de Laravel
        if ($expirationTime) {
            $ttl = $expirationTime->getTimestamp() - time();
            if ($ttl > 0) {
                Cache::put('ssn_token', $token, $ttl);
            }
        }

        // Guardar en base de datos
        \App\SSNToken::saveToken(
            $token, 
            $formattedExpiration, 
            $this->mockEnabled, 
            $username, 
            $cia
        );
    }

    /**
     * Obtiene el token desde cache o base de datos
     */
    public function getCachedToken(): ?string
    {
        // Primero intentar desde cache
        $token = Cache::get('ssn_token');
        if ($token) {
            return $token;
        }

        // Si no está en cache, intentar desde base de datos
        return \App\SSNToken::getValidToken();
    }

    /**
     * Limpia el token del cache
     */
    public function clearCachedToken(): void
    {
        Cache::forget('ssn_token');
    }
} 
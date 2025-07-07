<?php

namespace App\Services;

use App\Domain\Models\Presentation;
use App\Domain\Services\SSNAuthService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Exception;

class SSNService
{
    private $baseUrl;
    private $token;
    private $isMock;
    private $authService;

    public function __construct(SSNAuthService $authService = null)
    {
        $this->authService = $authService ?? app(SSNAuthService::class);
        $this->isMock = config('services.ssn.mock_enabled', false);
        $this->baseUrl = $this->isMock 
            ? config('services.ssn.base_url_testing', 'https://testri.ssn.gob.ar/api')
            : config('services.ssn.base_url_production', 'https://ri.ssn.gob.ar/api');
        
        // Obtener token desde cache o sesión (fallback)
        $this->token = $this->authService->getCachedToken() ?? session('ssn_token');
    }

    /**
     * Obtener token válido para las peticiones
     */
    private function getValidToken(): ?string
    {
        // Primero intentar desde cache
        $token = $this->authService->getCachedToken();
        
        if ($token && $this->authService->isTokenValid($token)) {
            return $token;
        }
        
        // Fallback a base de datos
        $dbToken = \App\SSNToken::getValidToken();
        if ($dbToken && $this->authService->isTokenValid($dbToken)) {
            return $dbToken;
        }
        
        // Fallback a sesión
        $sessionToken = session('ssn_token');
        if ($sessionToken && $this->authService->isTokenValid($sessionToken)) {
            return $sessionToken;
        }
        
        return null;
    }

    /**
     * Enviar presentación semanal a SSN
     */
    public function sendWeeklyPresentation(Presentation $presentation): array
    {
        try {
            $endpoint = '/inv/entregaSemanal';
            $data = $presentation->getSsnJson();

            return $this->makeRequest('POST', $endpoint, $data);
        } catch (Exception $e) {
            Log::error('Error enviando presentación semanal a SSN', [
                'presentation_id' => $presentation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Enviar presentación mensual a SSN
     */
    public function sendMonthlyPresentation(Presentation $presentation): array
    {
        try {
            $endpoint = '/inv/entregaMensual';
            $data = $presentation->getSsnJson();

            return $this->makeRequest('POST', $endpoint, $data);
        } catch (Exception $e) {
            Log::error('Error enviando presentación mensual a SSN', [
                'presentation_id' => $presentation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Confirmar presentación semanal
     */
    public function confirmWeeklyPresentation(Presentation $presentation): array
    {
        try {
            $endpoint = '/inv/confirmarEntregaSemanal';
            $data = [
                'CODIGOCOMPANIA' => $presentation->codigo_compania,
                'CRONOGRAMA' => $presentation->cronograma,
                'TIPOENTREGA' => $presentation->tipo_entrega,
            ];

            return $this->makeRequest('POST', $endpoint, $data);
        } catch (Exception $e) {
            Log::error('Error confirmando presentación semanal en SSN', [
                'presentation_id' => $presentation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Confirmar presentación mensual
     */
    public function confirmMonthlyPresentation(Presentation $presentation): array
    {
        try {
            $endpoint = '/inv/confirmarEntregaMensual';
            $data = [
                'CODIGOCOMPANIA' => $presentation->codigo_compania,
                'CRONOGRAMA' => $presentation->cronograma,
                'TIPOENTREGA' => $presentation->tipo_entrega,
            ];

            return $this->makeRequest('POST', $endpoint, $data);
        } catch (Exception $e) {
            Log::error('Error confirmando presentación mensual en SSN', [
                'presentation_id' => $presentation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Consultar estado de presentaciones
     */
    public function getPresentationStatus(string $codigoCompania, string $cronograma, string $tipoEntrega): array
    {
        try {
            $endpoint = $tipoEntrega === 'Semanal' ? '/inv/entregaSemanal' : '/inv/entregaMensual';
            $params = [
                'codigoCompania' => $codigoCompania,
                'cronograma' => $cronograma,
                'tipoEntrega' => $tipoEntrega,
            ];

            return $this->makeRequest('GET', $endpoint, [], $params);
        } catch (Exception $e) {
            Log::error('Error consultando estado de presentación en SSN', [
                'codigo_compania' => $codigoCompania,
                'cronograma' => $cronograma,
                'tipo_entrega' => $tipoEntrega,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Solicitar rectificación de presentación
     */
    public function requestRectification(Presentation $presentation): array
    {
        try {
            $endpoint = $presentation->isSemanal() ? '/inv/entregaSemanal' : '/inv/entregaMensual';
            $data = [
                'CODIGOCOMPANIA' => $presentation->codigo_compania,
                'CRONOGRAMA' => $presentation->cronograma,
                'TIPOENTREGA' => $presentation->tipo_entrega,
                'MOTIVO' => 'Solicitud de rectificación por parte de la compañía',
            ];

            return $this->makeRequest('PUT', $endpoint, $data);
        } catch (Exception $e) {
            Log::error('Error solicitando rectificación en SSN', [
                'presentation_id' => $presentation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Realizar petición HTTP a SSN
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], array $params = []): array
    {
        if ($this->isMock) {
            return $this->makeMockRequest($method, $endpoint, $data, $params);
        }

        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Obtener el token más actualizado en cada request
        $token = $this->getValidToken();
        if ($token) {
            $headers['Token'] = $token;
        }

        $request = Http::withHeaders($headers);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = match ($method) {
            'GET' => $request->get($url),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            default => throw new Exception("Método HTTP no soportado: {$method}"),
        };

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
                'status' => $response->status(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->body(),
            'status' => $response->status(),
        ];
    }

    /**
     * Simular respuesta de SSN para desarrollo
     */
    private function makeMockRequest(string $method, string $endpoint, array $data = [], array $params = []): array
    {
        // Simular delay de red
        usleep(300000); // 0.3 segundos

        $isWeekly = str_contains($endpoint, 'Semanal') || str_contains($endpoint, 'semanal');
        $isMonthly = str_contains($endpoint, 'Mensual') || str_contains($endpoint, 'mensual');
        $isStatus = $method === 'GET';
        $isRectification = str_contains($endpoint, 'rectificacion') || $method === 'PUT';
        $isConfirmation = str_contains($endpoint, 'confirmar');

        $now = now();
        $uniqueId = 'SSN-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-' . $now->format('YmdHis');

        if ($isStatus) {
            return [
                'success' => true,
                'data' => [
                    'id' => $uniqueId,
                    'estado' => 'PRESENTADO',
                    'fechaPresentacion' => $now->format('Y-m-d H:i:s'),
                    'fechaProcesamiento' => $now->addMinutes(2)->format('Y-m-d H:i:s'),
                    'observaciones' => 'Presentación procesada correctamente por el sistema SSN',
                    'codigoCompania' => $data['codigoCompania'] ?? '1',
                    'cronograma' => $data['cronograma'] ?? '2025-W01',
                    'tipoEntrega' => $isWeekly ? 'Semanal' : 'Mensual',
                    'totalOperaciones' => rand(5, 50),
                    'validaciones' => [
                        'formato' => 'OK',
                        'datos' => 'OK',
                        'fechas' => 'OK',
                        'especies' => 'OK'
                    ]
                ],
                'status' => 200,
            ];
        }

        if ($isConfirmation) {
            return [
                'success' => true,
                'data' => [
                    'id' => $uniqueId,
                    'mensaje' => 'Presentación confirmada exitosamente',
                    'fechaConfirmacion' => $now->format('Y-m-d H:i:s'),
                    'estado' => 'CONFIRMADO',
                    'codigoCompania' => $data['codigoCompania'] ?? '1',
                    'cronograma' => $data['cronograma'] ?? '2025-W01',
                    'tipoEntrega' => $isWeekly ? 'Semanal' : 'Mensual',
                ],
                'status' => 200,
            ];
        }

        if ($isRectification) {
            return [
                'success' => true,
                'data' => [
                    'id' => $uniqueId,
                    'mensaje' => 'Solicitud de rectificación enviada correctamente',
                    'fechaSolicitud' => $now->format('Y-m-d H:i:s'),
                    'estado' => 'RECTIFICACION_PENDIENTE',
                    'codigoCompania' => $data['CODIGOCOMPANIA'] ?? '1',
                    'cronograma' => $data['CRONOGRAMA'] ?? '2025-W01',
                    'tipoEntrega' => $data['TIPOENTREGA'] ?? ($isWeekly ? 'Semanal' : 'Mensual'),
                    'motivo' => $data['MOTIVO'] ?? 'Solicitud de rectificación',
                    'numeroSolicitud' => 'RECT-' . $now->format('YmdHis'),
                    'tiempoEstimado' => '48-72 horas hábiles'
                ],
                'status' => 200,
            ];
        }

        // Presentación normal (POST)
        $totalOperations = 0;
        if (isset($data['operaciones'])) {
            $totalOperations = count($data['operaciones']);
        } elseif (isset($data['stocks'])) {
            $totalOperations = count($data['stocks']);
        }

        return [
            'success' => true,
            'data' => [
                'id' => $uniqueId,
                'mensaje' => 'Presentación ' . ($isWeekly ? 'semanal' : 'mensual') . ' enviada exitosamente',
                'fechaEnvio' => $now->format('Y-m-d H:i:s'),
                'fechaRecepcion' => $now->addSeconds(rand(1, 5))->format('Y-m-d H:i:s'),
                'estado' => 'PRESENTADO',
                'codigoCompania' => $data['codigoCompania'] ?? '1',
                'cronograma' => $data['cronograma'] ?? '2025-W01',
                'tipoEntrega' => $isWeekly ? 'Semanal' : 'Mensual',
                'totalOperaciones' => $totalOperations,
                'numeroPresentacion' => 'PRES-' . $now->format('YmdHis'),
                'validaciones' => [
                    'formato' => 'OK',
                    'datos' => 'OK',
                    'fechas' => 'OK',
                    'especies' => 'OK',
                    'afectaciones' => 'OK'
                ],
                'observaciones' => 'Presentación procesada correctamente. No se detectaron errores de validación.',
                'datosEnviados' => [
                    'resumen' => [
                        'totalOperaciones' => $totalOperations,
                        'tipoEntrega' => $isWeekly ? 'Semanal' : 'Mensual',
                        'codigoCompania' => $data['codigoCompania'] ?? '1',
                        'cronograma' => $data['cronograma'] ?? '2025-W01'
                    ]
                ]
            ],
            'status' => 200,
        ];
    }

    /**
     * Verifica si el servicio está disponible
     */
    public function isAvailable(): bool
    {
        if ($this->isMock) {
            return true;
        }

        // Verificar que tenemos la configuración necesaria
        if (empty($this->baseUrl)) {
            return false;
        }

        // Verificar que tenemos un token válido
        if (empty($this->token)) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene información del servicio
     */
    public function getServiceInfo(): array
    {
        return [
            'name' => 'SSN Service',
            'version' => '1.0.0',
            'status' => $this->isAvailable() ? 'available' : 'unavailable',
            'mode' => $this->isMock ? 'mock' : 'production',
            'base_url' => $this->baseUrl,
            'has_token' => !empty($this->token),
        ];
    }
} 
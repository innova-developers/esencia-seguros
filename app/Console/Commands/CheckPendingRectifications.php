<?php

namespace App\Console\Commands;

use App\Domain\Models\Presentation;
use App\Domain\Models\ActivityLog;
use App\Domain\Services\SSNAuthService;
use App\Services\SSNService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingRectifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:check-pending-rectifications 
                            {--force : Forzar autenticación aunque haya token válido}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta todas las presentaciones en estado RECTIFICACION_PENDIENTE y verifica su estado en la SSN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando consulta de presentaciones con RECTIFICACION_PENDIENTE...');
        
        try {
            // Asegurar que tenemos un token válido antes de continuar
            $this->ensureValidToken();

            // Inicializar servicio SSN
            $ssnService = app(SSNService::class);

            // Buscar presentaciones en estado RECTIFICACION_PENDIENTE
            $presentations = Presentation::where('estado', Presentation::ESTADO_RECTIFICACION_PENDIENTE)
                ->orderBy('rectification_requested_at', 'asc')
                ->get();

            if ($presentations->isEmpty()) {
                $this->info('No hay presentaciones en estado RECTIFICACION_PENDIENTE');
                $this->logActivity('info', 'No hay presentaciones pendientes de rectificación');
                return 0;
            }

            $this->info("Encontradas {$presentations->count()} presentaciones en estado RECTIFICACION_PENDIENTE");
            $this->newLine();

            $processed = 0;
            $successful = 0;
            $errors = 0;

            // Crear tabla para mostrar resultados
            $results = [];

            foreach ($presentations as $presentation) {
                $this->info("Consultando presentación ID: {$presentation->id}");
                $this->line("  - Cronograma: {$presentation->cronograma}");
                $this->line("  - Tipo: {$presentation->tipo_entrega}");
                $this->line("  - Código Compañía: {$presentation->codigo_compania}");
                
                try {
                    // Consultar estado en SSN usando GET con query params
                    $response = $ssnService->getPresentationStatus(
                        $presentation->codigo_compania,
                        $presentation->cronograma,
                        $presentation->tipo_entrega
                    );

                    if ($response['success']) {
                        $data = $response['data'];
                        $estado = $data['estado'] ?? 'NO_DISPONIBLE';
                        
                        $this->info("  ✅ Estado SSN: {$estado}");
                        
                        // Guardar información de la consulta
                        $presentation->last_ssn_status = $estado;
                        $presentation->last_rectification_check_at = now();
                        $presentation->rectification_check_attempts = ($presentation->rectification_check_attempts ?? 0) + 1;
                        
                        // Actualizar ssn_response_data con la nueva consulta
                        $currentData = $presentation->ssn_response_data ?? [];
                        $currentData['status_check'] = [
                            'checked_at' => now()->toISOString(),
                            'ssn_status' => $estado,
                            'response_data' => $data,
                            'status_code' => $response['status'] ?? null,
                        ];
                        $presentation->ssn_response_data = $currentData;
                        $presentation->save();

                        $results[] = [
                            'id' => $presentation->id,
                            'cronograma' => $presentation->cronograma,
                            'tipo' => $presentation->tipo_entrega,
                            'codigo_compania' => $presentation->codigo_compania,
                            'estado_ssn' => $estado,
                            'status' => '✅ OK',
                        ];

                        $successful++;
                        
                        // Mostrar información adicional si está disponible
                        if (isset($data['fechaPresentacion'])) {
                            $this->line("  - Fecha Presentación: {$data['fechaPresentacion']}");
                        }
                        if (isset($data['observaciones'])) {
                            $this->line("  - Observaciones: {$data['observaciones']}");
                        }
                    } else {
                        $error = $response['error'] ?? 'Error desconocido';
                        $this->error("  ❌ Error: {$error}");
                        
                        $results[] = [
                            'id' => $presentation->id,
                            'cronograma' => $presentation->cronograma,
                            'tipo' => $presentation->tipo_entrega,
                            'codigo_compania' => $presentation->codigo_compania,
                            'estado_ssn' => 'ERROR',
                            'status' => "❌ {$error}",
                        ];

                        $errors++;
                        
                        Log::error("Error consultando SSN para presentación {$presentation->id}", [
                            'presentation_id' => $presentation->id,
                            'error' => $error,
                            'response' => $response,
                        ]);
                    }
                    
                    $processed++;
                    
                    // Pequeña pausa entre consultas para no sobrecargar SSN
                    if ($processed < $presentations->count()) {
                        sleep(1);
                    }
                    
                    $this->newLine();
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ❌ Excepción: {$e->getMessage()}");
                    
                    $results[] = [
                        'id' => $presentation->id,
                        'cronograma' => $presentation->cronograma,
                        'tipo' => $presentation->tipo_entrega,
                        'codigo_compania' => $presentation->codigo_compania,
                        'estado_ssn' => 'EXCEPCIÓN',
                        'status' => "❌ {$e->getMessage()}",
                    ];
                    
                    Log::error("Excepción consultando SSN para presentación {$presentation->id}", [
                        'presentation_id' => $presentation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $this->newLine();
                }
            }

            // Mostrar tabla de resultados
            $this->newLine();
            $this->info('=== RESUMEN DE CONSULTAS ===');
            $this->table(
                ['ID', 'Cronograma', 'Tipo', 'Código Compañía', 'Estado SSN', 'Status'],
                array_map(function ($result) {
                    return [
                        $result['id'],
                        $result['cronograma'],
                        $result['tipo'],
                        $result['codigo_compania'],
                        $result['estado_ssn'],
                        $result['status'],
                    ];
                }, $results)
            );

            $this->newLine();
            $this->info("=== ESTADÍSTICAS ===");
            $this->info("Total procesadas: {$processed}");
            $this->info("Consultas exitosas: {$successful}");
            $this->info("Errores: {$errors}");

            $this->logActivity('success', "Consulta completada. Procesadas: {$processed}, Exitosas: {$successful}, Errores: {$errors}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error general en el comando: {$e->getMessage()}");
            Log::error("Error general en comando de consulta de rectificaciones pendientes", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->logActivity('error', "Error general en comando: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Asegurar que tenemos un token válido antes de hacer las consultas
     */
    private function ensureValidToken(): void
    {
        $authService = app(SSNAuthService::class);
        
        // Verificar si hay token válido en cache
        $token = $authService->getCachedToken();
        
        if ($token && $authService->isTokenValid($token) && !$this->option('force')) {
            $this->info("✓ Token válido encontrado en cache");
            return;
        }

        // Intentar obtener token de la base de datos
        $dbToken = \App\SSNToken::getValidToken();
        if ($dbToken && $authService->isTokenValid($dbToken) && !$this->option('force')) {
            $this->info("✓ Token válido encontrado en base de datos");
            return;
        }

        // Si no hay token válido, intentar autenticar
        $this->info("No se encontró token válido. Intentando autenticar con SSN...");
        
        $ssnConfig = config('services.ssn');
        $authResult = $authService->authenticate(
            $ssnConfig['username'] ?? '',
            $ssnConfig['cia'] ?? '',
            $ssnConfig['password'] ?? ''
        );

        if ($authResult && isset($authResult['token'])) {
            $this->info("✓ Autenticación exitosa. Token obtenido.");
            
            // Cachear el token
            if (isset($authResult['expiration'])) {
                $authService->cacheToken(
                    $authResult['token'],
                    $authResult['expiration'],
                    $ssnConfig['username'] ?? '',
                    $ssnConfig['cia'] ?? ''
                );
            }
        } else {
            $this->error("❌ No se pudo obtener un token válido. Las consultas pueden fallar.");
            $this->warn("Verifica las credenciales de SSN en la configuración.");
        }
    }

    /**
     * Registrar actividad en el log
     */
    private function logActivity(string $status, string $description, array $details = []): void
    {
        try {
            ActivityLog::create([
                'action' => 'ssn_check_pending_rectifications',
                'module' => 'ssn',
                'description' => $description,
                'details' => $details,
                'status' => $status,
                'ip_address' => request()->ip() ?? 'cron',
                'user_agent' => 'Laravel Console Command'
            ]);
        } catch (\Exception $e) {
            Log::error('Error registrando actividad', [
                'error' => $e->getMessage(),
                'description' => $description
            ]);
        }
    }
}

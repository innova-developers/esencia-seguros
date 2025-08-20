<?php

namespace App\Console\Commands;

use App\Domain\Models\Presentation;
use App\Domain\Models\ActivityLog;
use App\Services\SSNService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckRectificationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:check-rectification-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado de las presentaciones en RECTIFICACION_PENDIENTE consultando la API de SSN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando verificación de estado de rectificaciones...');
        
        try {
            // Inicializar servicio SSN
            $ssnService = app(SSNService::class);

            // Buscar presentaciones en estado RECTIFICACION_PENDIENTE (tanto mensuales como semanales)
            $presentations = Presentation::where('estado', Presentation::ESTADO_RECTIFICACION_PENDIENTE)
                ->get();

            if ($presentations->isEmpty()) {
                $this->info('No hay presentaciones en estado RECTIFICACION_PENDIENTE');
                $this->logActivity('info', 'No hay presentaciones pendientes de rectificación');
                return 0;
            }

            $this->info("Encontradas {$presentations->count()} presentaciones en estado RECTIFICACION_PENDIENTE");

            $processed = 0;
            $updated = 0;
            $errors = 0;

            foreach ($presentations as $presentation) {
                $this->info("Procesando presentación ID: {$presentation->id} - {$presentation->cronograma}");
                
                try {
                    $result = $this->checkPresentationStatus($presentation, $ssnService);
                    
                    if ($result['success']) {
                        if ($result['updated']) {
                            $updated++;
                            $this->info("✅ Presentación {$presentation->id} actualizada a estado: {$result['new_status']}");
                        } else {
                            $this->info("ℹ️  Presentación {$presentation->id} aún pendiente. Estado SSN: {$result['ssn_status']}");
                        }
                    } else {
                        $errors++;
                        $this->error("❌ Error en presentación {$presentation->id}: {$result['error']}");
                    }
                    
                    $processed++;
                    
                    // Pequeña pausa entre consultas para no sobrecargar SSN
                    if ($processed < $presentations->count()) {
                        sleep(2);
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("❌ Excepción en presentación {$presentation->id}: {$e->getMessage()}");
                    Log::error("Error procesando presentación {$presentation->id} en cron de rectificación", [
                        'presentation_id' => $presentation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info("\n=== RESUMEN ===");
            $this->info("Total procesadas: {$processed}");
            $this->info("Actualizadas: {$updated}");
            $this->info("Errores: {$errors}");

            $this->logActivity('success', "Verificación completada. Procesadas: {$processed}, Actualizadas: {$updated}, Errores: {$errors}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error general en el comando: {$e->getMessage()}");
            Log::error("Error general en comando de verificación de rectificaciones", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->logActivity('error', "Error general en comando: {$e->getMessage()}");
            return 1;
        }
    }



    /**
     * Verificar estado de una presentación específica
     */
    private function checkPresentationStatus(Presentation $presentation, SSNService $ssnService): array
    {
        try {
            // Construir parámetros para la consulta
            $params = [
                'codigoCompania' => $presentation->codigo_compania,
                'tipoEntrega' => $presentation->tipo_entrega,
                'cronograma' => $presentation->cronograma
            ];

            // Usar el SSNService para hacer la consulta
            $response = $ssnService->checkPresentationStatus($presentation->cronograma, $params);

            if ($response['success']) {
                $data = $response['data'];
                $estado = $data['estado'] ?? '';
                
                $this->info("Respuesta SSN para presentación {$presentation->id}: Estado = {$estado}");

                // Si el estado es "RECTIFICAR", actualizar a A_RECTIFICAR
                if ($estado === 'RECTIFICAR') {
                    $presentation->estado = Presentation::ESTADO_A_RECTIFICAR;
                    $presentation->last_ssn_status = $estado;
                    $presentation->last_rectification_check_at = now();
                    $presentation->rectification_check_attempts = ($presentation->rectification_check_attempts ?? 0) + 1;
                    $presentation->ssn_response_data = array_merge(
                        $presentation->ssn_response_data ?? [],
                        [
                            'rectification_status_check' => [
                                'checked_at' => now()->toISOString(),
                                'ssn_status' => $estado,
                                'response_data' => $data
                            ]
                        ]
                    );
                    $presentation->save();

                    $this->logActivity('success', "Presentación {$presentation->id} actualizada a A_RECTIFICAR por estado SSN: {$estado}", [
                        'presentation_id' => $presentation->id,
                        'ssn_status' => $estado,
                        'cronograma' => $presentation->cronograma
                    ]);

                    return [
                        'success' => true,
                        'updated' => true,
                        'new_status' => Presentation::ESTADO_A_RECTIFICAR,
                        'ssn_status' => $estado
                    ];
                } else {
                    // Actualizar datos de respuesta SSN sin cambiar estado
                    $presentation->last_ssn_status = $estado;
                    $presentation->last_rectification_check_at = now();
                    $presentation->rectification_check_attempts = ($presentation->rectification_check_attempts ?? 0) + 1;
                    $presentation->ssn_response_data = array_merge(
                        $presentation->ssn_response_data ?? [],
                        [
                            'rectification_status_check' => [
                                'checked_at' => now()->toISOString(),
                                'ssn_status' => $estado,
                                'response_data' => $data
                            ]
                        ]
                    );
                    $presentation->save();

                    return [
                        'success' => true,
                        'updated' => false,
                        'ssn_status' => $estado
                    ];
                }

            } else {
                $error = $response['error'] ?? 'Error desconocido';
                Log::error("Error consultando SSN para presentación {$presentation->id}", [
                    'presentation_id' => $presentation->id,
                    'error' => $error
                ]);

                return [
                    'success' => false,
                    'error' => $error
                ];
            }

        } catch (\Exception $e) {
            Log::error("Error consultando SSN para presentación {$presentation->id}", [
                'presentation_id' => $presentation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Registrar actividad en el log
     */
    private function logActivity(string $status, string $description, array $details = []): void
    {
        try {
            ActivityLog::create([
                'action' => 'ssn_rectification_check',
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

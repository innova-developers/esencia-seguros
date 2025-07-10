<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Models\Presentation;
use App\Domain\Models\WeeklyOperation;
use App\Services\SSNService;
use App\Domain\Services\SSNAuthService;
use Carbon\Carbon;

class TestWeeklyPresentationFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:weekly-presentation-flow {--week=} {--user-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test complete weekly presentation flow with SSN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando flujo completo de presentación semanal (SIMULADO)...');
        
        // Desactivar logging para evitar errores de permisos
        config(['logging.default' => 'null']);
        
        // Paso 1: Verificar conexión SSN
        $this->info('1️⃣ Verificando conexión SSN...');
        $ssnAuthService = app(SSNAuthService::class);
        
        try {
            $token = $ssnAuthService->getCachedToken();
            if ($token) {
                $this->info('✅ Token SSN válido encontrado en cache');
            } else {
                $this->info('🔄 Obteniendo nuevo token SSN...');
                $authResult = $ssnAuthService->authenticate(
                    config('services.ssn.username'),
                    config('services.ssn.cia'),
                    config('services.ssn.password')
                );
                if ($authResult && $authResult['success']) {
                    $token = $authResult['token'];
                    $this->info('✅ Nuevo token SSN obtenido');
                } else {
                    $this->error('❌ No se pudo obtener token SSN');
                    return 1;
                }
            }
        } catch (\Exception $e) {
            $this->warn('⚠️  Error con cache, obteniendo token directamente: ' . $e->getMessage());
            $authResult = $ssnAuthService->authenticate(
                config('services.ssn.username'),
                config('services.ssn.cia'),
                config('services.ssn.password')
            );
            if ($authResult && $authResult['success']) {
                $token = $authResult['token'];
                $this->info('✅ Token SSN obtenido directamente');
            } else {
                $this->error('❌ No se pudo obtener token SSN');
                return 1;
            }
        }
        
        // Paso 2: Simular presentación de prueba
        $week = $this->option('week') ?? $this->generateTestWeek();
        $userId = $this->option('user-id');
        
        $this->info("2️⃣ Simulando presentación de prueba para semana: {$week}");
        
        // Simular datos de presentación
        $presentationData = [
            'id' => 999,
            'user_id' => $userId,
            'cronograma' => $week,
            'tipo_entrega' => 'Semanal',
            'estado' => 'VACIO',
            'codigo_compania' => config('services.ssn.cia'),
        ];
        
        $this->info("✅ Presentación simulada (ID: {$presentationData['id']})");
        
        // Paso 3: Simular operaciones de prueba
        $this->info('3️⃣ Simulando operaciones de prueba...');
        
        // Crear operaciones de prueba
        $operations = [
            [
                'tipo_operacion' => 'C',
                'tipo_especie' => 'TP',
                'codigo_especie' => 'AL30',
                'cant_especies' => 1000.0,
                'codigo_afectacion' => 'T',
                'tipo_valuacion' => 'V',
                'fecha_movimiento' => '2025-01-15',
                'fecha_liquidacion' => '2025-01-16',
                'precio_compra' => 150.50,
            ],
            [
                'tipo_operacion' => 'V',
                'tipo_especie' => 'TP',
                'codigo_especie' => 'AL30',
                'cant_especies' => 500.0,
                'codigo_afectacion' => 'T',
                'tipo_valuacion' => 'V',
                'fecha_movimiento' => '2025-01-17',
                'fecha_liquidacion' => '2025-01-18',
                'precio_venta' => 155.75,
                'fecha_pase_vt' => '2025-01-19',
                'precio_pase_vt' => 154.25,
            ]
        ];
        
        $this->info("✅ Operaciones simuladas creadas (" . count($operations) . " operaciones)");
        
        // Paso 4: Generar JSON para SSN
        $this->info('4️⃣ Generando JSON para SSN...');
        
        $codigoCompania = config('services.ssn.cia', '0001');
        
        $ssnJson = [
            'codigoCompania' => $codigoCompania,
            'cronograma' => $week,
            'tipoEntrega' => 'Semanal',
            'operaciones' => []
        ];

        // Convertir las operaciones al formato correcto
        foreach ($operations as $operation) {
            $ssnOperation = [
                'tipoOperacion' => $operation['tipo_operacion'],
                'tipoEspecie' => $operation['tipo_especie'],
                'codigoEspecie' => $operation['codigo_especie'],
                'cantEspecies' => (float) $operation['cant_especies'],
                'codigoAfectacion' => $operation['codigo_afectacion'],
                'tipoValuacion' => $operation['tipo_valuacion'],
                'fechaMovimiento' => $this->formatDateForSSN($operation['fecha_movimiento']),
                'fechaLiquidacion' => $this->formatDateForSSN($operation['fecha_liquidacion']),
            ];
            
            // Agregar campos específicos según el tipo de operación
            if ($operation['tipo_operacion'] === 'C' && !empty($operation['precio_compra'])) {
                $ssnOperation['precioCompra'] = (float) $operation['precio_compra'];
            }
            
            if ($operation['tipo_operacion'] === 'V') {
                $ssnOperation['precioVenta'] = (float) $operation['precio_venta'];
                $ssnOperation['fechaPaseVT'] = $this->formatDateForSSN($operation['fecha_pase_vt']);
                $ssnOperation['precioPaseVT'] = (float) $operation['precio_pase_vt'];
            }
            
            $ssnJson['operaciones'][] = $ssnOperation;
        }
        
        $this->info("✅ JSON generado correctamente");
        $this->line("   Código Compañía: {$codigoCompania}");
        $this->line("   Cronograma: {$week}");
        $this->line("   Operaciones: " . count($ssnJson['operaciones']));
        
        // Mostrar JSON para debugging
        $this->info('📋 JSON que se enviará a SSN:');
        $this->line(json_encode($ssnJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Paso 5: Simular envío a SSN
        $this->info('5️⃣ Simulando envío a SSN...');
        
        try {
            $ssnService = app(SSNService::class);
            $response = $ssnService->sendWeeklyPresentation($presentationData);
            
            if ($response['success']) {
                $this->info('✅ Presentación enviada exitosamente');
                $this->line("   ID: " . ($response['data']['id'] ?? 'N/A'));
                $this->line("   Estado: " . ($response['data']['estado'] ?? 'N/A'));
                $this->line("   Mensaje: " . ($response['data']['mensaje'] ?? 'N/A'));
            } else {
                $this->error('❌ Error al enviar presentación');
                $this->line("   Error: " . ($response['error'] ?? 'Error desconocido'));
                $this->line("   Status: " . ($response['status'] ?? 'N/A'));
                
                // Mostrar headers y body enviados si están disponibles
                if (isset($response['headers_enviados'])) {
                    $this->line("   Headers enviados: " . json_encode($response['headers_enviados']));
                }
                if (isset($response['body_enviado'])) {
                    $this->line("   Body enviado: " . json_encode($response['body_enviado']));
                }
            }
        } catch (Exception $e) {
            $this->error('❌ Excepción al enviar presentación: ' . $e->getMessage());
        }
        
        $this->info('🎉 Prueba completada');
        return 0;
    }
    
    private function generateTestWeek(): string
    {
        $currentWeek = Carbon::now()->subWeeks(1);
        return $currentWeek->format('Y-W');
    }
    
    private function createTestOperations(Presentation $presentation): void
    {
        $operations = [
            [
                'tipo_operacion' => 'C', // Compra
                'tipo_especie' => 'AC', // Acciones
                'codigo_especie' => 'GGAL',
                'cant_especies' => 100,
                'codigo_afectacion' => '001',
                'tipo_valuacion' => 'V', // Mercado
                'fecha_movimiento' => '01072025', // DDMMYYYY
                'fecha_liquidacion' => '03072025',
                'precio_compra' => 150.50,
            ],
            [
                'tipo_operacion' => 'V', // Venta
                'tipo_especie' => 'TP', // Títulos Públicos
                'codigo_especie' => 'BONAR2025',
                'cant_especies' => 50,
                'codigo_afectacion' => '002',
                'tipo_valuacion' => 'T', // Técnico
                'fecha_movimiento' => '02072025',
                'fecha_liquidacion' => '04072025',
                'precio_venta' => 95.25,
            ],
            [
                'tipo_operacion' => 'C', // Compra
                'tipo_especie' => 'FC', // Fondos Comunes
                'codigo_especie' => 'FCI001',
                'cant_especies' => 200.123456,
                'codigo_afectacion' => '003',
                'tipo_valuacion' => 'V', // Mercado
                'fecha_movimiento' => '03072025',
                'fecha_liquidacion' => '05072025',
                'precio_compra' => 75.80,
            ],
        ];
        
        foreach ($operations as $operationData) {
            WeeklyOperation::create(array_merge($operationData, [
                'presentation_id' => $presentation->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Formatear fecha para SSN (DDMMYYYY)
     */
    private function formatDateForSSN(?string $date): ?string
    {
        if (!$date) {
            return null;
        }
        
        try {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            // Intentar otros formatos comunes
            $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            return $date; // Si no se puede parsear, devolver como está
        } catch (\Exception $e) {
            return $date;
        }
    }
}

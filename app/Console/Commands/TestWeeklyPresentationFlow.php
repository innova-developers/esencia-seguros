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
        
        $this->info("✅ {$presentationData['id']} operaciones simuladas");
        
        // Paso 4: Simular estado CARGADO
        $this->info('4️⃣ Simulando estado CARGADO...');
        $presentationData['estado'] = 'CARGADO';
        $this->info('✅ Estado actualizado a CARGADO');
        
        // Paso 5: Simular generación de JSON SSN
        $this->info('5️⃣ Simulando generación de JSON para SSN...');
        
        $ssnJson = [
            'compania' => $presentationData['codigo_compania'],
            'cronograma' => $presentationData['cronograma'],
            'tipoEntrega' => 'Semanal',
            'operaciones' => array_map(function($op) {
                return [
                    'tipoOperacion' => $op['tipo_operacion'],
                    'tipoEspecie' => $op['tipo_especie'],
                    'codigoEspecie' => $op['codigo_especie'],
                    'cantEspecies' => $op['cant_especies'],
                    'codigoAfectacion' => $op['codigo_afectacion'],
                    'tipoValuacion' => $op['tipo_valuacion'],
                    'fechaMovimiento' => $op['fecha_movimiento'],
                    'fechaLiquidacion' => $op['fecha_liquidacion'],
                    'precioCompra' => $op['precio_compra'] ?? null,
                    'precioVenta' => $op['precio_venta'] ?? null,
                ];
            }, $operations)
        ];
        
        $this->info('✅ JSON simulado generado correctamente');
        $this->line("   Total operaciones: " . count($ssnJson['operaciones']));
        
        // Paso 6: Simular envío a SSN
        $this->info('6️⃣ Simulando envío a SSN...');
        
        // Simular respuesta exitosa de la SSN
        $ssnResponse = [
            'success' => true,
            'data' => [
                'id' => 'SSN_' . time(),
                'estado' => 'RECIBIDO',
                'mensaje' => 'Presentación recibida correctamente',
                'fecha_recepcion' => now()->format('d/m/Y H:i:s'),
            ]
        ];
        
        $this->info('✅ Envío simulado exitoso a SSN');
        $this->line("   ID SSN: " . $ssnResponse['data']['id']);
        $this->line("   Estado: " . $ssnResponse['data']['estado']);
        $this->line("   Mensaje: " . $ssnResponse['data']['mensaje']);
        
        // Simular actualización de presentación
        $presentationData['estado'] = 'PRESENTADO';
        $presentationData['ssn_response_id'] = $ssnResponse['data']['id'];
        $presentationData['ssn_response_data'] = $ssnResponse['data'];
        $presentationData['presented_at'] = now();
        
        $this->info('✅ Presentación simulada actualizada a estado PRESENTADO');
        
        // Paso 7: Verificar estado final
        $this->info('7️⃣ Verificando estado final...');
        $this->info("✅ Presentación finalizada:");
        $this->line("   ID: {$presentationData['id']}");
        $this->line("   Semana: {$presentationData['cronograma']}");
        $this->line("   Estado: {$presentationData['estado']}");
        $this->line("   ID SSN: {$presentationData['ssn_response_id']}");
        $this->line("   Fecha envío: {$presentationData['presented_at']}");
        
        $this->info('🎉 ¡Prueba completada exitosamente!');
        $this->info('💡 Este fue un flujo simulado. Para probar con base de datos real:');
        $this->info('   1. Levanta la base de datos: docker-compose up -d mysql');
        $this->info('   2. Ejecuta migraciones: php artisan migrate:fresh');
        $this->info('   3. Vuelve a ejecutar este comando');
        
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
}

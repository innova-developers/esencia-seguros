<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SSNService;
use App\Domain\Services\SSNAuthService;

class TestSSNRealConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ssn-real-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test real connection with SSN API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando conexión REAL con la API de la SSN...');
        
        // Desactivar logging para evitar errores de permisos
        config(['logging.default' => 'null']);
        
        // Paso 1: Verificar configuración
        $this->info('1️⃣ Verificando configuración...');
        $this->line("   Environment: " . config('app.env'));
        $this->line("   SSN Mock Enabled: " . (config('services.ssn.mock_enabled') ? 'YES' : 'NO'));
        $this->line("   SSN Base URL: " . config('services.ssn.base_url_testing'));
        $this->line("   SSN Username: " . config('services.ssn.username'));
        $this->line("   SSN CIA: " . config('services.ssn.cia'));
        
        // Paso 2: Verificar autenticación
        $this->info('2️⃣ Verificando autenticación...');
        $authService = app(SSNAuthService::class);
        
        try {
            $token = $authService->getCachedToken();
            if (!$token) {
                $this->info('🔄 Obteniendo nuevo token...');
                $authResult = $authService->authenticate(
                    config('services.ssn.username'),
                    config('services.ssn.cia'),
                    config('services.ssn.password')
                );
                if ($authResult && $authResult['success']) {
                    $token = $authResult['token'];
                    $this->info('✅ Token obtenido exitosamente');
                } else {
                    $this->error('❌ No se pudo obtener token');
                    return 1;
                }
            } else {
                $this->info('✅ Token encontrado en cache');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error en autenticación: ' . $e->getMessage());
            return 1;
        }
        
        // Paso 3: Verificar servicio SSN
        $this->info('3️⃣ Verificando servicio SSN...');
        $ssnService = app(SSNService::class);
        $serviceInfo = $ssnService->getServiceInfo();
        
        $this->line("   Status: " . $serviceInfo['status']);
        $this->line("   Mode: " . $serviceInfo['mode']);
        $this->line("   Base URL: " . $serviceInfo['base_url']);
        $this->line("   Has Token: " . ($serviceInfo['has_token'] ? 'YES' : 'NO'));
        
        if ($serviceInfo['mode'] === 'mock') {
            $this->error('❌ El servicio está en modo MOCK. Verifica la configuración.');
            return 1;
        }
        
        // Paso 4: Probar llamada real a la API
        $this->info('4️⃣ Probando llamada real a la API...');
        
        try {
            // Crear una presentación de prueba real
            $presentation = new \App\Domain\Models\Presentation([
                'user_id' => 1,
                'codigo_compania' => config('services.ssn.cia'),
                'cronograma' => '2025-27',
                'tipo_entrega' => 'Semanal',
                'version' => 1,
                'estado' => 'CARGADO',
            ]);
            
            // Agregar operaciones de prueba
            $presentation->setRelation('weeklyOperations', collect([
                new \App\Domain\Models\WeeklyOperation([
                    'tipo_operacion' => 'C',
                    'tipo_especie' => 'AC',
                    'codigo_especie' => 'TEST001',
                    'cant_especies' => 100,
                    'codigo_afectacion' => '001',
                    'tipo_valuacion' => 'V',
                    'fecha_movimiento' => '01072025',
                    'fecha_liquidacion' => '03072025',
                    'precio_compra' => 150.50,
                ])
            ]));
            
            $this->line("   Enviando presentación de prueba...");
            $response = $ssnService->sendWeeklyPresentation($presentation);
            
            $this->info('✅ Respuesta recibida de la SSN:');
            $this->line("   Success: " . ($response['success'] ? 'YES' : 'NO'));
            $this->line("   Status: " . ($response['status'] ?? 'N/A'));
            
            if ($response['success']) {
                $this->info('🎉 ¡Conexión REAL exitosa!');
                $this->line("   ID SSN: " . ($response['data']['id'] ?? 'N/A'));
                $this->line("   Estado: " . ($response['data']['estado'] ?? 'N/A'));
                $this->line("   Mensaje: " . ($response['data']['mensaje'] ?? 'N/A'));
            } else {
                $this->error('❌ Error en la respuesta de la SSN:');
                $this->line("   Error: " . ($response['error'] ?? 'N/A'));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error en la llamada a la API: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

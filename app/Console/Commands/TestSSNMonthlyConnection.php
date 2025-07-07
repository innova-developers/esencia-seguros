<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SSNService;
use App\Domain\Services\SSNAuthService;

class TestSSNMonthlyConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ssn-monthly-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test real connection with SSN API for monthly presentations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando conexión REAL con la API de la SSN para presentaciones mensuales...');
        
        // Desactivar logging para evitar errores de permisos
        config(['logging.default' => 'null']);
        
        // Paso 1: Verificar configuración
        $this->info('1️⃣ Verificando configuración...');
        $this->line("   SSN Mock Enabled: " . (config('services.ssn.mock_enabled') ? 'YES' : 'NO'));
        $this->line("   SSN Base URL: " . config('services.ssn.base_url_testing'));
        
        // Paso 2: Verificar autenticación
        $this->info('2️⃣ Verificando autenticación...');
        $authService = app(SSNAuthService::class);
        
        try {
            // Forzar obtención de nuevo token
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
        
        // Paso 4: Probar presentación mensual real
        $this->info('4️⃣ Probando presentación mensual real...');
        
        try {
            // Crear una presentación mensual de prueba
            $presentation = new \App\Domain\Models\Presentation([
                'id' => 999,
                'user_id' => 1,
                'codigo_compania' => config('services.ssn.cia'),
                'cronograma' => '2025-06',
                'tipo_entrega' => 'Mensual',
                'version' => 1,
                'estado' => 'CARGADO',
            ]);
            
            // Agregar stocks mensuales de prueba
            $presentation->setRelation('monthlyStocks', collect([
                new \App\Domain\Models\MonthlyStock([
                    'tipo_especie' => 'TP',
                    'codigo_especie' => 'BONAR2025',
                    'cant_especies' => 1000,
                    'valor_nominal' => 100.00,
                    'valor_mercado' => 95.50,
                    'fecha_valuacion' => '30062025',
                ])
            ]));
            
            $this->line("   Enviando presentación mensual...");
            $response = $ssnService->sendMonthlyPresentation($presentation);
            
            $this->info('✅ Respuesta recibida de la SSN:');
            $this->line("   Success: " . ($response['success'] ? 'YES' : 'NO'));
            $this->line("   Status: " . ($response['status'] ?? 'N/A'));
            
            if ($response['success']) {
                $this->info('🎉 ¡Presentación mensual REAL exitosa!');
                $this->line("   ID SSN: " . ($response['data']['id'] ?? 'N/A'));
                $this->line("   Estado: " . ($response['data']['estado'] ?? 'N/A'));
                $this->line("   Mensaje: " . ($response['data']['mensaje'] ?? 'N/A'));
            } else {
                $this->error('❌ Error en la respuesta de la SSN:');
                $this->line("   Error: " . ($response['error'] ?? 'N/A'));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error en la presentación mensual: ' . $e->getMessage());
            return 1;
        }
        
        // Paso 5: Probar rectificación mensual real
        $this->info('5️⃣ Probando rectificación mensual real...');
        
        try {
            // Actualizar la presentación para rectificación
            $presentation->estado = 'PRESENTADO';
            $presentation->ssn_response_id = 'SSN-TEST-123';
            
            $this->line("   Enviando solicitud de rectificación mensual...");
            $response = $ssnService->requestRectification($presentation);
            
            $this->info('✅ Respuesta de rectificación recibida:');
            $this->line("   Success: " . ($response['success'] ? 'YES' : 'NO'));
            $this->line("   Status: " . ($response['status'] ?? 'N/A'));
            
            if ($response['success']) {
                $this->info('🎉 ¡Rectificación mensual REAL exitosa!');
                $this->line("   ID SSN: " . ($response['data']['id'] ?? 'N/A'));
                $this->line("   Estado: " . ($response['data']['estado'] ?? 'N/A'));
                $this->line("   Mensaje: " . ($response['data']['mensaje'] ?? 'N/A'));
                $this->line("   Número Solicitud: " . ($response['data']['numeroSolicitud'] ?? 'N/A'));
            } else {
                $this->error('❌ Error en la rectificación de la SSN:');
                $this->line("   Error: " . ($response['error'] ?? 'N/A'));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error en la rectificación mensual: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

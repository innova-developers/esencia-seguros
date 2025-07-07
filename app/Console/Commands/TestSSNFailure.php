<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Services\SSNAuthService;

class TestSSNFailure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ssn-failure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test system functionality when SSN connection fails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando funcionalidad del sistema sin conexión SSN...');
        
        // Limpiar token SSN para simular fallo
        $ssnService = app(SSNAuthService::class);
        $ssnService->clearCachedToken();
        
        $this->info('✅ Token SSN limpiado');
        
        // Verificar estado de conexión
        $token = $ssnService->getCachedToken();
        if (!$token) {
            $this->info('✅ No hay token SSN (simulando fallo de conexión)');
        } else {
            $this->warn('⚠️  Aún hay token SSN en cache');
        }
        
        // Verificar que el dashboard funciona
        $this->info('🔍 Verificando que el dashboard funciona sin SSN...');
        
        try {
            $dashboardController = app(\App\Infrastructure\Http\Controllers\DashboardController::class);
            $ssnInfo = $dashboardController->getSSNConnectionInfo();
            
            if (!$ssnInfo['connected']) {
                $this->info('✅ Dashboard maneja correctamente la falta de conexión SSN');
                $this->info("   Estado: {$ssnInfo['status']}");
                $this->info("   Mensaje: {$ssnInfo['message']}");
            } else {
                $this->warn('⚠️  Dashboard muestra conexión SSN activa');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error en dashboard: ' . $e->getMessage());
        }
        
        // Verificar estadísticas
        $this->info('📊 Verificando estadísticas de presentaciones...');
        
        try {
            $stats = $dashboardController->getPresentationStats(1); // Usuario ID 1
            $this->info('✅ Estadísticas obtenidas correctamente:');
            foreach ($stats as $key => $value) {
                $this->line("   {$key}: {$value}");
            }
        } catch (\Exception $e) {
            $this->error('❌ Error obteniendo estadísticas: ' . $e->getMessage());
        }
        
        $this->info('🎉 Prueba completada. El sistema debería funcionar sin conexión SSN.');
        $this->info('💡 Solo las funciones de envío a la SSN estarán limitadas.');
    }
}
